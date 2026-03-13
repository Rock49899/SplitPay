<template>
  <aside
    :class="[
      'fixed mt-16 flex flex-col lg:mt-0 top-0 px-5 left-0 bg-white dark:bg-gray-900 dark:border-gray-800 text-gray-900 h-screen transition-all duration-300 ease-in-out z-99999 border-r border-gray-200',
      {
        // small screens: allow full width when mobile menu open, keep 290px on sm+
        'sm:lg:w-[290px] lg:w-[290px]': isExpanded || isMobileOpen || isHovered,
        'lg:w-[90px]': !isExpanded && !isHovered,
        'translate-x-0 w-full sm:w-[290px]': isMobileOpen,
        '-translate-x-full': !isMobileOpen,
        'lg:translate-x-0': true,
      },
    ]"
    @mouseenter="!isExpanded && (isHovered = true)"
    @mouseleave="isHovered = false"
  >
    <div
      :class="[
        'py-8 flex',
        !isExpanded && !isHovered ? 'lg:justify-center' : 'justify-start',
      ]"
    >
      <router-link to="/" class="flex items-center">
        <!-- Logo de l'institution si disponible -->
        <img
          v-if="institutionLogo && (isExpanded || isHovered || isMobileOpen)"
          :src="institutionLogo"
          alt="Logo"
          class="h-12 w-auto object-contain max-w-full"
        />
        <!-- Texte par défaut si pas de logo -->
        <span
          v-else-if="isExpanded || isHovered || isMobileOpen"
          class="text-xl sm:text-2xl lg:text-4xl font-extrabold tracking-tight text-gray-900 dark:text-white overflow-hidden truncate max-w-full"
        >
          {{ institutionName || 'SplitPay' }}
        </span>
        <!-- badge compact quand sidebar réduite -->
        <span
          v-else
          class="inline-flex items-center justify-center h-8 w-8 rounded bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white font-bold overflow-hidden"
          aria-hidden="true"
        >
          <!-- Mini logo ou initiales -->
          <img v-if="institutionLogo" :src="institutionLogo" alt="Logo" class="h-full w-full object-contain" />
          <span v-else>{{ institutionName?.[0] || 'SP' }}</span>
        </span>
      </router-link>
    </div>
    <div
      class="flex flex-col overflow-y-auto duration-300 ease-linear no-scrollbar"
    >
      <nav class="mb-6">
        <div class="flex flex-col gap-4">
          <div v-for="(menuGroup, groupIndex) in menuGroups" :key="groupIndex">
            <h2
              :class="[
                'mb-4 text-xs uppercase flex leading-[20px] text-gray-400',
                !isExpanded && !isHovered
                  ? 'lg:justify-center'
                  : 'justify-start',
              ]"
            >
              <template v-if="isExpanded || isHovered || isMobileOpen">
                {{ menuGroup.title }}
              </template>
              <HorizontalDots v-else />
            </h2>
            <ul class="flex flex-col gap-4">
              <li v-for="(item, index) in menuGroup.items" :key="item.name">
                <button
                  v-if="item.subItems"
                  @click="toggleSubmenu(groupIndex, index)"
                  :class="[
                    'menu-item group w-full',
                    {
                      'menu-item-active': isSubmenuOpen(groupIndex, index),
                      'menu-item-inactive': !isSubmenuOpen(groupIndex, index),
                    },
                    !isExpanded && !isHovered
                      ? 'lg:justify-center'
                      : 'lg:justify-start',
                  ]"
                >
                  <span
                    :class="[
                      isSubmenuOpen(groupIndex, index)
                        ? 'menu-item-icon-active'
                        : 'menu-item-icon-inactive',
                    ]"
                  >
                    <component :is="item.icon" />
                  </span>
                  <span
                    v-if="isExpanded || isHovered || isMobileOpen"
                    class="menu-item-text"
                    >{{ item.name }}</span
                  >
                  <ChevronDownIcon
                    v-if="isExpanded || isHovered || isMobileOpen"
                    :class="[
                      'ml-auto w-5 h-5 transition-transform duration-200',
                      {
                        'rotate-180 text-brand-500': isSubmenuOpen(
                          groupIndex,
                          index
                        ),
                      },
                    ]"
                  />
                </button>
                <router-link
                  v-else-if="item.path"
                  :to="item.path"
                  :class="[
                    'menu-item group',
                    {
                      'menu-item-active': isActive(item.path),
                      'menu-item-inactive': !isActive(item.path),
                    },
                  ]"
                >
                  <span
                    :class="[
                      isActive(item.path)
                        ? 'menu-item-icon-active'
                        : 'menu-item-icon-inactive',
                    ]"
                  >
                    <component :is="item.icon" />
                  </span>
                  <span
                    v-if="isExpanded || isHovered || isMobileOpen"
                    class="menu-item-text"
                    >{{ item.name }}</span
                  >
                </router-link>
                <transition
                  @enter="startTransition"
                  @after-enter="endTransition"
                  @before-leave="startTransition"
                  @after-leave="endTransition"
                >
                  <div
                    v-show="
                      isSubmenuOpen(groupIndex, index) &&
                      (isExpanded || isHovered || isMobileOpen)
                    "
                  >
                    <ul class="mt-2 space-y-1 ml-9">
                      <li v-for="subItem in item.subItems" :key="subItem.name">
                        <router-link
                          :to="subItem.path"
                          :class="[
                            'menu-dropdown-item',
                            {
                              'menu-dropdown-item-active': isActive(
                                subItem.path
                              ),
                              'menu-dropdown-item-inactive': !isActive(
                                subItem.path
                              ),
                            },
                          ]"
                        >
                          {{ subItem.name }}
                          <span class="flex items-center gap-1 ml-auto">
                            <span
                              v-if="subItem.new"
                              :class="[
                                'menu-dropdown-badge',
                                {
                                  'menu-dropdown-badge-active': isActive(
                                    subItem.path
                                  ),
                                  'menu-dropdown-badge-inactive': !isActive(
                                    subItem.path
                                  ),
                                },
                              ]"
                            >
                              new
                            </span>
                            <span
                              v-if="subItem.pro"
                              :class="[
                                'menu-dropdown-badge',
                                {
                                  'menu-dropdown-badge-active': isActive(
                                    subItem.path
                                  ),
                                  'menu-dropdown-badge-inactive': !isActive(
                                    subItem.path
                                  ),
                                },
                              ]"
                            >
                              pro
                            </span>
                          </span>
                        </router-link>
                      </li>
                    </ul>
                  </div>
                </transition>
              </li>
            </ul>
          </div>
        </div>
      </nav>
      <!-- <SidebarWidget v-if="isExpanded || isHovered || isMobileOpen" /> -->
    </div>
  </aside>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import { useRoute } from "vue-router";
import { usePermissions } from "@/composables/usePermissions";
import api from "@/services/api";

import {
  AnnexeIcon,
  GridIcon,
  CalenderIcon,
  UserCircleIcon,
  ChatIcon,
  MailIcon,
  DocsIcon,
  PieChartIcon,
  ChevronDownIcon,
  HorizontalDots,
  PageIcon,
  TableIcon,
  ListIcon,
  PlugInIcon,
  BellIcon,
} from "../../icons";
// import SidebarWidget from "./SidebarWidget.vue";
import BoxCubeIcon from "@/icons/BoxCubeIcon.vue";
import { useSidebar } from "@/composables/useSidebar";

const route = useRoute();
const { hasPermission, isGestionnaire, isComptable, user } = usePermissions();

const { isExpanded, isMobileOpen, isHovered, openSubmenu } = useSidebar();

// Variables pour le logo et le nom de l'institution
const institutionLogo = ref(null)
const institutionName = ref('')

// Charger les informations de l'institution
const fetchInstitutionInfo = async () => {
  try {
    // Récupérer l'utilisateur actuel
    const meResponse = await api.get('/admin/me').catch(() => api.get('/me'))
    const currentUser = meResponse.data?.user ?? meResponse.data
    
    if (currentUser?.annexe_id) {
      // Récupérer l'annexe
      const annexeResponse = await api.get(`/admin/annexes/${currentUser.annexe_id}`)
      const annexe = annexeResponse.data?.annexe ?? annexeResponse.data
      
      if (annexe?.institution_id) {
        // Récupérer l'institution
        const institutionResponse = await api.get(`/admin/institutions/${annexe.institution_id}`)
        const institution = institutionResponse.data?.institution ?? institutionResponse.data
        
        institutionName.value = institution.name ?? ''
        institutionLogo.value = institution.logo ? `/storage/${institution.logo}` : null
      }
    }
  } catch (error) {
    console.error('Error fetching institution info:', error)
  }
}

const handleBrandUpdated = () => {
  fetchInstitutionInfo()
}

onMounted(() => {
  fetchInstitutionInfo()
  window.addEventListener('institution-brand-updated', handleBrandUpdated)
})

onUnmounted(() => {
  window.removeEventListener('institution-brand-updated', handleBrandUpdated)
})

const baseMenuGroups = [
  {
    title: "Menu",
    items: [
      {
        icon: GridIcon,
        name: "Tableau de bord",
        path: "/",
        requiredPermission: "dashboard.view"
      },
      {
        icon: UserCircleIcon,
        name: "Profil utilisateur",
        path: "/profile",
        // No permission required
      },

      {
        icon: UserCircleIcon,
        name: "Utilisateurs",
        path: "/admin/users",
        requiredPermission: "user.view"
      },
      {
        icon: ListIcon,
        name: "Étudiants",
        path: "/admin/students",
        requiredPermission: "student.view"
      },
      {
        icon: AnnexeIcon,
        name: "Annexes",
        path: "/admin/annexes",
        requiredPermission: "annexe.view",
        hideForRoles: ['gestionnaire', 'comptable'] // Cache pour ces rôles même s'ils ont la permission
      },
      {
        icon: DocsIcon,
        name: "Académique",
        requiredPermission: "student.view",
        subItems: [
          { name: "Niveaux d'étude", path: "/admin/study-levels", requiredPermission: "student.view", pro: false },
          { name: "Spécialisations", path: "/admin/specializations", requiredPermission: "student.view", pro: false },
          { name: "Clôture de l'année", path: "/admin/school-year/close", requiredPermission: "student.edit", pro: false },
          // { name: "Classes", path: "/admin/classes", requiredPermission: "student.view", pro: false },
        ],
      },
      {
        icon: PieChartIcon,
        name: "Paiements",
        path: "/finances",
        requiredPermission: "payment.view"
      },
      {
        icon: BellIcon,
        name: "Notifications",
        path: "/admin/notifications",
        requiredPermission: "notification.view"
      },
      {
        icon: CalenderIcon,
        name: "Rappels",
        path: "/admin/reminders",
        requiredPermission: "reminder.view"
      },
      {
        icon: PlugInIcon,
        name: "Paramètres",
        path: "/settings",
        // No permission required
      },

    ],
  },
  
];

// Filter menu items based on permissions
const menuGroups = computed(() => {
  return baseMenuGroups.map(group => ({
    ...group,
    items: group.items.filter(item => {
      // Check if item should be hidden for specific roles
      if (item.hideForRoles) {
        if ((item.hideForRoles.includes('gestionnaire') && isGestionnaire.value) ||
            (item.hideForRoles.includes('comptable') && isComptable.value)) {
          return false;
        }
      }
      
      // No permission required - always show
      if (!item.requiredPermission) return true;
      
      // Check if user has required permission
      if (!hasPermission(item.requiredPermission)) return false;
      
      // If item has subitems, filter them too
      if (item.subItems) {
        const filteredSubItems = item.subItems.filter(subItem => 
          !subItem.requiredPermission || hasPermission(subItem.requiredPermission)
        );
        
        // Hide parent if no subitems remain
        if (filteredSubItems.length === 0) return false;
        
        // Update subitems with filtered list
        item.subItems = filteredSubItems;
      }
      
      return true;
    })
  }))
  .filter(group => group.items.length > 0); // Remove empty groups
});

const isActive = (path) => route.path === path;

const toggleSubmenu = (groupIndex, itemIndex) => {
  const key = `${groupIndex}-${itemIndex}`;
  openSubmenu.value = openSubmenu.value === key ? null : key;
};

const isAnySubmenuRouteActive = computed(() => {
  return menuGroups.value.some((group) =>
    group.items.some(
      (item) =>
        item.subItems && item.subItems.some((subItem) => isActive(subItem.path))
    )
  );
});

const isSubmenuOpen = (groupIndex, itemIndex) => {
  const key = `${groupIndex}-${itemIndex}`;
  return (
    openSubmenu.value === key ||
    (isAnySubmenuRouteActive.value &&
      menuGroups.value[groupIndex].items[itemIndex].subItems?.some((subItem) =>
        isActive(subItem.path)
      ))
  );
};

const startTransition = (el) => {
  el.style.height = "auto";
  const height = el.scrollHeight;
  el.style.height = "0px";
  el.offsetHeight; // force reflow
  el.style.height = height + "px";
};

const endTransition = (el) => {
  el.style.height = "";
};
</script>
