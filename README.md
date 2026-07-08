# Trends Discovery

Сервис обнаружения трендов поверх парсера Google Trends. Отслеживает растущие темы, рассчитывает скоринг и отображает статусы (Exploding / Regular / Peaked).

## Архитектура

```
                    HTTPS (Caddy)
  Browser ─────────────────────────────────┐
                                           ▼
                                    ┌────────────┐
                                    │   Caddy    │ :80/:443
                                    └─────┬──────┘
                           /api, /admin   │   /  (React SPA)
                                  ┌───────┴───────┐
                                  ▼               ▼
                           ┌──────────┐    ┌────────────┐
                           │ backend  │    │  frontend  │
                           │ Laravel  │    │  (static)  │
                           │ :8000    │    └────────────┘
                           └──┬───┬───┘
                              │   └──────────────────────┐
                    ┌─────────┴──────┐        ┌──────────┴──────┐
                    │  horizon       │         │   scheduler     │
                    │ (queue worker) │         │ (cron every 1m) │
                    └────────────────┘         └─────────────────┘
                              │
                    ┌─────────┴──────────┐
                    │ PostgreSQL + Redis  │
                    └────────────────────┘
                              │
                        shared network
                              │
                    ┌─────────┴──────────┐
                    │ google-trends-     │
                    │ parser :8000       │
                    └────────────────────┘
```

## Production deploy (VDS)

### Первый раз на новом сервере

```bash
# 1. Установить Docker + Docker Compose (если нет)

# 2. Создать shared-сеть для связи с парсером (если парсер на том же сервере)
docker network create shared

# 3. Клонировать репо
git clone git@github.com:mgrshn/trends-discovery.git
cd trends-discovery

# 4. Создать .env
cp env.prod.example .env
# Отредактировать: DOMAIN, APP_URL, APP_KEY, DB_PASSWORD, PARSER_API_KEY

# 5. Поднять
docker compose -f docker-compose.prod.yml up -d --build
```

`entrypoint.sh` при старте автоматически:
- применяет миграции (`php artisan migrate`)
- публикует ассеты Filament
- засевает категории
- кэширует конфиг и роуты

### Обновление

```bash
cd ~/apps/discovery && bash deploy.sh
```

Скрипт: стягивает код, пересобирает изменившиеся контейнеры, показывает статус и ссылку на админку.

### Переменные окружения (`.env`)

| Переменная | Пример | Описание |
|------------|--------|----------|
| `DOMAIN` | `example.com` | Хост без схемы — для Caddy + Let's Encrypt |
| `APP_URL` | `https://example.com` | Полный URL — для Laravel |
| `APP_KEY` | `base64:...` | Laravel app key (**только в `.env`**, не в docker-compose) |
| `DB_DATABASE` | `discovery` | Имя базы данных |
| `DB_USERNAME` | `discovery` | Пользователь БД |
| `DB_PASSWORD` | — | Пароль БД |
| `PARSER_URL` | `http://parser:8000` | URL парсера (через shared docker-сеть) |
| `PARSER_API_KEY` | — | API-ключ парсера |

Сгенерировать `APP_KEY`:
```bash
docker run --rm php:8.3-cli php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
```

## Расписание фоновых задач

| Задача | Частота | Описание |
|--------|---------|----------|
| `IngestTrendingJob` | каждые 30 мин | Тянет `/trending` для 51 страны |
| `SparklineFetchJob` | после инжеста | Берёт спарклайн (3m) для топ-20 топиков по каждому гео |
| `ScoreTopicsJob` | каждый час | Скоринг батчами по 50 топиков |
| `RelatedRisingIngestJob` | каждые 6 часов | Rising-запросы для топ-30 взрывных топиков |

### Проверить что автопарсинг работает

```bash
# 1. Планировщик видит задачи
docker compose -f docker-compose.prod.yml exec scheduler php artisan schedule:list

# 2. Horizon принимает джобы (статус should be running)
curl https://DOMAIN/horizon/api/stats

# 3. Данные появляются в БД
docker compose -f docker-compose.prod.yml exec backend php artisan tinker --execute="
echo 'Topics: ' . DB::table('topics')->count() . PHP_EOL;
echo 'Last ingest: ' . DB::table('topics')->max('last_seen_at') . PHP_EOL;
DB::table('topics')->selectRaw('status, count(*) n')
    ->whereNotNull('status')->groupBy('status')->get()
    ->each(fn(\$r) => print(\$r->status . ': ' . \$r->n . PHP_EOL));
"

# 4. Логи Horizon — посмотреть выполненные джобы
docker compose -f docker-compose.prod.yml logs --tail=50 horizon
```

### Запустить инжест вручную (Parse now)

Через админку: `/admin/parser-settings` → кнопка **Parse now** → выбрать гео → Dispatch jobs.

Или из CLI:
```bash
docker compose -f docker-compose.prod.yml exec backend php artisan tinker --execute="
foreach (App\Services\DashboardService::ingestGeos() as \$geo) {
    App\Jobs\IngestTrendingJob::dispatch(\$geo)->onQueue('default');
}
echo 'Dispatched jobs' . PHP_EOL;
"
```

## Полезные команды

```bash
# Статус контейнеров
docker compose -f docker-compose.prod.yml ps

# Логи
docker compose -f docker-compose.prod.yml logs -f backend
docker compose -f docker-compose.prod.yml logs -f horizon
docker compose -f docker-compose.prod.yml logs -f scheduler

# Перезапустить Horizon (после изменений в очередях)
docker compose -f docker-compose.prod.yml restart horizon

# Принудительно сбросить кэш конфига
docker compose -f docker-compose.prod.yml exec backend php artisan config:clear
docker compose -f docker-compose.prod.yml exec backend php artisan config:cache
```

## Локальная разработка

```bash
# Backend
cd backend && composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
php artisan serve

# Frontend (отдельный терминал)
cd frontend && npm install && npm run dev

# Horizon (отдельный терминал)
php artisan horizon
```
