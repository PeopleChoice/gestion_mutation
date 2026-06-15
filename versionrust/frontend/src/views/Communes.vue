<script setup>
import { ref, reactive, onMounted } from 'vue'
import api, { errMessage } from '../api'
import { useAuth } from '../stores/auth'

const auth = useAuth()
const items = ref([])
const q = ref('')
const form = reactive({ id: null, nom: '', departement: '', region: '' })
const error = ref(null)

async function load() {
  items.value = (await api.get('/communes', { params: { q: q.value } })).data
}
function edit(c) { Object.assign(form, c) }
function reset() { Object.assign(form, { id: null, nom: '', departement: '', region: '' }) }

async function save() {
  error.value = null
  try {
    if (form.id) await api.put(`/communes/${form.id}`, form)
    else await api.post('/communes', form)
    reset(); await load()
  } catch (e) { error.value = errMessage(e) }
}
async function remove(c) {
  if (!confirm(`Supprimer la commune « ${c.nom} » ?`)) return
  error.value = null
  try { await api.delete(`/communes/${c.id}`); await load() }
  catch (e) { error.value = errMessage(e) }
}
onMounted(load)
</script>

<template>
  <h1 class="text-2xl font-bold mb-6">Communes</h1>
  <div v-if="error" class="mb-4 p-3 rounded-lg bg-red-50 text-red-700 text-sm">{{ error }}</div>

  <div class="flex gap-3 mb-4">
    <input v-model="q" @input="load" placeholder="Rechercher…" class="border rounded-lg px-3 py-2 text-sm flex-1" />
  </div>

  <div class="grid md:grid-cols-3 gap-6">
    <div class="md:col-span-2 bg-white rounded-xl shadow-sm overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-500 text-left">
          <tr><th class="p-3">Nom</th><th class="p-3">Département</th><th class="p-3">Région</th><th class="p-3">Projets</th><th></th></tr>
        </thead>
        <tbody>
          <tr v-for="c in items" :key="c.id" class="border-t">
            <td class="p-3 font-medium">{{ c.nom }}</td>
            <td class="p-3 text-slate-500">{{ c.departement }}</td>
            <td class="p-3 text-slate-500">{{ c.region }}</td>
            <td class="p-3">{{ c.projets_count }}</td>
            <td class="p-3 text-right whitespace-nowrap" v-if="auth.hasRole('admin')">
              <button @click="edit(c)" class="text-blue-600 mr-3">Modifier</button>
              <button @click="remove(c)" class="text-red-600">Suppr.</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="auth.hasRole('admin')" class="bg-white rounded-xl shadow-sm p-5 h-fit">
      <h2 class="font-semibold mb-3">{{ form.id ? 'Modifier' : 'Nouvelle commune' }}</h2>
      <label class="text-xs font-semibold">Nom</label>
      <input v-model="form.nom" class="w-full border rounded-lg px-3 py-2 text-sm mb-2" />
      <label class="text-xs font-semibold">Département</label>
      <input v-model="form.departement" class="w-full border rounded-lg px-3 py-2 text-sm mb-2" />
      <label class="text-xs font-semibold">Région</label>
      <input v-model="form.region" class="w-full border rounded-lg px-3 py-2 text-sm mb-3" />
      <div class="flex gap-2">
        <button @click="save" class="flex-1 px-3 py-2 rounded-lg bg-blue-600 text-white text-sm">Enregistrer</button>
        <button v-if="form.id" @click="reset" class="px-3 py-2 rounded-lg bg-slate-200 text-sm">Annuler</button>
      </div>
    </div>
  </div>
</template>
