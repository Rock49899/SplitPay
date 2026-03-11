<template>
  <AdminLayout>
    <PageBreadcrumb pageTitle="Annexes" />

    <div class="">
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
          <button v-if="isSuperAdminInstitution" @click="showCreate = true" class="px-4 py-2 bg-brand-500 text-white rounded">Créer une annexe</button>
        </div>
      </div>

      <CreateAnnexe v-if="showCreate" :users="usersForSelect" :roles="roles" @created="onCreated" @close="showCreate = false" />

      <div class="overflow-x-auto bg-white rounded shadow">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nom</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Adresse</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ville</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Détails</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Responsable</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr 
            v-for="a in annexeStore.items"
            :key="a?.id"
            >
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
                  <span
                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium"
                    :class="a.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                  >
                    {{ a.is_active ? 'Actif' : 'Inactif' }}
                  </span>
                </template>
                <template v-else>
                  <select v-model="editForm.status" class="w-full rounded border px-2 py-1">
                    <option value="active">Actif</option>
                    <option value="inactive">Inactif</option>
                  </select>
                </template>
              </td>

              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                <template v-if="editingId !== a.id">
                  <div class="flex flex-col">
                    <span class="font-medium text-gray-900">{{ managerName(a) }}</span>
                    <span v-if="managerEmail(a)" class="text-xs text-gray-500">{{ managerEmail(a) }}</span>
                  </div>
                </template>
                <template v-else>
                  <select v-model="editForm.manager_id" class="w-full rounded border px-2 py-1">
                    <option value="">-- aucun --</option>
                    <option v-for="u in usersForSelect" :key="u.id" :value="u.id">{{ u.name ?? u.email }}</option>
                  </select>
                </template>
              </td>

              <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                <template v-if="editingId !== a.id">
                  <button @click="startEdit(a)" class="text-indigo-600 mr-3">Modifier</button>
                  <button @click="remove(a.id)" class="text-red-500">Supprimer</button>
                </template>
                <template v-else>
                  <button @click="saveEdit(a.id)" class="text-green-600 mr-3">Enregistrer</button>
                  <button @click="cancelEdit" class="text-gray-500">Annuler</button>
                </template>
              </td>
            </tr>

            <tr v-if="!annexes.length">
              <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">Aucune annexe</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import AdminLayout from '@/components/layout/AdminLayout.vue';
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue';
import ComponentCard from '@/components/common/ComponentCard.vue';
import CreateAnnexe from '@/components/annexes/CreateAnnexe.vue';
import { useAnnexeStore } from '@/stores/useAnnexeStore';
import { useRoleStore } from '@/stores/useRoleStore';
import { usePermissions } from '@/composables/usePermissions';
import userService from '@/services/userService';
import { useRouter, useRoute } from 'vue-router';
import api from '@/services/api';

const router = useRouter();
const route = useRoute();

const annexeStore = useAnnexeStore();
const roleStore = useRoleStore();
const { isSuperAdminInstitution } = usePermissions();

const annexes = computed(() => annexeStore.items);

const showCreate = ref(false);
const usersForSelect = ref([]);
const roles = ref([]);

const editingId = ref(null);
const editForm = ref({
  name: '', address: '', city: '', details: '', status: 'active', manager_id: null
});

const loadAnnexes = async (search = '') => {
  const ann = await annexeStore.fetchAnnexes({ per_page: 100, search });
  console.log(ann)

};

onMounted(async () => {
  try {
    await loadAnnexes(route.query.search ?? '');
    await loadUsersAndRoles();
  } catch (e) {
    console.error('Failed initializing annex list', e);
  }
});

watch(
  () => route.query.search,
  async (newSearch) => {
    try {
      await loadAnnexes(newSearch ?? '');
    } catch (e) {
      console.error('Annexes search fetch failed', e);
    }
  },
  { immediate: false }
);

const loadUsersAndRoles = async () => {
  try {
    const ures = await userService.index({ per_page: 200 });
    usersForSelect.value = ures.data?.data ?? ures.data ?? [];
  } catch (e) { usersForSelect.value = []; }
  await roleStore.fetchRoles();
  roles.value = roleStore.items;
};

const startEdit = (a) => {
  editingId.value = a.id;
  // find candidate super-admin from pivots if exists
  const candidate = (Array.isArray(a.user_annexes) && a.user_annexes.length)
    ? (a.user_annexes.find(ua => ua.is_primary)
       || a.user_annexes.find(ua => {
         const r = ua.role ?? {};
         const rn = (r.name ?? r.code ?? ua.role_name ?? '').toString().toLowerCase();
         return /super.*admin|admin.*super|super[_\s]?admin|annexe.*admin|superadmin/.test(rn);
       })
       || a.user_annexes[0])
    : null;
  editForm.value = {
    name: a.name ?? '',
    address: a.address ?? '',
    city: a.city ?? '',
    details: a.details ?? '',
    status: a.status ?? 'active',
    manager_id: candidate?.user?.id ?? a.responsable_id ?? null,
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
    alert('Échec de la mise à jour');
  }
};

const remove = async (id) => {
  if (!confirm('Supprimer cette annexe ?')) return;
  try {
    await annexeStore.deleteAnnexe(id);
  } catch (e) {
    console.error('Delete annexe failed', e);
    alert('Échec de la suppression');
  }
};

const onCreated = async (created) => {
  await loadAnnexes();
  showCreate.value = false;
};

const managerName = (a) => {
  // prefer pivot-based super-admin
  if (Array.isArray(a.user_annexes) && a.user_annexes.length) {
    let candidate = a.user_annexes.find(ua => ua.is_primary);
    if (!candidate) {
      candidate = a.user_annexes.find(ua => {
        const r = ua.role ?? {};
        const rn = (r.name ?? r.code ?? ua.role_name ?? '').toString().toLowerCase();
        return /super.*admin|admin.*super|super[_\s]?admin|annexe.*admin|superadmin/.test(rn);
      });
    }
    if (!candidate) candidate = a.user_annexes.find(ua => ua.user) || a.user_annexes[0];
    if (candidate) return candidate.user?.name ?? candidate.user?.email ?? candidate.user?.id ?? '-';
  }
  // fallback: any responsable/legacy fields
  return a.responsable?.name ?? a.manager_name ?? a.responsable_name ?? '-';
};

const managerEmail = (a) => {
  if (Array.isArray(a.user_annexes) && a.user_annexes.length) {
    let candidate = a.user_annexes.find(ua => ua.is_primary) 
      || a.user_annexes.find(ua => (ua.role && ((ua.role.name ?? '').toLowerCase().includes('admin'))))
      || a.user_annexes.find(ua => ua.user);
    if (candidate) return candidate.user?.email ?? '';
  }
  return a.responsable?.email ?? a.manager_email ?? a.responsable_email ?? '';
};
</script>

<style scoped>
/* ...existing styles... */
</style>
