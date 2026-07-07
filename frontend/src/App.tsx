import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom'
import Layout from './components/Layout'
import DashboardPage from './pages/DashboardPage'
import TrendAnalysisPage from './pages/TrendAnalysisPage'
import TrendsDatabasePage from './pages/TrendsDatabasePage'
import TrendTrackingPage from './pages/TrendTrackingPage'

export default function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/" element={<Layout />}>
          <Route index element={<Navigate to="/dashboard" replace />} />
          <Route path="dashboard" element={<DashboardPage />} />
          <Route path="analysis" element={<TrendAnalysisPage />} />
          <Route path="database" element={<TrendsDatabasePage />} />
          <Route path="tracking" element={<TrendTrackingPage />} />
        </Route>
      </Routes>
    </BrowserRouter>
  )
}
