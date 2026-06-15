<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api, { errMessage } from '../api'

const router = useRouter()
const items = ref([])
const communes = ref([])
const q = ref('')
const showForm = ref(false)
const form = reactive({ nom: '', commune_id: '', type_lotissement: '', description: '' })
const error = ref(null)

async function load() {
  items.value = (await api.get('/projets', { params: { q: q.value } })).data
}
async function loadCommunes() { communes.value = (await api.get('/communes')).data }

async function save() {
  error.value = null
  try {
    await api.post('/projets', { ...form, commune_id: Number(form.commune_id) })
    showForm.value = false
    Object.assign(form, { nom: '', commune_id: '', type_lotissement: '', description: '' })
    await load()
  } catch (e) { error.value = errMessage(e) }
}
onMounted(() => { load(); loadCommunes() })
</script>

<template>
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold">Projets</h1>
    <button @click="showForm = !showForm" class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm">+ Nouveau projet</button>
  </div>
  <div v-if="error" class="mb-4 p-3 rounded-lg bg-red-50 text-red-700 text-sm">{{ error }}</div>

  <div v-if="showForm" class="bg-white rounded-xl shadow-sm p-5 mb-6 grid md:grid-cols-2 gap-3">
    <div><label class="text-xs font-semibold">Nom</label>
      <input v-model="form.nom" class="w-full border rounded-lg px-3 py-2 text-sm" /></div>
    <div><label class="text-xs font-semibold">Commune</label>
      <select v-model="form.commune_id" class="w-full border rounded-lg px-3 py-2 text-sm">
        <option value="">— choisir —</option>
        <option v-for="c in communes" :key="c.id" :value="c.id">{{ c.nom }}</option>
      </select></div>
    <div><label class="text-xs font-semibold">Type de lotissement</label>
      <input v-model="form.type_lotissement" class="w-full border rounded-lg px-3 py-2 text-sm" /></div>
    <div><label class="text-xs font-semibold">Description</label>
      <input v-model="form.description" class="w-full border rounded-lg px-3 py-2 text-sm" /></div>
    <div class="md:col-span-2 text-right">
      <button @click="save" class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm">Enregistrer</button>
    </div>
  </div>

  <input v-model="q" @input="load" placeholder="Rechercher…" class="border rounded-lg px-3 py-2 text-sm w-full mb-4" />

  <div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 text-slate-500 text-left">
        <tr><th class="p-3">Code</th><th class="p-3">Nom</th><th class="p-3">Commune</th><th class="p-3">Parcelles</th></tr>
      </thead>
      <tbody>
        <tr v-for="p in items" :key="p.id" class="border-t hover:bg-slate-50 cursor-pointer" @click="router.push({ name: 'projet-detail', params: { id: p.id } })">
          <td class="p-3 font-mono text-xs">{{ p.code }}</td>
          <td class="p-3 font-medium">{{ p.nom }}</td>
          <td class="p-3 text-slate-500">{{ p.commune_nom }}</td>
          <td class="p-3">{{ p.parcelles_count }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
