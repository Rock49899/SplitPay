<template>
  <AdminLayout>
    <PageBreadcrumb pageTitle="Annexes" />

    <div class="">
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
          <button @click="showCreate = true" class="px-4 py-2 bg-brand-500 text-white rounded">Create Annexe</button>
        </div>
      </div>

      <CreateAnnexe v-if="showCreate" :users="usersForSelect" :roles="roles" @created="onCreated" @close="showCreate = false" />

      <div class="overflow-x-auto bg-white rounded shadow">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Address</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">City</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Details</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Manager</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="a in annexes" :key="a.id">
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                <template v-if="editingId !== a.id">{{ a.name }}</template>
                <template v-else><input v-model="editForm.name" class="w-full rounded border px-2 py-1" /></template>
              </td>

              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                <template v-if="editingId !== a.id">{{ a.address ?? '-' }}</template>
                <template v-else><input v-model="editForm.address" class="w-full rounded border px-2 py-1" /></template>
              </td>

              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                <template v-if="editingId !== a.id">{{ a.city ?? '-' }}</template>
                <template v-else><input v-model="editForm.city" class="w-full rounded border px-2 py-1" /></template>
              </td>

              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                <template v-if="editingId !== a.id">{{ a.details ?? '-' }}</template>
                <template v-else><input v-model="editForm.details" class="w-full rounded border px-2 py-1" /></template>
              </td>

              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                <template v-if="editingId !== a.id">
                  <span :class="a.status === 'active' ? 'text-green-600' : 'text-red-600'">{{ a.status ?? '-' }}</span>
                </template>
                <template v-else>
                  <select v-model="editForm.status" class="w-full rounded border px-2 py-1">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                  </select>
                </template>
              </td>

              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                <template v-if="editingId !== a.id">{{ managerName(a) }}</template>
                <template v-else>
                  <select v-model="editForm.manager_id" class="w-full rounded border px-2 py-1">
                    <option value="">-- none --</option>
                    <option v-for="u in usersForSelect" :key="u.id" :value="u.id">{{ u.name ?? u.email }}</option>
                  </select>
                </template>
              </td>

              <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                <template v-if="editingId !== a.id">
                  <button @click="startEdit(a)" class="text-indigo-600 mr-3">Edit</button>
                  <button @click="remove(a.id)" class="text-red-500">Delete</button>
                </template>
                <template v-else>
                  <button @click="saveEdit(a.id)" class="text-green-600 mr-3">Save</button>
                  <button @click="cancelEdit" class="text-gray-500">Cancel</button>
                </template>
              </td>
            </tr>

            <tr v-if="!annexes.length">
              <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">No annexes</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
/* filepath: /home/rock/PIEUVRE/Saas-schooling-project/resources/js/views/Annexes/AnnexeList.vue */
import { ref, computed, onMounted } from 'vue';
import AdminLayout from '@/components/layout/AdminLayout.vue';
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue';
import ComponentCard from '@/components/common/ComponentCard.vue';
import CreateAnnexe from '@/components/annexes/CreateAnnexe.vue';
import { useAnnexeStore } from '@/stores/useAnnexeStore';
import { useRoleStore } from '@/stores/useRoleStore';
import userService from '@/services/userService';
import { useRouter } from 'vue-router';
import api from '@/services/api';

const router = useRouter();

const annexeStore = useAnnexeStore();
const roleStore = useRoleStore();

const annexes = computed(() => annexeStore.items);

const showCreate = ref(false);
const usersForSelect = ref([]);
const roles = ref([]);

const editingId = ref(null);
const editForm = ref({
  name: '', address: '', city: '', details: '', status: 'active', manager_id: null
});

const loadAnnexes = async () => {
  await annexeStore.fetchAnnexes({ per_page: 100 });
};

const loadUsersAndRoles = async () => {
  try {
    const ures = await userService.index({ per_page: 200 });
    usersForSelect.value = ures.data?.data ?? ures.data ?? [];
  } catch (e) { usersForSelect.value = []; }
  await roleStore.fetchRoles();
  roles.value = roleStore.items;
};

onMounted(async () => {
  await Promise.all([loadAnnexes(), loadUsersAndRoles()]);
});

const startEdit = (a) => {
  editingId.value = a.id;
  editForm.value = {
    name: a.name ?? '',
    address: a.address ?? '',
    city: a.city ?? '',
    details: a.details ?? '',
    status: a.status ?? 'active',
    manager_id: a.manager?.id ?? a.responsable_id ?? null,
  };
};

const cancelEdit = () => {
  editingId.value = null;
};

const saveEdit = async (id) => {
  try {
    const payload = { ...editForm.value };
    await annexeStore.updateAnnexe(id, payload);
    // annexeStore.fetchAnnexes called inside updateAnnexe
    editingId.value = null;
  } catch (e) {
    console.error('Failed updating annexe', e);
    alert('Update failed');
  }
};

const remove = async (id) => {
  if (!confirm('Delete this annexe?')) return;
  try {
    await annexeStore.deleteAnnexe(id);
  } catch (e) {
    console.error('Delete annexe failed', e);
    alert('Delete failed');
  }
};

const onCreated = async (created) => {
  await loadAnnexes();
  showCreate.value = false;
};

const managerName = (a) => {
  return a.manager?.name ?? a.responsable?.name ?? a.manager_name ?? a.responsable_name ?? '-';
};
</script>

<style scoped>
/* ...existing styles... */
</style>
