<script setup>
import { ref, reactive, onMounted } from 'vue'
import api, { errMessage } from '../api'
import { useAuth } from '../stores/auth'

const auth = useAuth()
const rows = ref([])
const stats = reactive({ total: 0, en_attente: 0, validees: 0, refusees: 0 })
const filters = reactive({ q: '', statut: '', show_refusees: false })
const error = ref(null)

async function load() {
  const { data } = await api.get('/mutations', { params: filters })
  rows.value = data.data
  Object.assign(stats, data.stats)
}

async function valider(m) {
  error.value = null
  try { await api.post(`/mutations/${m.id}/valider`); await load() }
  catch (e) { error.value = errMessage(e) }
}
async function refuser(m) {
  const motif = prompt('Motif du refus ?')
  if (!motif) return
  error.value = null
  try { await api.post(`/mutations/${m.id}/refuser`, { motif_refus: motif }); await load() }
  catch (e) { error.value = errMessage(e) }
}

async function imprimer(m) {
  error.value = null
  try {
    const { data } = await api.get(`/mutations/${m.id}/notification`)
    const w = window.open('', '_blank')
    w.document.write(`<!DOCTYPE html><html><head><title>Notification ${m.numero_notification}</title>
      <style>body{font-family:Georgia,serif;max-width:800px;margin:24px auto;padding:0 24px;color:#111}</style></head>
      <body>${data.html}<script>window.onload=()=>window.print()<\/script></body></html>`)
    w.document.close()
  } catch (e) { error.value = errMessage(e) }
}

async function demanderAnnulation(m) {
  const motif = prompt("Motif de la demande d'annulation ?")
  if (!motif) return
  error.value = null
  try { await api.post(`/mutations/${m.id}/annulation`, { motif }); alert('Demande envoyée.') }
  catch (e) { error.value = errMessage(e) }
}

const badge = (s) => ({
  validee: 'bg-green-100 text-green-700', en_attente: 'bg-amber-100 text-amber-700',
  refusee: 'bg-red-100 text-red-700', annulee: 'bg-slate-200 text-slate-600',
}[s] || 'bg-slate-100')

onMounted(load)
</script>

<template>
  <h1 class="text-2xl font-bold mb-6">Mutations</h1>
  <div v-if="error" class="mb-4 p-3 rounded-lg bg-red-50 text-red-700 text-sm">{{ error }}</div>

  <div class="grid grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl p-4 shadow-sm"><div class="text-2xl font-bold">{{ stats.total }}</div><div class="text-sm text-slate-500">Total</div></div>
    <div class="bg-white rounded-xl p-4 shadow-sm"><div class="text-2xl font-bold text-amber-600">{{ stats.en_attente }}</div><div class="text-sm text-slate-500">En attente</div></div>
    <div class="bg-white rounded-xl p-4 shadow-sm"><div class="text-2xl font-bold text-green-600">{{ stats.validees }}</div><div class="text-sm text-slate-500">Validées</div></div>
    <div class="bg-white rounded-xl p-4 shadow-sm"><div class="text-2xl font-bold text-red-600">{{ stats.refusees }}</div><div class="text-sm text-slate-500">Refusées</div></div>
  </div>

  <div class="flex flex-wrap gap-3 mb-4 items-center">
    <input v-model="filters.q" @input="load" placeholder="N° notif, lot, propriétaire…" class="border rounded-lg px-3 py-2 text-sm flex-1" />
    <select v-model="filters.statut" @change="load" class="border rounded-lg px-3 py-2 text-sm">
      <option value="">Tous statuts</option>
      <option value="en_attente">En attente</option>
      <option value="validee">Validées</option>
      <option value="refusee">Refusées</option>
      <option value="annulee">Annulées</option>
    </select>
    <label class="text-sm flex items-center gap-2"><input type="checkbox" v-model="filters.show_refusees" @change="load" /> Voir refusées</label>
  </div>

  <div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 text-slate-500 text-left">
        <tr><th class="p-3">N° notif.</th><th class="p-3">Lot</th><th class="p-3">Projet</th>
          <th class="p-3">Nouveau propriétaire</th><th class="p-3">Statut</th><th></th></tr>
      </thead>
      <tbody>
        <tr v-for="m in rows" :key="m.id" class="border-t">
          <td class="p-3 font-medium">{{ m.numero_notification }}</td>
          <td class="p-3">{{ m.numero_lot }}</td>
          <td class="p-3 text-slate-500">{{ m.projet_nom }}</td>
          <td class="p-3">{{ m.nouveau_prenom }} {{ m.nouveau_nom }}</td>
          <td class="p-3"><span :class="['text-xs px-2 py-0.5 rounded-full', badge(m.statut)]">{{ m.statut }}</span></td>
          <td class="p-3 text-right whitespace-nowrap">
            <template v-if="m.statut==='en_attente' && auth.hasRole('admin')">
              <button @click="valider(m)" class="text-green-600 mr-3">Valider</button>
              <button @click="refuser(m)" class="text-red-600 mr-3">Refuser</button>
            </template>
            <template v-if="m.statut==='validee'">
              <button @click="imprimer(m)" class="text-blue-600 mr-3">Notification</button>
              <button @click="demanderAnnulation(m)" class="text-amber-600">Annuler</button>
            </template>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
