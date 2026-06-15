<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '../stores/auth'
import { errMessage } from '../api'

const router = useRouter()
const auth = useAuth()
const email = ref('admin@domaines.sn')
const password = ref('')
const error = ref(null)
const busy = ref(false)

async function submit() {
  busy.value = true; error.value = null
  try {
    await auth.login(email.value, password.value)
    router.push({ name: 'dashboard' })
  } catch (e) { error.value = errMessage(e) } finally { busy.value = false }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-900 to-slate-900 p-6">
    <form @submit.prevent="submit" class="bg-white w-full max-w-sm rounded-2xl shadow-2xl p-8">
      <h1 class="text-xl font-bold mb-1">Gestion Mutations</h1>
      <p class="text-slate-500 text-sm mb-6">Connexion à votre espace.</p>

      <div v-if="error" class="mb-4 p-3 rounded-lg bg-red-50 text-red-700 text-sm border border-red-200">{{ error }}</div>

      <label class="text-xs font-semibold">Email</label>
      <input v-model="email" type="email" class="w-full border rounded-lg px-3 py-2 text-sm mb-3" required />
      <label class="text-xs font-semibold">Mot de passe</label>
      <input v-model="password" type="password" class="w-full border rounded-lg px-3 py-2 text-sm mb-5" required />

      <button :disabled="busy" class="w-full px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold disabled:opacity-60">
        {{ busy ? 'Connexion…' : 'Se connecter' }}
      </button>
    </form>
  </div>
</template>
