import { useState, useEffect } from 'react'
import { motion, AnimatePresence } from 'framer-motion'
import { AlertTriangle, MessageSquare, Clock } from 'lucide-react'
import { Card, CardHeader, CardBody, Badge } from '@/components/ui'
import { cn } from '@/utils/cn'

interface ActivityItem {
  id: number
  type: 'pelanggaran' | 'konsultasi'
  siswa_nama: string
  kelas_nama: string
  description: string
  tanggal: string
  kategori?: string
}

interface ActivityFeedProps {
  refreshKey?: number
}

export function ActivityFeed({ refreshKey }: ActivityFeedProps) {
  const [items, setItems] = useState<ActivityItem[]>([])
  const [loading, setLoading] = useState(true)
  const [filter, setFilter] = useState<'all' | 'pelanggaran' | 'konsultasi'>('all')

  useEffect(() => {
    setLoading(true)
    const base = (window as any).APP_BASE ?? ''
    // Fetch recent violations
    const violPromise = fetch(`${base}/api/pelanggaran/list.php?limit=5`, { credentials: 'same-origin' })
      .then(r => r.json())
      .then(d => {
        if (d.error) return []
        const rows = d.data?.data || d.data || []
        return (Array.isArray(rows) ? rows : []).slice(0, 5).map((r: any) => ({
          id: r.id,
          type: 'pelanggaran' as const,
          siswa_nama: r.siswa_nama || r.nama || '-',
          kelas_nama: r.nama_kelas || r.kelas || '-',
          description: r.jenis_nama || r.keterangan || 'Pelanggaran',
          tanggal: r.tanggal,
          kategori: r.kategori || undefined,
        }))
      })
      .catch(() => [])

    // Fetch recent consultations
    const consPromise = fetch(`${base}/api/konsultasi/list.php?limit=5`, { credentials: 'same-origin' })
      .then(r => r.json())
      .then(d => {
        if (d.error) return []
        const rows = d.data?.data || d.data || []
        return (Array.isArray(rows) ? rows : []).slice(0, 5).map((r: any) => ({
          id: r.id + 10000,
          type: 'konsultasi' as const,
          siswa_nama: r.siswa_nama || r.nama || '-',
          kelas_nama: r.nama_kelas || r.kelas || '-',
          description: r.permasalahan?.substring(0, 60) || 'Konsultasi',
          tanggal: r.tanggal,
        }))
      })
      .catch(() => [])

    Promise.all([violPromise, consPromise])
      .then(([violations, consultations]) => {
        const all = [...violations, ...consultations]
          .sort((a, b) => new Date(b.tanggal).getTime() - new Date(a.tanggal).getTime())
          .slice(0, 8)
        setItems(all)
      })
      .finally(() => setLoading(false))
  }, [refreshKey])

  const filtered = filter === 'all' ? items : items.filter(i => i.type === filter)

  function formatDate(dateStr: string) {
    const d = new Date(dateStr)
    const now = new Date()
    const diffMs = now.getTime() - d.getTime()
    const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24))

    if (diffDays === 0) return 'Hari ini'
    if (diffDays === 1) return 'Kemarin'
    if (diffDays < 7) return `${diffDays} hari lalu`
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })
  }

  return (
    <Card>
      <CardHeader
        title="Aktivitas Terbaru"
        subtitle="Pelanggaran & konsultasi terkini"
        actions={
          <div className="flex items-center gap-1 bg-orbit-surface2 rounded-lg p-1">
            {(['all', 'pelanggaran', 'konsultasi'] as const).map(f => (
              <button
                key={f}
                onClick={() => setFilter(f)}
                className={cn(
                  'px-2.5 py-1 rounded-md text-xs font-medium transition-all capitalize',
                  filter === f
                    ? 'bg-orbit-primary text-white shadow-sm'
                    : 'text-slate-500 hover:text-slate-300'
                )}
              >
                {f === 'all' ? 'Semua' : f}
              </button>
            ))}
          </div>
        }
      />
      <CardBody className="p-0 pt-2">
        {loading ? (
          <div className="flex items-center justify-center py-10">
            <div className="w-6 h-6 border-2 border-orbit-primary border-t-transparent rounded-full animate-spin" />
          </div>
        ) : filtered.length === 0 ? (
          <div className="px-5 py-10 text-center text-sm text-slate-500">
            Tidak ada aktivitas terbaru
          </div>
        ) : (
          <div className="divide-y divide-orbit-border">
            <AnimatePresence mode="popLayout">
              {filtered.map((item, i) => (
                <motion.div
                  key={item.id}
                  initial={{ opacity: 0, x: -8 }}
                  animate={{ opacity: 1, x: 0 }}
                  exit={{ opacity: 0, x: 8 }}
                  transition={{ duration: 0.2, delay: i * 0.03 }}
                  className="flex items-start gap-3 px-5 py-3.5 hover:bg-orbit-surface2/50 transition-colors"
                >
                  <div className={cn(
                    'w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5',
                    item.type === 'pelanggaran'
                      ? 'bg-amber-500/10'
                      : 'bg-emerald-500/10'
                  )}>
                    {item.type === 'pelanggaran' ? (
                      <AlertTriangle className="w-4 h-4 text-amber-400" />
                    ) : (
                      <MessageSquare className="w-4 h-4 text-emerald-400" />
                    )}
                  </div>
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2 mb-0.5">
                      <p className="text-sm font-medium text-slate-200 truncate">
                        {item.siswa_nama}
                      </p>
                      <span className="text-xs text-slate-600">•</span>
                      <span className="text-xs text-slate-500">{item.kelas_nama}</span>
                    </div>
                    <p className="text-xs text-slate-400 truncate">{item.description}</p>
                    {item.kategori && (
                      <Badge
                        variant={item.kategori === 'Kekerasan' ? 'danger' : item.kategori === 'Narkoba' ? 'warning' : 'info'}
                        className="mt-1"
                      >
                        {item.kategori}
                      </Badge>
                    )}
                  </div>
                  <div className="flex items-center gap-1 text-xs text-slate-500 flex-shrink-0">
                    <Clock className="w-3 h-3" />
                    {formatDate(item.tanggal)}
                  </div>
                </motion.div>
              ))}
            </AnimatePresence>
          </div>
        )}
      </CardBody>
    </Card>
  )
}
