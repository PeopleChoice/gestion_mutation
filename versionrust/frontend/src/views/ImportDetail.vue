<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api, { errMessage } from '../api'
import { useAuth } from '../stores/auth'

const route = useRoute()
const auth = useAuth()
const data = ref(null)
const error = ref(null)

async function load() {
  try { data.value = (await api.get(`/imports/${route.params.id}`)).data }
  catch (e) { error.value = errMessage(e) }
}
async function valider(l) {
  error.value = null
  try { await api.post(`/imports/lignes/${l.id}/valider`, {}); await load() }
  catch (e) { error.value = errMessage(e) }
}
async function refuser(l) {
  const motif = prompt('Motif du refus ?') || 'Refusée'
  error.value = null
  try { await api.post(`/imports/lignes/${l.id}/refuser`, { motif }); await load() }
  catch (e) { error.value = errMessage(e) }
}
const badge = (s) => ({ validee: 'bg-green-100 text-green-700', en_attente: 'bg-amber-100 text-amber-700', refusee: 'bg-red-100 text-red-700' }[s] || 'bg-slate-100')
onMounted(load)
</script>

<template>
  <div v-if="data">
    <router-link :to="{ name: 'imports' }" class="text-blue-600 text-sm">← Imports</router-link>
    <h1 class="text-2xl font-bold mt-2 mb-1">{{ data.import.nom_fichier }}</h1>
    <p class="text-slate-500 mb-6">{{ data.import.projet_nom }} — {{ data.import.lignes_matchees }} / {{ data.import.total_lignes }} matchées</p>

    <div v-if="error" class="mb-4 p-3 rounded-lg bg-red-50 text-red-700 text-sm">{{ error }}</div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-500 text-left">
          <tr><th class="p-3">#</th><th class="p-3">Lot</th><th class="p-3">Nouveau propriétaire</th><th class="p-3">CNI</th><th class="p-3">Matché</th><th class="p-3">Statut</th><th></th></tr>
        </thead>
        <tbody>
          <tr v-for="l in data.lignes" :key="l.id" class="border-t">
            <td class="p-3 text-slate-400">{{ l.numero_ordre }}</td>
            <td class="p-3 font-medium">{{ l.numero_lot }}</td>
            <td class="p-3">{{ l.prenom }} {{ l.nom }}</td>
            <td class="p-3 text-slate-500">{{ l.cni_passport }}</td>
            <td class="p-3">{{ l.matched ? '✓' : '—' }}</td>
            <td class="p-3"><span :class="['text-xs px-2 py-0.5 rounded-full', badge(l.statut)]">{{ l.statut }}</span></td>
            <td class="p-3 text-right whitespace-nowrap">
              <template v-if="l.statut==='en_attente' && l.matched && auth.hasAny(['admin','gestionnaire'])">
                <button @click="valider(l)" class="text-green-600 mr-3">Valider</button>
                <button @click="refuser(l)" class="text-red-600">Refuser</button>
              </template>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
  <div v-else class="text-slate-500">Chargement…</div>
</template>
