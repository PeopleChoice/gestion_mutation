<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api, { errMessage, download } from '../api'

const router = useRouter()
const items = ref([])
const projets = ref([])
const projetId = ref('')
const file = ref(null)
const error = ref(null)
const ok = ref(null)
const busy = ref(false)

async function load() { items.value = (await api.get('/imports')).data }
async function loadProjets() { projets.value = (await api.get('/projets')).data }

function onFile(e) { file.value = e.target.files[0] }

async function upload() {
  if (!projetId.value || !file.value) { error.value = 'Projet et fichier requis.'; return }
  busy.value = true; error.value = null; ok.value = null
  try {
    const fd = new FormData()
    fd.append('projet_id', projetId.value)
    fd.append('file', file.value)
    const { data } = await api.post('/imports', fd)
    ok.value = `Import créé : ${data.total} lignes, ${data.matchees} matchées.`
    await load()
  } catch (e) { error.value = errMessage(e) } finally { busy.value = false }
}
onMounted(() => { load(); loadProjets() })
</script>

<template>
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold">Imports Excel</h1>
    <button @click="download('/imports/template', 'modele_import.xlsx')" class="px-4 py-2 rounded-lg bg-slate-200 text-sm font-semibold">⬇ Modèle Excel</button>
  </div>
  <div v-if="error" class="mb-4 p-3 rounded-lg bg-red-50 text-red-700 text-sm">{{ error }}</div>
  <div v-if="ok" class="mb-4 p-3 rounded-lg bg-green-50 text-green-700 text-sm">{{ ok }}</div>

  <div class="bg-white rounded-xl shadow-sm p-5 mb-6 grid md:grid-cols-3 gap-3 items-end">
    <div><label class="text-xs font-semibold">Projet</label>
      <select v-model="projetId" class="w-full border rounded-lg px-3 py-2 text-sm">
        <option value="">— choisir —</option>
        <option v-for="p in projets" :key="p.id" :value="p.id">{{ p.nom }}</option>
      </select></div>
    <div><label class="text-xs font-semibold">Fichier .xlsx</label>
      <input type="file" accept=".xlsx,.xls" @change="onFile" class="w-full text-sm" /></div>
    <button @click="upload" :disabled="busy" class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm disabled:opacity-60">
      {{ busy ? 'Import…' : 'Importer' }}
    </button>
  </div>

  <div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 text-slate-500 text-left">
        <tr><th class="p-3">Fichier</th><th class="p-3">Projet</th><th class="p-3">Lignes</th><th class="p-3">Matchées</th><th class="p-3">En attente</th><th class="p-3">Statut</th></tr>
      </thead>
      <tbody>
        <tr v-for="i in items" :key="i.id" class="border-t hover:bg-slate-50 cursor-pointer" @click="router.push({ name: 'import-detail', params: { id: i.id } })">
          <td class="p-3 font-medium">{{ i.nom_fichier }}</td>
          <td class="p-3 text-slate-500">{{ i.projet_nom }}</td>
          <td class="p-3">{{ i.total_lignes }}</td>
          <td class="p-3">{{ i.lignes_matchees }}</td>
          <td class="p-3">{{ i.en_attente }}</td>
          <td class="p-3"><span class="text-xs px-2 py-0.5 rounded-full bg-slate-100">{{ i.statut }}</span></td>
        </tr>
        <tr v-if="!items.length"><td colspan="6" class="p-6 text-center text-slate-400">Aucun import.</td></tr>
      </tbody>
    </table>
  </div>
</template>
