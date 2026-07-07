# Trends Discovery

Сервис обнаружения трендов поверх парсера Google Trends. Отслеживает растущие темы, рассчитывает скоринг и отображает статусы (Exploding / Regular / Peaked).

## Архитектура

```
┌─────────────────────────────────────────────────────┐
│                  docker-compose                      │
│                                                      │
│  ┌──────────┐   ┌──────────┐   ┌────────────────┐  │
│  │ frontend │   │ backend  │   │    horizon     │  │
│  │ React SPA│──▶│ Laravel  │──▶│ (queue worker) │  │
│  │ :3000    │   │ :8080    │   └────────────────┘  │
│  └──────────┘   └────┬─────┘   ┌────────────────┐  │
│                       │         │   scheduler    │  │
│                       │         │ (cron every 1m)│  │
│               ┌───────┴──────┐  └────────────────┘  │
│               │  PostgreSQL  │                       │
│               │  Redis       │                       │
│               └──────────────┘                       │
└─────────────────────────────────────────────────────┘
              │
              ▼ HTTP
     google-trends-parser
     http://localhost:8000
```

## Требования

- Docker + Docker Compose
- Запущенный сервис [google-trends-parser](../google-trends-parser) на `http://localhost:8000`

## Быстрый старт

### 1. Переменные окружения

```bash
cp backend/.env.example backend/.env
```

Обязательно задать `APP_KEY` в `backend/.env`:

```bash
# Сгенерировать ключ (нужен запущенный PHP или Docker)
docker run --rm php:8.3-cli php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
```

Минимальный `backend/.env`:

```env
APP_KEY=base64:ВАШ_КЛЮЧ_ЗДЕСЬ
PARSER_URL=http://host.docker.internal:8000
PARSER_API_KEY=           # оставить пустым если парсер без авторизации
```

### 2. Запуск

```bash
docker compose up -d
```

При первом запуске entrypoint автоматически:
- установит PHP-зависимости (`composer install`)
- применит миграции (`php artisan migrate`)
- опубликует ассеты Filament
- засеет категории

### 3. Проверка

| Сервис | URL |
|--------|-----|
| React SPA | http://localhost:3000 |
| Backend API | http://localhost:8080/api/v1 |
| Admin (Filament) | http://localhost:8080/admin |
| Horizon (очереди) | http://localhost:8080/horizon |
| Health check | http://localhost:8080/api/health |

### 4. Первые данные

Данные появляются автоматически — инжест-джоба запускается каждые 30 минут по расписанию. Для немедленного запуска:

```bash
# Запустить инжест по всем 51 гео прямо сейчас
docker compose exec backend php artisan tinker --execute="
foreach (App\Services\DashboardService::ingestGeos() as \$geo) {
    App\Jobs\IngestTrendingJob::dispatch(\$geo);
}
echo 'Dispatched ' . count(App\Services\DashboardService::ingestGeos()) . ' jobs';
"

# Запустить скоринг вручную
docker compose exec backend php artisan tinker --execute="App\Jobs\ScoreTopicsJob::dispatch(50);"
```

## Расписание фоновых задач

| Задача | Частота | Описание |
|--------|---------|----------|
| `IngestTrendingJob` | каждые 30 мин | Тянет `/trending` для 51 страны |
| `SparklineFetchJob` | после инжеста | Берёт спарклайн (3m) для топ-20 топиков по каждому гео |
| `ScoreTopicsJob` | каждый час | Скоринг батчами по 50 топиков |
| `RelatedRisingIngestJob` | каждые 6 часов | Rising-запросы для топ-30 взрывных топиков |

## Полезные команды

```bash
# Статус сервисов
docker compose ps

# Логи
docker compose logs -f backend
docker compose logs -f horizon

# Tinker (Laravel REPL)
docker compose exec backend php artisan tinker

# Статистика топиков
docker compose exec backend php artisan tinker --execute="
echo 'Total: ' . DB::table('topics')->count() . PHP_EOL;
DB::table('topics')->selectRaw('status, count(*) n')
    ->whereNotNull('status')->groupBy('status')->get()
    ->each(fn(\$r) => print(\$r->status . ': ' . \$r->n . PHP_EOL));
"

# Проверить расписание
docker compose exec backend php artisan schedule:list

# Перезапустить Horizon (после изменений в очередях)
docker compose restart horizon
```

## Переменные окружения

| Переменная | По умолчанию | Описание |
|------------|-------------|----------|
| `APP_KEY` | — | Laravel app key (обязателен, только в `.env`) |
| `PARSER_URL` | `http://host.docker.internal:8000` | URL парсера Google Trends |
| `PARSER_API_KEY` | пусто | API-ключ парсера (если включена авторизация) |
| `DB_DATABASE` | `discovery` | Имя базы данных |
| `DB_USERNAME` | `discovery` | Пользователь БД |
| `DB_PASSWORD` | `secret` | Пароль БД |
| `BACKEND_PORT` | `8080` | Внешний порт backend |
| `FRONTEND_PORT` | `3000` | Внешний порт frontend |
| `DB_EXTERNAL_PORT` | `5433` | Внешний порт PostgreSQL |
