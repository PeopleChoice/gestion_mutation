<script setup>
import { ref, reactive, onMounted } from 'vue'
import api, { errMessage } from '../api'
import { useAuth } from '../stores/auth'

const auth = useAuth()
const items = ref([])
const q = ref('')
const error = ref(null)
const ok = ref(null)

// Modal d'attribution
const modal = ref(null) // parcelle ciblée
const attr = reactive({ civilite: 'Monsieur', prenom: '', nom: '', cni_passport: '', telephone: '', numero_notification: '', force_password: '' })

async function load() { items.value = (await api.get('/parcelles', { params: { q: q.value } })).data }

function openAttribuer(p) {
  modal.value = p
  Object.assign(attr, { civilite: 'Monsieur', prenom: '', nom: '', cni_passport: '', telephone: '', numero_notification: '', force_password: '' })
}

async function submitAttribuer() {
  error.value = null; ok.value = null
  try {
    const { data } = await api.post(`/parcelles/${modal.value.id}/attribuer`, attr)
    ok.value = `Attribué — notification ${data.numero_notification}.`
    modal.value = null
    await load()
  } catch (e) { error.value = errMessage(e) }
}
onMounted(load)
</script>

<template>
  <h1 class="text-2xl font-bold mb-6">Parcelles</h1>
  <div v-if="error" class="mb-4 p-3 rounded-lg bg-red-50 text-red-700 text-sm">{{ error }}</div>
  <div v-if="ok" class="mb-4 p-3 rounded-lg bg-green-50 text-green-700 text-sm">{{ ok }}</div>

  <input v-model="q" @input="load" placeholder="Rechercher lot, propriétaire, CNI…" class="border rounded-lg px-3 py-2 text-sm w-full mb-4" />

  <div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 text-slate-500 text-left">
        <tr><th class="p-3">Lot</th><th class="p-3">Projet</th><th class="p-3">Propriétaire</th><th class="p-3">CNI</th><th></th></tr>
      </thead>
      <tbody>
        <tr v-for="p in items" :key="p.id" class="border-t">
          <td class="p-3 font-medium">{{ p.numero_lot }}</td>
          <td class="p-3 text-slate-500">{{ p.projet_nom }}</td>
          <td class="p-3">
            <span v-if="p.proprietaire_id">{{ p.proprietaire_prenom }} {{ p.proprietaire_nom }}</span>
            <span v-else class="text-amber-600 text-xs">Vierge</span>
          </td>
          <td class="p-3 text-slate-500">{{ p.proprietaire_cni }}</td>
          <td class="p-3 text-right">
            <button v-if="auth.hasAny(['admin','gestionnaire'])" @click="openAttribuer(p)" class="text-blue-600">Attribuer</button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>

  <!-- Modal attribution -->
  <div v-if="modal" class="fixed inset-0 bg-black/40 flex items-center justify-center p-6" @click.self="modal=null">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
      <h2 class="font-bold mb-1">Attribuer le lot {{ modal.numero_lot }}</h2>
      <p class="text-xs text-slate-500 mb-4">{{ modal.projet_nom }}</p>

      <div class="grid grid-cols-2 gap-3">
        <div><label class="text-xs font-semibold">Civilité</label>
          <select v-model="attr.civilite" class="w-full border rounded-lg px-3 py-2 text-sm">
            <option>Monsieur</option><option>Madame</option><option>Société</option>
          </select></div>
        <div><label class="text-xs font-semibold">N° notification</label>
          <input v-model="attr.numero_notification" placeholder="auto" class="w-full border rounded-lg px-3 py-2 text-sm" /></div>
        <div><label class="text-xs font-semibold">Prénom</label>
          <input v-model="attr.prenom" class="w-full border rounded-lg px-3 py-2 text-sm" /></div>
        <div><label class="text-xs font-semibold">Nom</label>
          <input v-model="attr.nom" class="w-full border rounded-lg px-3 py-2 text-sm" /></div>
        <div><label class="text-xs font-semibold">CNI / Passeport</label>
          <input v-model="attr.cni_passport" class="w-full border rounded-lg px-3 py-2 text-sm" /></div>
        <div><label class="text-xs font-semibold">Téléphone</label>
          <input v-model="attr.telephone" class="w-full border rounded-lg px-3 py-2 text-sm" /></div>
      </div>
      <div v-if="modal.proprietaire_id" class="mt-3">
        <label class="text-xs font-semibold text-amber-700">Lot déjà attribué — mot de passe de forçage</label>
        <input v-model="attr.force_password" type="password" class="w-full border rounded-lg px-3 py-2 text-sm" />
      </div>

      <div class="flex gap-2 mt-5">
        <button @click="submitAttribuer" class="flex-1 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm">Attribuer</button>
        <button @click="modal=null" class="px-4 py-2 rounded-lg bg-slate-200 text-sm">Annuler</button>
      </div>
    </div>
  </div>
</template>
