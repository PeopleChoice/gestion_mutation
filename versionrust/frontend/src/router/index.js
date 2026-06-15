import { createRouter, createWebHistory } from 'vue-router'
import { useAuth } from '../stores/auth'
import api from '../api'

const routes = [
  { path: '/setup', name: 'setup', component: () => import('../views/Setup.vue'), meta: { public: true } },
  { path: '/login', name: 'login', component: () => import('../views/Login.vue'), meta: { public: true } },
  { path: '/verification/:hash', name: 'verification', component: () => import('../views/Verification.vue'), meta: { public: true } },

  { path: '/', name: 'dashboard', component: () => import('../views/Dashboard.vue') },
  { path: '/communes', name: 'communes', component: () => import('../views/Communes.vue') },
  { path: '/projets', name: 'projets', component: () => import('../views/Projets.vue') },
  { path: '/projets/:id', name: 'projet-detail', component: () => import('../views/ProjetDetail.vue') },
  { path: '/parcelles', name: 'parcelles', component: () => import('../views/Parcelles.vue') },
  { path: '/mutations', name: 'mutations', component: () => import('../views/Mutations.vue') },
  { path: '/recherche', name: 'recherche', component: () => import('../views/Recherche.vue') },

  { path: '/:pathMatch(.*)*', name: 'notfound', component: () => import('../views/NotFound.vue'), meta: { public: true } },
]

const router = createRouter({ history: createWebHistory(), routes })

// Garde globale : configuration -> auth.
let installedChecked = false
let installed = false

router.beforeEach(async (to) => {
  // Vérifie une fois si l'app est configurée.
  if (!installedChecked) {
    try {
      const { data } = await api.get('/setup/status')
      installed = data.installed
    } catch (_) { installed = false }
    installedChecked = true
  }

  if (!installed && to.name !== 'setup') return { name: 'setup' }
  if (installed && to.name === 'setup') return { name: 'login' }

  const auth = useAuth()
  if (!to.meta.public && !auth.isAuthenticated) return { name: 'login' }
  if (to.name === 'login' && auth.isAuthenticated) return { name: 'dashboard' }
  return true
})

export default router
