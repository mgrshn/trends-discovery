# План реализации: trends-discovery

ТЗ для агента-разработчика. Самодостаточный документ — контекст, ограничения, спецификация по этапам.

---

## 1. Контекст

### Что уже существует (НЕ трогать, НЕ переписывать)

Рабочий сервис **google-trends-parser** (`~/Projects/SMH/google-trends-parser`) — headless API-парсер Google Trends:
FastAPI + PostgreSQL + Redis-очередь + воркеры со скрейпингом через ротирующие прокси. Поднят через docker compose,
`http://localhost:8000`, авторизация `X-API-Key` (если env `API_KEY` пуст — отключена, локально так и есть).
**Мы к нему ходим только по HTTP; его внутренний стек (Python) нас не касается — своё пишем на Laravel.**

Его эндпоинты (полная документация — Swagger на `http://localhost:8000/docs`):

| Эндпоинт | Что отдаёт |
|---|---|
| `GET /trends?keyword=&geo=&period=&category=&engine=` | Временной ряд интереса 0–100. `period`: 1h/4h/1d/7d/1m/3m/12m/5y/all/custom(+from,to). `engine`: web/youtube/images/news/shopping. Ответ: `{cached, points: [{ts, value}]}`. Может вернуть 202 (данные собираются — повторить) |
| `GET /trends/batch?keywords=a,b&geos=US,GB&…` | До 20 ключей × N гео разом. Кеш сразу, остальное `status: queued` |
| `GET /trends/compare?keywords=a,b&…` | 2–5 ключей в общей шкале (значения сравнимы между ключами) |
| `GET /trends/regions?keyword=&geo=&…` | География интереса: `regions: [{geo_code, name, value 0–100}]` |
| `GET /trends/related?keyword=&geo=&…` | Связанные запросы: `top: [{query, value}]`, `rising: [{query, value, formatted}]` — formatted `"+250%"` или `"Breakout"` |
| `GET /trending?geo=&since_hours=&limit=&category=&sort=&status=` | Трендовые запросы страны (Trending Now): `{keyword, volume, growth_pct, started_at, breakdown[], categories[], first_seen_at}`. История копится, сортировки volume/growth/recency/title |
| `GET /status`, `GET /health` | Мониторинг |

Парсер сам опрашивает Trending Now по ~65 гео каждые 15 минут (cron) — история в его БД уже накапливается.

### Что строим

**trends-discovery** — новый монорепозиторий рядом с парсером. Функциональная копия
Exploding Topics Pro (https://www.semrush.com/app/exploding-topics/pro/dashboard), страница за страницей.
Заказчик визуально сравнивает каждую страницу с оригиналом.

**Разведка UX оригинала лежит в `docs/ux/`** (скриншоты + разбор каждой страницы + `SUMMARY.md` +
реальные категории с числами в `trends-database.md`). Читать перед вёрсткой соответствующей страницы.

```
trends-discovery/
├── backend/            Laravel 13: API + бизнес-логика в сервис-слое, Filament (админка/курация)
├── frontend/           React SPA (Vite + TS + Tailwind + ApexCharts): копия страниц ET Pro
├── docker-compose.yml  postgres + backend (php-fpm/octane) + frontend + scheduler/queue
└── README.md
```

---

## 2. Стек

- **Backend: Laravel 13** (или максимальная версия, которую поддерживает текущий стабильный Filament —
  проверить при старте). PostgreSQL. Очереди/планировщик — Laravel Scheduler + Horizon.
  Клиент к парсеру — обёртка на Http-фасаде с retry на 202 (поллинг 2s до 60s).
- **Админка/курация: Filament** — подключить рано и минимально (не в самый последний этап).
  Ресурсы для topics, категорий, проектов; экшены approve/reject; операционные правки данных.
- **Frontend: отдельная React SPA** — Vite + TypeScript + Tailwind + ApexCharts (линейные графики
  и спарклайны). Ходит ТОЛЬКО в API бэкенда. Шрифт **Manrope** (Google Fonts — как в оригинале).
- **Скоринг: на PHP** (см. этап 3). В PHP нет numpy/statsmodels — базовую математику (наклон,
  скользящее среднее, z-score, рост %) пишем руками, это тривиально. Тяжёлую статистику
  (STL-декомпозиция, строгая сезонность) на старте заменяем упрощениями; если реально понадобится —
  ВЫНОСИМ этот кусок в маленький Python-сервис за HTTP (не тащим Python в основной бэкенд).
- **Вебсокеты: не в MVP.** Когда дойдём до алертов/живой ленты — Laravel Reverb + Echo.
  Первый тренировочный кейс — пуш «анализ готов» вместо 202-поллинга на странице Trend Analysis.

Конвенции — как в парсере по духу: `.env` + `.env.example`, миграции (Laravel migrations), docker compose,
осмысленный лог-уровень. UI-язык — английский (копия оригинала).

---

## 3. Жёсткие ограничения

1. **Никакой авторизации/регистрации/биллинга** в продуктовой части. Но закладываем на будущее:
   все API-роуты — в одной route-группе (чтобы позже навесить middleware auth одной строкой),
   а в продуктовых таблицах (projects и т.п.) — колонка `user_id BIGINT NULL` с первого дня.
   Filament-панель своей встроенной аутентификацией не мешает — она внутренняя, для нас.
2. **С парсером общаться ТОЛЬКО по HTTP** (env `PARSER_URL`, заголовок `X-API-Key` из env `PARSER_API_KEY`).
   Никаких прямых подключений к БД/Redis парсера.
3. **Свой PostgreSQL** (отдельный инстанс в своём compose). Discovery хранит производные данные
   (топики, метрики, статусы) и продуктовые сущности; сырые ряды НЕ копирует — они живут в кеше
   парсера, при необходимости перезапрашиваются.
4. **Бизнес-логика — в сервис-слое Laravel**, не в контроллерах и не в Filament/Livewire-компонентах.
   Filament и API — только тонкие view/transport поверх сервисов. Это сохраняет переносимость:
   позже поверх тех же сервисов можно добавить что угодно, не переписывая ядро.
5. **Запрос пользователя никогда не запускает скрейпинг** (кроме страницы Trend Analysis — там это фича).
   Каталог и дашборд отвечают из своей БД за миллисекунды; наполнение — фоновыми джобами.
6. Из ET Pro НЕ копируем (отдельные фазы, не сейчас): TikTok Insights, Trending Startups,
   Trending Products, Meta Trends, Reports Library, API Access. Auth-стены и апселлы — тоже нет.

---

## 4. Схема БД discovery (черновик, уточнять по ходу)

Реализовать Laravel-миграциями. Ориентир:

```
topics
  id, keyword, geo (''=worldwide), UNIQUE(keyword, geo),
  source ('trending'|'related_rising'|'breakdown'|'manual'),
  seed_keyword (из какого seed пришёл),
  category_id (наша категория),
  status ('exploding'|'regular'|'peaked'|'noise'|null=не оценён),
  score (композитный, для сортировки),
  volume (последний известный объём),
  growth_3m, growth_6m, growth_12m (% по сглаженному ряду),
  sparkline (jsonb — закешированный мини-ряд для карточки),
  discovered_at, last_scored_at,
  approved (null=не смотрели, true/false=решение куратора)

topic_metrics_history (topic_id, computed_at, score, growth_3m, growth_6m, growth_12m, status)
projects (id, user_id BIGINT NULL, name, created_at)
project_topics (project_id, topic_id, added_at)
categories (id, name, google_category_ids int[])   -- маппинг наших категорий на ID Google
```

Категории: у Google свои ID (в `/trending` поле `categories`, в `/trends` параметр `category`).
Оригинальные категории ET с реальными числами — в `docs/ux/trends-database.md`
(Business, Technology, Beauty, Health & Wellness, Home, Entertainment, Fashion, Finance,
Food & Beverage, Industry, Lifestyle & Culture, Retail, Productivity, Supply Chain & Logistics,
Marketing, Cybersecurity, Human Resources, Legal, Real Estate, Workplace). Сделать таблицу-маппинг
наших категорий → списки Google-category-ID.

---

## 5. Этапы (по зависимости; каждый заканчивается демо заказчику)

### Этап 0 — Скаффолд (1–2 дня) ✅ ВЫПОЛНЕН 2026-07-07

Каркас монорепо: Laravel-бэкенд (+Filament, +Horizon), React-фронт (Vite), docker-compose
(свой postgres, backend, frontend, queue-воркер/scheduler). Клиент к парсеру (Http-обёртка с retry на 202).
Health-роут, проверяющий доступность парсера. Layout фронта: сайдбар с разделами как у ET Pro
(Dashboard, Trend Tracking, Trends Database, Trend Analysis + плейсхолдеры остальных), шрифт Manrope,
чистый светлый стиль под оригинал (см. `docs/ux/`).

Критерий: `docker compose up` поднимает всё; фронт открывается; health бэкенда зелёный и видит парсер;
Filament-панель доступна на `/admin`.

### Этап 1 — Trend Analysis (страница) (2–3 дня) ✅ ВЫПОЛНЕН 2026-07-07

Копия страницы анализа топика. Данные на 100% готовы в парсере — это чистый UI + прокси-эндпоинт.

Backend: `GET /api/analysis?keyword=&geo=&period=` — сервис агрегирует 3 вызова парсера
(`/trends`, `/trends/related`, `/trends/regions`) в один ответ; 202 обрабатывает поллингом.

Фронт-страница (референс — `docs/ux/trend-analysis.md`):
- поиск по любой теме + выбор гео (Worldwide + страны) + период (у ET: 3 Months … 15 Years —
  маппить на наши 3m/12m/5y/all)
- большой линейный график временного ряда (ApexCharts)
- блоки: Related (top + rising с бейджами Breakout / +250%), Interest by Region (список/карта)
- счётчик «анализов в месяц» НЕ делаем (у нас безлимит — наше преимущество, а не ограничение)

Критерий: заказчик вводит любую тему → за секунды видит график/related/regions, переключает гео и период.

### Этап 2 — Dashboard (2–3 дня) ✅ ВЫПОЛНЕН 2026-07-07

Копия главной: карусель/сетка "Recommended Trends" — мини-график (спарклайн), Volume, Growth %, категория,
кнопка Track; фильтр по категориям. Референс — `docs/ux/dashboard.md` (поля карточки: название, Volume «18.1K»,
Growth «+492%», «+ TRACK TOPIC»).

Backend:
- инжест-джоба (Scheduler, каждые 15–30 мин): тянет `/trending` по списку гео → upsert в `topics`
  (source='trending'); breakdown-вариации → тоже topics (source='breakdown')
- `GET /api/dashboard?category=&geo=` — топ-карточки; пока скоринга нет — ранжировать по
  `growth_pct DESC, volume DESC`
- спарклайн карточки: ряд из парсера `/trends?period=1m`, батчем при инжесте топ-N, кешировать в
  `topics.sparkline`; НЕ дёргать парсер на рендер

Критерий: главная как у ET (карточки с цифрами и спарклайнами), фильтр категорий работает.

### Этап 3 — Скоринг и статусы (неделя) ✅ ВЫПОЛНЕН 2026-07-08

Пайплайн, превращающий сырой рост в статусы ET (Exploding/Regular/Peaked). Свой PHP-код, ~300 строк,
без внешних форков. Математику писать руками (это школьная статистика).

Для каждого топика без свежего скора (Scheduler, батчами, приоритет — новые и с высоким growth):
1. Взять ряд `12m` (weekly) у парсера (батчами через `/trends/batch`)
2. **Gate по объёму**: почти пустой ряд (мало hasData) → status='noise', дальше не считать
3. **Сглаживание**: скользящее среднее 4 недели; все метрики — по сглаженному ряду
4. **Метрики**: наклон лог-линейной регрессии (12 недель); ускорение (вторая разность);
   отношение SMA-короткое/SMA-длинное; рост % на 3м/6м/12м
5. **Спайк vs устойчивый**: z-score пика + проверка «уровень через 2–4 недели после пика остался выше базы?»;
   спайк с полным откатом → пометка event-driven
6. **Сезонность** (если есть год+ истории): автокорреляция на лаге 52 недель, порог ~0.3 → пометка seasonal.
   (Полноценный STL пока не нужен; если позже понадобится — отдельный Python-сервис.)
7. **Статус**: Exploding (наклон > порога И ускорение ≥ 0 И не event-driven) · Regular (устойчивый
   умеренный рост) · Peaked (наклон < 0 или текущее сильно ниже исторического максимума)
8. **Композитный score** = w1·наклон + w2·ускорение + w3·short/long + w4·log(volume+1) + w5·свежесть
   − w6·сезонность − w7·волатильность. Веса — константы в конфиге, крутить по глазу
9. Записать в topics + topic_metrics_history

Бюджет: скоринг топика = 1 запрос к парсеру (ряд 12m, у него кеш 7 дней). 10K топиков/день — в пределах его 1M/день.
Dashboard после этого пересаживается на score/status вместо сырого growth_pct.

Критерий: у топиков появились статусы; на карточках бейджи Exploding/Regular/Peaked; выдача глазами
адекватна (спайки-однодневки не в топе).

### Этап 4 — Trends Database (страница) (3–5 дней) ✅ ВЫПОЛНЕН 2026-07-08

Копия каталога: таблица трендов с поиском и фильтрами. Референс — `docs/ux/trends-database.md`.

Backend:
- петля кандидатов: для одобренных/высокоскоровых топиков дёргать `/trends/related` → rising-запросы →
  новые topics (source='related_rising', seed_keyword=родитель). Scheduler, с дедупом
- `GET /api/catalog?q=&category=&status=&sort=&growth_min=&page=` — поиск (ILIKE/полнотекст PG),
  фильтры (категория, статус, диапазон роста/объёма), сортировки, пагинация
- счётчики трендов по категориям (как «Business 379K» в оригинале)

Фронт: таблица (keyword, спарклайн, volume, growth %, статус-бейдж, категория), клик по строке →
страница Trend Analysis этого ключа. Поиск + фильтры + пагинация.

Критерий: каталог листается/ищется/фильтруется; база растёт сама день ото дня (видно по счётчикам).

### Этап 5 — Trend Tracking (Projects) (2–3 дня) ✅ ВЫПОЛНЕН 2026-07-08

Референс — `docs/ux/trend-tracking.md` (пустое состояние, создание проекта, Export CSV).
- CRUD проектов (user_id NULL), кнопка Track на карточках/строках, страница проекта со списком
  отслеживаемых топиков и их метриками
- Export to CSV (проект и выдача каталога)

Критерий: создать проект → добавить тренды → видеть динамику → выгрузить CSV.

### Этап 6 — Курация (в Filament) (1 день, но подключить Filament РАНО — с этапа 0) ✅ ВЫПОЛНЕН 2026-07-08

Filament-ресурс над topics: очередь неразмеченных (approved IS NULL), фильтры по статусу/категории,
экшены approve/reject (в т.ч. bulk). Rejected скрыты из Dashboard/каталога по умолчанию.
Панель — внутренняя, для команды.

---

## 6. Что показывать заказчику

После каждого этапа — ссылка/скриншот страницы рядом с оригиналом ET Pro (`docs/ux/`).
Пиксель-перфект НЕ нужен; нужны те же элементы, данные и механики.

## 7. Не делать (чтобы не расползлось)

- Auth, тарифы, лимиты «100 анализов» — нет
- TikTok / Startups / Products / Meta Trends / Reports / API Access — следующие фазы, не начинать
- ML / BERTopic / прогнозы — нет, только эвристики этапа 3
- Вебсокеты — не в MVP (Reverb позже)
- Не менять код парсера. Не хватает эндпоинта/поля — записать в `PARSER_REQUESTS.md` и продолжать,
  не блокируясь: почти всё решается на стороне discovery