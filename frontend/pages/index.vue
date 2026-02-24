<template>
  <div class="grid gap-8 md:grid-cols-2">
    <div class="rounded-xl bg-white p-6 shadow">
      <h2 class="text-2xl font-semibold">Admin</h2>
      <p class="mt-2 text-gray-600">Manage events, candidates, categories and voters.</p>
      <div class="mt-4">
        <NuxtLink to="/auth/login" class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Go to Admin</NuxtLink>
      </div>
    </div>
    <div class="rounded-xl bg-white p-6 shadow">
      <h2 class="text-2xl font-semibold">Voters</h2>
      <p class="mt-2 text-gray-600">Enter your voting link and code to vote.</p>
      <div class="mt-4 flex gap-2">
        <NuxtLink to="/vote" class="inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-700">Vote</NuxtLink>
      </div>
    </div>
  </div>
</template>

