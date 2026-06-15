<script setup>
import { ref, reactive, onMounted } from 'vue'
import api, { errMessage } from '../api'

const users = ref([])
const allRoles = ref([])
const error = ref(null)
const editing = ref(null)
const blank = () => ({ id: null, name: '', email: '', password: '', roles: [] })
const form = reactive(blank())

async function load() {
  users.value = (await api.get('/users')).data
  allRoles.value = (await api.get('/roles')).data
}
function nouveau() { editing.value = 'new'; Object.assign(form, blank()) }
function edit(u) { editing.value = u.id; Object.assign(form, { id: u.id, name: u.name, email: u.email, password: '', roles: [...u.roles] }) }
function cancel() { editing.value = null }

async function save() {
  error.value = null
  try {
    if (form.id) await api.put(`/users/${form.id}`, form)
    else await api.post('/users', form)
    editing.value = null; await load()
  } catch (e) { error.value = errMessage(e) }
}
async function resetPwd(u) {
  const password = prompt(`Nouveau mot de passe pour ${u.name} ?`)
  if (!password) return
  try { await api.post(`/users/${u.id}/password`, { password }); alert('Mot de passe modifié.') }
  catch (e) { error.value = errMessage(e) }
}
async function remove(u) {
  if (!confirm(`Supprimer ${u.name} ?`)) return
  try { await api.delete(`/users/${u.id}`); await load() }
  catch (e) { error.value = errMessage(e) }
}
onMounted(load)
</script>

<template>
  <div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold">Utilisateurs</h1>
    <button @click="nouveau" class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm">+ Nouvel utilisateur</button>
  </div>
  <div v-if="error" class="mb-4 p-3 rounded-lg bg-red-50 text-red-700 text-sm">{{ error }}</div>

  <div v-if="editing" class="bg-white rounded-xl shadow-sm p-5 mb-6 grid md:grid-cols-2 gap-3">
    <div><label class="text-xs font-semibold">Nom</label>
      <input v-model="form.name" class="w-full border rounded-lg px-3 py-2 text-sm" /></div>
    <div><label class="text-xs font-semibold">Email</label>
      <input v-model="form.email" type="email" class="w-full border rounded-lg px-3 py-2 text-sm" /></div>
    <div v-if="!form.id"><label class="text-xs font-semibold">Mot de passe</label>
      <input v-model="form.password" type="password" class="w-full border rounded-lg px-3 py-2 text-sm" /></div>
    <div class="md:col-span-2">
      <label class="text-xs font-semibold">Rôles</label>
      <div class="flex flex-wrap gap-3 mt-1">
        <label v-for="r in allRoles" :key="r" class="text-sm flex items-center gap-1">
          <input type="checkbox" :value="r" v-model="form.roles" /> {{ r }}
        </label>
      </div>
    </div>
    <div class="md:col-span-2 flex gap-2">
      <button @click="save" class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm">Enregistrer</button>
      <button @click="cancel" class="px-4 py-2 rounded-lg bg-slate-200 text-sm">Annuler</button>
    </div>
  </div>

  <div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 text-slate-500 text-left">
        <tr><th class="p-3">Nom</th><th class="p-3">Email</th><th class="p-3">Rôles</th><th></th></tr>
      </thead>
      <tbody>
        <tr v-for="u in users" :key="u.id" class="border-t">
          <td class="p-3 font-medium">{{ u.name }}</td>
          <td class="p-3 text-slate-500">{{ u.email }}</td>
          <td class="p-3"><span v-for="r in u.roles" :key="r" class="text-xs px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 mr-1">{{ r }}</span></td>
          <td class="p-3 text-right whitespace-nowrap">
            <button @click="edit(u)" class="text-blue-600 mr-3">Modifier</button>
            <button @click="resetPwd(u)" class="text-amber-600 mr-3">Mot de passe</button>
            <button @click="remove(u)" class="text-red-600">Suppr.</button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
