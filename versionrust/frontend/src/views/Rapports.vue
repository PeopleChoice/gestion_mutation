<script setup>
import { ref, reactive, onMounted } from 'vue'
import api, { errMessage } from '../api'

const projets = ref([])
const filters = reactive({ projet_id: '', statut: '', date_from: '', date_to: '' })
const result = ref(null)
const error = ref(null)

async function loadProjets() { projets.value = (await api.get('/projets')).data }

async function generer() {
  error.value = null
  try {
    const payload = { ...filters, projet_id: filters.projet_id ? Number(filters.projet_id) : null }
    result.value = (await api.post('/rapports', payload)).data
  } catch (e) { error.value = errMessage(e) }
}

function imprimer() { window.print() }
onMounted(loadProjets)
</script>

<template>
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold">Rapports</h1>
    <button v-if="result" @click="imprimer" class="px-4 py-2 rounded-lg bg-slate-200 text-sm">Imprimer</button>
  </div>
  <div v-if="error" class="mb-4 p-3 rounded-lg bg-red-50 text-red-700 text-sm">{{ error }}</div>

  <div class="bg-white rounded-xl shadow-sm p-5 mb-6 grid md:grid-cols-5 gap-3 items-end print:hidden">
    <div><label class="text-xs font-semibold">Projet</label>
      <select v-model="filters.projet_id" class="w-full border rounded-lg px-3 py-2 text-sm">
        <option value="">Tous</option>
        <option v-for="p in projets" :key="p.id" :value="p.id">{{ p.nom }}</option>
      </select></div>
    <div><label class="text-xs font-semibold">Statut</label>
      <select v-model="filters.statut" class="w-full border rounded-lg px-3 py-2 text-sm">
        <option value="">Tous</option><option value="validee">Validées</option>
        <option value="refusee">Refusées</option><option value="en_attente">En attente</option>
        <option value="annulee">Annulées</option>
      </select></div>
    <div><label class="text-xs font-semibold">Du</label>
      <input type="date" v-model="filters.date_from" class="w-full border rounded-lg px-3 py-2 text-sm" /></div>
    <div><label class="text-xs font-semibold">Au</label>
      <input type="date" v-model="filters.date_to" class="w-full border rounded-lg px-3 py-2 text-sm" /></div>
    <button @click="generer" class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm">Générer</button>
  </div>

  <div v-if="result">
    <div class="grid grid-cols-4 gap-4 mb-6">
      <div class="bg-white rounded-xl p-4 shadow-sm"><div class="text-2xl font-bold">{{ result.stats.total }}</div><div class="text-sm text-slate-500">Total</div></div>
      <div class="bg-white rounded-xl p-4 shadow-sm"><div class="text-2xl font-bold text-green-600">{{ result.stats.validees }}</div><div class="text-sm text-slate-500">Validées</div></div>
      <div class="bg-white rounded-xl p-4 shadow-sm"><div class="text-2xl font-bold text-red-600">{{ result.stats.refusees }}</div><div class="text-sm text-slate-500">Refusées</div></div>
      <div class="bg-white rounded-xl p-4 shadow-sm"><div class="text-2xl font-bold text-slate-500">{{ result.stats.annulees }}</div><div class="text-sm text-slate-500">Annulées</div></div>
    </div>
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-500 text-left">
          <tr><th class="p-3">N° notif.</th><th class="p-3">Date</th><th class="p-3">Lot</th><th class="p-3">Projet</th><th class="p-3">Bénéficiaire</th><th class="p-3">Statut</th></tr>
        </thead>
        <tbody>
          <tr v-for="(m, i) in result.mutations" :key="i" class="border-t">
            <td class="p-3 font-medium">{{ m.numero_notification }}</td>
            <td class="p-3 text-slate-500">{{ m.date_mutation }}</td>
            <td class="p-3">{{ m.numero_lot }}</td>
            <td class="p-3 text-slate-500">{{ m.projet_nom }}</td>
            <td class="p-3">{{ m.nouveau_prenom }} {{ m.nouveau_nom }}</td>
            <td class="p-3">{{ m.statut }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
