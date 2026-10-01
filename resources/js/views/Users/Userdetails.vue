<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="`User: ${user?.name || '...'} `"/>
    <div class="space-y-5 sm:space-y-6">
      <ComponentCard title="User details">
        <!-- Avatar header -->
        <div class="flex items-center gap-4 mb-6 pb-5 border-b border-slate-100 dark:border-slate-700">
          <div class="relative shrink-0">
            <AvatarDisplay 
              :src="avatarPreview || user?.avatar_url" 
              :label="form.name" 
              :size="72"
              :clickable="!!(avatarPreview || user?.avatar_url)"
              @click="showImageModal = true"
            />
            <label v-if="editMode" class="absolute -bottom-1 -right-1 w-6 h-6 bg-brand-500 rounded-full flex items-center justify-center cursor-pointer shadow">
              <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <input type="file" class="sr-only" accept="image/jpeg,image/jpg,image/png,image/webp" @change="onAvatarChange" />
            </label>
          </div>
          <div>
            <p class="font-semibold text-gray-900 dark:text-gray-100">{{ form.name || '—' }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ form.email }}</p>
            <p v-if="editMode" class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Cliquez sur l'icône caméra pour changer la photo</p>
            <p v-else-if="avatarPreview || user?.avatar_url" class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Cliquez sur la photo pour agrandir</p>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
          <!-- Name -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">Nom</label>
            <template v-if="!editMode">
              <p class="mt-1 text-gray-900 dark:text-white">{{ form.name || '—' }}</p>
            </template>
            <template v-else>
              <input v-model="form.name" class="mt-1 block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-slate-900 placeholder:text-slate-400 dark:text-white dark:bg-slate-700 dark:border-slate-600" />
            </template>
          </div>

          <!-- Email -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">Email</label>
            <template v-if="!editMode">
              <p class="mt-1 text-gray-900 dark:text-white">{{ form.email || '—' }}</p>
            </template>
            <template v-else>
              <input v-model="form.email" class="mt-1 block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-slate-900 placeholder:text-slate-400 dark:text-white dark:bg-slate-700 dark:border-slate-600" />
            </template>
          </div>

          <!-- Phone -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">Téléphone</label>
            <template v-if="!editMode">
              <p class="mt-1 text-gray-900 dark:text-white">{{ form.phone || '—' }}</p>
            </template>
            <template v-else>
              <input v-model="form.phone" class="mt-1 block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-slate-900 placeholder:text-slate-400 dark:text-white dark:bg-slate-700 dark:border-slate-600" />
            </template>
          </div>

          <!-- Scope -->
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">Scope</label>
            <p class="mt-1 text-gray-900 dark:text-gray-100">{{ form.scope || '—' }}</p>
          </div>

          <!-- Annexes -->
          <!-- <div>
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
          </div> -->

          <!-- Active -->
          <div class="flex items-center gap-3">
            <template v-if="!editMode">
              <span :class="form.is_active ? 'text-green-600' : 'text-red-600'">
                {{ form.is_active ? 'Actif' : 'Inactif' }}
              </span>
            </template>
            <template v-else>
              <input type="checkbox" id="is_active" v-model="form.is_active" class="h-4 w-4" />
              <label for="is_active" class="text-sm text-gray-700 dark:text-gray-400">Actif</label>
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
          Modifier
        </button>
          <button v-else @click="save" :disabled="saving" class="px-4 py-2 bg-brand-500 text-white rounded disabled:opacity-50">
            <span v-if="!saving">Enregistrer</span><span v-else>Enregistrement...</span>
          </button>
          <button v-if="editMode" @click="cancelEdit" class="px-4 py-2 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-200 rounded hover:bg-slate-50 dark:hover:bg-slate-600">Annuler</button>
          <button @click="goBack" class="ml-auto px-4 py-2 border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-200 rounded hover:bg-slate-50 dark:hover:bg-slate-600">Retour</button>
        </div>
      </ComponentCard>

      <ComponentCard title="Roles & Permissions">
        <div class="grid grid-cols-1 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">Sélectionner une annexe</label>
            <select v-model="selectedAnnexe" class="mt-1 block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:bg-slate-700 dark:text-white dark:border-slate-600">
              <option value="">-- sélectionner une annexe --</option>
              <option v-for="a in annexes" :key="a.id" :value="a.id">{{ a.name }}</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">Assign role</label>
            <select v-model="selectedRole" class="mt-1 block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:bg-slate-700 dark:text-white dark:border-slate-600">
              <option value="">-- select role --</option>
              <option v-for="r in roles" :key="r.id" :value="r.id">{{ roleLabel(r) }}</option>
            </select>
          </div>

          <div class="flex items-end gap-3">
            <label class="inline-flex items-center">
              <input type="checkbox" v-model="isPrimary" class="form-checkbox" />
              <span class="ml-2 text-sm text-gray-700 dark:text-gray-400">Primary</span>
            </label>
            <button @click="assignRole" :disabled="assigning || !selectedRole || !selectedAnnexe" class="px-4 py-2 bg-brand-500 text-white rounded disabled:opacity-50">
              <span v-if="!assigning">Assign</span>
              <span v-else>Assigning...</span>
            </button>
          </div>
        </div>

        <div class="mt-4">
          <h4 class="text-sm font-medium text-gray-700 dark:text-gray-400 mb-2">Current roles</h4>
          <ul class="space-y-2">
            <li v-for="r in rolesList" :key="r.id + '-' + r.annexe_id" class="flex items-center justify-between bg-slate-50 dark:bg-slate-700/60 border border-slate-100 dark:border-slate-600 rounded p-2">
              <div>
                <div class="font-medium text-sm text-gray-900 dark:text-white">{{ r.name }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                  {{ r.code ?? '' }} - {{ r.annexe_name }}
                  <span v-if="r.is_primary" class="ml-2 text-brand-500">(Primary)</span>
                </div>
              </div>
              <button @click="removeRole(r.annexe_id)" class="text-red-500 text-sm hover:text-red-700">Remove</button>
            </li>
            <li v-if="!rolesList.length" class="text-sm text-gray-500 dark:text-gray-400">No roles</li>
          </ul>
        </div>
      </ComponentCard>

      <div v-if="error" class="text-sm text-red-600 dark:text-red-400 mt-2">{{ error }}</div>
    </div>

    <!-- Modal pour agrandir l'avatar -->
    <ImageViewerModal
      v-model="showImageModal"
      :image-src="avatarPreview || user?.avatar_url || ''"
      :image-alt="form.name"
    />
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AdminLayout from '@/components/layout/AdminLayout.vue';
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue';
import ComponentCard from '@/components/common/ComponentCard.vue';
import userService from '@/services/userService';
import api from '@/services/api';
import AvatarDisplay from '@/components/shared/AvatarDisplay.vue';
import ImageViewerModal from '@/components/shared/ImageViewerModal.vue';
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
const selectedAnnexe = ref(''); // Annexe sélectionnée pour assign role
const isPrimary = ref(false);

// Avatar
const avatarFile = ref(null);
const avatarPreview = ref(null);
const showImageModal = ref(false);
const onAvatarChange = (e) => {
  const file = e.target.files?.[0];
  if (!file) return;
  avatarFile.value = file;
  avatarPreview.value = URL.createObjectURL(file);
};

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
    roles.value = (roleStore.items ?? []).filter(r => r?.code !== 'platform_admin');
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
  avatarFile.value = null;
  avatarPreview.value = null;
  editMode.value = false;
};

const save = async () => {
  saving.value = true;
  error.value = null;
  try {
    const fd = new FormData();
    Object.entries(form.value).forEach(([k, v]) => {
      if (v == null || v === '') return;
      if (typeof v === 'boolean') {
        fd.append(k, v ? '1' : '0');
        return;
      }
      fd.append(k, String(v));
    });
    if (avatarFile.value) fd.append('avatar', avatarFile.value);
    await userService.update(id, fd);
    avatarFile.value = null;
    avatarPreview.value = null;
    await load();
    editMode.value = false;
    
    // Notifier UserMenu de se rafraîchir si c'est le profil de l'utilisateur connecté
    window.dispatchEvent(new CustomEvent('user-profile-updated'));
  } catch (e) {
    error.value = e.response?.data?.message || e.message || 'Update failed';
  } finally {
    saving.value = false;
  }
};

const assignRole = async () => {
  if (!selectedRole.value || !selectedAnnexe.value) {
    error.value = 'Please select both annexe and role';
    return;
  }
  assigning.value = true;
  error.value = null;
  try {
    await api.post(`admin/users/${id}/assign-role`, {
      annexe_id: selectedAnnexe.value,
      role_id: selectedRole.value,
      is_primary: isPrimary.value ?? false,
    });
    await load();
    selectedRole.value = '';
    selectedAnnexe.value = '';
    isPrimary.value = false;
  } catch (e) {
    error.value = e.response?.data?.message || e.message || 'Assign role failed';
  } finally {
    assigning.value = false;
  }
};

const removeRole = async (annexeId) => {
  if (!confirm('Remove role from this annexe?')) return;
  try {
    await api.post(`admin/users/${id}/remove-role`, {
      annexe_id: annexeId,
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
    return user.value.user_annexes.map(ua => ({
      id: ua.role?.id ?? ua.role_id ?? String(Math.random()),
      name: ua.role?.name ?? ua.role_name ?? 'role',
      code: ua.role?.code ?? ua.role_code ?? '',
      annexe_id: ua.annexe_id,
      annexe_name: ua.annexe?.name ?? ua.annexe_name ?? 'Unknown annexe',
      is_primary: ua.is_principal ?? false,
    }));
  }
  if (Array.isArray(user.value.roles)) {
    return user.value.roles.map(r => ({
      ...r,
      annexe_id: r.pivot?.annexe_id ?? user.value.annexe_id,
      annexe_name: 'N/A',
      is_primary: false,
    }));
  }
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
</style>
