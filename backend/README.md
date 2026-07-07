# Backend — Trends Discovery

Laravel 13 API + Filament 5 admin panel. PHP 8.3-fpm-alpine. Очереди через Laravel Horizon, планировщик — отдельный контейнер.

## Структура

```
backend/
├── app/
│   ├── Http/Controllers/     # API-контроллеры
│   ├── Jobs/                 # Фоновые задачи
│   ├── Models/               # Eloquent-модели
│   ├── Services/             # Бизнес-логика
│   └── Filament/             # Filament 5 (ресурсы, виджеты)
├── database/
│   ├── migrations/           # Схема БД
│   └── seeders/              # CategorySeeder
├── routes/
│   ├── api.php               # /api/v1/* маршруты
│   └── console.php           # Расписание задач
└── entrypoint.sh             # Docker entrypoint
```

## API маршруты

Все маршруты под префиксом `/api/v1`. Авторизации нет (внутренний сервис). `user_id BIGINT NULL` предусмотрен в продуктовых таблицах для будущей интеграции.

### Health

| Метод | Путь | Описание |
|-------|------|----------|
| GET | `/api/health` | Статус сервиса (DB + Redis) |

### Trend Analysis

| Метод | Путь | Описание |
|-------|------|----------|
| POST | `/api/v1/analysis/run` | Запуск анализа по ключевому слову |
| GET | `/api/v1/analysis/{jobId}` | Статус / результат анализа |

Анализ работает асинхронно: POST возвращает `202 + job_id`, GET — статус `pending`/`processing`/`done`/`error`. Клиент поллит до получения `done`.

### Dashboard

| Метод | Путь | Описание |
|-------|------|----------|
| GET | `/api/v1/dashboard/trending` | Real-time: топ по гео из `/trending` парсера |
| GET | `/api/v1/dashboard/longterm` | Long-term: топики из БД, отсортированные по статусу+скору |

Параметры `trending`: `geo` (обязателен, код страны ISO 3166-1 alpha-2).  
Параметры `longterm`: `geo`, `status`, `category_id`, `page`, `per_page`.

### Catalog (Trends Database)

| Метод | Путь | Описание |
|-------|------|----------|
| GET | `/api/v1/catalog` | Поиск и фильтрация топиков |
| GET | `/api/v1/catalog/categories` | Категории + кол-во топиков в каждой |

Параметры `catalog`: `q` (поиск ILIKE), `category`, `status`, `sort` (growth/volume/newest), `page`, `per_page`.

### Projects (Trend Tracking)

| Метод | Путь | Описание |
|-------|------|----------|
| GET | `/api/v1/projects` | Список проектов |
| POST | `/api/v1/projects` | Создать проект (`{"name": "..."}`) |
| DELETE | `/api/v1/projects/{id}` | Удалить проект |
| GET | `/api/v1/projects/{id}/topics` | Топики в проекте |
| POST | `/api/v1/projects/{id}/topics` | Добавить топик (`{"topic_id": N}`) |
| DELETE | `/api/v1/projects/{id}/topics/{topicId}` | Удалить топик из проекта |
| GET | `/api/v1/projects/{id}/export` | Экспорт CSV |

## Сервисы

### `ParserClient`

HTTP-клиент к парсеру Google Trends (`PARSER_URL` + `X-API-Key: PARSER_API_KEY`).  
Методы: `trending(geo)`, `trends(keyword, geo, period)`, `relatedQueries(keyword, geo)`.  
Обрабатывает `202 Accepted` с поллингом (до 30 попыток, интервал 2с).

### `ScoringService`

Чистый PHP, без внешних зависимостей. На входе — массив точек `[timestamp_ms => value]`.

| Метод | Описание |
|-------|----------|
| `score(points, volume)` | Считает все метрики, возвращает `{status, score, growth_3m, growth_6m, growth_12m}` |
| `computeStatus(...)` | `exploding` / `regular` / `peaked` / `noise` |
| `logLinearSlope(points)` | OLS-регрессия по `log(value)`, недельный наклон |
| `isEventDriven(points)` | z-score > 2.5 + пик в последних 40% данных + текущее < 50% от максимума |

**Пороги статусов:**
- `exploding`: slope > 0.025, SMA-ratio > 1.05, !eventDriven, fullSlope ≥ 0, recentAvg ≥ 15
- `peaked`: slope < −0.015 ИЛИ fullSlope (весь ряд) < −0.015

### `DashboardService`

`getLongtermTopics()` — сортировка: `CASE status WHEN 'exploding' THEN 1 WHEN 'regular' THEN 2 WHEN 'peaked' THEN 3` + `score DESC NULLS LAST`.  
`INGEST_GEOS` — список из 51 страны для инжеста.

### `CatalogService`

`getTopics()` — ILIKE-поиск по keyword, фильтр по category/status, сортировка по `COALESCE(growth_12m, growth_pct) DESC NULLS LAST`.

### `ProjectService`

CRUD для проектов + топиков. `exportCsv()` возвращает строку CSV: `keyword,geo,status,volume,growth_12m,growth_pct,category,added_at`.

## Фоновые задачи (Jobs)

### `IngestTrendingJob(geo)`

Тянет `/trending` из парсера для одного гео. Апсертит топики и breakdown-элементы. Отправляет `SparklineFetchJob`.

### `SparklineFetchJob(geo, topicIds)`

Берёт `/trends?period=3m` для топ-20 топиков по гео. Сохраняет JSONB в `topics.sparkline`.

### `ScoreTopicsJob(batchSize=50)`

Выбирает топики, у которых `last_scored_at IS NULL` или `> 7 дней`. Приоритет — не скоренные сначала, потом `growth_pct DESC`. Вызывает `ScoringService::score()`, пишет в `topics` + `topic_metrics_history`.

### `RelatedRisingIngestJob(keyword, geo)`

Берёт `/trends/related` из парсера. Сохраняет rising-результаты. Паттерн: INSERT-or-skip (не upsert), потому что уникальный индекс только на `(keyword, geo)`.

## Расписание

Настроено в `routes/console.php`:

```
IngestTrendingJob      — каждые 30 мин × 51 гео
ScoreTopicsJob(50)     — каждый час
RelatedRisingIngestJob — каждые 6 часов (топ-30 exploding/regular топиков)
```

## База данных

### Основные таблицы

| Таблица | Описание |
|---------|----------|
| `topics` | Главная таблица: keyword, geo, status, score, growth_*, volume, sparkline JSONB, approved |
| `topic_metrics_history` | История скоринга по времени |
| `categories` | Справочник категорий |
| `projects` | Пользовательские проекты |
| `project_topics` | Связь M2M: проект ↔ топик |

Уникальные индексы:
- `topics(keyword, geo)` — один топик = одна пара keyword+geo
- `project_topics(project_id, topic_id)` — нет дублей в проекте

### Подключение (снаружи Docker)

```
host: localhost
port: 5433
database: discovery
user: discovery
password: secret
```

## Filament Admin (`/admin`)

Без авторизации (внутренний инструмент). Доступен только по сети Docker/localhost.

**Topics Curation** — главная страница:
- Колонки: keyword, geo (badge), source (badge), status (badge), score, growth_pct, volume, category, approved (иконка), discovered_at
- Фильтры: status, source, approved (тернарный — pending/approved/rejected)
- Действия на строке: Approve / Reject
- Массовые действия: bulk approve, bulk reject, delete

## Конфигурация

### Ключевые .env переменные

| Переменная | Описание |
|------------|----------|
| `APP_KEY` | Laravel app key. Только в `backend/.env`, никогда в docker-compose env |
| `PARSER_URL` | URL парсера, например `http://host.docker.internal:8000` |
| `PARSER_API_KEY` | API-ключ парсера (пусто если не нужен) |
| `REDIS_CLIENT` | Должно быть `predis` (не phpredis) |
| `QUEUE_CONNECTION` | `redis` |

### Важно: APP_KEY

`APP_KEY` должен быть только в `backend/.env`. Если передать его через `docker-compose environment:`, пустая строка перезапишет значение из `.env` и Laravel сломается.

## Разработка

```bash
# Войти в контейнер
docker compose exec backend sh

# Tinker
php artisan tinker

# Запустить конкретную задачу
php artisan tinker --execute="App\Jobs\IngestTrendingJob::dispatch('US');"

# Свежая миграция с сидером
php artisan migrate:fresh --seed

# Посмотреть очереди
php artisan horizon:status

# Логи приложения
php artisan pail
```
