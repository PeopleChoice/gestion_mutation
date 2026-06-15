<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import AppLayout from './components/AppLayout.vue'

const route = useRoute()
// Les pages publiques (setup, login, vérification) s'affichent sans la coque.
const isPublic = computed(() => route.meta.public)
</script>

<template>
  <AppLayout v-if="!isPublic">
    <router-view />
  </AppLayout>
  <router-view v-else />
</template>
