<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="currentPageTitle" />
    <div class="flex justify-start mb-4">
      <button @click="showCreateModal = true" class="px-4 py-2 bg-brand-500 text-white rounded">Create User</button>
    </div>
    <CreateUser v-if="showCreateModal" :roles="roles" :annexes="annexes" @created="onCreated" @close="showCreateModal = false" />
    <div class="space-y-5 sm:space-y-6">
      <ComponentCard v-for="(usersInAnnexe, annexeName) in groupedByAnnexe" :key="annexeName" :title="`${annexeName}`">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Annexes</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role(s)</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="user in (usersInAnnexe || [])" :key="user.id">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  <div class="flex items-center gap-2.5">
                    <AvatarDisplay :src="user.avatar_url" :label="user.name" :size="32" />
                    <span>{{ user.name }}</span>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ user.email }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ annexeNames(user) || '-' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ roleNames(user) || '-' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                  <span :class="user.is_active ? 'text-green-600' : 'text-red-600'">
                    {{ user.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                  <router-link :to="`/admin/users/${user.id}`" class="text-brand-500 hover:underline mr-3">View</router-link>
                  <button @click="remove(user.id)" class="text-red-500 hover:underline">Delete</button>
                </td>
              </tr>
              <tr v-if="!usersInAnnexe || usersInAnnexe.length === 0">
                <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">No users</td>
              </tr>
            </tbody>
          </table>
        </div>
      </ComponentCard>

      <!-- pagination controls (global for the store) -->
      <div class="flex items-center justify-between">
        <div></div>
        <div class="flex items-center gap-2">
          <button @click="prevPage" :disabled="users.page <= 1" class="px-3 py-1 border rounded">Prev</button>
          <span>Page {{ users.page }}</span>
          <button @click="nextPage" :disabled="users.meta && users.page >= users.meta.last_page" class="px-3 py-1 border rounded">Next</button>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import PageBreadcrumb from "@/components/common/PageBreadcrumb.vue";
import AdminLayout from "@/components/layout/AdminLayout.vue";
import ComponentCard from "@/components/common/ComponentCard.vue";
import { useUserStore } from "@/stores/useUserStore";
import { useRouter, useRoute } from 'vue-router';
import CreateUser from '@/components/user/CreateUser.vue';
import AvatarDisplay from '@/components/shared/AvatarDisplay.vue';
import roleService from '@/services/roleService';
import annexeService from '@/services/annexeService';

const currentPageTitle = ref("Users");
const users = useUserStore();
const router = useRouter();
const route = useRoute();

// modal + lookup state
const showCreateModal = ref(false);
const roles = ref([]);
const annexes = ref([]);

// ensure page defined
users.page = users.page || 1;

onMounted(async () => {
  try {
    users.setQuery(route.query.search ?? '');
    await users.fetchUsers();
    // load roles & annexes used by modal
    try {
      const rr = await roleService.index();
      roles.value = rr.data?.data ?? rr.data ?? [];
    } catch (e) { console.error('roles load', e); }
    try {
      const ar = await annexeService.index();
      annexes.value = ar.data?.data ?? ar.data ?? [];
    } catch (e) { console.error('annexes load', e); }
  } catch (e) {
    const status = e?.response?.status;
    if (status === 401) {
      router.push('/signin');
      return;
    }
    console.error('Failed fetching users', e);
  }
});

// NEW: react to URL search param changes (live search)
watch(
  () => route.query.search,
  async (newSearch) => {
    try {
      users.setQuery(newSearch ?? '');
      users.setPage(1);
      await users.fetchUsers();
    } catch (e) {
      console.error('Users search fetch failed', e);
      // optional: user message
      // alert('Search failed. Please try again or check server logs.');
    }
  },
  { immediate: false }
);

const onCreated = async (created) => {
  // refresh list after new user created
  await users.fetchUsers();
  showCreateModal.value = false;
  // optional: navigate to detail
  // router.push(`/admin/users/${created.id}`);
};

const groupedByAnnexe = computed(() => {
  const map = {};
  const items = Array.isArray(users.items) ? users.items : [];
  items.forEach((u) => {
    // Try primary annexe first
    let annexeKey = null;
    
    // Check for primary annexe from user_annexes
    if (Array.isArray(u.user_annexes) && u.user_annexes.length > 0) {
      const primary = u.user_annexes.find(ua => ua.is_principal);
      if (primary && primary.annexe) {
        annexeKey = primary.annexe.name || primary.annexe.id || "No Annexe";
      } else {
        // No primary, use first annexe
        const first = u.user_annexes[0];
        if (first && first.annexe) {
          annexeKey = first.annexe.name || first.annexe.id || "No Annexe";
        }
      }
    }
    
    // Fallback to direct annexe relation
    if (!annexeKey && u.annexe) {
      annexeKey = u.annexe.name || u.annexe.id || "No Annexe";
    }
    
    // Final fallback
    if (!annexeKey) {
      annexeKey = "No Annexe";
    }
    
    if (!map[annexeKey]) map[annexeKey] = [];
    map[annexeKey].push(u);
  });
  // keep consistent ordering (optional)
  return map;
});

const prevPage = async () => {
  if (users.page > 1) {
    users.setPage(users.page - 1);
    await users.fetchUsers();
  }
};
const nextPage = async () => {
  if (!users.meta || !users.meta.last_page || users.page < users.meta.last_page) {
    users.setPage(users.page + 1);
    await users.fetchUsers();
  }
};

const remove = async (id) => {
  if (!confirm("Delete this user?")) return;
  await users.deleteUser(id);
};


const annexeNames = (u) => {
  if (!u) return '';
  // If pivot exists and contains annexe info
  if (Array.isArray(u.user_annexes) && u.user_annexes.length) {
    const mapped = u.user_annexes.map(ua => {
      const ann = ua.annexe ?? ua.annexe_data ?? (ua.annexe_name ? { name: ua.annexe_name } : null);
      return {
        name: ann?.name ?? ua.annexe_name ?? ua.name ?? ua.annexe_id ?? null,
        primary: !!ua.is_primary,
      };
    }).filter(a => a.name);
    // sort primary first
    mapped.sort((a,b) => (b.primary === true) - (a.primary === true));
    return [...new Set(mapped.map(m => m.name))].join(', ');
  }

  if (Array.isArray(u.annexes) && u.annexes.length) {
    // prefer primary flag if exists on annexe objects
    const primaryFirst = [...u.annexes].sort((a,b) => (b.is_primary ? 1:0) - (a.is_primary ? 1:0));
    return primaryFirst.map(a => a.name ?? a.id).filter(Boolean).join(', ');
  }

  // single annexe relation
  if (u.annexe && (u.annexe.name || u.annexe.id)) {
    return u.annexe.name ?? u.annexe.id;
  }

  if (u.annexe_id) {
    const found = (annexes || []).find?.(a => String(a.id) === String(u.annexe_id));
    if (found) return found.name ?? found.id;
  }

  if (Array.isArray(u.annexe_ids) && u.annexe_ids.length) {
    const names = u.annexe_ids.map(id => {
      const f = (annexes || []).find?.(a => String(a.id) === String(id));
      return f ? (f.name ?? f.id) : null;
    }).filter(Boolean);
    if (names.length) return [...new Set(names)].join(', ');
  }

  return '';
};

const roleNames = (u) => {
  if (!u) return '';
  // pivot user_annexes may include role object or role_name
  if (Array.isArray(u.user_annexes) && u.user_annexes.length) {
    const names = u.user_annexes.map(ua => {
      if (ua.role && (ua.role.name || ua.role.code)) return ua.role.name ?? ua.role.code;
      return ua.role_name ?? ua.role?.name ?? ua.role_code ?? null;
    }).filter(Boolean);
    if (names.length) return [...new Set(names)].join(', ');
  }
  if (Array.isArray(u.roles) && u.roles.length) {
    return u.roles.map(r => r.name ?? r.title ?? r.code ?? r.id).filter(Boolean).join(', ');
  }
  return '';
};
</script>
