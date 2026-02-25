<template>
  <div class="space-y-6">
    <div v-if="!verified" class="mx-auto max-w-md rounded-xl bg-white p-6 shadow">
      <h1 class="text-2xl font-semibold">Enter Your Code</h1>
      <form @submit.prevent="validate" class="mt-4 space-y-3">
        <input v-model="code" placeholder="Code" class="w-full rounded-lg border px-3 py-2" />
        <button type="submit"
          class="w-full rounded-lg bg-emerald-600 px-3 py-2 text-white hover:bg-emerald-700">Validate</button>
      </form>
      <p v-if="err" class="mt-2 text-sm text-red-600">{{ err }}</p>
    </div>
    <div v-else class="space-y-6">
      <div class="rounded-xl bg-white p-6 shadow">
        <h2 class="text-xl font-semibold">{{ event.name }}</h2>
        <p class="text-sm text-gray-600">Select one candidate per category</p>
      </div>
      <div class="grid gap-6 md:grid-cols-2">
        <div v-for="cat in categories" :key="cat.id" class="rounded-xl bg-white p-4 shadow">
          <h3 class="font-semibold">{{ cat.name }}</h3>
          <div class="mt-3 grid grid-cols-1 gap-3">
            <label v-for="cand in candidatesByCategory[cat.id] || []" :key="cand.id"
              class="flex items-center gap-3 rounded-lg border p-3 hover:bg-gray-50">
              <input type="radio" :name="'cat' + cat.id" :value="cand.id" v-model="selected[cat.id]" />
              <img v-if="cand.image_path" :src="imageUrl(cand.image_path)" class="h-10 w-10 rounded object-cover" />
              <span>{{ cand.name }}</span>
            </label>
          </div>
        </div>
      </div>
      <div class="flex justify-end">
        <button @click="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Submit
          Vote</button>
      </div>
      <p v-if="submitErr" class="text-sm text-red-600">{{ submitErr }}</p>
      <p v-if="success" class="text-sm text-emerald-600">Vote recorded. Thank you.</p>
    </div>
  </div>
</template>
<script setup lang="ts">
const route = useRoute()
const token = String(route.params.token)
const api = useApi()
const event = ref<any>({})
const categories = ref<any[]>([])
const candidatesByCategory = reactive<Record<number, any[]>>({})
const selected = reactive<Record<number, number>>({})
const verified = ref(false)
const code = ref('')
const err = ref('')
const submitErr = ref('')
const success = ref(false)
const load = async () => {
  const e: any = await $fetch(useRuntimeConfig().public.apiBase + `/public/events/${token}`)
  event.value = e
  categories.value = e.categories || []
  const candidates = e.candidates || []
  for (const cand of candidates) {
    const list = candidatesByCategory[cand.category_id] || []
    list.push(cand)
    candidatesByCategory[cand.category_id] = list
  }
}
onMounted(load)
const validate = async () => {
  err.value = ''
  try {
    const res: any = await $fetch(useRuntimeConfig().public.apiBase + `/public/events/${token}/code/validate`, { method: 'POST', body: { code: code.value } })
    if (res.valid) verified.value = true
  } catch (e: any) {
    err.value = 'Invalid or used code'
  }
}
const submit = async () => {
  submitErr.value = ''
  success.value = false
  const choices = Object.entries(selected).map(([k, v]) => ({ category_id: Number(k), candidate_id: Number(v) }))
  try {
    await $fetch(useRuntimeConfig().public.apiBase + `/public/events/${token}/vote`, { method: 'POST', body: { code: code.value, choices } })
    success.value = true
  } catch (e: any) {
    submitErr.value = 'Submission failed'
  }
}
const imageUrl = (p: string) => `${useRuntimeConfig().public.backendBase}/${p}`
</script>
