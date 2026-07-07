# Trends Database

**URL:** `/pro/database` (or click nav)  
**Screenshots:** `trends-database/nav-click.png` (category list), `trends-database/full.png`, `trends-database/category-drilldown.png`

## Layout

Full-width. Top: page title **Trends Database** + search bar spanning full width.

## Category List (root view)

Search bar: `Search Trends Database` placeholder.

Category grid (2-column, icon + name + count):

| Category | Count |
|----------|-------|
| Business | 379,547 |
| Technology | 244,228 |
| Beauty | 125,864 |
| Health & Wellness | 323,082 |
| Home | 59,565 |
| Entertainment | 87,086 |
| Fashion | 50,613 |
| Finance | 68,082 |
| Food & Beverage | 98,743 |
| Industry | 159,238 |
| Lifestyle & Culture | 132,853 |
| Retail | 136,483 |
| Productivity | 100,640 |
| Supply Chain & Logistics | 24,616 |
| Marketing | 37,919 |
| Cybersecurity | 23,970 |
| Human Resources | 14,704 |
| Legal | 9,194 |
| Real Estate | 9,845 |
| Workplace | 22,176 |

Total: ~1.8M topics indexed.

## Category Drilldown View (e.g. Business Topics)

After clicking a category:

### Filter Bar

| Filter | Options observed |
|--------|-----------------|
| **Sort By** | Growth *(selected)*, Volume, Newest |
| **Timeframe** | 5 Years *(selected)*, 1 Year, 6 Months, 3 Months, 1 Month |
| **Type** | Brands + Non-Brands *(selected)*, Brands Only, Non-Brands Only |
| **Volatility** | Volatile + Stable *(selected)*, Volatile, Stable |

### Topic Cards

Each card in the grid:
- **Topic name** (bold, e.g. "Barrier Moisturizer")
- Short description (1-2 sentence snippet)
- **Volume** (e.g. `45.5M`) with label
- **Growth badge** (e.g. `+99X+`) — green for positive
- **Sparkline** (line chart, ~5 years)
- **Status tag** (e.g. "Exploding", "Regular", "Peaked")
- **+ TRACK TOPIC** button

### Sample Data Points

```
Topic: "Barrier Moisturizer"
Category: Business / Skincare
Volume: 45.5M
Growth: +99X+
Description: "Skincare products designed to protect and repair the skin..."
Status: [inferred Exploding]
```

## Interactions

| Trigger | Behaviour |
|---------|-----------|
| Type in search bar | Filters visible topics as-you-type (client-side or debounced) |
| Click category | Navigates into category drilldown |
| Change **Sort By** | Re-orders card grid |
| Change **Timeframe** | Updates sparklines + growth % for selected window |
| Change **Type** | Filters brands vs non-brands |
| Change **Volatility** | Filters stable vs volatile trends |
| Click **+ TRACK TOPIC** | Adds to current project |
| Click card | Opens Trend Analysis detail for that topic |

## API Calls Observed

No REST calls captured (data likely SSR or cached bundle). Recommend instrumenting the network tab manually while scrolling through the category — the endpoint probably looks like:

```
GET /api/v1/topics?category=business&sort=growth&timeframe=5y&type=all&page=1
```
