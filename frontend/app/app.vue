<template>
  <NuxtLayout>
    <NuxtPage />
  </NuxtLayout>
</template>

<style>
/* Global styles */
:root {
  --primary-gradient: linear-gradient(90deg, #0d6efd 0%, #6610f2 100%);
}

.text-primary-gradient {
  background: var(--primary-gradient);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
}

body {
  font-family: 'Inter', sans-serif;
}
</style>
