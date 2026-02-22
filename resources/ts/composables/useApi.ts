import { createFetch } from '@vueuse/core'
import { destr } from 'destr'

let csrfToken: string | null = null

export const initializeCsrfToken = async () => {
  if (csrfToken)
    return
  try {
    await fetch('/sanctum/csrf-cookie', {
      credentials: 'include',
    })
    // Extract CSRF token from cookie
    const cookie = document.cookie
      .split('; ')
      .find(row => row.startsWith('XSRF-TOKEN='))
    if (cookie) {
      csrfToken = decodeURIComponent(cookie.split('=')[1])
    }
  }
  catch (error) {
    console.error('Failed to initialize CSRF token:', error)
  }
}

export const useApi = createFetch({
  baseUrl: import.meta.env.VITE_API_BASE_URL || '/api',
  fetchOptions: {
    headers: {
      Accept: 'application/json',
    },
    credentials: 'include',
  },
  options: {
    refetch: true,
    async beforeFetch({ options }) {
      const accessToken = useCookie('accessToken').value

      if (accessToken) {
        options.headers = {
          ...options.headers,
          Authorization: `Bearer ${accessToken}`,
        }
      }
      else {
        // For unauthenticated requests, ensure CSRF token is initialized
        await initializeCsrfToken()
        if (csrfToken) {
          options.headers = {
            ...options.headers,
            'X-CSRF-TOKEN': csrfToken,
          }
        }
      }

      return { options }
    },
    afterFetch(ctx) {
      const { data, response } = ctx

      // Parse data if it's JSON

      let parsedData = null
      try {
        parsedData = destr(data)
      }
      catch (error) {
        console.error(error)
      }

      return { data: parsedData, response }
    },
  },
})
