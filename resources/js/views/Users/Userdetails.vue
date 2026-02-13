<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="`User: ${user?.name || '...'} `"/>
    <div class="space-y-5 sm:space-y-6">
      <ComponentCard title="User details">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
          <!-- Name -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">Name</label>
            <template v-if="!editMode">
              <p class="mt-1 text-gray-900">{{ form.name || '—' }}</p>
            </template>
            <template v-else>
              <input v-model="form.name" class="mt-1 block w-full rounded-md border px-3 py-2 text-white bg-gray-800 placeholder:text-gray-400" />
            </template>
          </div>

          <!-- Email -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">Email</label>
            <template v-if="!editMode">
              <p class="mt-1 text-gray-900">{{ form.email || '—' }}</p>
            </template>
            <template v-else>
              <input v-model="form.email" class="mt-1 block w-full rounded-md border px-3 py-2 text-white bg-gray-800 placeholder:text-gray-400" />
            </template>
          </div>

          <!-- Phone -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">Phone</label>
            <template v-if="!editMode">
              <p class="mt-1 text-gray-900">{{ form.phone || '—' }}</p>
            </template>
            <template v-else>
              <input v-model="form.phone" class="mt-1 block w-full rounded-md border px-3 py-2 text-white bg-gray-800 placeholder:text-gray-400" />
            </template>
          </div>

          <!-- Scope -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">Scope</label>
            <p class="mt-1 text-gray-900">{{ form.scope || '—' }}</p>
          </div>

          <!-- Annexes -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">Annexes</label>
            <template v-if="!editMode">
              <p class="mt-1 text-gray-900">{{ annexeNames(user) || 'No annexes' }}</p>
            </template>
            <template v-else>
              <div class="mt-1 space-y-2 max-h-48 overflow-auto p-2 border rounded bg-gray-50 dark:bg-gray-800">
                <div v-for="a in annexes" :key="a.id" class="flex items-center gap-2">
                  <input
                    type="checkbox"
                    :id="`ann-edit-${a.id}`"
                    :checked="hasAnnexe(user, a.id)"
                    @change="toggleAnnexe($event.target.checked, a.id)"
                  />
                  <label :for="`ann-edit-${a.id}`" class="flex-1 text-gray-900 dark:text-white">{{ a.name }}</label>

                  <button
                    v-if="hasAnnexe(user, a.id) && !isPrimaryAnnexe(user, a.id)"
                    @click.prevent="setPrimaryAnnexe(a.id)"
                    class="ml-2 text-xs px-2 py-1 border rounded text-gray-700 dark:text-gray-200"
                  >
                    Set primary
                  </button>

                  <span v-else-if="isPrimaryAnnexe(user, a.id)" class="ml-2 text-xs text-brand-500">Primary</span>
                </div>
              </div>
            </template>
          </div>

          <!-- Active -->
          <div class="flex items-center gap-3">
            <template v-if="!editMode">
              <span :class="form.is_active ? 'text-green-600' : 'text-red-600'">
                {{ form.is_active ? 'Active' : 'Inactive' }}
              </span>
            </template>
            <template v-else>
              <input type="checkbox" id="is_active" v-model="form.is_active" class="h-4 w-4" />
              <label for="is_active" class="text-sm text-gray-700 dark:text-gray-400">Active</label>
            </template>
          </div>
        </div>

        <div class="mt-6 flex items-center gap-3">
          <button v-if="!editMode" @click="enterEdit" class="edit-button">
          <svg
            class="fill-current"
            width="18"
            height="18"
            viewBox="0 0 18 18"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
          >
            <path
              fill-rule="evenodd"
              clip-rule="evenodd"
              d="M15.0911 2.78206C14.2125 1.90338 12.7878 1.90338 11.9092 2.78206L4.57524 10.116C4.26682 10.4244 4.0547 10.8158 3.96468 11.2426L3.31231 14.3352C3.25997 14.5833 3.33653 14.841 3.51583 15.0203C3.69512 15.1996 3.95286 15.2761 4.20096 15.2238L7.29355 14.5714C7.72031 14.4814 8.11172 14.2693 8.42013 13.9609L15.7541 6.62695C16.6327 5.74827 16.6327 4.32365 15.7541 3.44497L15.0911 2.78206ZM12.9698 3.84272C13.2627 3.54982 13.7376 3.54982 14.0305 3.84272L14.6934 4.50563C14.9863 4.79852 14.9863 5.2734 14.6934 5.56629L14.044 6.21573L12.3204 4.49215L12.9698 3.84272ZM11.2597 5.55281L5.6359 11.1766C5.53309 11.2794 5.46238 11.4099 5.43238 11.5522L5.01758 13.5185L6.98394 13.1037C7.1262 13.0737 7.25666 13.003 7.35947 12.9002L12.9833 7.27639L11.2597 5.55281Z"
              fill=""
            />
          </svg>
          Edit
        </button>
          <button v-else @click="save" :disabled="saving" class="px-4 py-2 bg-brand-500 text-white rounded disabled:opacity-50">
            <span v-if="!saving">Save</span><span v-else>Saving...</span>
          </button>
          <button v-if="editMode" @click="cancelEdit" class="px-4 py-2 border rounded">Cancel</button>
          <button @click="goBack" class="ml-auto px-4 py-2 border rounded">Back</button>
        </div>
      </ComponentCard>

      <ComponentCard title="Roles & Permissions">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">Assign role</label>
            <select v-model="selectedRole" class="mt-1 block w-full rounded-md border px-3 py-2 text-sm bg-gray-800 text-white">
              <option value="">-- select role --</option>
              <option v-for="r in roles" :key="r.id" :value="r.id">{{ roleLabel(r) }}</option>
            </select>
          </div>

          <div class="flex items-end gap-3">
            <label class="inline-flex items-center">
              <input type="checkbox" v-model="isPrimary" class="form-checkbox" />
              <span class="ml-2 text-sm text-gray-700 dark:text-gray-400">Primary</span>
            </label>
            <button @click="assignRole" :disabled="assigning || !selectedRole" class="px-4 py-2 bg-brand-500 text-white rounded disabled:opacity-50">
              <span v-if="!assigning">Assign</span>
              <span v-else>Assigning...</span>
            </button>
          </div>
        </div>

        <div class="mt-4">
          <h4 class="text-sm font-medium text-gray-700 dark:text-gray-400 mb-2">Current roles</h4>
          <ul class="space-y-2">
            <li v-for="r in rolesList" :key="r.id" class="flex items-center justify-between bg-gray-50 rounded p-2">
              <div>
                <div class="font-medium text-sm text-gray-900">{{ r.name }}</div>
                <div class="text-xs text-gray-500">{{ r.code ?? '' }}</div>
              </div>
              <button @click="removeRole(r.id)" class="text-red-500 text-sm">Remove</button>
            </li>
            <li v-if="!rolesList.length" class="text-sm text-gray-500">No roles</li>
          </ul>
        </div>
      </ComponentCard>

      <div v-if="error" class="text-sm text-red-600 mt-2">{{ error }}</div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AdminLayout from '@/components/layout/AdminLayout.vue';
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue';
import ComponentCard from '@/components/common/ComponentCard.vue';
import userService from '@/services/userService';
import { useRoleStore } from '@/stores/useRoleStore';
import { useAnnexeStore } from '@/stores/useAnnexeStore';

const roleStore = useRoleStore();
const annexeStore = useAnnexeStore();

// defensive initialization: avoid runtime ReferenceError if useRoute/useRouter not available
const _route = typeof useRoute === 'function' ? useRoute() : { params: {} };
const _router = typeof useRouter === 'function' ? useRouter() : { push: () => {} };
const route = _route;
const router = _router;
const id = route?.params?.id ?? null;

const user = ref(null);
const originalUser = ref(null);
const roles = ref([]);
const annexes = ref([]);
const selectedRole = ref('');
const isPrimary = ref(false);
const form = ref({
  name: '',
  email: '',
  phone: '',
  scope: '',
  is_active: true,
});
const loading = ref(false);
const saving = ref(false);
const assigning = ref(false);
const error = ref(null);
const editMode = ref(false);

const roleLabel = (r) => {
  if (!r) return '';
  return r.name ?? r.title ?? r.display_name ?? r.label ?? r.code ?? r.id ?? '';
};

const load = async () => {
  loading.value = true;
  error.value = null;
  if (!id) { error.value='No user id provided'; loading.value=false; return; }
  try {
    const res = await userService.show(id);
    const payload = res.data;
    const u = payload.user ?? payload.data ?? payload;
    user.value = u;
    originalUser.value = JSON.parse(JSON.stringify(u));
    form.value = { name: u.name ?? '', email: u.email ?? '', phone: u.phone ?? '', scope: u.scope ?? '', is_active: u.is_active ?? true };

    // roles via store
    await roleStore.fetchRoles();
    roles.value = roleStore.items;
    // annexes via store
    await annexeStore.fetchAnnexes();
    annexes.value = annexeStore.items;
    // choose default selectedRole...
  } catch (e) {
    console.error('Failed to load user details', e);
    const status = e?.response?.status;
    if (status === 401) {
      router.push('/signin');
      return;
    }
    error.value = e.response?.data?.message || JSON.stringify(e.response?.data) || e.message || 'Failed to load user';
  } finally {
    loading.value = false;
  }
};

const enterEdit = () => {
  editMode.value = true;
  error.value = null;
};

const cancelEdit = () => {
  if (originalUser.value) {
    form.value = {
      name: originalUser.value.name ?? '',
      email: originalUser.value.email ?? '',
      phone: originalUser.value.phone ?? '',
      scope: originalUser.value.scope ?? '',
      is_active: originalUser.value.is_active ?? true,
    };
  }
  editMode.value = false;
};

const save = async () => {
  saving.value = true;
  error.value = null;
  try {
    await userService.update(id, form.value);
    await load();
    editMode.value = false;
  } catch (e) {
    error.value = e.response?.data?.message || e.message || 'Update failed';
  } finally {
    saving.value = false;
  }
};

const assignRole = async () => {
  if (!selectedRole.value) return;
  assigning.value = true;
  error.value = null;
  try {
    await api.post(`admin/users/${id}/assign-role`, {
      annexe_id: user.value.annexe?.id ?? null,
      role_id: selectedRole.value,
      is_primary: isPrimary.value ?? false,
    });
    await load();
    selectedRole.value = '';
    isPrimary.value = false;
  } catch (e) {
    error.value = e.response?.data?.message || e.message || 'Assign role failed';
  } finally {
    assigning.value = false;
  }
};

const removeRole = async (roleId) => {
  if (!confirm('Remove role?')) return;
  try {
    await api.post(`admin/users/${id}/remove-role`, {
      annexe_id: user.value.annexe?.id ?? null,
      role_id: roleId,
    });
    await load();
  } catch (e) {
    error.value = e.response?.data?.message || e.message || 'Remove failed';
  }
};

const goBack = () => router.push('/admin/users');

const rolesList = computed(() => {
  if (!user.value) return [];
  if (Array.isArray(user.value.user_annexes) && user.value.user_annexes.length) {
    return user.value.user_annexes.map(ua => ua.role ?? {
      id: ua.role_id ?? ua.id ?? String(Math.random()),
      name: ua.role_name ?? ua.role?.name ?? ua.role_code ?? 'role',
      code: ua.role?.code ?? ua.role_code ?? '',
    });
  }
  if (Array.isArray(user.value.roles)) return user.value.roles;
  return [];
});

// helpers for annexes and roles
const annexeNames = (u) => {
  if (!u) return '';
  if (Array.isArray(u.user_annexes) && u.user_annexes.length) {
    const mapped = u.user_annexes.map(ua => {
      const ann = ua.annexe ?? ua.annexe_data ?? (ua.annexe_name ? { name: ua.annexe_name } : null);
      return { name: ann?.name ?? ua.annexe_name ?? ua.name ?? ua.annexe_id ?? null, primary: !!ua.is_primary };
    }).filter(a => a.name);
    mapped.sort((a,b) => (b.primary === true ? 1 : 0) - (a.primary === true ? 1 : 0));
    return [...new Set(mapped.map(m => m.name))].join(', ');
  }
  if (Array.isArray(u.annexes) && u.annexes.length) {
    const primaryFirst = [...u.annexes].sort((a,b) => (b.is_primary ? 1:0) - (a.is_primary ? 1:0));
    return primaryFirst.map(a => a.name ?? a.id).filter(Boolean).join(', ');
  }
  if (u.annexe && (u.annexe.name || u.annexe.id)) {
    return u.annexe.name ?? u.annexe.id;
  }
  return '';
};

const roleNames = (u) => {
  if (!u) return '';
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

const hasAnnexe = (u, annId) => {
  if (!u) return false;
  if (Array.isArray(u.user_annexes)) return u.user_annexes.some(ua => (ua.annexe_id ?? ua.annexe?.id) === annId);
  if (Array.isArray(u.annexes)) return u.annexes.some(a => a.id === annId);
  if (u.annexe) return (u.annexe.id === annId);
  return false;
};

const isPrimaryAnnexe = (u, annId) => {
  if (!u) return false;
  if (Array.isArray(u.user_annexes)) {
    const ua = u.user_annexes.find(x => (x.annexe_id ?? x.annexe?.id) === annId);
    return !!(ua && ua.is_primary);
  }
  if (u.annexe) return (u.annexe.id === annId);
  return false;
};

const toggleAnnexe = async (checked, annId) => {
  if (!user.value) return;
  if (checked) {
    const roleToUse = selectedRole.value || (user.value.roles && user.value.roles[0]?.id);
    if (!roleToUse) {
      alert('Select a role first to assign this user to an annexe.');
      await load();
      return;
    }
    try {
      await api.post(`admin/users/${id}/assign-role`, {
        annexe_id: annId,
        role_id: roleToUse,
        is_primary: false,
      });
      await load();
    } catch (e) {
      console.error('assign annexe failed', e);
      alert(e.response?.data?.message || e.message || 'Assign failed');
      await load();
    }
  } else {
    let roleId = null;
    if (Array.isArray(user.value.user_annexes)) {
      const ua = user.value.user_annexes.find(x => (x.annexe_id ?? x.annexe?.id) === annId);
      roleId = ua?.role_id ?? ua?.role?.id ?? null;
    }
    if (!roleId) roleId = selectedRole.value || user.value.roles?.[0]?.id;
    if (!roleId) {
      alert('Cannot remove annexe: role unknown.');
      await load();
      return;
    }
    try {
      await api.post(`admin/users/${id}/remove-role`, {
        annexe_id: annId,
        role_id: roleId,
      });
      await load();
    } catch (e) {
      console.error('remove annexe failed', e);
      alert(e.response?.data?.message || e.message || 'Remove failed');
      await load();
    }
  }
};

const setPrimaryAnnexe = async (annId) => {
  if (!user.value) return;
  if (!hasAnnexe(user.value, annId)) {
    const roleToUse = selectedRole.value || user.value.roles?.[0]?.id;
    if (!roleToUse) {
      alert('Select a role first to assign primary annexe.');
      return;
    }
    try {
      await api.post(`admin/users/${id}/assign-role`, {
        annexe_id: annId,
        role_id: roleToUse,
        is_primary: true,
      });
      await load();
    } catch (e) {
      console.error('assign primary failed', e);
      alert(e.response?.data?.message || e.message || 'Assign primary failed');
      await load();
    }
    return;
  }
  let roleId = null;
  if (Array.isArray(user.value.user_annexes)) {
    const ua = user.value.user_annexes.find(x => (x.annexe_id ?? x.annexe?.id) === annId);
    roleId = ua?.role_id ?? ua?.role?.id ?? null;
  }
  if (!roleId) roleId = selectedRole.value || user.value.roles?.[0]?.id;
  if (!roleId) {
    alert('Cannot set primary: role unknown.');
    return;
  }
  try {
    await api.post(`admin/users/${id}/assign-role`, {
      annexe_id: annId,
      role_id: roleId,
      is_primary: true,
    });
    await load();
  } catch (e) {
    console.error('set primary failed', e);
    alert(e.response?.data?.message || e.message || 'Set primary failed');
    await load();
  }
};

onMounted(load);
</script>

<style scoped>
/* force option styles for dark select dropdowns (inline as fallback) */
select option {
  color: #ffffff !important;
  background-color: #1f2937 !important; /* Tailwind gray-800 */
}
/* placeholder option may be shown in white too */
select option[value=""] {
  color: #ffffff !important;
  background-color: #1f2937 !important;
}
</style>
