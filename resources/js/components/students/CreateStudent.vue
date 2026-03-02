<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center">
    <div class="fixed inset-0 bg-black/50" @click="close"></div>
    <div class="bg-white dark:bg-gray-900 rounded-lg p-6 z-50 w-full max-w-2xl shadow-lg max-h-[80vh] overflow-auto">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Create Student</h3>
        <button @click="close" class="text-gray-500 hover:text-gray-700">✕</button>
      </div>

      <div class="grid grid-cols-1 gap-3">
        <!-- Avatar upload -->
        <div class="flex items-center gap-4">
          <div class="w-16 h-16 rounded-full bg-gray-100 border border-gray-200 flex items-center justify-center overflow-hidden shrink-0">
            <img v-if="avatarPreview" :src="avatarPreview" class="w-full h-full object-cover" />
            <svg v-else class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
            </svg>
          </div>
          <div class="flex-1">
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Photo (optional)</label>
            <input type="file" accept="image/jpeg,image/jpg,image/png,image/webp" @change="onAvatarChange" class="block w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100" />
            <p class="text-xs text-gray-400 mt-0.5">JPG, PNG or WebP &mdash; max 2 MB, square recommended</p>
          </div>
        </div>
        <input v-model="form.first_name" placeholder="First name" class="px-3 py-2 rounded border bg-transparent text-gray-900 dark:text-white" />
        <input v-model="form.last_name" placeholder="Last name" class="px-3 py-2 rounded border bg-transparent text-gray-900 dark:text-white" />
        <input v-model="form.email" placeholder="Email" class="px-3 py-2 rounded border bg-transparent text-gray-900 dark:text-white" />
        <input v-model="form.phone" placeholder="Phone" class="px-3 py-2 rounded border bg-transparent text-gray-900 dark:text-white" />

        <input v-model="form.matricule" placeholder="Matricule" class="px-3 py-2 rounded border bg-transparent text-gray-900 dark:text-white" />
        
        <div>
          <label class="block text-sm mb-1 text-gray-700 dark:text-gray-400">Study Level</label>
          <select v-model="form.study_level_id" class="w-full px-3 py-2 rounded border bg-transparent text-gray-900 dark:text-white">
            <option :value="null">Select study level</option>
            <option v-for="level in studyLevels" :key="level.id" :value="level.id">{{ level.label }}</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm mb-1 text-gray-700 dark:text-gray-400">Specialization</label>
          <select v-model="form.specialization_id" class="w-full px-3 py-2 rounded border bg-transparent text-gray-900 dark:text-white">
            <option :value="null">Select specialization</option>
            <option v-for="spec in specializations" :key="spec.id" :value="spec.id">{{ spec.label }}</option>
          </select>
        </div>
        
        <input v-model.number="form.tuition_amount" placeholder="Tuition amount" type="number" class="px-3 py-2 rounded border bg-transparent text-gray-900 dark:text-white" />
        <label class="inline-flex items-center gap-2">
          <input type="checkbox" v-model="form.is_active" />
          <span class="text-sm text-gray-700 dark:text-gray-400">Active</span>
        </label>

        <div>
          <label class="block text-sm mb-1 text-gray-700 dark:text-gray-400">Annexes (select one primary)</label>
          <div class="max-h-40 overflow-auto p-2 border rounded bg-gray-50 dark:bg-gray-800 space-y-1">
            <div v-for="a in annexesLocal" :key="a.id" class="flex items-center gap-2">
              <input type="checkbox" :value="a.id" v-model="form.annexes" />
              <label class="flex-1 text-gray-900 dark:text-white">{{ a.name }}</label>
              <input type="radio" name="primary" :value="a.id" v-model="form.primary_annexe" :disabled="!form.annexes.includes(a.id)" />
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
import { ref, onMounted } from 'vue';
import studentService from '@/services/studentService';
import annexeService from '@/services/annexeService';
import studyLevelService from '@/services/studyLevelService';
import specializationService from '@/services/specializationService';
import api from '@/services/api';
import { useAnnexeStore } from '@/stores/useAnnexeStore';
const annexeStore = useAnnexeStore();

const props = defineProps({
  annexes: { type: Array, default: () => [] },
});

const emit = defineEmits(['created', 'close']);

const annexesLocal = ref(props.annexes ?? []);
const studyLevels = ref([]);
const specializations = ref([]);

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
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  matricule: '',
  study_level_id: null,
  specialization_id: null,
  study_level_id: null,
  specialization_id: null,
  tuition_amount: null,
  is_active: true,
  annexes: [],
  primary_annexe: null,
});

const loading = ref(false);
const error = ref(null);

onMounted(async () => {
  if (!annexesLocal.value.length) {
    await annexeStore.fetchAnnexes();
    annexesLocal.value = annexeStore.items;
  }
  await loadStudyLevels();
  await loadSpecializations();
});

const loadStudyLevels = async () => {
  try {
    const res = await studyLevelService.index();
    studyLevels.value = res.data?.data ?? res.data ?? [];
  } catch (err) {
    console.error('Failed to load study levels', err);
  }
};

const loadSpecializations = async () => {
  try {
    const res = await specializationService.index();
    specializations.value = res.data?.data ?? res.data ?? [];
  } catch (err) {
    console.error('Failed to load specializations', err);
  }
};

const close = () => emit('close');

const submit = async () => {
  error.value = null;
  // minimal validation
  if (!form.value.first_name || !form.value.last_name) {
    error.value = 'First and last name required';
    return;
  }
  if (!form.value.matricule) {
    error.value = 'Matricule is required';
    return;
  }
  if (!form.value.annexes.length && !form.value.primary_annexe) {
    error.value = 'Select at least one annexe';
    return;
  }

  loading.value = true;
  try {
    const fd = new FormData();
    fd.append('first_name', form.value.first_name);
    fd.append('last_name', form.value.last_name);
    if (form.value.email) fd.append('email', form.value.email);
    if (form.value.phone) fd.append('phone', form.value.phone);
    fd.append('matricule', form.value.matricule);
    if (form.value.study_level_id) fd.append('study_level_id', form.value.study_level_id);
    if (form.value.specialization_id) fd.append('specialization_id', form.value.specialization_id);
    if (form.value.tuition_amount != null) fd.append('tuition_amount', form.value.tuition_amount);
    fd.append('is_active', form.value.is_active ? '1' : '0');
    fd.append('annexe_id', form.value.primary_annexe || (form.value.annexes[0] ?? ''));
    form.value.annexes.forEach(id => fd.append('annexes[]', id));
    if (avatarFile.value) fd.append('avatar', avatarFile.value);

    const res = await studentService.store(fd);
    const created = res.data?.student ?? res.data;
    // optionally assign other annexes via API if backend requires; omitted for brevity
    emit('created', created);
    close();
  } catch (e) {
    error.value = e.response?.data?.message || e.message || 'Create failed';
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
/* minimal */
</style>
