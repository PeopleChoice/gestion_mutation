<script setup>
import { ref, onMounted } from 'vue'
import api, { errMessage } from '../api'

const items = ref([])
const error = ref(null)

async function load() {
  try { items.value = (await api.get('/annulations')).data }
  catch (e) { error.value = errMessage(e) }
}
async function traiter(a, action) {
  let motif_rejet = null
  if (action === 'rejeter') { motif_rejet = prompt('Motif du rejet ?'); if (motif_rejet === null) return }
  error.value = null
  try { await api.post(`/annulations/${a.id}/traiter`, { action, motif_rejet }); await load() }
  catch (e) { error.value = errMessage(e) }
}
onMounted(load)
</script>

<template>
  <h1 class="text-2xl font-bold mb-6">Demandes d'annulation</h1>
  <div v-if="error" class="mb-4 p-3 rounded-lg bg-red-50 text-red-700 text-sm">{{ error }}</div>

  <div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 text-slate-500 text-left">
        <tr><th class="p-3">N° notif.</th><th class="p-3">Lot</th><th class="p-3">Demandeur</th><th class="p-3">Motif</th><th></th></tr>
      </thead>
      <tbody>
        <tr v-for="a in items" :key="a.id" class="border-t">
          <td class="p-3 font-medium">{{ a.numero_notification }}</td>
          <td class="p-3">{{ a.numero_lot }}</td>
          <td class="p-3 text-slate-500">{{ a.demandeur_nom }}</td>
          <td class="p-3 text-slate-500">{{ a.motif }}</td>
          <td class="p-3 text-right whitespace-nowrap">
            <button @click="traiter(a, 'approuver')" class="text-green-600 mr-3">Approuver</button>
            <button @click="traiter(a, 'rejeter')" class="text-red-600">Rejeter</button>
          </td>
        </tr>
        <tr v-if="!items.length"><td colspan="5" class="p-6 text-center text-slate-400">Aucune demande en attente.</td></tr>
      </tbody>
    </table>
  </div>
</template>
