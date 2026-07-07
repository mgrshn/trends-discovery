# Trending Products

**URL:** click "Trending Products" nav item  
**Screenshots:** `trending-products/nav-click.png` (table with blurred/loading data)

## Layout

Full-width table view. Top-right: **Export Products To CSV** button.

## Filter Bar

| Filter | Default | Description |
|--------|---------|-------------|
| **TIMEFRAME** | 5 Years | Time window for trend data |
| **CATEGORY** | All | Product category |
| **GROWTH** | All | Growth rate filter |
| **VOLUME** | All | Search volume range |
| **REVENUE** | All | Estimated revenue |
| **BSR** | All | Best Seller Rank (Amazon) |
| **PRICE** | All | Product price range |
| **SALES** | All | Estimated sales volume |
| **REVIEWS** | All | Review count filter |

9 filter dimensions — richer than Trending Startups (adds Amazon-specific: BSR, Revenue, Sales, Reviews, Price).

## Table Columns

| Column | Description |
|--------|-------------|
| **Product Information** | Thumbnail + product name + description |
| **Growth** | % growth badge + sparkline |
| **Volume** | Monthly search volume |
| **TikTok Activity** | TikTok-specific engagement metric |
| **Avg. Revenue** | Average monthly revenue (Amazon-sourced) |
| **Avg. BSR** | Average Best Seller Rank |
| **Avg. Price** | Average selling price |
| **Avg. Sales** | Average monthly unit sales |
| **Avg. Reviews** | Average review count |

Note: data was blurred/loading in screenshot — likely a plan gating behavior (Entrepreneur plan doesn't include some columns).

## Interactions

| Trigger | Behaviour |
|---------|-----------|
| Filter dropdowns | Filter table results |
| Column header | Sort by that metric |
| **Export Products To CSV** | Downloads current view |
| Click product row | Opens product detail / Trend Analysis |

## Data Sources Implied

- Google search volume (Growth, Volume)
- Amazon BSR / revenue / sales / price / reviews
- TikTok activity feed (same data as TikTok Insights)

## API Shape (inferred)

```
GET /api/v1/products?timeframe=5y&category=all&page=1&sort=growth
→ {
    "total": 50000,
    "page": 1,
    "results": [
      {
        "name": "...",
        "description": "...",
        "thumbnail_url": "...",
        "volume": 12000,
        "growth_pct": 450,
        "tiktok_activity": 9800000,
        "avg_revenue_usd": 45000,
        "avg_bsr": 320,
        "avg_price_usd": 29.99,
        "avg_sales": 1500,
        "avg_reviews": 234,
        "sparkline": [...]
      }
    ]
  }
```
