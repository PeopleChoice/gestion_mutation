<script setup>
import { ref, onMounted } from 'vue'
import api from '../api'

const d = ref(null)
onMounted(async () => { d.value = (await api.get('/dashboard')).data })
</script>

<template>
  <div v-if="d">
    <h1 class="text-2xl font-bold mb-6">Tableau de bord</h1>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
      <div class="bg-white rounded-xl p-5 shadow-sm">
        <div class="text-3xl font-bold">{{ d.parcelles.total }}</div>
        <div class="text-sm text-slate-500">Parcelles</div>
      </div>
      <div class="bg-white rounded-xl p-5 shadow-sm">
        <div class="text-3xl font-bold text-green-600">{{ d.parcelles.attribuees }}</div>
        <div class="text-sm text-slate-500">Attribuées</div>
      </div>
      <div class="bg-white rounded-xl p-5 shadow-sm">
        <div class="text-3xl font-bold text-blue-600">{{ d.mutations.validees }}</div>
        <div class="text-sm text-slate-500">Mutations validées</div>
      </div>
      <div class="bg-white rounded-xl p-5 shadow-sm">
        <div class="text-3xl font-bold text-amber-600">{{ d.mutations.en_attente }}</div>
        <div class="text-sm text-slate-500">En attente</div>
      </div>
    </div>

    <div class="grid md:grid-cols-2 gap-6">
      <div class="bg-white rounded-xl p-5 shadow-sm">
        <h2 class="font-semibold mb-3">Dernières mutations</h2>
        <table class="w-full text-sm">
          <tbody>
            <tr v-for="m in d.dernieres_mutations" :key="m.id" class="border-t">
              <td class="py-2">{{ m.numero_notification }}</td>
              <td class="text-slate-500">Lot {{ m.numero_lot }}</td>
              <td class="text-slate-500">{{ m.projet_nom }}</td>
              <td class="text-right"><span class="text-xs px-2 py-0.5 rounded-full bg-slate-100">{{ m.statut }}</span></td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="bg-white rounded-xl p-5 shadow-sm">
        <h2 class="font-semibold mb-3">Top projets</h2>
        <table class="w-full text-sm">
          <tbody>
            <tr v-for="p in d.top_projets" :key="p.id" class="border-t">
              <td class="py-2">{{ p.nom }} <span class="text-xs text-slate-400">{{ p.code }}</span></td>
              <td class="text-right text-slate-500">{{ p.attribuees_count }} / {{ p.parcelles_count }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div v-else class="text-slate-500">Chargement…</div>
</template>
