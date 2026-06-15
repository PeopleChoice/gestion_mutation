<script setup>
import { download } from '../api'

const tables = [
  'communes', 'projets', 'proprietaires', 'parcelles', 'mutations',
  'imports', 'import_lignes', 'document_templates', 'mutation_annulations', 'activity_logs', 'users',
]
</script>

<template>
  <h1 class="text-2xl font-bold mb-6">Sauvegarde / Export</h1>

  <div class="bg-white rounded-xl shadow-sm p-5 mb-6">
    <h2 class="font-semibold mb-2">Sauvegarde complète</h2>
    <p class="text-sm text-slate-500 mb-3">Exporte toutes les tables dans un seul fichier JSON.</p>
    <button @click="download('/admin/export.json', 'sauvegarde.json')" class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm">⬇ Télécharger la sauvegarde JSON</button>
  </div>

  <div class="bg-white rounded-xl shadow-sm p-5">
    <h2 class="font-semibold mb-3">Export CSV par table</h2>
    <div class="flex flex-wrap gap-2">
      <button v-for="t in tables" :key="t" @click="download(`/admin/export/${t}`, `${t}.csv`)"
        class="px-3 py-2 rounded-lg bg-slate-100 text-sm hover:bg-slate-200">{{ t }}.csv</button>
    </div>
  </div>
</template>
