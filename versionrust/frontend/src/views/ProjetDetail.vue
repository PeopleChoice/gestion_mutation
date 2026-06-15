<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '../api'

const route = useRoute()
const data = ref(null)
onMounted(async () => { data.value = (await api.get(`/projets/${route.params.id}`)).data })
</script>

<template>
  <div v-if="data">
    <router-link :to="{ name: 'projets' }" class="text-blue-600 text-sm">← Projets</router-link>
    <h1 class="text-2xl font-bold mt-2 mb-1">{{ data.projet.nom }}
      <span class="text-sm font-mono text-slate-400">{{ data.projet.code }}</span></h1>
    <p class="text-slate-500 mb-6">{{ data.projet.commune_nom }}</p>

    <div class="grid grid-cols-3 gap-4 mb-6">
      <div class="bg-white rounded-xl p-4 shadow-sm"><div class="text-2xl font-bold">{{ data.stats.total }}</div><div class="text-sm text-slate-500">Parcelles</div></div>
      <div class="bg-white rounded-xl p-4 shadow-sm"><div class="text-2xl font-bold text-green-600">{{ data.stats.attribuees }}</div><div class="text-sm text-slate-500">Attribuées</div></div>
      <div class="bg-white rounded-xl p-4 shadow-sm"><div class="text-2xl font-bold text-amber-600">{{ data.stats.non_attribuees }}</div><div class="text-sm text-slate-500">Vierges</div></div>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-500 text-left">
          <tr><th class="p-3">Lot</th><th class="p-3">Propriétaire</th><th class="p-3">Superficie</th><th class="p-3">Usage</th></tr>
        </thead>
        <tbody>
          <tr v-for="pa in data.parcelles" :key="pa.id" class="border-t">
            <td class="p-3 font-medium">{{ pa.numero_lot }}</td>
            <td class="p-3">{{ pa.proprietaire_prenom }} {{ pa.proprietaire_nom }}
              <span v-if="!pa.proprietaire_id" class="text-xs text-amber-600">vierge</span></td>
            <td class="p-3 text-slate-500">{{ pa.superficie }}</td>
            <td class="p-3 text-slate-500">{{ pa.usage }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
  <div v-else class="text-slate-500">Chargement…</div>
</template>
