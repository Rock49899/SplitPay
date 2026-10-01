import { createRouter, createWebHistory } from 'vue-router'
import { isSessionStillValid } from '@/middleware/sessionTimeout'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  scrollBehavior(to, from, savedPosition) {
    return savedPosition || { left: 0, top: 0 }
  },
  routes: [
    {
      path: '/platform',
      name: 'PlatformDashboard',
      component: () => import('../views/AdminPlatforme/PlatformDashboard.vue'),
      meta: { title: 'Plateforme', requiresAuth: true, requiresPlatform: true },
    },
    {
      path: '/platform/institutions',
      name: 'PlatformInstitutions',
      component: () => import('../views/AdminPlatforme/PlatformInstitutions.vue'),
      meta: { title: 'Institutions', requiresAuth: true, requiresPlatform: true },
    },
    {
      path: '/platform/annexes',
      name: 'PlatformAnnexes',
      component: () => import('../views/AdminPlatforme/PlatformAnnexes.vue'),
      meta: { title: 'Annexes', requiresAuth: true, requiresPlatform: true },
    },
    {
      path: '/platform/users',
      name: 'PlatformUsers',
      component: () => import('../views/AdminPlatforme/PlatformUsers.vue'),
      meta: { title: 'Utilisateurs plateforme', requiresAuth: true, requiresPlatform: true },
    },
    {
      path: '/platform/settings',
      name: 'PlatformSettings',
      component: () => import('../views/AdminPlatforme/PlatformSettings.vue'),
      meta: { title: 'Paramètres plateforme', requiresAuth: true, requiresPlatform: true },
    },
    {
      path: '/platform/profile',
      name: 'PlatformProfile',
      component: () => import('../views/AdminPlatforme/PlatformProfile.vue'),
      meta: { title: 'Profil plateforme', requiresAuth: true, requiresPlatform: true },
    },
    {
      path: '/platform/institutions/:id',
      name: 'PlatformInstitutionDetails',
      component: () => import('../views/AdminPlatforme/PlatformInstitutionDetails.vue'),
      meta: { title: 'Détail institution', requiresAuth: true, requiresPlatform: true },
    },
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
      path: '/admin/annexe/:id/settings',
      name: 'AnnexeSettings',
      component: () => import('../views/Settings/AnnexeSettings.vue').catch(() => import('../views/Placeholders/PlaceholderPage.vue')),
      meta: { title: 'Paramètres Annexe', requiresAuth: true },
    },
    {
      path: '/admin/institution/settings',
      name: 'InstitutionSettings',
      component: () => import('../views/Settings/InstitutionSettings.vue').catch(() => import('../views/Placeholders/PlaceholderPage.vue')),
      meta: { title: 'Paramètres Institution', requiresAuth: true },
    },
    {
      path: '/admin/notifications',
      name: 'NotificationList',
      component: () => import('../views/Notifications/NotificationList.vue').catch(() => import('../views/Placeholders/PlaceholderPage.vue')),
      meta: { title: 'Notifications', requiresAuth: true },
    },
    {
      path: '/admin/reminders',
      name: 'ReminderList',
      component: () => import('../views/Settings/ReminderList.vue').catch(() => import('../views/Placeholders/PlaceholderPage.vue')),
      meta: { title: 'Rappels Automatiques', requiresAuth: true },
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

// Helper function to get role-based dashboard route
function getRoleDashboard(user) {
  if (!user) return { name: 'Dashboard' };

  const isPlatform =
    user?.is_platform_admin === true
    || user?.scope === 'platform'
    || (Array.isArray(user?.roles) && user.roles.some((r) => r?.code === 'platform_admin'));

  if (isPlatform) {
    return { name: 'PlatformDashboard' };
  }
  
  switch(user.role) {
    case 'super_admin':
    case 'gestionnaire':
    case 'comptable':
      return { name: 'Dashboard' };
    default:
      return { name: 'Dashboard' };
  }
}

router.beforeEach(async (to, from, next) => {
  document.title = to.meta.title ? `${to.meta.title} · SplitPay` : 'SplitPay';

  let token = localStorage.getItem('api_token');
  if (token && !isSessionStillValid()) {
    localStorage.removeItem('api_token');
    localStorage.removeItem('user');
    localStorage.removeItem('session_started_at');
    localStorage.removeItem('session_last_activity_at');
    token = null;
  }
  const userStr = localStorage.getItem('user');
  const user = userStr ? JSON.parse(userStr) : null;
  const isPlatform =
    user?.is_platform_admin === true
    || user?.scope === 'platform'
    || (Array.isArray(user?.roles) && user.roles.some((r) => r?.code === 'platform_admin'));

  // Redirect to dashboard if already authenticated and trying to access signin/signup
  if ((to.name === 'Signin' || to.name === 'Signup') && token) {
    return next(getRoleDashboard(user));
  }

  // Protection espace admin
  if (to.meta.requiresAuth) {
    if (!token) {
      return next({ name: 'Signin', query: { redirect: to.fullPath } });
    }
  }

  // Protection espace plateforme (strictement réservé au platform admin)
  if (to.meta.requiresPlatform) {
    if (!token) {
      return next({ name: 'Signin', query: { redirect: to.fullPath } });
    }
    if (!isPlatform) {
      return next({ name: 'Dashboard' });
    }
  }

  // Empêcher un platform admin d'utiliser l'ancien espace admin standard
  if (token && isPlatform && to.meta.requiresAuth && !to.meta.requiresPlatform) {
    return next({ name: 'PlatformDashboard' });
  }

  // Protection espace étudiant
  if (to.meta.requiresStudentAuth) {
    const studentToken = localStorage.getItem('student_token');
    const studentExpiry = Number(localStorage.getItem('student_token_expires_at') || 0);
    const studentExpired = studentExpiry && Date.now() > studentExpiry;
    if (studentExpired) {
      localStorage.removeItem('student_token');
      localStorage.removeItem('student_token_expires_at');
    }

    if (!studentToken || studentExpired) {
      return next({ name: 'StudentLogin' });
    }
  }

  next();
})
