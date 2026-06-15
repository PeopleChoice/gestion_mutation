<script setup>
import { ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import api from '../api'

const router = useRouter()
const q = ref('')
const res = ref({ mutations: [], parcelles: [], proprietaires: [], projets: [] })

let timer = null
watch(q, () => {
  clearTimeout(timer)
  timer = setTimeout(async () => {
    if (q.value.trim().length < 2) { res.value = { mutations: [], parcelles: [], proprietaires: [], projets: [] }; return }
    res.value = (await api.get('/recherche/rapide', { params: { q: q.value } })).data
  }, 250)
})
</script>

<template>
  <h1 class="text-2xl font-bold mb-6">Recherche globale</h1>
  <input v-model="q" placeholder="Rechercher partout (min. 2 caractères)…" class="border rounded-lg px-4 py-3 text-sm w-full mb-6" />

  <div class="grid md:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl shadow-sm p-5">
      <h2 class="font-semibold mb-2">Mutations</h2>
      <ul class="text-sm divide-y">
        <li v-for="m in res.mutations" :key="m.id" class="py-2 flex justify-between">
          <span>{{ m.numero_notification }} — Lot {{ m.numero_lot }}</span>
          <span class="text-xs text-slate-400">{{ m.statut }}</span>
        </li>
        <li v-if="!res.mutations.length" class="py-2 text-slate-400">—</li>
      </ul>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-5">
      <h2 class="font-semibold mb-2">Parcelles</h2>
      <ul class="text-sm divide-y">
        <li v-for="p in res.parcelles" :key="p.id" class="py-2">Lot {{ p.numero_lot }} <span class="text-slate-400">— {{ p.projet_nom }}</span></li>
        <li v-if="!res.parcelles.length" class="py-2 text-slate-400">—</li>
      </ul>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-5">
      <h2 class="font-semibold mb-2">Propriétaires</h2>
      <ul class="text-sm divide-y">
        <li v-for="pr in res.proprietaires" :key="pr.id" class="py-2">{{ pr.prenom }} {{ pr.nom }} <span class="text-slate-400">{{ pr.cni_passport }}</span></li>
        <li v-if="!res.proprietaires.length" class="py-2 text-slate-400">—</li>
      </ul>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-5">
      <h2 class="font-semibold mb-2">Projets</h2>
      <ul class="text-sm divide-y">
        <li v-for="pj in res.projets" :key="pj.id" class="py-2 cursor-pointer hover:text-blue-600" @click="router.push({ name: 'projet-detail', params: { id: pj.id } })">
          {{ pj.nom }} <span class="text-slate-400">{{ pj.commune_nom }}</span>
        </li>
        <li v-if="!res.projets.length" class="py-2 text-slate-400">—</li>
      </ul>
    </div>
  </div>
</template>
