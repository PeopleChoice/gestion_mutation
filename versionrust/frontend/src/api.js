import axios from 'axios'

/** Client HTTP partagé. baseURL '/api' (proxifié en dev, même origine en prod). */
const api = axios.create({ baseURL: '/api' })

// Joint le jeton JWT à chaque requête.
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

// Gestion centralisée des erreurs d'auth / configuration.
let router = null
export function bindRouter(r) { router = r }

api.interceptors.response.use(
  (r) => r,
  (error) => {
    const status = error?.response?.status
    const code = error?.response?.data?.error
    if (status === 503 && code === 'not_configured') {
      if (router && router.currentRoute.value.name !== 'setup') router.push({ name: 'setup' })
    } else if (status === 401) {
      localStorage.removeItem('token')
      if (router && router.currentRoute.value.name !== 'login') router.push({ name: 'login' })
    }
    return Promise.reject(error)
  }
)

/** Extrait un message d'erreur lisible. */
export function errMessage(e) {
  return e?.response?.data?.message || e?.message || 'Erreur inconnue'
}

export default api
