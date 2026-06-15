<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '../api'

const route = useRoute()
const result = ref(null)
const loading = ref(true)

onMounted(async () => {
  try {
    const { data } = await api.get(`/verification/${route.params.hash}`)
    result.value = data
  } catch (_) {
    result.value = { valide: false, message: 'Erreur lors de la vérification.' }
  } finally { loading.value = false }
})
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-100 p-6">
    <div class="bg-white w-full max-w-md rounded-2xl shadow-xl p-8 text-center">
      <h1 class="text-lg font-bold mb-4">Vérification de document</h1>

      <div v-if="loading" class="text-slate-500">Vérification en cours…</div>

      <template v-else-if="result?.valide">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-green-100 text-green-600 text-2xl mb-3">✓</div>
        <p class="text-green-700 font-semibold mb-4">Document authentique</p>
        <dl class="text-left text-sm space-y-1">
          <div class="flex justify-between"><dt class="text-slate-500">N° notification</dt><dd class="font-medium">{{ result.numero_notification }}</dd></div>
          <div class="flex justify-between"><dt class="text-slate-500">Lot</dt><dd class="font-medium">{{ result.numero_lot }}</dd></div>
          <div class="flex justify-between"><dt class="text-slate-500">Projet</dt><dd class="font-medium">{{ result.projet }}</dd></div>
          <div class="flex justify-between"><dt class="text-slate-500">Commune</dt><dd class="font-medium">{{ result.commune }}</dd></div>
          <div class="flex justify-between"><dt class="text-slate-500">Bénéficiaire</dt><dd class="font-medium">{{ result.beneficiaire }}</dd></div>
          <div class="flex justify-between"><dt class="text-slate-500">Pièce</dt><dd class="font-medium">{{ result.cni }}</dd></div>
          <div class="flex justify-between"><dt class="text-slate-500">Statut</dt><dd class="font-medium">{{ result.statut }}</dd></div>
        </dl>
      </template>

      <template v-else>
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-red-100 text-red-600 text-2xl mb-3">✕</div>
        <p class="text-red-700 font-semibold">{{ result?.message || 'Document non valide.' }}</p>
      </template>
    </div>
  </div>
</template>
