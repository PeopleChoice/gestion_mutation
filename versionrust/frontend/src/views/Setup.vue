<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import api, { errMessage } from '../api'

const router = useRouter()
const form = reactive({ driver: 'mysql', host: '127.0.0.1', port: 3306, database: 'gestion_mutations', username: 'root', password: '' })
const testMsg = ref(null)
const testOk = ref(false)
const busy = ref(false)
const error = ref(null)

async function test() {
  testMsg.value = 'Test en cours…'; testOk.value = false
  try {
    const { data } = await api.post('/setup/test', payload())
    testOk.value = data.ok; testMsg.value = data.message
  } catch (e) { testOk.value = false; testMsg.value = errMessage(e) }
}

function payload() {
  return form.driver === 'sqlite'
    ? { driver: 'sqlite' }
    : { driver: 'mysql', host: form.host, port: Number(form.port), database: form.database, username: form.username, password: form.password }
}

async function install() {
  busy.value = true; error.value = null
  try {
    await api.post('/setup', payload())
    router.push({ name: 'login' }).then(() => location.reload())
  } catch (e) { error.value = errMessage(e) } finally { busy.value = false }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-900 to-slate-900 p-6">
    <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl p-8">
      <h1 class="text-xl font-bold">Configuration de la base de données</h1>
      <p class="text-slate-500 text-sm mb-6">Première utilisation : choisissez où stocker les données.</p>

      <div v-if="error" class="mb-4 p-3 rounded-lg bg-red-50 text-red-700 text-sm border border-red-200">{{ error }}</div>

      <div class="grid grid-cols-2 gap-3 mb-4">
        <button type="button" @click="form.driver='mysql'"
          :class="['border-2 rounded-xl p-4 text-center', form.driver==='mysql' ? 'border-blue-600 bg-blue-50' : 'border-slate-200']">
          <div class="font-semibold">MySQL</div><div class="text-xs text-slate-500">Serveur existant (réseau)</div>
        </button>
        <button type="button" @click="form.driver='sqlite'"
          :class="['border-2 rounded-xl p-4 text-center', form.driver==='sqlite' ? 'border-blue-600 bg-blue-50' : 'border-slate-200']">
          <div class="font-semibold">SQLite</div><div class="text-xs text-slate-500">Fichier local autonome</div>
        </button>
      </div>

      <div v-if="form.driver==='mysql'" class="space-y-3">
        <div class="flex gap-3">
          <div class="flex-1"><label class="text-xs font-semibold">Hôte</label>
            <input v-model="form.host" class="w-full border rounded-lg px-3 py-2 text-sm" /></div>
          <div class="w-28"><label class="text-xs font-semibold">Port</label>
            <input v-model="form.port" class="w-full border rounded-lg px-3 py-2 text-sm" /></div>
        </div>
        <div><label class="text-xs font-semibold">Base</label>
          <input v-model="form.database" class="w-full border rounded-lg px-3 py-2 text-sm" /></div>
        <div class="flex gap-3">
          <div class="flex-1"><label class="text-xs font-semibold">Utilisateur</label>
            <input v-model="form.username" class="w-full border rounded-lg px-3 py-2 text-sm" /></div>
          <div class="flex-1"><label class="text-xs font-semibold">Mot de passe</label>
            <input v-model="form.password" type="password" class="w-full border rounded-lg px-3 py-2 text-sm" /></div>
        </div>
      </div>
      <p v-else class="text-sm text-slate-500">Un fichier <code>database.sqlite</code> sera créé automatiquement. Aucun serveur requis.</p>

      <div v-if="testMsg" :class="['mt-4 p-3 rounded-lg text-sm', testOk ? 'bg-green-50 text-green-700' : 'bg-amber-50 text-amber-700']">{{ testMsg }}</div>

      <div class="flex gap-3 mt-6">
        <button @click="test" class="px-4 py-2 rounded-lg bg-slate-200 text-sm font-semibold">Tester la connexion</button>
        <button @click="install" :disabled="busy" class="flex-1 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold disabled:opacity-60">
          {{ busy ? 'Installation…' : 'Installer et démarrer' }}
        </button>
      </div>
    </div>
  </div>
</template>
