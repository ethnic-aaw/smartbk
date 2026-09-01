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

function ThemeToggle({ theme, toggle }: { theme: Theme; toggle: () => void }) {
  return (
    <button
      onClick={toggle}
      className="fixed bottom-4 right-4 z-50 w-10 h-10 rounded-full bg-orbit-surface border border-orbit-border shadow-lg flex items-center justify-center text-slate-400 hover:text-slate-200 hover:border-orbit-border2 transition-all"
      title={theme === 'dark' ? 'Mode terang' : 'Mode gelap'}
    >
      {theme === 'dark' ? (
        <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
          <circle cx="12" cy="12" r="5" />
          <path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" />
        </svg>
      ) : (
        <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
          <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
        </svg>
      )}
    </button>
  )
}

function App() {
  const [wrapper, setWrapper] = useState<HTMLDivElement | null>(null)
  const { theme, toggle } = useObTheme({ current: wrapper })

  return (
    <div className="ob-dash" ref={setWrapper}>
      <DashboardPage />
      <ThemeToggle theme={theme} toggle={toggle} />
    </div>
  )
}

createRoot(document.getElementById('react-dashboard')!).render(
  <StrictMode>
    <App />
  </StrictMode>,
)
