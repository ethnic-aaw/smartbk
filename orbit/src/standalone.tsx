import { useState, useEffect } from 'react'
import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import './index.css'
import { DashboardPage } from './pages/dashboard/DashboardPage'
import type { Theme } from './types'

function useObTheme(ref: React.RefObject<HTMLDivElement | null>) {
  const [theme, setTheme] = useState<Theme>(() => {
    const stored = localStorage.getItem('orbit-theme')
    return (stored as Theme) ?? 'light'
  })

  useEffect(() => {
    const el = ref.current
    if (!el) return
    if (theme === 'dark') {
      el.classList.add('ob-dark')
      el.classList.remove('ob-light')
    } else {
      el.classList.add('ob-light')
      el.classList.remove('ob-dark')
    }
    localStorage.setItem('orbit-theme', theme)
  }, [theme, ref])

  return { theme, toggle: () => setTheme(t => (t === 'dark' ? 'light' : 'dark')) }
}

function App() {
  const [wrapper, setWrapper] = useState<HTMLDivElement | null>(null)
  useObTheme({ current: wrapper })

  return (
    <div className="ob-dash" ref={setWrapper}>
      <DashboardPage />
    </div>
  )
}

createRoot(document.getElementById('react-dashboard')!).render(
  <StrictMode>
    <App />
  </StrictMode>,
)
