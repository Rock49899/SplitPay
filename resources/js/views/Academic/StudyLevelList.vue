<template>
  <AdminLayout>
    <PageBreadcrumb pageTitle="Study Levels" />
    
    <div class="p-6">
      <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-slate-200 dark:border-slate-700">
        <!--Header -->
        <div class="p-6 border-b border-slate-200 dark:border-slate-700">
          <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="flex-1 max-w-md">
              <input
                v-model="search"
                type="text"
                placeholder="Search study level..."
                class="w-full px-4 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white"
                @input="debounceSearch"
              />
            </div>
            <button
              @click="openCreateModal"
              class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition-colors"
            >
              + Add Study Level
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
                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Description</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-300 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-slate-800 divide-y divide-slate-200 dark:divide-slate-700">
              <tr v-if="loading">
                <td colspan="4" class="px-6 py-12 text-center text-slate-500">Loading...</td>
              </tr>
              <tr v-else-if="studyLevels.length === 0">
                <td colspan="4" class="px-6 py-12 text-center text-slate-500">No study levels found</td>
              </tr>
              <tr v-else v-for="level in studyLevels" :key="level.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900 dark:text-white">{{ level.code }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900 dark:text-white">{{ level.label }}</td>
                <td class="px-6 py-4 text-sm text-slate-500 dark:text-slate-400">{{ level.description || 'N/A' }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                  <button @click="openEditModal(level)" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400">Edit</button>
                  <button @click="deleteLevel(level)" class="text-red-600 hover:text-red-900 dark:text-red-400">Delete</button>
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
              {{ editingLevel ? 'Edit Study Level' : 'New Study Level' }}
            </h3>
            <button @click="closeModal" class="text-slate-400 hover:text-slate-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <form @submit.prevent="saveLevel" class="p-6 space-y-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Code *</label>
              <input v-model="form.code" type="text" required placeholder="e.g., L1, M2" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white" />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Label *</label>
              <input v-model="form.label" type="text" required placeholder="e.g., Bachelor Year 1" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white" />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Description</label>
              <textarea v-model="form.description" rows="3" class="w-full px-3 py-2 border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-slate-900 dark:text-white"></textarea>
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
import studyLevelService from '@/services/studyLevelService';

const studyLevels = ref([]);
const loading = ref(false);
const search = ref('');
const showModal = ref(false);
const editingLevel = ref(null);
const saving = ref(false);
const searchTimeout = ref(null);
const form = ref({ code: '', label: '', description: '' });

async function loadStudyLevels() {
  loading.value = true;
  try {
    const response = await studyLevelService.index({ search: search.value });
    studyLevels.value = response.data.data || response.data;
  } catch (error) {
    console.error('Error:', error);
    alert('Error loading data');
  } finally {
    loading.value = false;
  }
}

function debounceSearch() {
  if (searchTimeout.value) clearTimeout(searchTimeout.value);
  searchTimeout.value = setTimeout(loadStudyLevels, 500);
}

function openCreateModal() {
  editingLevel.value = null;
  form.value = { code: '', label: '', description: '' };
  showModal.value = true;
}

function openEditModal(level) {
  editingLevel.value = level;
  form.value = { ...level };
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
  editingLevel.value = null;
}

async function saveLevel() {
  saving.value = true;
  try {
    if (editingLevel.value) {
      await studyLevelService.update(editingLevel.value.id, form.value);
    } else {
      await studyLevelService.store(form.value);
    }
    await loadStudyLevels();
    closeModal();
  } catch (error) {
    alert(error.response?.data?.message || 'Error');
  } finally {
    saving.value = false;
  }
}

async function deleteLevel(level) {
  if (!confirm(`Delete "${level.label}"?`)) return;
  try {
    await studyLevelService.destroy(level.id);
    await loadStudyLevels();
  } catch (error) {
    alert(error.response?.data?.message || 'Error');
  }
}

onMounted(loadStudyLevels);
</script>
