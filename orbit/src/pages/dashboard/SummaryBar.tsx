import { motion } from 'framer-motion'
import { TrendingUp, TrendingDown, Minus } from 'lucide-react'
import { cn } from '@/utils/cn'

interface SummaryItem {
  label: string
  value: number
  prevValue?: number
  suffix?: string
  color: string
}

interface SummaryBarProps {
  items: SummaryItem[]
}

export function SummaryBar({ items }: SummaryBarProps) {
  return (
    <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
      {items.map((item, i) => {
        const diff = item.prevValue !== undefined ? item.value - item.prevValue : 0
        const pct = item.prevValue && item.prevValue > 0
          ? Math.round(((item.value - item.prevValue) / item.prevValue) * 100)
          : 0
        const trend = diff > 0 ? 'up' : diff < 0 ? 'down' : 'neutral'

        return (
          <motion.div
            key={item.label}
            initial={{ opacity: 0, scale: 0.95 }}
            animate={{ opacity: 1, scale: 1 }}
            transition={{ duration: 0.3, delay: 0.05 + i * 0.06 }}
            className="bg-orbit-surface border border-orbit-border rounded-xl p-4 hover:border-orbit-border2 transition-all duration-200"
          >
            <div className="flex items-center justify-between mb-2">
              <span className="text-xs font-medium text-slate-500">{item.label}</span>
              {item.prevValue !== undefined && (
                <div className={cn(
                  'flex items-center gap-0.5 text-[11px] font-medium px-1.5 py-0.5 rounded-full',
                  trend === 'up' && 'bg-red-500/10 text-red-400',
                  trend === 'down' && 'bg-emerald-500/10 text-emerald-400',
                  trend === 'neutral' && 'bg-slate-500/10 text-slate-400',
                )}>
                  {trend === 'up' && <TrendingUp className="w-3 h-3" />}
                  {trend === 'down' && <TrendingDown className="w-3 h-3" />}
                  {trend === 'neutral' && <Minus className="w-3 h-3" />}
                  {Math.abs(pct)}%
                </div>
              )}
            </div>
            <div className="flex items-baseline gap-1">
              <span className="text-2xl font-bold" style={{ color: item.color }}>
                {item.value.toLocaleString('id-ID')}
              </span>
              {item.suffix && (
                <span className="text-xs text-slate-500">{item.suffix}</span>
              )}
            </div>
          </motion.div>
        )
      })}
    </div>
  )
}
