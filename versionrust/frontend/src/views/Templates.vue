<script setup>
import { ref, reactive, onMounted } from 'vue'
import api, { errMessage } from '../api'

const items = ref([])
const error = ref(null)
const editing = ref(null)
const blank = () => ({ id: null, nom: '', type: 'notification_attribution', entete_html: '', corps_html: '', pied_html: '', centre_fiscal: '', bureau: '', actif: true })
const form = reactive(blank())

async function load() { items.value = (await api.get('/templates')).data }

function edit(t) { editing.value = t.id; Object.assign(form, t, { actif: !!t.actif }) }
function nouveau() { editing.value = 'new'; Object.assign(form, blank()) }
function cancel() { editing.value = null }

async function save() {
  error.value = null
  try {
    if (form.id) await api.put(`/templates/${form.id}`, form)
    else await api.post('/templates', form)
    editing.value = null; await load()
  } catch (e) { error.value = errMessage(e) }
}
async function toggle(t) { await api.post(`/templates/${t.id}/toggle`); await load() }
async function remove(t) {
  if (!confirm(`Supprimer « ${t.nom} » ?`)) return
  await api.delete(`/templates/${t.id}`); await load()
}
onMounted(load)
</script>

<template>
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold">Modèles de documents</h1>
    <button @click="nouveau" class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm">+ Nouveau modèle</button>
  </div>
  <div v-if="error" class="mb-4 p-3 rounded-lg bg-red-50 text-red-700 text-sm">{{ error }}</div>

  <div v-if="editing" class="bg-white rounded-xl shadow-sm p-5 mb-6 space-y-3">
    <div class="grid md:grid-cols-2 gap-3">
      <div><label class="text-xs font-semibold">Nom</label>
        <input v-model="form.nom" class="w-full border rounded-lg px-3 py-2 text-sm" /></div>
      <div><label class="text-xs font-semibold">Type</label>
        <input v-model="form.type" class="w-full border rounded-lg px-3 py-2 text-sm" /></div>
      <div><label class="text-xs font-semibold">Centre fiscal</label>
        <input v-model="form.centre_fiscal" class="w-full border rounded-lg px-3 py-2 text-sm" /></div>
      <div><label class="text-xs font-semibold">Bureau</label>
        <input v-model="form.bureau" class="w-full border rounded-lg px-3 py-2 text-sm" /></div>
    </div>
    <div><label class="text-xs font-semibold">En-tête (HTML)</label>
      <textarea v-model="form.entete_html" rows="3" class="w-full border rounded-lg px-3 py-2 text-xs font-mono"></textarea></div>
    <div><label class="text-xs font-semibold">Corps (HTML, placeholders {{ '{{...}}' }})</label>
      <textarea v-model="form.corps_html" rows="6" class="w-full border rounded-lg px-3 py-2 text-xs font-mono"></textarea></div>
    <div><label class="text-xs font-semibold">Pied de page (HTML)</label>
      <textarea v-model="form.pied_html" rows="3" class="w-full border rounded-lg px-3 py-2 text-xs font-mono"></textarea></div>
    <label class="text-sm flex items-center gap-2"><input type="checkbox" v-model="form.actif" /> Actif</label>
    <div class="flex gap-2">
      <button @click="save" class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm">Enregistrer</button>
      <button @click="cancel" class="px-4 py-2 rounded-lg bg-slate-200 text-sm">Annuler</button>
    </div>
  </div>

  <div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 text-slate-500 text-left">
        <tr><th class="p-3">Nom</th><th class="p-3">Type</th><th class="p-3">Actif</th><th></th></tr>
      </thead>
      <tbody>
        <tr v-for="t in items" :key="t.id" class="border-t">
          <td class="p-3 font-medium">{{ t.nom }}</td>
          <td class="p-3 text-slate-500">{{ t.type }}</td>
          <td class="p-3"><span :class="['text-xs px-2 py-0.5 rounded-full', t.actif ? 'bg-green-100 text-green-700' : 'bg-slate-200']">{{ t.actif ? 'oui' : 'non' }}</span></td>
          <td class="p-3 text-right whitespace-nowrap">
            <button @click="edit(t)" class="text-blue-600 mr-3">Modifier</button>
            <button @click="toggle(t)" class="text-amber-600 mr-3">{{ t.actif ? 'Désactiver' : 'Activer' }}</button>
            <button @click="remove(t)" class="text-red-600">Suppr.</button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
