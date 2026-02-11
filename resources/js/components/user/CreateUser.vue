<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center">
    <div class="fixed inset-0 bg-black/50" @click="close"></div>
    <div class="bg-white dark:bg-gray-900 rounded-lg p-6 z-50 w-full max-w-2xl shadow-lg">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Create User</h3>
        <button @click="close" class="text-gray-500 hover:text-gray-700">✕</button>
      </div>

      <div class="grid grid-cols-1 gap-3">
        <input v-model="form.name" placeholder="Full name" class="px-3 py-2 rounded border bg-transparent text-gray-900 dark:text-white" />
        <input v-model="form.email" placeholder="Email" class="px-3 py-2 rounded border bg-transparent text-gray-900 dark:text-white" />
        <input v-model="form.password" type="password" placeholder="Password" class="px-3 py-2 rounded border bg-transparent text-gray-900 dark:text-white" />
        <input v-model="form.phone" placeholder="Phone" class="px-3 py-2 rounded border bg-transparent text-gray-900 dark:text-white" />

        <div>
          <label class="block text-sm mb-1 text-gray-700 dark:text-gray-400">Role</label>
          <select v-model="form.role_id" class="w-full rounded border px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
            <option value="">-- select role --</option>
            <option v-for="r in rolesLocal" :key="r.id" :value="r.id">{{ labelForRole(r) }}</option>
          </select>
        </div>

        <div>
          <label class="block text-sm mb-1 text-gray-700 dark:text-gray-400">Annexes (choose one primary)</label>
          <div class="space-y-1 max-h-40 overflow-auto p-2 border rounded bg-gray-50 dark:bg-gray-800">
            <div v-for="a in annexesLocal" :key="a.id" class="flex items-center gap-2">
              <input type="checkbox" :value="a.id" v-model="form.annexes" />
              <label class="flex-1 text-gray-900 dark:text-white">{{ a.name }}</label>
              <input type="radio" name="primaryAnnexe" :value="a.id" v-model="form.primary_annexe" :disabled="!form.annexes.includes(a.id)" />
            </div>
          </div>
        </div>

        <div class="flex gap-2 justify-end mt-3">
          <button @click="close" class="px-3 py-2 border rounded">Cancel</button>
          <button @click="submit" :disabled="loading" class="px-4 py-2 bg-brand-500 text-white rounded">
            <span v-if="!loading">Create</span>
            <span v-else>Creating...</span>
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

const props = defineProps({
  roles: { type: Array, default: () => [] },
  annexes: { type: Array, default: () => [] },
});

const emit = defineEmits(['created', 'close']);

const rolesLocal = ref(props.roles ?? []);
const annexesLocal = ref(props.annexes ?? []);

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
    const payload = {
      name: form.value.name,
      email: form.value.email,
      password: form.value.password,
      phone: form.value.phone || null,
      role_id: form.value.role_id || null,
      annexe_id: form.value.primary_annexe || null,
      is_active: true,
      scope: 'annexe', // par défaut l'utilisateur créén à le scope annexe
    };
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


