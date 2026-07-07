# Trend Analysis

**URL:** click "Trend Analysis" nav item  
**Screenshots:** `trend-analysis/empty-state.png` (empty state with search), `trend-analysis/empty.png` (LOADING state), `trend-analysis/topic-AI-agents.png` (dashboard redirected — search didn't navigate)

## Layout

Centered content area. Clean, minimal UI — a single search bar with geo selector.

## Empty State

```
Trend Analysis
Get in-depth trend data on any topic

[ 🔍 Search for any topic          ] [ 🌐 Worldwide ▼ ]

100/100 Analyses remaining   Refreshes in 26 days
```

### Search Bar
- Full-width text input with magnifier icon
- Placeholder: `Search for any topic`
- Debounced autocomplete (inferred)

### Geo Selector
- Dropdown showing globe icon + "Worldwide"
- Options: Worldwide + individual countries (standard Google Trends geo set)

### Usage Meter
- `100/100 Analyses remaining` — quota counter
- `Refreshes in 26 days` — monthly reset
- Plan limits:  Entrepreneur: 10/mo · Investor: 100/mo · Business: 500/mo

## Loaded State (inferred from screenshots + competitor analysis)

After submitting a topic, the page shows:

### Header
- Topic name (large)
- Status badge (Exploding / Regular / Peaked / Dormant)
- Volume + Growth summary stats
- Timeframe selector tabs: `1M · 3M · 6M · 1Y · 2Y · 5Y · All Time`
- Geo dropdown

### Interest Over Time Chart
- Line chart (Google Trends-style, 0–100 normalized scale OR actual search volume)
- X-axis: time ticks
- Y-axis: volume
- Hover tooltip: date + value
- Period comparison: previous period delta shown

### Related Topics / Queries
- Two columns: **Rising** and **Top**
- Each entry: topic name + growth % or index value

### Regions Breakdown
- Choropleth map (interest by country/state)
- Below: ranked list of regions with interest index

### Channel Breakdown (Business plan)
- Bar chart: search volume split by platform (Google, Amazon, TikTok, etc.)

## Interactions

| Trigger | Behaviour |
|---------|-----------|
| Type in search → Enter / click suggestion | Loads trend analysis for topic |
| **Worldwide** dropdown | Re-fetches data for selected geo |
| Timeframe tab (1M/3M/etc.) | Updates chart + stats for period |
| Hover chart | Shows tooltip with date + volume |
| Click related topic | Opens analysis for that topic |
| Click region on map | Drills down to sub-regions |

## API Shape (inferred)

```
GET /api/v1/analysis?topic=breathwork&geo=&timeframe=5y
→ {
    "topic": "breathwork",
    "status": "exploding",          // exploding | regular | peaked | dormant
    "volume": 45000,
    "growth_pct": 340,
    "points": [
      { "date": "2020-01", "value": 12 },
      ...
    ],
    "related_rising": [
      { "name": "box breathing", "growth_pct": 920 }
    ],
    "related_top": [
      { "name": "breathing exercises", "index": 100 }
    ],
    "regions": [
      { "geo": "US", "value": 100 },
      { "geo": "GB", "value": 72 }
    ]
  }
```
