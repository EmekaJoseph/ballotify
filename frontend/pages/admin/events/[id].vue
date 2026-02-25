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
            <li v-for="c in categories" :key="c.id" class="flex items-center gap-2">
              <input v-model="c.name" class="flex-1 rounded border px-2 py-1" />
              <button @click="saveCategory(c)" class="rounded-lg border px-3 py-1.5 hover:bg-gray-50">Save</button>
              <button @click="deleteCategory(c)"
                class="rounded-lg border px-3 py-1.5 text-red-600 hover:bg-red-50">Delete</button>
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
            <input v-model.number="count" type="number" min="1" placeholder="Count"
              class="w-full rounded-lg border px-3 py-2" />
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
              <input v-model="cand.name" class="w-full rounded border px-2 py-1 font-medium" />
              <span class="text-sm text-gray-600">Category #{{ cand.category_id }}</span>
              <button @click="saveCandidate(cand)" class="rounded-lg border px-3 py-1.5 hover:bg-gray-50">Save</button>
              <button @click="removeCandidate(cand)"
                class="rounded-lg border px-3 py-1.5 text-red-600 hover:bg-red-50">Delete</button>
            </div>
          </div>
          <form @submit.prevent="addCandidate" class="mt-4 grid gap-2 md:grid-cols-3">
            <select v-model="candidateCategoryId" class="rounded-lg border px-3 py-2">
              <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
            <input v-model="candidateName" placeholder="Candidate name" class="rounded-lg border px-3 py-2" />
            <input type="file" ref="fileInput" class="rounded-lg border px-3 py-2" />
            <button class="md:col-span-3 rounded-lg bg-blue-600 px-3 py-2 text-white hover:bg-blue-700">Add
              Candidate</button>
          </form>
          <div class="mt-6 border-t pt-4">
            <h3 class="font-semibold">Edit Candidate</h3>
            <form @submit.prevent="editCandidate" class="mt-3 grid gap-2 md:grid-cols-4">
              <select v-model="editCandidateId" class="rounded-lg border px-3 py-2">
                <option disabled value="">Select candidate</option>
                <option v-for="c in candidates" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
              <select v-model="editCategoryId" class="rounded-lg border px-3 py-2">
                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
              <input v-model="editName" placeholder="New name" class="rounded-lg border px-3 py-2" />
              <input type="file" ref="editFileInput" class="rounded-lg border px-3 py-2" />
              <button class="md:col-span-4 rounded-lg border px-3 py-2 hover:bg-gray-50">Apply</button>
            </form>
          </div>
          <div class="mt-8 rounded-xl bg-white p-4 shadow">
            <h2 class="font-semibold">Voters</h2>
            <form @submit.prevent="addVoter" class="mt-3 grid gap-2 md:grid-cols-3">
              <input v-model="newVoterName" placeholder="Name (optional)" class="rounded-lg border px-3 py-2" />
              <input v-model="newVoterCode" placeholder="Code" class="rounded-lg border px-3 py-2" />
              <button class="rounded-lg bg-emerald-600 px-3 py-2 text-white hover:bg-emerald-700">Add Voter</button>
            </form>
            <div class="mt-4 grid gap-2">
              <div v-for="v in voters" :key="v.id" class="flex items-center gap-3 rounded-lg border p-3">
                <input v-model="v.name" placeholder="Name" class="w-40 rounded border px-2 py-1" />
                <input v-model="v.code" placeholder="Code" class="w-40 rounded border px-2 py-1" />
                <span class="text-sm" :class="v.used_at ? 'text-red-600' : 'text-emerald-600'">
                  {{ v.used_at ? 'Used' : 'Unused' }}
                </span>
                <div class="ml-auto flex items-center gap-2">
                  <button @click="saveVoter(v)" class="rounded-lg border px-3 py-1.5 hover:bg-gray-50">Save</button>
                  <button @click="resetVoter(v)" class="rounded-lg border px-3 py-1.5 hover:bg-gray-50"
                    v-if="v.used_at">Reset</button>
                  <button @click="deleteVoter(v)"
                    class="rounded-lg border px-3 py-1.5 text-red-600 hover:bg-red-50">Delete</button>
                </div>
              </div>
            </div>
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
const count = ref<number | null>(null)
const candidateCategoryId = ref<number | null>(null)
const candidateName = ref('')
const fileInput = ref<HTMLInputElement | null>(null)
const publicUrl = ref('')
const voters = ref<any[]>([])
const newVoterName = ref('')
const newVoterCode = ref('')
const editCandidateId = ref<number | ''>('')
const editCategoryId = ref<number | null>(null)
const editName = ref('')
const editFileInput = ref<HTMLInputElement | null>(null)
const load = async () => {
  const e = await api.get<any>(`/events/${id}`)
  data.value = e
  categories.value = e.categories || []
  candidates.value = e.candidates || []
  const link: any = await api.get(`/events/${id}/voting-link`)
  publicUrl.value = link.public_url
  voters.value = await api.get(`/events/${id}/voters`)
}
onMounted(load)
const addCategory = async () => {
  if (!catName.value) return
  await api.post(`/events/${id}/categories`, { name: catName.value })
  catName.value = ''
  await load()
}
const saveCategory = async (c: any) => {
  await api.patch(`/events/${id}/categories/${c.id}`, { name: c.name })
  await load()
}
const deleteCategory = async (c: any) => {
  await api.del(`/events/${id}/categories/${c.id}`)
  await load()
}
const generate = async () => {
  const res: any = await api.post(`/events/${id}/voters/generate`, { count: count.value ?? 1 })
  codes.value = res.voters
}
const addCandidate = async () => {
  if (!candidateCategoryId.value || !candidateName.value) return
  const file = fileInput.value?.files?.[0]
  const headers: Record<string, string> = {}
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
const editCandidate = async () => {
  if (!editCandidateId.value) return
  const token = process.client ? localStorage.getItem('token') : null
  const headers: Record<string, string> = {}
  if (token) headers['Authorization'] = `Bearer ${token}`
  const form = new FormData()
  if (editName.value) form.append('name', editName.value)
  if (editCategoryId.value) form.append('category_id', String(editCategoryId.value))
  const file = editFileInput.value?.files?.[0]
  if (file) form.append('image', file)
  await $fetch(useRuntimeConfig().public.apiBase + `/events/${id}/candidates/${editCandidateId.value}`, { method: 'PATCH', body: form, headers })
  editCandidateId.value = ''
  editCategoryId.value = null
  editName.value = ''
  if (editFileInput.value) editFileInput.value.value = ''
  await load()
}
const saveCandidate = async (cand: any) => {
  const token = process.client ? localStorage.getItem('token') : null
  const headers: Record<string, string> = {}
  if (token) headers['Authorization'] = `Bearer ${token}`
  const form = new FormData()
  form.append('name', cand.name)
  form.append('category_id', String(cand.category_id))
  await $fetch(useRuntimeConfig().public.apiBase + `/events/${id}/candidates/${cand.id}`, { method: 'PATCH', body: form, headers })
  await load()
}
const removeCandidate = async (cand: any) => {
  const token = process.client ? localStorage.getItem('token') : null
  const headers: Record<string, string> = {}
  if (token) headers['Authorization'] = `Bearer ${token}`
  await $fetch(useRuntimeConfig().public.apiBase + `/events/${id}/candidates/${cand.id}`, { method: 'DELETE', headers })
  await load()
}
const addVoter = async () => {
  const payload: any = { code: newVoterCode.value }
  if (newVoterName.value) payload.name = newVoterName.value
  await api.post(`/events/${id}/voters`, payload)
  newVoterName.value = ''
  newVoterCode.value = ''
  await load()
}
const saveVoter = async (v: any) => {
  const token = process.client ? localStorage.getItem('token') : null
  const headers: Record<string, string> = {}
  if (token) headers['Authorization'] = `Bearer ${token}`
  const body: any = new FormData()
  body.append('name', v.name ?? '')
  body.append('code', v.code)
  await $fetch(useRuntimeConfig().public.apiBase + `/events/${id}/voters/${v.id}`, { method: 'PATCH', body, headers })
  await load()
}
const resetVoter = async (v: any) => {
  const token = process.client ? localStorage.getItem('token') : null
  const headers: Record<string, string> = {}
  if (token) headers['Authorization'] = `Bearer ${token}`
  const body: any = new FormData()
  body.append('used_at', '')
  await $fetch(useRuntimeConfig().public.apiBase + `/events/${id}/voters/${v.id}`, { method: 'PATCH', body, headers })
  await load()
}
const deleteVoter = async (v: any) => {
  const token = process.client ? localStorage.getItem('token') : null
  const headers: Record<string, string> = {}
  if (token) headers['Authorization'] = `Bearer ${token}`
  await $fetch(useRuntimeConfig().public.apiBase + `/events/${id}/voters/${v.id}`, { method: 'DELETE', headers })
  await load()
}
const imageUrl = (p: string) => `${useRuntimeConfig().public.backendBase}/${p}`
const copy = async () => {
  if (publicUrl.value) await navigator.clipboard.writeText(publicUrl.value)
}
</script>
