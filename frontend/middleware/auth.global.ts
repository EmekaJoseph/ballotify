export default defineNuxtRouteMiddleware((to) => {
  const token = process.client ? localStorage.getItem('token') : null
  if (to.path.startsWith('/admin') && !token) {
    return navigateTo('/auth/login')
  }
})

