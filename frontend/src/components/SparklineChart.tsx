import ReactApexChart from 'react-apexcharts'
import { ApexOptions } from 'apexcharts'

interface Props {
  data: number[]
  color?: string
}

const options: ApexOptions = {
  chart: {
    type: 'area',
    sparkline: { enabled: true },
    animations: { enabled: false },
  },
  stroke: { curve: 'smooth', width: 2 },
  fill: {
    type: 'gradient',
    gradient: {
      shadeIntensity: 1,
      opacityFrom: 0.3,
      opacityTo: 0.02,
    },
  },
  tooltip: { enabled: false },
  xaxis: { crosshairs: { show: false } },
  yaxis: { min: 0 },
}

export default function SparklineChart({ data, color = '#4F46E5' }: Props) {
  const series = [{ data }]
  const opts: ApexOptions = { ...options, colors: [color] }

  return (
    <ReactApexChart
      type="area"
      series={series}
      options={opts}
      width="100%"
      height={60}
    />
  )
}
