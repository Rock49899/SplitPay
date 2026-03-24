<template>
  <AdminLayout>
    <PageBreadcrumb 
      pageTitle="Paramètres" 
    />
    
    <div class="mt-6">
      <!-- Header -->
      <div class="mb-8">
        <p class="mt-2 text-gray-600 dark:text-gray-400">
          Configurez et personnalisez votre système de gestion scolaire
        </p>
      </div>

      <!-- Settings Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

        <!-- Institution Settings (Super Admin Institution) -->
        <router-link
          to="/admin/institution/settings"
          v-if="showInstitutionSettings"
          class="group bg-white dark:bg-gray-dark rounded-lg shadow-sm hover:shadow-lg p-6 transition-all border border-gray-200 dark:border-gray-800 hover:border-cyan-500 dark:hover:border-cyan-500"
        >
          <div class="flex items-start gap-4">
            <div class="flex-shrink-0 w-12 h-12 bg-cyan-100 dark:bg-cyan-900/30 rounded-lg flex items-center justify-center text-cyan-600 dark:text-cyan-400 group-hover:scale-110 transition-transform">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21h18M5 21V7l8-4 6 3v15M9 9h.01M9 12h.01M9 15h.01M13 9h.01M13 12h.01M13 15h.01"></path>
              </svg>
            </div>
            <div class="flex-1">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1 group-hover:text-cyan-600 dark:group-hover:text-cyan-400">
                Paramètres Institution
              </h3>
              <p class="text-sm text-gray-600 dark:text-gray-400">
                Modifiez les informations globales et le logo de votre institution
              </p>
              <div class="mt-3 flex items-center gap-2 text-xs text-cyan-600 dark:text-cyan-400 font-medium">
                <span>Modifier</span>
                <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
              </div>
            </div>
          </div>
        </router-link>

        <!-- Annexe Settings (role-scoped) -->
        <router-link
          :to="annexeSettingsRoute"
          v-if="showAnnexeSettings"
          class="group bg-white dark:bg-gray-dark rounded-lg shadow-sm hover:shadow-lg p-6 transition-all border border-gray-200 dark:border-gray-800 hover:border-teal-500 dark:hover:border-teal-500"
        >
          <div class="flex items-start gap-4">
            <div class="flex-shrink-0 w-12 h-12 bg-teal-100 dark:bg-teal-900/30 rounded-lg flex items-center justify-center text-teal-600 dark:text-teal-400 group-hover:scale-110 transition-transform">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10m-2 10H9a2 2 0 01-2-2V7h10v12a2 2 0 01-2 2z"></path>
              </svg>
            </div>
            <div class="flex-1">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1 group-hover:text-teal-600 dark:group-hover:text-teal-400">
                Paramètres Annexe
              </h3>
              <p class="text-sm text-gray-600 dark:text-gray-400">
                Gérez les informations de votre annexe autorisée
              </p>
              <div class="mt-3 flex items-center gap-2 text-xs text-teal-600 dark:text-teal-400 font-medium">
                <span>Modifier</span>
                <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
              </div>
            </div>
          </div>
        </router-link>
        
        <!-- Rappels Automatiques -->
        <router-link
          to="/admin/reminders"
          v-if="hasPermission('reminder.view')"
          class="group bg-white dark:bg-gray-dark rounded-lg shadow-sm hover:shadow-lg p-6 transition-all border border-gray-200 dark:border-gray-800 hover:border-blue-500 dark:hover:border-blue-500"
        >
          <div class="flex items-start gap-4">
            <div class="flex-shrink-0 w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center text-blue-600 dark:text-blue-400 group-hover:scale-110 transition-transform">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
            <div class="flex-1">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1 group-hover:text-blue-600 dark:group-hover:text-blue-400">
                Rappels Automatiques
              </h3>
              <p class="text-sm text-gray-600 dark:text-gray-400">
                Configurez les rappels de paiement envoyés automatiquement aux parents
              </p>
              <div class="mt-3 flex items-center gap-2 text-xs text-blue-600 dark:text-blue-400 font-medium">
                <span>Configurer</span>
                <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
              </div>
            </div>
          </div>
        </router-link>

        <!-- Profile Settings -->
        <router-link
          to="/profile"
          class="group bg-white dark:bg-gray-dark rounded-lg shadow-sm hover:shadow-lg p-6 transition-all border border-gray-200 dark:border-gray-800 hover:border-green-500 dark:hover:border-green-500"
        >
          <div class="flex items-start gap-4">
            <div class="flex-shrink-0 w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center text-green-600 dark:text-green-400 group-hover:scale-110 transition-transform">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
              </svg>
            </div>
            <div class="flex-1">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1 group-hover:text-green-600 dark:group-hover:text-green-400">
                Profil Utilisateur
              </h3>
              <p class="text-sm text-gray-600 dark:text-gray-400">
                Modifiez vos informations personnelles et préférences de compte
              </p>
              <div class="mt-3 flex items-center gap-2 text-xs text-green-600 dark:text-green-400 font-medium">
                <span>Modifier</span>
                <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
              </div>
            </div>
          </div>
        </router-link>

        <!-- Notifications Settings -->
        <router-link
          to="/admin/notifications"
          v-if="hasPermission('notification.view')"
          class="group bg-white dark:bg-gray-dark rounded-lg shadow-sm hover:shadow-lg p-6 transition-all border border-gray-200 dark:border-gray-800 hover:border-purple-500 dark:hover:border-purple-500"
        >
          <div class="flex items-start gap-4">
            <div class="flex-shrink-0 w-12 h-12 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center text-purple-600 dark:text-purple-400 group-hover:scale-110 transition-transform">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
              </svg>
            </div>
            <div class="flex-1">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1 group-hover:text-purple-600 dark:group-hover:text-purple-400">
                Notifications
              </h3>
              <p class="text-sm text-gray-600 dark:text-gray-400">
                Consultez et gérez vos notifications système et alertes
              </p>
              <div class="mt-3 flex items-center gap-2 text-xs text-purple-600 dark:text-purple-400 font-medium">
                <span>Voir tout</span>
                <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
              </div>
            </div>
          </div>
        </router-link>

        <!-- Academic Settings -->
        <router-link
          to="/admin/study-levels"
          v-if="hasPermission('student.view')"
          class="group bg-white dark:bg-gray-dark rounded-lg shadow-sm hover:shadow-lg p-6 transition-all border border-gray-200 dark:border-gray-800 hover:border-orange-500 dark:hover:border-orange-500"
        >
          <div class="flex items-start gap-4">
            <div class="flex-shrink-0 w-12 h-12 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center text-orange-600 dark:text-orange-400 group-hover:scale-110 transition-transform">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
              </svg>
            </div>
            <div class="flex-1">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1 group-hover:text-orange-600 dark:group-hover:text-orange-400">
                Configuration Académique
              </h3>
              <p class="text-sm text-gray-600 dark:text-gray-400">
                Gérez les niveaux d'étude, spécialisations et grilles de frais
              </p>
              <div class="mt-3 flex items-center gap-2 text-xs text-orange-600 dark:text-orange-400 font-medium">
                <span>Configurer</span>
                <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
              </div>
            </div>
          </div>
        </router-link>

        <!-- Users & Roles (Admin only) -->
        <router-link
          to="/admin/users"
          v-if="hasPermission('user.view')"
          class="group bg-white dark:bg-gray-dark rounded-lg shadow-sm hover:shadow-lg p-6 transition-all border border-gray-200 dark:border-gray-800 hover:border-red-500 dark:hover:border-red-500"
        >
          <div class="flex items-start gap-4">
            <div class="flex-shrink-0 w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center text-red-600 dark:text-red-400 group-hover:scale-110 transition-transform">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
              </svg>
            </div>
            <div class="flex-1">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1 group-hover:text-red-600 dark:group-hover:text-red-400">
                Utilisateurs & Rôles
              </h3>
              <p class="text-sm text-gray-600 dark:text-gray-400">
                Gérez les comptes utilisateurs et leurs permissions
              </p>
              <div class="mt-3 flex items-center gap-2 text-xs text-red-600 dark:text-red-400 font-medium">
                <span>Gérer</span>
                <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
              </div>
            </div>
          </div>
        </router-link>

        <!-- Annexes Management (Institution Admin only) -->
        <router-link
          to="/admin/annexes"
          v-if="hasPermission('annexe.view') && !isGestionnaire && !isComptable"
          class="group bg-white dark:bg-gray-dark rounded-lg shadow-sm hover:shadow-lg p-6 transition-all border border-gray-200 dark:border-gray-800 hover:border-indigo-500 dark:hover:border-indigo-500"
        >
          <div class="flex items-start gap-4">
            <div class="flex-shrink-0 w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center text-indigo-600 dark:text-indigo-400 group-hover:scale-110 transition-transform">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
              </svg>
            </div>
            <div class="flex-1">
              <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1 group-hover:text-indigo-600 dark:group-hover:text-indigo-400">
                Gestion des Annexes
              </h3>
              <p class="text-sm text-gray-600 dark:text-gray-400">
                Créez et gérez les différentes annexes de votre institution
              </p>
              <div class="mt-3 flex items-center gap-2 text-xs text-indigo-600 dark:text-indigo-400 font-medium">
                <span>Gérer</span>
                <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
              </div>
            </div>
          </div>
        </router-link>

      </div>

      <!-- Info Box -->
      <div class="mt-8 bg-blue-50 dark:bg-slate-800 border border-blue-200 dark:border-slate-600 rounded-lg p-6">
        <div class="flex gap-4">
          <svg class="w-6 h-6 text-blue-600 dark:text-blue-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          <div class="flex-1">
            <h4 class="font-semibold text-blue-900 dark:text-white mb-2">À propos des paramètres</h4>
            <p class="text-sm text-blue-800 dark:text-gray-300">
              Les options disponibles dépendent de votre rôle et de vos permissions. 
              Si vous ne voyez pas certaines options, contactez votre administrateur système.
            </p>
          </div>
        </div>
      </div>
    </div>
    
  </AdminLayout>
</template>

<script setup>
import { computed } from 'vue';
import AdminLayout from '@/components/layout/AdminLayout.vue';
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue';
import { usePermissions } from '@/composables/usePermissions';

const { hasPermission, hasRole, isGestionnaire, isComptable, currentUser } = usePermissions();

const showInstitutionSettings = computed(() => hasRole('super_admin_institution'));

const principalAnnexeId = computed(() => {
  const annexes = currentUser.value?.annexes || [];
  return annexes.find(a => a.is_principal)?.id || annexes[0]?.id || null;
});

const ownAnnexeId = computed(() => {
  return currentUser.value?.annexe_id || principalAnnexeId.value;
});

const annexeSettingsRoute = computed(() => {
  if (showInstitutionSettings.value) {
    return principalAnnexeId.value ? `/admin/annexe/${principalAnnexeId.value}/settings` : '/settings';
  }
  return ownAnnexeId.value ? `/admin/annexe/${ownAnnexeId.value}/settings` : '/settings';
});

const showAnnexeSettings = computed(() => {
  if (showInstitutionSettings.value) return !!principalAnnexeId.value;
  return (hasRole('super_admin_annexe') || hasRole('admin_annexe')) && !!ownAnnexeId.value;
});
</script>
