# TikTok Insights (BETA)

**URL:** click "TikTok Insights" nav item  
**Screenshots:** `tiktok-insights/nav-click.png`

## Layout

Full-width table view. Top-right: **Export TikTok Insights To CSV** button.

## Filter Bar

| Filter | Options |
|--------|---------|
| Search | `Search TikTok Hashtags` placeholder |
| **SORT BY** | Views Growth *(selected)* |
| **TIMEFRAME** | Past 30 Days *(selected)* |
| **CATEGORY** | All Categories *(selected)* |

## Table Columns

| Column | Description |
|--------|-------------|
| **Topic** | Thumbnail + topic name + short description |
| **TikTok Views** | TikTok-specific view count (past 12 months) |
| **Total Views** | All-platform views (past 30 days + % vs prev 30 days) |
| **TikTok Posts** | Number of TikTok posts (past 12 months) |
| **Total Posts** | All-platform posts (past 30 days + % vs prev 30 days) |
| **Hashtags** | Top associated hashtags with post counts |

## Sample Data Rows

```
Topic: "Barrier Moisturizer"
  TikTok Views:  Past 12 months (sparkline shown)
  Total Views:   123.6M (Past 30 days) / +4.5K% vs prev 30 days
  TikTok Posts:  Past 12 months (sparkline shown)
  Total Posts:   43 (Past 30 days) / +54% vs prev 30 days
  Hashtags:      #Moisturizer (113), #skincare (102), #skintok (88)

Topic: "B12 Gummies"
  TikTok Views:  Past 12 months (sparkline shown)
  Total Views:   11.5M (Past 30 days)
  TikTok Posts:  Past 12 months (sparkline shown)
  Total Posts:   17 (Past 30 days)
  Hashtags:      #b12 (320)

Topic: "Finger Exercise Equipment"
  Total Views:   65.3M / +2.76% vs prev 30 days
  Total Posts:   5 (Past 30 days) / -75%
```

## Interactions

| Trigger | Behaviour |
|---------|-----------|
| **SORT BY** dropdown | Sorts table: Views Growth, TikTok Views, Total Views, Posts |
| **TIMEFRAME** dropdown | Switches date window for metrics |
| **CATEGORY** dropdown | Filters to a specific industry |
| Search bar | Filters rows by hashtag/keyword |
| **Export TikTok Insights To CSV** | Downloads current filtered table |
| Click row | Opens topic detail / Trend Analysis |

## Notes

- BETA badge on nav item — feature in active development
- Feedback widget visible: "TikTok Insights is a new beta feature — How can we make it twice as good?"
- Both TikTok-specific and cross-platform metrics shown side by side
- Sparklines per row (past 12 months trend line)

## API Shape (inferred from table data)

```json
{
  "topics": [
    {
      "name": "Barrier Moisturizer",
      "description": "...",
      "thumbnail_url": "...",
      "tiktok_views_12m": null,
      "total_views_30d": 123600000,
      "total_views_30d_pct_change": 4500,
      "tiktok_posts_12m": null,
      "total_posts_30d": 43,
      "total_posts_30d_pct_change": 54,
      "hashtags": [
        {"tag": "#Moisturizer", "count": 113},
        {"tag": "#skincare", "count": 102}
      ]
    }
  ]
}
```
