<template>
  <AdminLayout>
    <PageBreadcrumb pageTitle="Spécialisations" />
    
    <div >
      <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
        <!-- Header -->
        <div class="p-6 border-b border-slate-200 dark:border-slate-700">
          <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="flex-1 max-w-md">
              <input
                v-model="search"
                type="text"
                placeholder="Rechercher une spécialisation..."
                class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                @input="debounceSearch"
              />
            </div>
            <button
              @click="openCreateModal"
              class="px-4 py-2 bg-in digo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition-colors"
            >
              + Ajouter une spécialisation
            </button>
          </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-slate-50 dark:bg-slate-700 border-b border-slate-200 dark:border-slate-600">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Sigle</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Libellé</th>
                <!-- <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Description</th> -->
                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
              <tr v-if="loading">
                <td colspan="4" class="px-6 py-12 text-center text-slate-500">Chargement...</td>
              </tr>
              <tr v-else-if="specializations.length === 0">
                <td colspan="4" class="px-6 py-12 text-center text-slate-500">Aucune spécialisation trouvée</td>
              </tr>
              <tr v-else v-for="spec in specializations" :key="spec.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900 dark:text-white">{{ spec.code }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900 dark:text-white">{{ spec.label }}</td>
                <!-- <td class="px-6 py-4 text-sm text-slate-500 dark:text-slate-400">{{ spec.description || 'N/A' }}</td> -->
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                  <button @click="openEditModal(spec)" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400">Modifier</button>
                  <button @click="deleteSpecialization(spec)" class="text-red-600 hover:text-red-900 dark:text-red-400">Supprimer</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" @click.self="closeModal">
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl max-w-md w-full">
          <div class="border-b border-slate-200 dark:border-slate-700 px-6 py-4 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">
              {{ editing ? 'Modifier la spécialisation' : 'Nouvelle spécialisation' }}
            </h3>
            <button @click="closeModal" class="text-slate-400 hover:text-slate-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <form @submit.prevent="save" class="p-6 space-y-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Sigle *</label>
              <input v-model="form.code" type="text" required placeholder="ex : GTL, INFO" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white" />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Libellé *</label>
              <input v-model="form.label" type="text" required placeholder="ex : Informatique et Télécoms" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white" />
            </div>
            <!-- <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Description</label>
              <textarea v-model="form.description" rows="3" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white"></textarea>
            </div> -->
            <div class="flex justify-end gap-3 pt-4">
              <button type="button" @click="closeModal" class="px-4 py-2 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700">Annuler</button>
              <button type="submit" :disabled="saving" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg disabled:opacity-50">
                {{ saving ? 'Enregistrement...' : 'Enregistrer' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import AdminLayout from '@/components/layout/AdminLayout.vue';
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue';
import specializationService from '@/services/specializationService';

const specializations = ref([]);
const loading = ref(false);
const search = ref('');
const showModal = ref(false);
const editing = ref(null);
const saving = ref(false);
const searchTimeout = ref(null);
const form = ref({ code: '', label: '', description: '' });

async function load() {
  loading.value = true;
  try {
    const response = await specializationService.index({ search: search.value });
    specializations.value = response.data.data || response.data;
  } catch (error) {
    console.error('Error:', error);
    alert('Erreur de chargement');
  } finally {
    loading.value = false;
  }
}

function debounceSearch() {
  if (searchTimeout.value) clearTimeout(searchTimeout.value);
  searchTimeout.value = setTimeout(load, 500);
}

function openCreateModal() {
  editing.value = null;
  form.value = { code: '', label: '', description: '' };
  showModal.value = true;
}

function openEditModal(spec) {
  editing.value = spec;
  form.value = { ...spec };
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
  editing.value = null;
}

async function save() {
  saving.value = true;
  try {
    if (editing.value) {
      await specializationService.update(editing.value.id, form.value);
    } else {
      await specializationService.store(form.value);
    }
    await load();
    closeModal();
  } catch (error) {
    alert(error.response?.data?.message || 'Error');
  } finally {
    saving.value = false;
  }
}

async function deleteSpecialization(spec) {
  if (!confirm(`Delete "${spec.label}"?`)) return;
  try {
    await specializationService.destroy(spec.id);
    await load();
  } catch (error) {
    alert(error.response?.data?.message || 'Error');
  }
}

onMounted(load);
</script>
