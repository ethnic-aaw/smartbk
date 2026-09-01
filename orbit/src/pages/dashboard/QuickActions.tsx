import { motion } from 'framer-motion'
import { UserPlus, AlertTriangle, BookOpen, CalendarX, Users, ClipboardList } from 'lucide-react'
import { cn } from '@/utils/cn'

const actions = [
  {
    label: 'Tambah Siswa',
    href: '/siswa/tambah',
    icon: UserPlus,
    color: 'from-blue-500 to-blue-600',
    bg: 'bg-blue-500/10',
    text: 'text-blue-400',
  },
  {
    label: 'Catat Pelanggaran',
    href: '/pelanggaran/tambah',
    icon: AlertTriangle,
    color: 'from-amber-500 to-orange-500',
    bg: 'bg-amber-500/10',
    text: 'text-amber-400',
  },
  {
    label: 'Konsultasi Baru',
    href: '/konsultasi/tambah',
    icon: BookOpen,
    color: 'from-emerald-500 to-teal-500',
    bg: 'bg-emerald-500/10',
    text: 'text-emerald-400',
  },
  {
    label: 'Buku Tamu',
    href: '/buku_tamu/tambah',
    icon: ClipboardList,
    color: 'from-violet-500 to-purple-500',
    bg: 'bg-violet-500/10',
    text: 'text-violet-400',
  },
  {
    label: 'Riwayat Pelanggaran',
    href: '/pelanggaran/riwayat',
    icon: CalendarX,
    color: 'from-rose-500 to-pink-500',
    bg: 'bg-rose-500/10',
    text: 'text-rose-400',
  },
  {
    label: 'Master Siswa',
    href: '/siswa',
    icon: Users,
    color: 'from-cyan-500 to-sky-500',
    bg: 'bg-cyan-500/10',
    text: 'text-cyan-400',
  },
]

export function QuickActions() {
  const base = import.meta.env.BASE_URL || '/'

  return (
    <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
      {actions.map((action, i) => (
        <motion.a
          key={action.label}
          href={`${base.replace(/\/$/, '')}${action.href}`}
          initial={{ opacity: 0, y: 12 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.3, delay: 0.1 + i * 0.05 }}
          className={cn(
            'group flex flex-col items-center gap-2.5 p-4 rounded-xl border border-orbit-border',
            'bg-orbit-surface hover:border-orbit-border2 transition-all duration-200',
            'hover:shadow-md hover:-translate-y-0.5'
          )}
        >
          <div className={cn(
            'w-10 h-10 rounded-xl flex items-center justify-center transition-transform duration-200',
            action.bg,
            'group-hover:scale-110'
          )}>
            <action.icon className={cn('w-5 h-5', action.text)} />
          </div>
          <span className="text-xs font-medium text-center text-slate-400 group-hover:text-slate-200 transition-colors leading-tight">
            {action.label}
          </span>
        </motion.a>
      ))}
    </div>
  )
}
