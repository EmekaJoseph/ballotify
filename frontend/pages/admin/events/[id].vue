<template>
  <div class="space-y-8">
    <div class="flex items-center justify-between">
      <NuxtLink to="/admin" class="text-sm text-gray-600 hover:text-gray-900">Back</NuxtLink>
      <div class="text-right">
        <p class="text-sm text-gray-600">Public URL</p>
        <div class="flex items-center gap-2">
          <input readonly :value="publicUrl" class="w-full rounded-lg border px-3 py-2" />
          <button @click="copy" class="rounded-lg border px-3 py-2 hover:bg-gray-50">Copy</button>
        </div>
      </div>
    </div>
    <div class="grid gap-8 md:grid-cols-3">
      <div class="md:col-span-1 space-y-4">
        <div class="rounded-xl bg-white p-4 shadow">
          <h2 class="font-semibold">Categories</h2>
          <ul class="mt-2 space-y-2">
            <li v-for="c in categories" :key="c.id" class="flex items-center justify-between">
              <span>{{ c.name }}</span>
            </li>
          </ul>
          <form @submit.prevent="addCategory" class="mt-3 flex gap-2">
            <input v-model="catName" placeholder="New category" class="flex-1 rounded-lg border px-3 py-2" />
            <button class="rounded-lg bg-blue-600 px-3 py-2 text-white hover:bg-blue-700">Add</button>
          </form>
        </div>
        <div class="rounded-xl bg-white p-4 shadow">
          <h2 class="font-semibold">Generate Voters</h2>
          <form @submit.prevent="generate" class="mt-3 space-y-3">
            <input v-model.number="count" type="number" min="1" placeholder="Count" class="w-full rounded-lg border px-3 py-2" />
            <button class="w-full rounded-lg bg-emerald-600 px-3 py-2 text-white hover:bg-emerald-700">Generate</button>
          </form>
          <p v-if="codes.length" class="mt-3 text-sm">Generated {{ codes.length }} codes</p>
        </div>
      </div>
      <div class="md:col-span-2">
        <div class="rounded-xl bg-white p-4 shadow">
          <h2 class="font-semibold">Candidates</h2>
          <div class="mt-3 grid gap-3">
            <div v-for="cand in candidates" :key="cand.id" class="flex items-center gap-3 rounded-lg border p-3">
              <img v-if="cand.image_path" :src="imageUrl(cand.image_path)" class="h-12 w-12 rounded object-cover" />
              <div class="flex-1">
                <p class="font-medium">{{ cand.name }}</p>
                <p class="text-sm text-gray-600">Category #{{ cand.category_id }}</p>
              </div>
            </div>
          </div>
          <form @submit.prevent="addCandidate" class="mt-4 grid gap-2 md:grid-cols-3">
            <select v-model="candidateCategoryId" class="rounded-lg border px-3 py-2">
              <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
            <input v-model="candidateName" placeholder="Candidate name" class="rounded-lg border px-3 py-2" />
            <input type="file" ref="fileInput" class="rounded-lg border px-3 py-2" />
            <button class="md:col-span-3 rounded-lg bg-blue-600 px-3 py-2 text-white hover:bg-blue-700">Add Candidate</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup lang="ts">
const route = useRoute()
const id = Number(route.params.id)
const api = useApi()
const data = ref<any>(null)
const categories = ref<any[]>([])
const candidates = ref<any[]>([])
const catName = ref('')
const codes = ref<any[]>([])
const count = ref<number|null>(null)
const candidateCategoryId = ref<number|null>(null)
const candidateName = ref('')
const fileInput = ref<HTMLInputElement|null>(null)
const publicUrl = ref('')
const load = async () => {
  const e = await api.get<any>(`/events/${id}`)
  data.value = e
  categories.value = e.categories || []
  candidates.value = e.candidates || []
  const link:any = await api.get(`/events/${id}/voting-link`)
  publicUrl.value = link.public_url
}
onMounted(load)
const addCategory = async () => {
  if (!catName.value) return
  await api.post(`/events/${id}/categories`, { name: catName.value })
  catName.value = ''
  await load()
}
const generate = async () => {
  const res:any = await api.post(`/events/${id}/voters/generate`, { count: count.value ?? 1 })
  codes.value = res.voters
}
const addCandidate = async () => {
  if (!candidateCategoryId.value || !candidateName.value) return
  const file = fileInput.value?.files?.[0]
  const headers:Record<string,string> = {}
  const token = process.client ? localStorage.getItem('token') : null
  if (token) headers['Authorization'] = `Bearer ${token}`
  const form = new FormData()
  form.append('category_id', String(candidateCategoryId.value))
  form.append('name', candidateName.value)
  if (file) form.append('image', file)
  await $fetch(useRuntimeConfig().public.apiBase + `/events/${id}/candidates`, { method: 'POST', body: form, headers })
  candidateName.value = ''
  if (fileInput.value) fileInput.value.value = ''
  await load()
}
const imageUrl = (p:string) => `http://localhost:8000/storage/${p}`
const copy = async () => {
  if (publicUrl.value) await navigator.clipboard.writeText(publicUrl.value)
}
</script>

