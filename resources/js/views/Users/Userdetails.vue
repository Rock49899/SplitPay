<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="`User: ${user?.name || '...'} `"/>
    <div class="space-y-5 sm:space-y-6 ">
      <ComponentCard :title="`User details`">
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

          <!-- Annexe (show all annexes user belongs to) -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">Annexes</label>
            <p class="mt-1 text-gray-900">{{ annexeNames(user) || 'No annexes' }}</p>
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
          <button v-if="!editMode" @click="enterEdit" class="px-4 py-2 bg-brand-500 text-white rounded">Edit</button>
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
            <select
              v-model="selectedRole"
              class="mt-1 block w-full rounded-md border px-3 py-2 text-sm bg-gray-800 text-white"
            >
              <option value="">-- select role --</option>
              <option
                v-for="r in roles"
                :key="r.id"
                :value="r.id"
              >
                {{ roleLabel(r) }}
              </option>
            </select>

            <!-- Visible debug: available roles (ensures roles are actually loaded) -->
            <!-- <div class="mt-2 text-sm">
              <div class="text-gray-500 dark:text-gray-400">Available roles ({{ roles.length }}):</div>
              <div class="mt-1 flex flex-wrap gap-2">
                <span v-for="r in roles" :key="r.id" class="px-2 py-1 rounded bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-white text-xs">
                  {{ roleLabel(r) }}
                </span>
                <span v-if="!roles.length" class="text-gray-400">No roles loaded</span>
              </div>

               <pre v-if="roles.length" class="mt-2 p-2 bg-gray-50 dark:bg-gray-800 text-xs text-gray-700 dark:text-gray-200 overflow-auto max-h-48">
                {{ JSON.stringify(roles, null, 2) }}
              </pre> -->
            <!-- </div> -->
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
            <li v-for="r in rolesList" :key="r.id" class="flex items-center justify-between  bg-gray-50 rounded">
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

      <!-- Annexes membership editor
      <ComponentCard title="Annexes Membership">
        <div class="text-sm text-gray-600 dark:text-gray-300 mb-2">Manage annexes for this user. Select annexes to add/remove. Choose a role (above) to assign when adding.</div>
        <div class="space-y-2 max-h-56 overflow-auto p-2 border rounded bg-gray-50 dark:bg-gray-800">
          <div v-for="a in annexes" :key="a.id" class="flex items-center gap-3">
            <input
              type="checkbox"
              :id="`ann-${a.id}`"
              :checked="hasAnnexe(user, a.id)"
              @change="toggleAnnexe($event.target.checked, a.id)"
            />
            <label :for="`ann-${a.id}`" class="flex-1 text-gray-900 dark:text-white">{{ a.name }}</label>
            <input
              type="radio"
              name="primaryAnnexeEdit"
              :value="a.id"
              :checked="isPrimaryAnnexe(user, a.id)"
              @change="setPrimaryAnnexe(a.id)"
              :disabled="!hasAnnexe(user, a.id)"
            />
          </div>
        </div>
      </ComponentCard> -->

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
import roleService from '@/services/roleService';
import api from '@/services/api';
import annexeService from '@/services/annexeService';

const route = useRoute();
const router = useRouter();
const id = route.params.id;

const user = ref(null);
const originalUser = ref(null); // to revert cancel
const roles = ref([]);

// list of all annexes available for editor
const annexes = ref([]);

const roleLabel = (r) => {
  if (!r) return '';
  return r.name ?? r.title ?? r.display_name ?? r.label ?? r.code ?? r.id ?? '';
};
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

const load = async () => {
  loading.value = true;
  error.value = null;

  if (!id) {
    error.value = 'No user id provided in route.';
    loading.value = false;
    return;
  }

  try {
    const res = await userService.show(id);
    // debug: log response shape to help adapt parsing
    console.debug('userService.show response:', res && res.data);

    const payload = res.data;
    const u = payload.user ?? payload.data ?? payload;
    user.value = u;
    originalUser.value = JSON.parse(JSON.stringify(u));

    form.value = {
      name: u.name ?? '',
      email: u.email ?? '',
      phone: u.phone ?? '',
      scope: u.scope ?? '',
      is_active: u.is_active ?? true,
    };

    // load available roles (for select)
    const r = await roleService.index();
    roles.value = r.data.data ?? r.data ?? [];

    // load annexes list for editor
    try {
      const ar = await annexeService.index();
      annexes.value = ar.data?.data ?? ar.data ?? [];
    } catch (err) {
      console.error('Failed loading annexes', err);
      annexes.value = [];
    }

  } catch (e) {
    console.error('Failed to load user details', e);
    const status = e?.response?.status;
    if (status === 401) {
      router.push('/signin');
      return;
    }
    // show raw response message if available
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
    return user.value.user_annexes.map(ua => {
      // ua may include role object or role_id / role_name fields
      if (ua.role) return ua.role;
      return {
        id: ua.role_id ?? ua.id ?? String(Math.random()),
        name: (ua.role_name ?? ua.role?.name) ?? ua.role_code ?? 'role',
        code: ua.role?.code ?? ua.role_code ?? '',
      };
    });
  }
  if (Array.isArray(user.value.roles)) return user.value.roles;
  return [];
});

// helper: return comma-joined annex names for a user (primary first if present)
const annexeNames = (u) => {
  if (!u) return '';
  if (Array.isArray(u.user_annexes) && u.user_annexes.length) {
    const mapped = u.user_annexes
      .map((ua) => {
        const ann = ua.annexe ?? ua.annexe_data ?? (ua.annexe_name ? { name: ua.annexe_name } : null);
        return {
          name: ann?.name ?? ua.annexe_name ?? ua.name ?? ua.annexe_id ?? null,
          primary: !!ua.is_primary,
        };
      })
      .filter((a) => a.name);
    mapped.sort((a, b) => (b.primary === true ? 1 : 0) - (a.primary === true ? 1 : 0));
    return [...new Set(mapped.map((m) => m.name))].join(', ');
  }
  if (Array.isArray(u.annexes) && u.annexes.length) {
    const primaryFirst = [...u.annexes].sort((a, b) => (b.is_primary ? 1 : 0) - (a.is_primary ? 1 : 0));
    return primaryFirst.map((a) => a.name ?? a.id).filter(Boolean).join(', ');
  }
  if (u.annexe && (u.annexe.name || u.annexe.id)) {
    return u.annexe.name ?? u.annexe.id;
  }
  return '';
};

onMounted(load);


</script>

<style scoped>
select option {
  color: #ffffff !important;
  background-color: #1f2937 !important; 
}
select option[value=""] {
  color: #ffffff !important;
  background-color: #1f2937 !important;
}
</style>
