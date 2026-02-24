<template>
  <div class="min-h-screen bg-gray-50 text-gray-900">
    <header class="border-b bg-white">
      <div class="mx-auto max-w-7xl px-4 py-3 flex items-center justify-between">
        <NuxtLink to="/" class="font-semibold tracking-tight text-xl">Ballotify</NuxtLink>
        <nav class="flex items-center gap-4">
          <NuxtLink to="/admin" class="hover:text-blue-600">Admin</NuxtLink>
          <NuxtLink to="/vote" class="hover:text-blue-600">Vote</NuxtLink>
        </nav>
      </div>
    </header>
    <main class="mx-auto max-w-7xl px-4 py-6">
      <slot />
    </main>
  </div>
 </template>

