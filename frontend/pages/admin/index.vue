<template>
  <div class="grid gap-8 md:grid-cols-3">
    <div class="md:col-span-2 space-y-4">
      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold">Your Events</h1>
        <button @click="logout" class="rounded-lg border px-3 py-1.5 hover:bg-gray-50">Logout</button>
      </div>
      <div class="grid gap-4">
        <div v-for="e in events" :key="e.id" class="rounded-xl bg-white p-4 shadow flex items-center justify-between">
          <div>
            <p class="font-medium">{{ e.name }}</p>
            <p class="text-sm text-gray-600">ID {{ e.id }}</p>
          </div>
          <NuxtLink :to="`/admin/events/${e.id}`" class="rounded-lg bg-blue-600 px-3 py-1.5 text-white hover:bg-blue-700">Open</NuxtLink>
        </div>
      </div>
    </div>
    <div>
      <div class="rounded-xl bg-white p-4 shadow">
        <h2 class="text-lg font-semibold">Create Event</h2>
        <form @submit.prevent="create" class="mt-3 space-y-3">
          <input v-model="name" placeholder="Event name" class="w-full rounded-lg border px-3 py-2" />
          <input v-model.number="expected" type="number" min="0" placeholder="Expected voters" class="w-full rounded-lg border px-3 py-2" />
          <button class="w-full rounded-lg bg-emerald-600 px-3 py-2 text-white hover:bg-emerald-700">Create</button>
        </form>
        <p v-if="err" class="mt-2 text-sm text-red-600">{{ err }}</p>
      </div>
    </div>
  </div>
</template>
<script setup lang="ts">
const api = useApi()
const events = ref<any[]>([])
const name = ref('')
const expected = ref<number|null>(null)
const err = ref('')
const load = async () => { events.value = await api.get('/events') }
onMounted(load)
const create = async () => {
  err.value = ''
  try {
    await api.post('/events', { name: name.value, expected_voters: expected.value ?? 0 })
    name.value = ''
    expected.value = null
    await load()
  } catch (e:any) { err.value = 'Failed to create' }
}
const logout = async () => {
  try { await api.post('/auth/logout', {}) } catch(e){}
  if (process.client) localStorage.removeItem('token')
  await navigateTo('/auth/login')
}
</script>

