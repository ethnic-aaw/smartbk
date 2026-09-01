import {
  LayoutDashboard,
  Users,
  GraduationCap,
  AlertTriangle,
  BookOpen,
  ClipboardList,
  CalendarClock,
  Settings,
  HelpCircle,
  UserCog,
  School,
} from 'lucide-react'
import type { NavSection } from '@/types'

export const navigation: NavSection[] = [
  {
    title: 'Menu Utama',
    items: [
      { label: 'Dashboard', icon: LayoutDashboard, href: '/dashboard' },
    ],
  },
  {
    title: 'Data Master',
    items: [
      {
        label: 'Siswa',
        icon: GraduationCap,
        children: [
          { label: 'Daftar Siswa', href: '/siswa' },
          { label: 'Tambah Siswa', href: '/siswa/tambah' },
          { label: 'Import Siswa', href: '/siswa/import' },
        ],
      },
      {
        label: 'Kelas',
        icon: School,
        children: [
          { label: 'Daftar Kelas', href: '/kelas' },
          { label: 'Generate Tahun Ajaran', href: '/kelas/generate' },
        ],
      },
      {
        label: 'User',
        icon: UserCog,
        children: [
          { label: 'Daftar User', href: '/user' },
          { label: 'Approval', href: '/user/approval' },
        ],
      },
    ],
  },
  {
    title: 'Aktivitas',
    items: [
      {
        label: 'Pelanggaran',
        icon: AlertTriangle,
        children: [
          { label: 'Riwayat Pelanggaran', href: '/pelanggaran/riwayat' },
          { label: 'Catat Pelanggaran', href: '/pelanggaran/tambah' },
          { label: 'Master Pelanggaran', href: '/pelanggaran/master' },
        ],
      },
      {
        label: 'Konsultasi',
        icon: BookOpen,
        children: [
          { label: 'Daftar Konsultasi', href: '/konsultasi' },
          { label: 'Konsultasi Baru', href: '/konsultasi/tambah' },
        ],
      },
      {
        label: 'Buku Tamu',
        icon: ClipboardList,
        href: '/buku_tamu',
      },
    ],
  },
  {
    title: 'Sistem',
    items: [
      { label: 'Pengaturan', icon: Settings, href: '/settings' },
      { label: 'Bantuan', icon: HelpCircle, href: '/help' },
    ],
  },
]
