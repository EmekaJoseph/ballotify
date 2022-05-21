import { createRouter, createWebHistory, RouteRecordRaw } from 'vue-router'
import HomeView from '../views/General/Home/home.vue'
import ContactView from '../views/General/Contact/contact.vue'
import AboutView from '../views/General/About/about.vue'

const routes: Array<RouteRecordRaw> = [
  {
    path: '/',
    name: 'Home',
    component: HomeView
  },
  {
    path: '/contact',
    name: 'Contact',
    component: ContactView
  },

  {
    path: '/about',
    name: 'About',
    component: AboutView
  },

  {
    path: '/admin',
    name: 'Admin',
    component: () => import('../views/Admin/Login/index.vue')
  },

  {
    path: '/account',
    name: 'Admin-layout',
    component: () => import('../views/Admin/admin-layout.vue'),
    children: [
      { path: 'dashboard', name: 'Dashboard', component: () => import('../views/Admin/Dashboard/dashboard.vue') },
      { path: 'groups', name: 'Groups', component: () => import('../views/Admin/Groups/groups.vue') },
      { path: 'members', name: 'Members', component: () => import('../views/Admin/Members/members.vue') },
      { path: 'messages', name: 'Messages', component: () => import('../views/Admin/Messages/messages.vue') },
    ],
  },


  {
    path: '/:pathMatch(.*)*',
    name: 'Invalid',
    component: () => import('../views/General/invalid.vue')
  },

]



const router = createRouter({
  history: createWebHistory(process.env.BASE_URL),
  linkActiveClass: 'active',
  routes
})

router.afterEach((to, from) => {
  document.title = 'Ballotify | ' + to.name?.toString()
})

export default router
