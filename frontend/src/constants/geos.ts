export interface Geo {
  code: string
  name: string
}

export const GEO_LIST: Geo[] = [
  { code: '',   name: 'Worldwide' },
  { code: 'US', name: 'United States' },
  { code: 'GB', name: 'United Kingdom' },
  { code: 'CA', name: 'Canada' },
  { code: 'AU', name: 'Australia' },
  { code: 'DE', name: 'Germany' },
  { code: 'FR', name: 'France' },
  { code: 'IN', name: 'India' },
  { code: 'BR', name: 'Brazil' },
  { code: 'MX', name: 'Mexico' },
  { code: 'JP', name: 'Japan' },
  { code: 'KR', name: 'South Korea' },
  { code: 'SG', name: 'Singapore' },
  { code: 'NL', name: 'Netherlands' },
  { code: 'SE', name: 'Sweden' },
  { code: 'NO', name: 'Norway' },
  { code: 'ES', name: 'Spain' },
  { code: 'IT', name: 'Italy' },
  { code: 'PL', name: 'Poland' },
  { code: 'RU', name: 'Russia' },
  { code: 'UA', name: 'Ukraine' },
  { code: 'ZA', name: 'South Africa' },
  { code: 'NG', name: 'Nigeria' },
  { code: 'EG', name: 'Egypt' },
  { code: 'AR', name: 'Argentina' },
  { code: 'CL', name: 'Chile' },
  { code: 'CO', name: 'Colombia' },
  { code: 'ID', name: 'Indonesia' },
  { code: 'PH', name: 'Philippines' },
  { code: 'TH', name: 'Thailand' },
  { code: 'VN', name: 'Vietnam' },
  { code: 'NZ', name: 'New Zealand' },
  { code: 'CH', name: 'Switzerland' },
  { code: 'AT', name: 'Austria' },
  { code: 'BE', name: 'Belgium' },
  { code: 'PT', name: 'Portugal' },
  { code: 'CZ', name: 'Czech Republic' },
  { code: 'HU', name: 'Hungary' },
  { code: 'RO', name: 'Romania' },
  { code: 'TR', name: 'Turkey' },
  { code: 'IL', name: 'Israel' },
  { code: 'SA', name: 'Saudi Arabia' },
  { code: 'AE', name: 'United Arab Emirates' },
  { code: 'PK', name: 'Pakistan' },
  { code: 'BD', name: 'Bangladesh' },
  { code: 'MY', name: 'Malaysia' },
  { code: 'HK', name: 'Hong Kong' },
  { code: 'TW', name: 'Taiwan' },
  { code: 'GR', name: 'Greece' },
  { code: 'DK', name: 'Denmark' },
  { code: 'FI', name: 'Finland' },
  { code: 'IE', name: 'Ireland' },
]

export const PERIOD_OPTIONS = [
  { label: '1 Month',  value: '1m'  },
  { label: '3 Months', value: '3m'  },
  { label: '1 Year',   value: '12m' },
  { label: '5 Years',  value: '5y'  },
  { label: 'All Time', value: 'all' },
]

export const ENGINE_OPTIONS = [
  { label: 'Web',      value: 'web'      },
  { label: 'YouTube',  value: 'youtube'  },
  { label: 'Images',   value: 'images'   },
  { label: 'News',     value: 'news'     },
  { label: 'Shopping', value: 'shopping' },
]

// Гео без Worldwide — для режима Real-time (Trending Now не поддерживает worldwide)
export const GEO_LIST_COUNTRIES = GEO_LIST.filter((g) => g.code !== '')

// Гео для инжеста trending-данных (должен совпадать с DashboardService::INGEST_GEOS)
export const REALTIME_GEOS = GEO_LIST.filter((g) =>
  ['US', 'GB', 'AU', 'CA', 'IN', 'DE', 'FR'].includes(g.code),
)
