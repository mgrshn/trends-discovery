# Meta Trends

**URL:** click "Meta Trends" nav item  
**Screenshots:** `meta-trends/nav-click.png`

## Layout

Full-width content area. Page title: **Meta Trends** with info icon (ⓘ).  
Top-right: **All Meta Trends** link.

## Structure

Two content sections visible: **Trending** + **Technology** (with more below the fold).

### Section: Trending (top)

Curated editorial analysis articles — pre-written deep-dive reports on macro trend clusters.

Card layout:
- **Featured card** (large, left): Featured meta trend with a paragraph of editorial analysis text
- **Small cards** (grid, right): 6–8 topic names each with **View Analysis** link

### Featured Card Example

```
Title: "Holiday Tech Gifts"
Analysis excerpt: "Global holiday tech spending reached $42 billion, up 7.5% year-over-year. 
Smart home devices—such as video doorbells, robot vacuums, and connected lighting—saw a 14% 
sales increase, driven by rising home automation interest. Wearables, especially 
smartwatches and fitness trackers, remained top sellers, with Apple, Garmin, and Whoopi 
leading category growth.
Notably, AI-powered gadgets—like smart assistants and translation earbuds—experienced a 
28% uptick, indicating rising consumer comfort with emerging tech. Gifting trends are also 
increasingly influenced by sustainability, with brands offering energy-efficient tech and 
recycled packaging seeing higher conversion rates..."
Button: [View Analysis →]
```

### Small Cards (Trending section)

```
Futuristic Home Products   [View Analysis →]
Next Gen Face Care         [View Analysis →]
Everything is AI           [View Analysis →]
AI Marketing Tools         [View Analysis →]
AI Infused Products        [View Analysis →]
Travel eSIM                [View Analysis →]
```

### Section: Technology (below)

Similar layout — Wellness section also visible (partially).

Full list from page text:
```
Trending Car Accessories · Bike Accessories Trending In 2026 · The Smart Kitchen Revolution
Pet Products · Barrier Repair Skincare · Doomsday Preparation · Next Gen Beauty
Beauty Devices & Hardware · AI Marketing Tools · AI Infused Products · Trending Beverages
Batteries Everywhere · Natural Anti Inflammatories · Docking And Charging · Travel Gadgets
Longevity Skincare · Longevity Supplements · Collagen Craze · ...
```

## Meta Trend Detail View (View Analysis)

Clicking "View Analysis" opens a full editorial page:
- Long-form analysis text (multi-paragraph)
- Embedded trend charts for constituent topics
- Related topics list
- CTA: Track all topics in this meta trend

## Interactions

| Trigger | Behaviour |
|---------|-----------|
| **View Analysis** button/link | Opens full meta-trend report page |
| **All Meta Trends** link | Shows complete list |
| Section headers (Trending, Technology, Wellness…) | Scroll anchors or filter tabs |

## API Shape (inferred)

```
GET /api/v1/meta-trends
→ {
    "sections": [
      {
        "name": "Trending",
        "featured": {
          "title": "Holiday Tech Gifts",
          "body": "...",
          "topics": ["smart home", "wearables", ...]
        },
        "items": [
          { "title": "Futuristic Home Products", "slug": "futuristic-home-products" },
          ...
        ]
      }
    ]
  }

GET /api/v1/meta-trends/:slug
→ {
    "title": "Holiday Tech Gifts",
    "body_html": "...",
    "topics": [...],
    "charts": [...]
  }
```
