<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center">
    <div class="fixed inset-0 bg-black/50" @click="close"></div>
    <div class="bg-white dark:bg-gray-900 rounded-lg p-6 z-50 w-full max-w-2xl shadow-lg">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Créer un utilisateur</h3>
        <button @click="close" class="text-gray-500 hover:text-gray-700">✕</button>
      </div>

      <div class="grid grid-cols-1 gap-3">
        <!-- Avatar upload -->
        <div class="flex items-center gap-4 mb-2">
          <div class="relative shrink-0">
            <AvatarDisplay :src="avatarPreview" :label="form.name" :size="64" />
            <label class="absolute -bottom-1 -right-1 w-6 h-6 bg-brand-500 rounded-full flex items-center justify-center cursor-pointer shadow">
              <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <input type="file" class="sr-only" accept="image/jpeg,image/jpg,image/png,image/webp" @change="onAvatarChange" />
            </label>
          </div>
          <div class="text-xs text-gray-500 dark:text-gray-400">Cliquez sur l'icône caméra pour ajouter une photo</div>
        </div>
        
        <input v-model="form.name" placeholder="Nom complet" class="px-3 py-2 rounded border bg-transparent text-gray-900 dark:text-white" />
        <input v-model="form.email" placeholder="Email" class="px-3 py-2 rounded border bg-transparent text-gray-900 dark:text-white" />
        <input v-model="form.password" type="password" placeholder="Mot de passe" class="px-3 py-2 rounded border bg-transparent text-gray-900 dark:text-white" />
        <input v-model="form.phone" placeholder="Téléphone" class="px-3 py-2 rounded border bg-transparent text-gray-900 dark:text-white" />

        <div>
          <label class="block text-sm mb-1 text-gray-700 dark:text-gray-400">Rôle</label>
          <select v-model="form.role_id" class="w-full rounded border px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
            <option value="">-- sélectionner un rôle --</option>
            <option v-for="r in rolesLocal" :key="r.id" :value="r.id">{{ labelForRole(r) }}</option>
          </select>
        </div>

        <div>
          <label class="block text-sm mb-1 text-gray-700 dark:text-gray-400">Annexes (choisissez-en une comme principale)</label>
          <div class="space-y-1 max-h-40 overflow-auto p-2 border rounded bg-gray-50 dark:bg-gray-800">
            <div v-for="a in annexesLocal" :key="a.id" class="flex items-center gap-2">
              <input type="checkbox" :value="a.id" v-model="form.annexes" />
              <label class="flex-1 text-gray-900 dark:text-white">{{ a.name }}</label>
              <input type="radio" name="primaryAnnexe" :value="a.id" v-model="form.primary_annexe" :disabled="!form.annexes.includes(a.id)" />
            </div>
          </div>
        </div>

        <div class="flex gap-2 justify-end mt-3">
          <button @click="close" class="px-3 py-2 border rounded">Annuler</button>
          <button @click="submit" :disabled="loading" class="px-4 py-2 bg-brand-500 text-white rounded">
            <span v-if="!loading">Créer</span>
            <span v-else>Création...</span>
          </button>
        </div>

        <div v-if="error" class="text-sm text-red-600 mt-2">{{ error }}</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, toRefs } from 'vue';
import userService from '@/services/userService';
import roleService from '@/services/roleService';
import annexeService from '@/services/annexeService';
import api from '@/services/api';
import AvatarDisplay from '@/components/shared/AvatarDisplay.vue';

const props = defineProps({
  roles: { type: Array, default: () => [] },
  annexes: { type: Array, default: () => [] },
});

const emit = defineEmits(['created', 'close']);

const rolesLocal = ref(props.roles ?? []);
const annexesLocal = ref(props.annexes ?? []);

// Avatar
const avatarFile = ref(null);
const avatarPreview = ref(null);

const onAvatarChange = (e) => {
  const file = e.target.files?.[0];
  if (!file) return;
  avatarFile.value = file;
  avatarPreview.value = URL.createObjectURL(file);
};

const form = ref({
  name: '',
  email: '',
  password: '',
  phone: '',
  role_id: '',
  annexes: [],
  primary_annexe: null,
});

const loading = ref(false);
const error = ref(null);

onMounted(async () => {
  // if parent didn't pass roles/annexes, fetch here
  if (!rolesLocal.value.length) {
    try {
      const r = await roleService.index();
      rolesLocal.value = r.data?.data ?? r.data ?? [];
    } catch (e) { /* ignore */ }
  }
  if (!annexesLocal.value.length) {
    try {
      const a = await annexeService.index();
      annexesLocal.value = a.data?.data ?? a.data ?? [];
    } catch (e) { /* ignore */ }
  }
});

const labelForRole = (r) => r?.name ?? r?.title ?? r?.code ?? r?.id ?? '';

const close = () => emit('close');

const submit = async () => {
  error.value = null;
  if (!form.value.name || !form.value.email || !form.value.password) {
    error.value = 'Name, email and password are required';
    return;
  }
  loading.value = true;
  try {
    // create user with primary annexe and role
    let payload;
    
    // Si avatar présent, utiliser FormData
    if (avatarFile.value && avatarFile.value instanceof File) {
      payload = new FormData();
      payload.append('name', form.value.name);
      payload.append('email', form.value.email);
      payload.append('password', form.value.password);
      if (form.value.phone) payload.append('phone', form.value.phone);
      if (form.value.role_id) payload.append('role_id', form.value.role_id);
      if (form.value.primary_annexe) payload.append('annexe_id', form.value.primary_annexe);
      payload.append('is_active', '1');
      payload.append('scope', 'annexe');
      payload.append('avatar', avatarFile.value);
    } else {
      payload = {
        name: form.value.name,
        email: form.value.email,
        password: form.value.password,
        phone: form.value.phone || null,
        role_id: form.value.role_id || null,
        annexe_id: form.value.primary_annexe || null,
        is_active: true,
        scope: 'annexe',
      };
    }
    
    const res = await userService.store(payload);
    const created = res.data?.user ?? res.data;
    // assign other annexes
    const extras = (form.value.annexes || []).filter(a => a !== form.value.primary_annexe);
    for (const ann of extras) {
      try {
        await api.post(`admin/users/${created.id}/assign-role`, {
          annexe_id: ann,
          role_id: form.value.role_id || null,
          is_primary: false,
        });
      } catch (e) { /* continue */ }
    }
    emit('created', created);
    close();
  } catch (e) {
    error.value = e.response?.data?.message || e.message || 'Create failed';
  } finally {
    loading.value = false;
  }
};
</script>


