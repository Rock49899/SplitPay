import { createRouter, createWebHistory } from 'vue-router'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  scrollBehavior(to, from, savedPosition) {
    return savedPosition || { left: 0, top: 0 }
  },
  routes: [
    {
      path: '/',
      name: 'Dashboard',
      component: () => import('../views/Dashboard.vue'),
      meta: {
        title: 'Dashboard',
      },
    },
    {
      path: '/profile',
      name: 'Profile',
      component: () => import('../views/Others/UserProfile.vue').catch(() => import('../views/Placeholders/PlaceholderPage.vue')),
      meta: {
        title: 'Profile',
      },
    },
    {
      path: '/admin/users',
      name: 'Users',
      component: () => import('../views/Users/Userlist.vue'),
      meta: {
        title: 'Users',
      },
    },
    {
      path: '/admin/users/:id',
      name: 'User Details',
      component: () => import('../views/Users/Userdetails.vue'),
      meta: {
        title: 'User details',
      },
    },
    {
      path: '/admin/students',
      name: 'Students',
      component: () => import('../views/Students/StudentList.vue').catch(() => import('../views/Placeholders/PlaceholderPage.vue')),
      meta: { title: 'Students' },
    },
    {
      path: '/admin/students/:id',
      name: 'StudentDetails',
      component: () => import('../views/Students/StudentDetails.vue').catch(() => import('../views/Placeholders/PlaceholderPage.vue')),
      meta: { title: 'Student details' },
    },
    {
      path: '/admin/students/:id/finance',
      name: 'StudentFinance',
      component: () => import('../views/Students/StudentFinance.vue').catch(() => import('../views/Placeholders/PlaceholderPage.vue')),
      meta: { title: 'Student finance' },
    },
    {
      path: '/admin/annexes',
      name: 'Annexes',
      component: () => import('../views/Annexes/AnnexeList.vue').catch(() => import('../views/Placeholders/PlaceholderPage.vue')),
      meta: { title: 'Annexes' },
    },
    {
      path: '/finances',
      name: 'Finances',
      component: () => import('../views/Finance/FinanceDashboard.vue').catch(() => import('../views/Placeholders/PlaceholderPage.vue')),
      meta: { title: 'Finances' },
    },
    {
      path: '/settings',
      name: 'Settings',
      component: () => import('../views/Settings/Settings.vue').catch(() => import('../views/Placeholders/PlaceholderPage.vue')),
      meta: { title: 'Settings' },
    },
    {
      path: '/charts',
      name: 'Charts',
      component: () => import('../views/Chart/LineChart/LineChart.vue'),
      meta: { title: 'Charts' },
    },
    {
      path: '/line-chart',
      name: 'Line Chart',
      component: () => import('../views/Chart/LineChart/LineChart.vue'),
    },
    {
      path: '/bar-chart',
      name: 'Bar Chart',
      component: () => import('../views/Chart/BarChart/BarChart.vue'),
    },
    {
      path: '/blank',
      name: 'Blank',
      component: () => import('../views/Pages/BlankPage.vue'),
      meta: {
        title: 'Blank',
      },
    },

    {
      path: '/error-404',
      name: '404 Error',
      component: () => import('../views/Errors/FourZeroFour.vue'),
      meta: {
        title: '404 Error',
      },
    },

    {
      path: '/signin',
      name: 'Signin',
      component: () => import('../views/Auth/Signin.vue'),
      meta: {
        title: 'Signin',
      },
    },
    {
      path: '/signup',
      name: 'Signup',
      component: () => import('../views/Auth/Signup.vue'),
      meta: {
        title: 'Signup',
      },
    },
  ],
})

export default router

router.beforeEach((to, from, next) => {
  document.title = `Vue.js ${to.meta.title} | TailAdmin - Vue.js Tailwind CSS Dashboard Template`
  next()
})
