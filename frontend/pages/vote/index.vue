<template>
  <div class="mx-auto max-w-lg rounded-xl bg-white p-6 shadow">
    <h1 class="text-2xl font-semibold">Enter Voting Link</h1>
    <form @submit.prevent="go" class="mt-4 space-y-3">
      <input v-model="token" placeholder="Link token" class="w-full rounded-lg border px-3 py-2" />
      <button type="submit" class="w-full rounded-lg bg-emerald-600 px-3 py-2 text-white hover:bg-emerald-700">Continue</button>
    </form>
  </div>
</template>
<script setup lang="ts">
const token = ref('')
const go = async () => {
  if (!token.value) return
  await navigateTo(`/vote/${token.value}`)
}
</script>

