<script setup>
import { useAuth } from '../stores/auth'
import { useRouter } from 'vue-router'

const auth = useAuth()
const router = useRouter()

const nav = [
  { name: 'dashboard', label: 'Tableau de bord', icon: '▦' },
  { name: 'projets', label: 'Projets', icon: '▣' },
  { name: 'parcelles', label: 'Parcelles', icon: '▤' },
  { name: 'mutations', label: 'Mutations', icon: '⇄' },
  { name: 'communes', label: 'Communes', icon: '◉' },
  { name: 'recherche', label: 'Recherche', icon: '🔍' },
]

function logout() {
  auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <div class="min-h-screen flex bg-slate-100">
    <!-- Sidebar -->
    <aside class="w-60 bg-slate-900 text-slate-100 flex flex-col">
      <div class="px-5 py-4 border-b border-slate-700">
        <div class="font-bold text-lg">Gestion Mutations</div>
        <div class="text-xs text-slate-400">Édition native (Rust + Vue)</div>
      </div>
      <nav class="flex-1 p-3 space-y-1">
        <router-link
          v-for="item in nav" :key="item.name" :to="{ name: item.name }"
          class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm hover:bg-slate-800 transition"
          active-class="bg-blue-600 hover:bg-blue-600"
        >
          <span class="w-5 text-center">{{ item.icon }}</span>{{ item.label }}
        </router-link>
      </nav>
      <div class="p-3 border-t border-slate-700">
        <div class="text-sm font-medium">{{ auth.user?.name }}</div>
        <div class="text-xs text-slate-400 mb-2">{{ (auth.roles || []).join(', ') }}</div>
        <button @click="logout" class="w-full text-left text-sm text-red-300 hover:text-red-200">Se déconnecter</button>
      </div>
    </aside>

    <!-- Contenu -->
    <main class="flex-1 overflow-auto">
      <div class="max-w-6xl mx-auto p-6">
        <slot />
      </div>
    </main>
  </div>
</template>
