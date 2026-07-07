import ReactApexChart from 'react-apexcharts'
import type { ApexOptions } from 'apexcharts'
import type { TrendPoint } from '../api/analysis'

interface Props {
  points: TrendPoint[]
  period: string
}

function periodToDateFormat(period: string): string {
  if (period === '1m' || period === '3m') return 'dd MMM'
  if (period === '12m') return 'MMM yyyy'
  return 'yyyy'
}

export default function TrendChart({ points, period }: Props) {
  const series = [
    {
      name: 'Interest',
      data: points.map((p) => [p.date, p.value]),
    },
  ]

  const options: ApexOptions = {
    chart: {
      type: 'area',
      toolbar: { show: false },
      zoom: { enabled: false },
      fontFamily: 'Manrope, sans-serif',
      background: 'transparent',
      animations: { enabled: true, speed: 400 },
    },
    stroke: {
      curve: 'smooth',
      width: 2,
      colors: ['#4F46E5'],
    },
    fill: {
      type: 'gradient',
      gradient: {
        shadeIntensity: 1,
        opacityFrom: 0.18,
        opacityTo: 0.01,
        stops: [0, 100],
        colorStops: [
          { offset: 0, color: '#4F46E5', opacity: 0.18 },
          { offset: 100, color: '#4F46E5', opacity: 0.01 },
        ],
      },
    },
    colors: ['#4F46E5'],
    dataLabels: { enabled: false },
    markers: {
      size: 0,
      hover: { size: 5, sizeOffset: 2 },
    },
    tooltip: {
      x: { format: periodToDateFormat(period) },
      y: {
        formatter: (val: number) => `${Math.round(val)} / 100`,
        title: { formatter: () => 'Interest' },
      },
      theme: 'light',
    },
    xaxis: {
      type: 'datetime',
      axisBorder: { show: false },
      axisTicks: { show: false },
      labels: {
        style: { colors: '#94A3B8', fontSize: '12px', fontFamily: 'Manrope, sans-serif' },
        datetimeUTC: false,
      },
      crosshairs: {
        stroke: { color: '#CBD5E1', dashArray: 4, width: 1 },
      },
    },
    yaxis: {
      min: 0,
      max: 100,
      tickAmount: 4,
      labels: {
        style: { colors: '#94A3B8', fontSize: '12px', fontFamily: 'Manrope, sans-serif' },
        formatter: (val: number) => String(Math.round(val)),
      },
    },
    grid: {
      borderColor: '#F1F5F9',
      strokeDashArray: 4,
      xaxis: { lines: { show: false } },
      yaxis: { lines: { show: true } },
      padding: { left: 0, right: 8 },
    },
    legend: { show: false },
  }

  if (points.length === 0) {
    return (
      <div className="flex items-center justify-center h-56 text-slate-400 text-sm">
        No data available for this period
      </div>
    )
  }

  return (
    <div className="-mx-2">
      <ReactApexChart
        type="area"
        series={series}
        options={options}
        height={240}
      />
    </div>
  )
}
