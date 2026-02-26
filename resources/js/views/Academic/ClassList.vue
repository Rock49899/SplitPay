<template>
  <AdminLayout>
    <PageBreadcrumb pageTitle="Classes" />
    
    <div class="p-6">
      <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
        <!-- Header -->
        <div class="p-6 border-b border-slate-200 dark:border-slate-700">
          <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="flex-1 max-w-md">
              <input
                v-model="search"
                type="text"
                placeholder="Search class..."
                class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                @input="debounceSearch"
              />
            </div>
            <button
              @click="openCreateModal"
              class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition-colors"
            >
              + Add Class
            </button>
          </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-slate-50 dark:bg-slate-700 border-b border-slate-200 dark:border-slate-600">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Code</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Label</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Study Level</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Specialization</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
              <tr v-if="loading">
                <td colspan="5" class="px-6 py-12 text-center text-slate-500">Loading...</td>
              </tr>
              <tr v-else-if="classes.length === 0">
                <td colspan="5" class="px-6 py-12 text-center text-slate-500">No classes found</td>
              </tr>
              <tr v-else v-for="cls in classes" :key="cls.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900 dark:text-white">{{ cls.code }}</td>
                <td class="px-6 py-4 text-sm text-slate-900 dark:text-white">{{ cls.label }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">{{ cls.study_level?.label || 'N/A' }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500 dark:text-slate-400">{{ cls.specialization?.label || 'N/A' }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                  <button @click="openEditModal(cls)" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400">Edit</button>
                  <button @click="deleteClass(cls)" class="text-red-600 hover:text-red-900 dark:text-red-400">Delete</button>
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
              {{ editing ? 'Edit Class' : 'New Class' }}
            </h3>
            <button @click="closeModal" class="text-slate-400 hover:text-slate-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <form @submit.prevent="save" class="p-6 space-y-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Study Level *</label>
              <select v-model="form.study_level_id" required class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                <option value="">-- Select study level --</option>
                <option v-for="level in studyLevels" :key="level.id" :value="level.id">{{ level.label }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Specialization *</label>
              <select v-model="form.specialization_id" required class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                <option value="">-- Select specialization --</option>
                <option v-for="spec in specializations" :key="spec.id" :value="spec.id">{{ spec.label }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Code *</label>
              <input v-model="form.code" type="text" required placeholder="e.g., A, B, C" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white" />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Label *</label>
              <input v-model="form.label" type="text" required placeholder="e.g., L1 CS - Group A" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white" />
            </div>
            <div class="flex justify-end gap-3 pt-4">
              <button type="button" @click="closeModal" class="px-4 py-2 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700">Cancel</button>
              <button type="submit" :disabled="saving" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg disabled:opacity-50">
                {{ saving ? 'Saving...' : 'Save' }}
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
import classService from '@/services/classService';
import studyLevelService from '@/services/studyLevelService';
import specializationService from '@/services/specializationService';

const classes = ref([]);
const studyLevels = ref([]);
const specializations = ref([]);
const loading = ref(false);
const search = ref('');
const showModal = ref(false);
const editing = ref(null);
const saving = ref(false);
const searchTimeout = ref(null);
const form = ref({ study_level_id: '', specialization_id: '', code: '', label: '' });

async function load() {
  loading.value = true;
  try {
    const response = await classService.index({ search: search.value });
    classes.value = response.data.data || response.data;
  } catch (error) {
    console.error('Error:', error);
    alert('Error loading data');
  } finally {
    loading.value = false;
  }
}

async function loadAcademicData() {
  try {
    const [levelsRes, specsRes] = await Promise.all([
      studyLevelService.index({ per_page: 100 }),
      specializationService.index({ per_page: 100 })
    ]);
    studyLevels.value = levelsRes.data.data || levelsRes.data;
    specializations.value = specsRes.data.data || specsRes.data;
  } catch (error) {
    console.error('Error loading academic data:', error);
  }
}

function debounceSearch() {
  if (searchTimeout.value) clearTimeout(searchTimeout.value);
  searchTimeout.value = setTimeout(load, 500);
}

function openCreateModal() {
  editing.value = null;
  form.value = { study_level_id: '', specialization_id: '', code: '', label: '' };
  showModal.value = true;
}

function openEditModal(cls) {
  editing.value = cls;
  form.value = { 
    study_level_id: cls.study_level_id, 
    specialization_id: cls.specialization_id, 
    code: cls.code, 
    label: cls.label 
  };
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
      await classService.update(editing.value.id, form.value);
    } else {
      await classService.store(form.value);
    }
    await load();
    closeModal();
  } catch (error) {
    alert(error.response?.data?.message || 'Error');
  } finally {
    saving.value = false;
  }
}

async function deleteClass(cls) {
  if (!confirm(`Delete "${cls.label}"?`)) return;
  try {
    await classService.destroy(cls.id);
    await load();
  } catch (error) {
    alert(error.response?.data?.message || 'Error');
  }
}

onMounted(() => {
  load();
  loadAcademicData();
});
</script>
