import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/vite'
import { resolve } from 'path'

const isDash = process.env.DASH === '1'

export default defineConfig({
  base: isDash ? './' : '/orbit/',
  plugins: [react(), tailwindcss()],
  resolve: {
    alias: {
      '@': resolve(__dirname, './src'),
    },
  },
  ...(isDash
    ? {
        build: {
          outDir: 'dist/dash',
          emptyOutDir: true,
          cssCodeSplit: false,
          rollupOptions: {
            input: resolve(__dirname, 'src/standalone.tsx'),
            output: {
              entryFileNames: 'dashboard.js',
              chunkFileNames: 'chunks/[name]-[hash].js',
              assetFileNames: (assetInfo) => {
                if (assetInfo.name && assetInfo.name.endsWith('.css')) return 'assets/dashboard[extname]'
                return 'assets/[name]-[hash][extname]'
              },
            },
          },
        },
      }
    : {}),
})
