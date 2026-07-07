# Trending Startups

**URL:** click "Trending Startups" nav item  
**Screenshots:** `trending-startups/nav-click.png`

## Layout

Full-width table view. Top-right: **Export Startups To CSV** button.

## Filter Bar

| Filter | Default | Options Visible |
|--------|---------|-----------------|
| **TIMEFRAME** | 5 Years | — |
| **CATEGORY** | All | — |
| **GROWTH** | All | — |
| **VOLUME** | All | — |
| **TOTAL FUNDING** | All | — |
| **LATEST ROUND** | All | — |
| **NO. EMPLOYEES** | All | — |
| **LOCATION** | All | — |

All filters are dropdown selects. 8 filter dimensions total.

## Table Columns

| Column | Description |
|--------|-------------|
| **Company** | Logo + company name + one-line description |
| **Growth** | % growth badge (green, +99X+ format) + sparkline |
| **Volume** | Monthly search volume (e.g. `70`, `246K`, `6.6K`, `33.1K`) |
| **Total Funding** | VC/Angel total (e.g. `Bootstrapped`, `$90.3M`, `Undisclosed`) |
| **Latest Round** | Funding round type (e.g. `Bootstrapped`, `Series C`, `Undisclosed`) |
| **No. Employees** | Band (e.g. `1-10`, `101-250`) |
| **Categories** | Tags (e.g. `Health & Wel... · Fitness & Ex... · Mental Health`) |

## Sample Data Rows

```
Company: Ré+Spin By Halle Berry
  Description: "Community platform dedicated to health an..."
  Growth: +99X+  (sparkline: steep rise)
  Volume: 70
  Total Funding: Bootstrapped
  Latest Round: Bootstrapped
  No. Employees: 1-10
  Categories: Health & Wellness · Fitness & Ex... · Mental Health

Company: Abacus Ai
  Description: "Abacus Ai is a machine learning and artificial..."
  Growth: +99X+
  Volume: 246K
  Total Funding: $90.3M
  Latest Round: Series C
  No. Employees: 101-250
  Categories: Business · Technology · Software

Company: Fastgpt
  Description: "FastGPT is an open-source AI knowledge..."
  Growth: +99X+
  Volume: 6.6K
  Total Funding: Undisclosed
  Latest Round: Undisclosed
  No. Employees: 1-10
  Categories: Technology · Software · Artificial Int...

Company: Vanceai
  Description: "Platform offering a range of AI-powered tools..."
  Growth: +99X+
  Volume: 33.1K
  Total Funding: Undisclosed
  Latest Round: Undisclosed
  No. Employees: 101-250
  Categories: Business · Software · Artificial Int...
```

## Interactions

| Trigger | Behaviour |
|---------|-----------|
| Any filter dropdown | Filters table in-place |
| Column header click | Sorts table by that column |
| **Export Startups To CSV** | Downloads current view |
| Click company name | Opens Trend Analysis / company detail |
| Click sparkline | — (likely opens detail) |

## API Shape (inferred)

```
GET /api/v1/startups?timeframe=5y&category=all&growth=all&page=1&sort=growth
→ {
    "total": 12500,
    "page": 1,
    "results": [
      {
        "name": "Abacus Ai",
        "slug": "abacus-ai",
        "logo_url": "...",
        "description": "...",
        "volume": 246000,
        "growth_pct": 9900,
        "total_funding_usd": 90300000,
        "latest_round": "Series C",
        "employee_band": "101-250",
        "categories": ["Business", "Technology", "Software"],
        "sparkline": [...]
      }
    ]
  }
```
