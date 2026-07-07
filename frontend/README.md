# Frontend — Trends Discovery

React 19 SPA на Vite + TypeScript + Tailwind CSS v4 + ApexCharts. Шрифт Manrope. Язык UI — английский.

## Стек

| Зависимость | Версия | Назначение |
|-------------|--------|------------|
| React | 19 | UI-фреймворк |
| react-router-dom | 7 | Клиентский роутинг |
| ApexCharts + react-apexcharts | 4 / 1.6 | Графики трендов и спарклайны |
| Tailwind CSS | v4 | Стили |
| Vite | 6 | Сборщик + dev-сервер |

## Структура

```
frontend/src/
├── api/              # HTTP-клиенты к backend API
│   ├── index.ts      # BASE = '/api/v1', общий fetch
│   ├── analysis.ts   # Trend Analysis
│   ├── catalog.ts    # Trends Database (каталог)
│   ├── dashboard.ts  # Dashboard
│   └── projects.ts   # Trend Tracking (проекты)
├── components/       # Переиспользуемые компоненты
│   ├── Layout.tsx    # Sidebar + основной layout
│   ├── TrendCard.tsx # Карточка топика (dashboard)
│   ├── SparklineChart.tsx  # Мини-график ApexCharts
│   ├── TrendChart.tsx      # Полный график тренда
│   ├── RelatedQueries.tsx  # Блок related queries
│   └── RegionsList.tsx     # Список регионов
├── context/
│   └── TrackingContext.tsx # Глобальный стейт трекинга топиков
├── constants/
│   └── geos.ts       # Список стран (51 гео) с названиями
├── pages/            # Страницы-роуты
│   ├── DashboardPage.tsx      # /
│   ├── TrendAnalysisPage.tsx  # /analysis
│   ├── TrendsDatabasePage.tsx # /database
│   └── TrendTrackingPage.tsx  # /tracking
└── App.tsx           # Роутер + TrackingProvider
```

## Страницы

### Dashboard (`/`)

Два режима:
- **Real-time** — тянет `/api/v1/dashboard/trending?geo=XX` (выбор страны в дропдауне). Данные из парсера в реальном времени.
- **Long-term** — тянет `/api/v1/dashboard/longterm` (данные из БД после скоринга). Поддерживает worldwide-режим.

Карточки топиков (`TrendCard`) показывают: keyword, гео, статус (`Exploding` / `Regular` / `Peaked`), growth, volume, спарклайн, кнопку "+ TRACK TOPIC".

### Trend Analysis (`/analysis`)

Поиск произвольного ключевого слова. Параметры:
- Keyword (обязателен)
- Geo (дропдаун с 51 страной + Worldwide)
- Engine (web / youtube / images / news / shopping)

Запускает анализ через POST, поллит результат каждые 2с. После завершения показывает:
- Полный график (ApexCharts area chart)
- Related queries (с бейджами Breakout / +NNN%)
- Regions (карта интереса по регионам)

### Trends Database (`/database`)

Каталог всех проиндексированных и отскоренных топиков.

- Поиск (350ms debounce)
- Browse by Category (сетка категорий, видна только без активного поиска/фильтра)
- Фильтры: Status (Exploding/Regular/Peaked), Sort (growth/volume/newest)
- Пагинация (24 на страницу)
- Кнопка Track Topic на каждой карточке

### Trend Tracking (`/tracking`)

Управление проектами и отслеживаемыми топиками.

- Список проектов (сайдбар слева): создание, удаление
- Список топиков проекта: спарклайн, статус, рост, объём
- Удаление топика из проекта (кнопка × при hover)
- Экспорт CSV всего проекта
- Empty-стейты с CTA

## TrackingContext

Глобальный React-контекст для добавления топиков в проекты. Доступен через `useTracking()`.

```typescript
const { trackTopic, trackedIds } = useTracking()
```

`trackTopic(topicId)`:
1. Если проектов нет — автоматически создаёт "My Project"
2. Добавляет топик в первый проект
3. Обновляет `trackedIds` оптимистично

`trackedIds: Set<number>` — IDs отслеживаемых топиков (для отображения ✓ TRACKED).

## Прокси API

Vite проксирует все `/api/*` запросы на backend:

```
BACKEND_URL (docker-internal) → http://backend:8000
Browser request: /api/v1/... → proxy → http://backend:8000/api/v1/...
```

Frontend всегда использует относительные пути (`/api/v1/...`). `BACKEND_URL` — только серверная переменная для Vite, в браузерный бандл не попадает.

## Разработка (вне Docker)

```bash
cd frontend
npm install

# Задать адрес backend
export BACKEND_URL=http://localhost:8080

npm run dev       # dev-сервер на :3000
npm run build     # production build в dist/
npm run preview   # preview production build
```

## Сборка в Docker

Контейнер запускает `npm run dev` в режиме разработки с HMR. При изменении файлов браузер обновляется автоматически.

Для production-сборки используйте multi-stage Dockerfile или `npm run build` с раздачей через nginx/caddy.
