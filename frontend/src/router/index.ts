import { createRouter, createWebHistory, RouteRecordRaw } from 'vue-router'
import HomeView from '../views/General/Home/home.vue'
import ContactView from '../views/General/Contact/contact.vue'
import AboutView from '../views/General/About/about.vue'

// store
import { adminAccount } from '@/store/admin/account'

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
      { path: 'groupdetails', name: 'Grp(details)', props: route => ({ query: route.query }), component: () => import('../views/Admin/Groups/details/groupdetails.vue') },
      { path: 'members', name: 'Members', component: () => import('../views/Admin/Members/members.vue') },
      { path: 'messages', name: 'Messages', component: () => import('../views/Admin/Messages/messages.vue') },
      { path: 'settings', name: 'Settings', component: () => import('../views/Admin/Settings/settings.vue') },
      { path: 'event', name: 'Event', props: route => ({ query: route.query }), component: () => import('../views/Admin/Events/event.vue') },
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



router.beforeEach(async (to, from, next) => {
  const admin = adminAccount()
  let isLoggedIn = admin.hasAccess
  if (to.name == 'Home' || to.name == 'About' || to.name == 'Contact') {
    next()
  }
  else if (to.name !== 'Admin' && !isLoggedIn) {
    next({ name: 'Admin' })
  }
  else if (to.name == 'Admin' && isLoggedIn) {
    next({ name: 'Dashboard' })
  }
  else {
    next()
  }

})


router.afterEach((to, from) => {
  document.title = 'Ballotify | ' + to.name?.toString()
})

export default router
