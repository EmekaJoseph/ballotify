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
    path: '/admin/login',
    name: 'Login',
    component: () => import('../views/Admin/Init/Login/index.vue')
  },

  {
    path: '/admin/signup',
    name: 'SignUp',
    component: () => import('../views/Admin/Init/SignUp/index.vue')
  },

  {
    path: '/vote',
    name: 'Vote',
    component: () => import('../views/General/Vote/_layout.vue'),
    children: [
      { path: 'home', name: 'Vote_Home', props: route => ({ query: route.query }), component: () => import('../views/General/Vote/home.vue') },
      { path: 'leaderboard', name: 'Leaderboard', props: route => ({ query: route.query }), component: () => import('../views/General/Vote/board.vue') },
      { path: 'stats', name: 'Vote_Stats', props: route => ({ query: route.query }), component: () => import('../views/General/Vote/stats.vue') },
      { path: 'voting', name: 'Voting', props: route => ({ query: route.query }), component: () => import('../views/General/Vote/voting.vue') },
    ],
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
  let genaralPages: string[] = ['Home', 'About', 'Contact', 'Vote', 'Leaderboard', 'Vote_Stats', 'Voting', 'Vote_Home']
  let adminPages: string[] = ['Login', 'SignUp']
  if (genaralPages.some(x => x == to.name)) {
    next()
  }
  else if (!adminPages.some(x => x == to.name) && !isLoggedIn) {
    next({ name: 'Login' })
  }
  else if (adminPages.some(x => x == to.name) && isLoggedIn) {
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
