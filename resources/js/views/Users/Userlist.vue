<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="currentPageTitle" />
    <div class="flex justify-end mb-4">
      <button @click="showCreateModal = true" class="px-4 py-2 bg-brand-500 text-white rounded">Create User</button>
    </div>
    <CreateUser v-if="showCreateModal" :roles="roles" :annexes="annexes" @created="onCreated" @close="showCreateModal = false" />
    <div class="space-y-5 sm:space-y-6">
      <ComponentCard v-for="(usersInAnnexe, annexeName) in groupedByAnnexe" :key="annexeName" :title="`Users — ${annexeName}`">
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
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ user.name }}</td>
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
import { ref, onMounted, computed } from "vue";
import PageBreadcrumb from "@/components/common/PageBreadcrumb.vue";
import AdminLayout from "@/components/layout/AdminLayout.vue";
import ComponentCard from "@/components/common/ComponentCard.vue";
import { useUserStore } from "@/stores/useUserStore";
import { useRouter } from 'vue-router';
import CreateUser from '@/components/user/CreateUser.vue';
import roleService from '@/services/roleService';
import annexeService from '@/services/annexeService';

const currentPageTitle = ref("Users");
const users = useUserStore();
const router = useRouter();

// modal + lookup state
const showCreateModal = ref(false);
const roles = ref([]);
const annexes = ref([]);

// ensure page defined
users.page = users.page || 1;

onMounted(async () => {
  try {
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
    // if unauthorized, redirect to signin; otherwise rethrow/log
    const status = e?.response?.status;
    if (status === 401) {
      router.push('/signin');
      return;
    }
    console.error('Failed fetching users', e);
  }
});

const onCreated = async (created) => {
  // refresh list after new user created
  await users.fetchUsers();
  showCreateModal.value = false;
  // optional: navigate to detail
  // router.push(`/admin/users/${created.id}`);
};

const groupedByAnnexe = computed(() => {
  const map = {};
  (users.items || []).forEach((u) => {
    const ann = (u.annexe && (u.annexe.name || u.annexe.id)) || "No Annexe";
    if (!map[ann]) map[ann] = [];
    map[ann].push(u);
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
