<template>
    <div class="mx-auto max-w-md rounded-xl bg-white p-8 shadow">
        <h1 class="mb-6 text-2xl font-semibold">Register</h1>
        <form @submit.prevent="submit" class="space-y-4">
            <input v-model="name" placeholder="Name" class="w-full rounded-lg border px-4 py-2" />
            <input v-model="email" type="email" placeholder="Email" class="w-full rounded-lg border px-4 py-2" />
            <input v-model="password" type="password" placeholder="Password"
                class="w-full rounded-lg border px-4 py-2" />
            <button class="w-full rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">Create account</button>
        </form>
        <p v-if="error" class="mt-3 text-sm text-red-600">{{ error }}</p>
    </div>
</template>
<script setup lang="ts">
const name = ref('')
const email = ref('')
const password = ref('')
const error = ref('')
const api = useApi()
const submit = async () => {
    error.value = ''
    try {
        const res: any = await api.post('/auth/register', { name: name.value, email: email.value, password: password.value })
        if (process.client) localStorage.setItem('token', res.token)
        await navigateTo('/admin')
    } catch (e: any) {
        error.value = 'Registration failed'
    }
}
</script>
