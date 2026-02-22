import type { App } from 'vue'

export default async (app: App) => {
  // Only enable MSW in development mode
  if (import.meta.env.DEV) {
    try {
      const setupMsw = (await import('./fake-api')).default
      setupMsw()
    }
    catch (error) {
      console.error('Failed to setup MSW:', error)
    }
  }
}
