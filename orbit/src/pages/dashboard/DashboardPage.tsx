import { useState, useEffect, useCallback } from 'react'
import {
  AreaChart, Area, PieChart, Pie, Cell,
  XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, Legend,
} from 'recharts'
import { motion, AnimatePresence } from 'framer-motion'
import { Users, BookOpen, AlertTriangle, CalendarX, RefreshCw, TrendingUp, Clock } from 'lucide-react'
import { Card, CardHeader, CardBody, StatCard } from '@/components/ui'
import { cn } from '@/utils/cn'
import { QuickActions } from './QuickActions'
import { ActivityFeed } from './ActivityFeed'
import { SummaryBar } from './SummaryBar'
import type { StatCardData } from '@/types'

const periods = ['7D', '30D', '90D', '1Y'] as const
type Period = typeof periods[number]

const KAT_COLORS: Record<string, string> = {
  'Kedisiplinan': '#2563EB',
  'Tata Krama': '#10B981',
  'Kekerasan': '#EF4444',
  'Narkoba': '#F59E0B',
  'Lainnya': '#8B5CF6',
}

interface AnalyticsData {
  totalSiswa: number
  totalKelas: number
  siswaBermasalah: number
  pelanggaranHariIni: number
  kategoriDominan: { name: string; value: number; count: number; color: string }[]
  trenPelanggaran: { date: string; jumlah: number }[]
  trenPerKategori: Record<string, string | number>[]
  topSiswa: { id: number; nama: string; nipd: string; nama_kelas: string; total_poin: number }[]
  sparkPelanggaran: { value: number }[]
  tahunAjaran: string
  period: string
}

function TrendTooltip({ active, payload, label }: any) {
  if (!active || !payload?.length) return null
  return (
    <div className="bg-orbit-surface border border-orbit-border rounded-xl p-3 shadow-xl">
      <p className="text-xs text-slate-500 mb-2 font-medium">{label}</p>
      {payload.map((p: any) => (
        <div key={p.dataKey} className="flex items-center gap-2 text-sm">
          <div className="w-2.5 h-2.5 rounded-full" style={{ background: p.color }} />
          <span className="text-slate-400">{p.dataKey}</span>
          <span className="text-slate-200 font-semibold ml-auto">{p.value}</span>
        </div>
      ))}
    </div>
  )
}

function PieTooltip({ active, payload }: any) {
  if (!active || !payload?.length) return null
  const data = payload[0]
  return (
    <div className="bg-orbit-surface border border-orbit-border rounded-xl p-3 shadow-xl">
      <div className="flex items-center gap-2 text-sm">
        <div className="w-2.5 h-2.5 rounded-full" style={{ background: data.payload.color }} />
        <span className="text-slate-300 font-medium">{data.name}</span>
      </div>
      <p className="text-xs text-slate-500 mt-1">{data.value}% • {data.payload.count} kasus</p>
    </div>
  )
}

function formatDateAxis(dateStr: string, period: Period) {
  const d = new Date(dateStr)
  if (period === '7D') return d.toLocaleDateString('id-ID', { weekday: 'short' })
  if (period === '30D') return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })
  if (period === '90D') return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })
  return d.toLocaleDateString('id-ID', { month: 'short', year: '2-digit' })
}

export function DashboardPage() {
  const [period, setPeriod] = useState<Period>('1Y')
  const [data, setData] = useState<AnalyticsData | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [lastUpdated, setLastUpdated] = useState<Date | null>(null)
  const [refreshKey, setRefreshKey] = useState(0)
  const [autoRefresh, setAutoRefresh] = useState(true)

  const fetchData = useCallback(() => {
    setLoading(true)
    const base = (window as any).APP_BASE ?? ''
    fetch(`${base}/api/dashboard/analytics.php?period=${period}`, { credentials: 'same-origin' })
      .then(r => { if (!r.ok) throw new Error(r.statusText); return r.json() })
      .then(d => {
        if (d.error) throw new Error(d.error)
        setData(d)
        setError(null)
        setLastUpdated(new Date())
      })
      .catch(e => setError(e.message))
      .finally(() => setLoading(false))
  }, [period])

  useEffect(() => {
    fetchData()
  }, [fetchData])

  // Auto-refresh every 60 seconds — paused when tab hidden
  useEffect(() => {
    if (!autoRefresh) return
    const interval = setInterval(() => {
      if (document.visibilityState !== 'visible') return
      fetchData()
      setRefreshKey(k => k + 1)
    }, 60000)
    return () => clearInterval(interval)
  }, [autoRefresh, fetchData])

  const handleManualRefresh = () => {
    fetchData()
    setRefreshKey(k => k + 1)
  }

  const stats: StatCardData[] = data ? [
    {
      id: 'total-siswa',
      label: 'Total Siswa Aktif',
      value: data.totalSiswa.toLocaleString('id-ID'),
      change: 0,
      changeLabel: `Tahun ${data.tahunAjaran}`,
      icon: Users,
      color: 'primary',
      sparkData: data.sparkPelanggaran,
    },
    {
      id: 'total-kelas',
      label: 'Total Kelas',
      value: data.totalKelas.toLocaleString('id-ID'),
      change: 0,
      changeLabel: 'Rombongan belajar',
      icon: BookOpen,
      color: 'accent',
      sparkData: data.sparkPelanggaran,
    },
    {
      id: 'siswa-bermasalah',
      label: 'Siswa Bermasalah',
      value: data.siswaBermasalah.toLocaleString('id-ID'),
      change: data.siswaBermasalah,
      changeLabel: 'Poin > 75 (kritis)',
      icon: AlertTriangle,
      color: 'warning',
      sparkData: data.sparkPelanggaran,
    },
    {
      id: 'pelanggaran-hari',
      label: 'Pelanggaran Hari Ini',
      value: data.pelanggaranHariIni.toLocaleString('id-ID'),
      change: data.pelanggaranHariIni,
      changeLabel: 'Dicatat hari ini',
      icon: CalendarX,
      color: 'success',
      sparkData: data.sparkPelanggaran,
    },
  ] : []

  const trendData = data?.trenPerKategori.map(row => {
    const r = row as Record<string, string | number>
    return {
      date: r.date as string,
      label: formatDateAxis(r.date as string, period),
      Kedisiplinan: (r['Kedisiplinan'] as number) || 0,
      'Tata Krama': (r['Tata Krama'] as number) || 0,
      Kekerasan: (r['Kekerasan'] as number) || 0,
      Narkoba: (r['Narkoba'] as number) || 0,
      Lainnya: (r['Lainnya'] as number) || 0,
    }
  }) ?? []

  const now = new Date()
  const greeting = now.getHours() < 12 ? 'Selamat pagi' : now.getHours() < 17 ? 'Selamat siang' : 'Selamat malam'
  const dateStr = now.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })

  // Summary items for the horizontal bar
  const summaryItems = data ? [
    { label: 'Total Pelanggaran', value: data.trenPelanggaran.reduce((s, r) => s + r.jumlah, 0), color: '#EF4444' },
    { label: 'Kategori Teratas', value: data.kategoriDominan[0]?.count || 0, suffix: data.kategoriDominan[0]?.name, color: '#2563EB' },
    { label: 'Rata-rata Poin', value: data.topSiswa.length > 0 ? Math.round(data.topSiswa.reduce((s, r) => s + r.total_poin, 0) / data.topSiswa.length) : 0, color: '#F59E0B' },
    { label: 'Siswa Tercatat', value: data.topSiswa.length, color: '#10B981' },
  ] : []

  return (
    <div className="flex-1 overflow-y-auto p-6 space-y-5 max-w-[1600px]">
      {/* Header with refresh */}
      <motion.div
        initial={{ opacity: 0, y: -8 }}
        animate={{ opacity: 1, y: 0 }}
        className="flex items-start justify-between"
      >
        <div>
          <h1 className="text-2xl font-bold text-slate-100">{greeting} 👋</h1>
          <p className="text-slate-500 text-sm mt-1">{dateStr}</p>
          {lastUpdated && (
            <div className="flex items-center gap-1.5 mt-2">
              <div className="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse" />
              <span className="text-[11px] text-slate-600">
                Diperbarui {lastUpdated.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })}
              </span>
            </div>
          )}
        </div>
        <div className="flex items-center gap-2">
          {/* Auto-refresh toggle */}
          <button
            onClick={() => setAutoRefresh(a => !a)}
            className={cn(
              'flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition-all',
              autoRefresh
                ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20'
                : 'bg-orbit-surface2 text-slate-500 border border-orbit-border'
            )}
          >
            <div className={cn('w-1.5 h-1.5 rounded-full', autoRefresh ? 'bg-emerald-400 animate-pulse' : 'bg-slate-500')} />
            Auto
          </button>
          {/* Manual refresh */}
          <button
            onClick={handleManualRefresh}
            disabled={loading}
            className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-orbit-surface2 text-slate-400 border border-orbit-border hover:border-orbit-border2 hover:text-slate-200 transition-all disabled:opacity-50"
          >
            <RefreshCw className={cn('w-3.5 h-3.5', loading && 'animate-spin')} />
            Refresh
          </button>
          {data && (
            <div className="text-right ml-2">
              <p className="text-[11px] text-slate-600">Tahun Ajaran</p>
              <p className="text-sm font-semibold text-slate-300">{data.tahunAjaran}</p>
            </div>
          )}
        </div>
      </motion.div>

      {/* Loading state */}
      {loading && !data && (
        <div className="flex items-center justify-center py-20">
          <div className="flex flex-col items-center gap-3">
            <div className="w-8 h-8 border-2 border-orbit-primary border-t-transparent rounded-full animate-spin" />
            <p className="text-sm text-slate-500">Memuat data dashboard...</p>
          </div>
        </div>
      )}

      {/* Error state */}
      {error && (
        <motion.div
          initial={{ opacity: 0, y: -8 }}
          animate={{ opacity: 1, y: 0 }}
          className="bg-red-500/10 border border-red-500/20 rounded-xl p-4 flex items-center justify-between"
        >
          <div className="flex items-center gap-2 text-red-400 text-sm">
            <AlertTriangle className="w-4 h-4" />
            Gagal memuat data: {error}
          </div>
          <button
            onClick={handleManualRefresh}
            className="text-xs text-red-400 hover:text-red-300 font-medium"
          >
            Coba lagi
          </button>
        </motion.div>
      )}

      {data && !loading && (
        <>
          {/* KPI Stats Row */}
          <div className="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            {stats.map((stat, i) => (
              <StatCard key={stat.id} data={stat} index={i} />
            ))}
          </div>

          {/* Quick Actions */}
          <motion.div
            initial={{ opacity: 0, y: 8 }}
            animate={{ opacity: 1, y: 0 }}
            transition={{ delay: 0.3 }}
          >
            <QuickActions />
          </motion.div>

          {/* Summary Bar */}
          {summaryItems.length > 0 && (
            <SummaryBar items={summaryItems} />
          )}

          {/* Main charts row + Activity Feed */}
          <div className="grid grid-cols-1 xl:grid-cols-3 gap-4">
            {/* Tren Pelanggaran — takes 2/3 */}
            <Card className="xl:col-span-2">
              <CardHeader
                title="Tren Pelanggaran"
                subtitle="Jumlah pelanggaran per kategori"
                actions={
                  <div className="flex items-center gap-1 bg-orbit-surface2 rounded-lg p-1">
                    {periods.map(p => (
                      <button
                        key={p}
                        onClick={() => setPeriod(p)}
                        className={cn(
                          'px-2.5 py-1 rounded-md text-xs font-medium transition-all',
                          period === p
                            ? 'bg-orbit-primary text-white shadow-sm'
                            : 'text-slate-500 hover:text-slate-300'
                        )}
                      >
                        {p}
                      </button>
                    ))}
                  </div>
                }
              />
              <CardBody className="pt-2">
                <ResponsiveContainer width="100%" height={280}>
                  <AreaChart data={trendData} margin={{ top: 5, right: 5, bottom: 0, left: -20 }}>
                    <defs>
                      <linearGradient id="grad-kedisiplinan" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stopColor="#2563EB" stopOpacity={0.35} />
                        <stop offset="100%" stopColor="#2563EB" stopOpacity={0} />
                      </linearGradient>
                      <linearGradient id="grad-tatakrama" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stopColor="#10B981" stopOpacity={0.30} />
                        <stop offset="100%" stopColor="#10B981" stopOpacity={0} />
                      </linearGradient>
                      <linearGradient id="grad-kekerasan" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stopColor="#EF4444" stopOpacity={0.30} />
                        <stop offset="100%" stopColor="#EF4444" stopOpacity={0} />
                      </linearGradient>
                      <linearGradient id="grad-narkoba" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stopColor="#F59E0B" stopOpacity={0.30} />
                        <stop offset="100%" stopColor="#F59E0B" stopOpacity={0} />
                      </linearGradient>
                      <linearGradient id="grad-lainnya" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stopColor="#8B5CF6" stopOpacity={0.30} />
                        <stop offset="100%" stopColor="#8B5CF6" stopOpacity={0} />
                      </linearGradient>
                    </defs>
                    <CartesianGrid strokeDasharray="3 3" stroke="rgba(0,0,0,0.06)" />
                    <XAxis dataKey="label" tick={{ fontSize: 11, fill: '#6C757D' }} axisLine={false} tickLine={false} />
                    <YAxis tick={{ fontSize: 11, fill: '#6C757D' }} axisLine={false} tickLine={false} allowDecimals={false} />
                    <Tooltip content={<TrendTooltip />} />
                    <Legend iconType="circle" iconSize={8} wrapperStyle={{ fontSize: 11, paddingTop: 8 }} />
                    <Area type="monotone" dataKey="Kedisiplinan" stroke="#2563EB" strokeWidth={2.5} fill="url(#grad-kedisiplinan)" dot={false} activeType="circle" />
                    <Area type="monotone" dataKey="Tata Krama" stroke="#10B981" strokeWidth={2.5} fill="url(#grad-tatakrama)" dot={false} activeType="circle" />
                    <Area type="monotone" dataKey="Kekerasan" stroke="#EF4444" strokeWidth={2.5} fill="url(#grad-kekerasan)" dot={false} activeType="circle" />
                    <Area type="monotone" dataKey="Narkoba" stroke="#F59E0B" strokeWidth={2.5} fill="url(#grad-narkoba)" dot={false} activeType="circle" />
                    <Area type="monotone" dataKey="Lainnya" stroke="#8B5CF6" strokeWidth={2.5} fill="url(#grad-lainnya)" dot={false} activeType="circle" />
                  </AreaChart>
                </ResponsiveContainer>
              </CardBody>
            </Card>

            {/* Activity Feed — takes 1/3 */}
            <div className="xl:col-span-1">
              <ActivityFeed refreshKey={refreshKey} />
            </div>
          </div>

          {/* Kategori Dominan + Top Siswa */}
          <div className="grid grid-cols-1 xl:grid-cols-3 gap-4">
            {/* Kategori Dominan — takes 1/3 */}
            <Card>
              <CardHeader title="Kategori Dominan" subtitle="Distribusi pelanggaran" />
              <CardBody className="pt-2">
                <ResponsiveContainer width="100%" height={180}>
                  <PieChart>
                    <Pie
                      data={data.kategoriDominan}
                      cx="50%"
                      cy="50%"
                      innerRadius={55}
                      outerRadius={78}
                      paddingAngle={3}
                      dataKey="value"
                      strokeWidth={0}
                    >
                      {data.kategoriDominan.map((entry, i) => (
                        <Cell key={i} fill={entry.color} />
                      ))}
                    </Pie>
                    <Tooltip content={<PieTooltip />} />
                  </PieChart>
                </ResponsiveContainer>
                <div className="space-y-2.5 mt-2">
                  {data.kategoriDominan.map(item => (
                    <div key={item.name} className="flex items-center gap-3">
                      <div className="w-2.5 h-2.5 rounded-full flex-shrink-0" style={{ background: item.color }} />
                      <span className="text-xs text-slate-400 flex-1">{item.name}</span>
                      <div className="flex items-center gap-2">
                        <div className="w-20 h-1.5 bg-orbit-surface3 rounded-full overflow-hidden">
                          <motion.div
                            initial={{ width: 0 }}
                            animate={{ width: `${item.value}%` }}
                            transition={{ duration: 0.8, delay: 0.2 }}
                            className="h-full rounded-full"
                            style={{ background: item.color }}
                          />
                        </div>
                        <span className="text-xs text-slate-300 w-8 text-right">{item.value}%</span>
                      </div>
                    </div>
                  ))}
                </div>
              </CardBody>
            </Card>

            {/* Top 10 Siswa — takes 2/3 */}
            <Card className="xl:col-span-2">
              <CardHeader
                title="Top 10 Siswa Poin Tertinggi"
                subtitle={`${data.topSiswa.length} siswa tercatat`}
              />
              <CardBody className="p-0 pt-2">
                <div className="overflow-x-auto">
                  <table className="w-full">
                    <thead>
                      <tr className="border-b border-orbit-border">
                        {['Rank', 'Nama', 'NIPD', 'Kelas', 'Total Poin', 'Level'].map(col => (
                          <th key={col} className="text-left text-[11px] font-semibold text-slate-600 uppercase tracking-wider px-5 pb-3">
                            {col}
                          </th>
                        ))}
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-orbit-border">
                      {data.topSiswa.length === 0 ? (
                        <tr>
                          <td colSpan={6} className="px-5 py-8 text-center text-sm text-slate-500">
                            Tidak ada data pelanggaran
                          </td>
                        </tr>
                      ) : (
                        data.topSiswa.map((s, i) => {
                          const poin = s.total_poin
                          const maxPoin = Math.max(...data.topSiswa.map(x => x.total_poin), 100)
                          const progress = Math.min((poin / maxPoin) * 100, 100)

                          let level: string
                          let levelColor: string
                          let progressColor: string
                          if (poin > 75) {
                            level = 'Berat'
                            levelColor = 'bg-red-500/15 text-red-400'
                            progressColor = 'bg-red-500'
                          } else if (poin > 30) {
                            level = 'Sedang'
                            levelColor = 'bg-amber-500/15 text-amber-400'
                            progressColor = 'bg-amber-500'
                          } else {
                            level = 'Ringan'
                            levelColor = 'bg-emerald-500/15 text-emerald-400'
                            progressColor = 'bg-emerald-500'
                          }

                          const rankBg = i === 0 ? 'bg-amber-500/15 text-amber-400' : i === 1 ? 'bg-slate-400/15 text-slate-400' : i === 2 ? 'bg-orange-500/15 text-orange-400' : ''

                          return (
                            <motion.tr
                              key={s.id}
                              initial={{ opacity: 0, x: -8 }}
                              animate={{ opacity: 1, x: 0 }}
                              transition={{ duration: 0.2, delay: i * 0.03 }}
                              className="hover:bg-orbit-surface2/50 transition-colors"
                            >
                              <td className="px-5 py-3">
                                <span className={cn(
                                  'inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold',
                                  rankBg || 'text-slate-500'
                                )}>
                                  {i + 1}
                                </span>
                              </td>
                              <td className="px-5 py-3">
                                <p className="text-sm text-slate-200 font-medium">{s.nama}</p>
                              </td>
                              <td className="px-5 py-3 text-sm text-slate-400">{s.nipd || '-'}</td>
                              <td className="px-5 py-3 text-sm text-slate-400">{s.nama_kelas}</td>
                              <td className="px-5 py-3">
                                <div className="flex items-center gap-2">
                                  <div className="w-16 h-1.5 bg-orbit-surface3 rounded-full overflow-hidden">
                                    <motion.div
                                      initial={{ width: 0 }}
                                      animate={{ width: `${progress}%` }}
                                      transition={{ duration: 0.6, delay: 0.3 + i * 0.05 }}
                                      className={cn('h-full rounded-full', progressColor)}
                                    />
                                  </div>
                                  <span className={cn('text-sm font-semibold', poin > 75 ? 'text-red-400' : poin > 30 ? 'text-amber-400' : 'text-emerald-400')}>
                                    {poin}
                                  </span>
                                </div>
                              </td>
                              <td className="px-5 py-3">
                                <span className={cn('text-xs font-semibold px-2.5 py-1 rounded-full', levelColor)}>
                                  {level}
                                </span>
                              </td>
                            </motion.tr>
                          )
                        })
                      )}
                    </tbody>
                  </table>
                </div>
              </CardBody>
            </Card>
          </div>
        </>
      )}
    </div>
  )
}
