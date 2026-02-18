<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center">
    <div class="fixed inset-0 bg-black/50" @click="close"></div>
    <div class="bg-white dark:bg-gray-900 rounded-lg p-6 z-50 w-full max-w-3xl shadow-lg overflow-auto max-h-[80vh]">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Create Annexe</h3>
        <button @click="close" class="text-gray-500 hover:text-gray-700">✕</button>
      </div>

      <div class="grid grid-cols-1 gap-3">
        <input v-model="form.name" placeholder="Annexe name" class="px-3 py-2 rounded border bg-transparent text-gray-900 dark:text-white" />
        <input v-model="form.address" placeholder="Address" class="px-3 py-2 rounded border bg-transparent text-gray-900 dark:text-white" />
        <input v-model="form.city" placeholder="City" class="px-3 py-2 rounded border bg-transparent text-gray-900 dark:text-white" />
        <input v-model="form.details" placeholder="Details" class="px-3 py-2 rounded border bg-transparent text-gray-900 dark:text-white" />

        <div>
          <label class="block text-sm mb-2">Manager (super admin annexe)</label>
          <select v-model="form.manager_id" class="w-full rounded border px-3 py-2 bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
            <option value="">-- select manager --</option>
            <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name ?? u.email }}</option>
          </select>
        </div>

        <div>
          <label class="block text-sm mb-2">Add users to this annexe (select users and role)</label>
          <div class="max-h-48 overflow-auto border rounded p-2 bg-gray-50 dark:bg-gray-800 space-y-2">
            <div v-for="u in users" :key="u.id" class="flex items-center gap-3">
              <input type="checkbox" :value="u.id" v-model="selectedUsers" />
              <div class="flex-1 text-sm text-gray-900 dark:text-white">{{ u.name ?? u.email }}</div>
              <select v-model="userRole[u.id]" class="rounded border px-2 py-1 bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                <option value="" :style="optionStyle">-- role --</option>
                <option
                  v-for="r in roles"
                  :key="r.id"
                  :value="r.id"
                  :style="optionStyle"
                >
                  {{ roleLabel(r) }}
                </option>
              </select>
            </div>
          </div>
        </div>

        <div class="flex gap-2 justify-end mt-3">
          <button @click="close" class="px-3 py-2 border rounded">Cancel</button>
          <button @click="submit" :disabled="loading" class="px-4 py-2 bg-brand-500 text-white rounded">
            <span v-if="!loading">Create</span><span v-else>Creating...</span>
          </button>
        </div>

        <div v-if="error" class="text-sm text-red-600 mt-2">{{ error }}</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import userService from '@/services/userService';
import { useAnnexeStore } from '@/stores/useAnnexeStore';
import { useRoleStore } from '@/stores/useRoleStore';

const annexeStore = useAnnexeStore();
const roleStore = useRoleStore();

const props = defineProps({
  users: { type: Array, default: () => [] },
  roles: { type: Array, default: () => [] },
});

const emit = defineEmits(['created','close']);

const users = ref(props.users ?? []);
const roles = ref(props.roles ?? []);

const form = ref({
  name: '',
  address: '',
  city: '',
  details: '',
  manager_id: null,
});

const selectedUsers = ref([]);
const userRole = ref({}); // map userId -> roleId

const loading = ref(false);
const error = ref(null);
const isDark = ref(false);

onMounted(async () => {
  // detect theme
  isDark.value = document.documentElement.classList.contains('dark');

  // load users if not passed as prop
  if (!users.value.length) {
    try {
      const ures = await userService.index({ per_page: 200 });
      users.value = ures.data?.data ?? ures.data ?? [];
    } catch (e) { users.value = []; }
  }
  // load roles via role store
  if (!roles.value.length) {
    await roleStore.fetchRoles();
    roles.value = roleStore.items;
  }
});

const close = () => emit('close');

const submit = async () => {
  error.value = null;
  if (!form.value.name) { error.value = 'Name required'; return; }
  loading.value = true;
  try {
    const res = await annexeStore.createAnnexe({
      name: form.value.name,
      address: form.value.address ?? null,
      city: form.value.city ?? null,
      annexe_details: form.value.details ?? null,
      manager_id: form.value.manager_id ?? null,
      status: form.value.status ?? 'active',
    });
    const annexe = res.data?.annexe ?? res.data ?? res;
    // assign selected users with their roles
    for (const uid of selectedUsers.value) {
      const rid = userRole.value[uid] ?? null;
      try {
        await annexeStore.assignUser(annexe.id, uid, rid);
      } catch (err) {
        console.error('assign user to annexe failed', uid, err);
      }
    }
    emit('created', annexe);
    close();
  } catch (e) {
    console.error(e);
    error.value = e.response?.data?.message || e.message || 'Create failed';
  } finally {
    loading.value = false;
  }
};

// reusable inline style for option elements (works in most browsers)
const optionStyle = computed(() => ({
  color: isDark.value ? '#ffffff' : '#000000',
  background: isDark.value ? '#1f2937' : '#ffffff',
  '-webkit-text-fill-color': isDark.value ? '#ffffff' : '#000000',
}));

// helper to pick best display field for a role
const roleLabel = (r) => {
  if (!r) return '';
  return r.name ?? r.title ?? r.display_name ?? r.label ?? r.code ?? r.id ?? '';
};
</script>
