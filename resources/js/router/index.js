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
      meta: { title: 'Dashboard', requiresAuth: true },
    },
    {
      path: '/profile',
      name: 'Profile',
      component: () => import('../views/Others/UserProfile.vue').catch(() => import('../views/Placeholders/PlaceholderPage.vue')),
      meta: { title: 'Profile', requiresAuth: true },
    },
    {
      path: '/admin/users',
      name: 'Users',
      component: () => import('../views/Users/Userlist.vue'),
      meta: { title: 'Users', requiresAuth: true },
    },
    {
      path: '/admin/users/:id',
      name: 'User Details',
      component: () => import('../views/Users/Userdetails.vue'),
      meta: { title: 'User details', requiresAuth: true },
    },
    {
      path: '/admin/students',
      name: 'Students',
      component: () => import('../views/Students/StudentList.vue').catch(() => import('../views/Placeholders/PlaceholderPage.vue')),
      meta: { title: 'Students', requiresAuth: true },
    },
    {
      path: '/admin/students/:id',
      name: 'StudentDetails',
      component: () => import('../views/Students/StudentDetails.vue').catch(() => import('../views/Placeholders/PlaceholderPage.vue')),
      meta: { title: 'Student details', requiresAuth: true },
    },
    {
      path: '/admin/students/:id/finance',
      name: 'StudentFinance',
      component: () => import('../views/Students/StudentFinance.vue').catch(() => import('../views/Placeholders/PlaceholderPage.vue')),
      meta: { title: 'Student finance', requiresAuth: true },
    },
    {
      path: '/admin/annexes',
      name: 'Annexes',
      component: () => import('../views/Annexes/AnnexeList.vue').catch(() => import('../views/Placeholders/PlaceholderPage.vue')),
      meta: { title: 'Annexes', requiresAuth: true },
    },
    {
      path: '/admin/study-levels',
      name: 'StudyLevels',
      component: () => import('../views/Academic/StudyLevelList.vue'),
      meta: { title: 'Study Levels', requiresAuth: true },
    },
    {
      path: '/admin/specializations',
      name: 'Specializations',
      component: () => import('../views/Academic/SpecializationList.vue').catch(() => import('../views/Placeholders/PlaceholderPage.vue')),
      meta: { title: 'Specializations', requiresAuth: true },
    },
    {
      path: '/admin/school-year/close',
      name: 'SchoolYearClose',
      component: () => import('../views/Academic/SchoolYearClose.vue').catch(() => import('../views/Placeholders/PlaceholderPage.vue')),
      meta: { title: 'Clôture d\'année scolaire', requiresAuth: true },
    },
    {
      path: '/finances',
      name: 'Finances',
      component: () => import('../views/Finance/FinanceDashboard.vue').catch(() => import('../views/Placeholders/PlaceholderPage.vue')),
      meta: { title: 'Finances', requiresAuth: true },
    },
    {
      path: '/settings',
      name: 'Settings',
      component: () => import('../views/Settings/Settings.vue').catch(() => import('../views/Placeholders/PlaceholderPage.vue')),
      meta: { title: 'Settings', requiresAuth: true },
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
    {
      path: '/payment/:token',
      name: 'PaymentLink',
      component: () => import('../views/public/PaymentLinkPage.vue'),
      props: true
    },

    //espace étudiant 
    {
      path: '/student/login',
      name: 'StudentLogin',
      component: () => import('../views/StudentAccount/LoginPage.vue'),
      meta: { title: 'Connexion Étudiant', studentPublic: true },
    },
    {
      path: '/student/profile',
      name: 'StudentProfile',
      component: () => import('../views/StudentAccount/ProfilePage.vue'),
      meta: { title: 'Mon Espace Étudiant', requiresStudentAuth: true },
    },
  ],
})

export default router

router.beforeEach((to, from, next) => {
  document.title = `Vue.js ${to.meta.title ?? ''} | SplitPay`;

  // Protection espace admin
  if (to.meta.requiresAuth) {
    const token = localStorage.getItem('api_token');
    if (!token) {
      return next({ name: 'Signin', query: { redirect: to.fullPath } });
    }
  }

  // Protection espace étudiant
  if (to.meta.requiresStudentAuth) {
    const token = localStorage.getItem('student_token');
    if (!token) {
      return next({ name: 'StudentLogin' });
    }
  }

  next();
})
