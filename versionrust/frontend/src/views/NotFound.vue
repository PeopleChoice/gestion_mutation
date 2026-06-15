<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-100">
    <div class="text-center">
      <div class="text-5xl font-bold text-slate-300">404</div>
      <p class="text-slate-500 mt-2">Page introuvable.</p>
      <router-link :to="{ name: 'dashboard' }" class="text-blue-600 text-sm mt-3 inline-block">Retour à l'accueil</router-link>
    </div>
  </div>
</template>
