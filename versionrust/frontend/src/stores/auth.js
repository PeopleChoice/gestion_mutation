import { defineStore } from 'pinia'
import api from '../api'

export const useAuth = defineStore('auth', {
  state: () => ({
    token: localStorage.getItem('token') || null,
    user: JSON.parse(localStorage.getItem('user') || 'null'),
  }),
  getters: {
    isAuthenticated: (s) => !!s.token,
    roles: (s) => s.user?.roles || [],
    hasRole: (s) => (role) => (s.user?.roles || []).includes(role),
    hasAny: (s) => (list) => list.some((r) => (s.user?.roles || []).includes(r)),
  },
  actions: {
    async login(email, password) {
      const { data } = await api.post('/login', { email, password })
      this.token = data.token
      this.user = data.user
      localStorage.setItem('token', data.token)
      localStorage.setItem('user', JSON.stringify(data.user))
    },
    logout() {
      this.token = null
      this.user = null
      localStorage.removeItem('token')
      localStorage.removeItem('user')
    },
  },
})
