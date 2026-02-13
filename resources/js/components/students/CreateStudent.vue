<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center">
    <div class="fixed inset-0 bg-black/50" @click="close"></div>
    <div class="bg-white dark:bg-gray-900 rounded-lg p-6 z-50 w-full max-w-2xl shadow-lg max-h-[80vh] overflow-auto">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Create Student</h3>
        <button @click="close" class="text-gray-500 hover:text-gray-700">✕</button>
      </div>

      <div class="grid grid-cols-1 gap-3">
        <input v-model="form.first_name" placeholder="First name" class="px-3 py-2 rounded border bg-transparent text-gray-900 dark:text-white" />
        <input v-model="form.last_name" placeholder="Last name" class="px-3 py-2 rounded border bg-transparent text-gray-900 dark:text-white" />
        <input v-model="form.email" placeholder="Email" class="px-3 py-2 rounded border bg-transparent text-gray-900 dark:text-white" />
        <input v-model="form.phone" placeholder="Phone" class="px-3 py-2 rounded border bg-transparent text-gray-900 dark:text-white" />
        <input v-model="form.birth_date" type="date" placeholder="Birth date" class="px-3 py-2 rounded border bg-transparent text-gray-900 dark:text-white" />

        <input v-model="form.matricule" placeholder="Matricule" class="px-3 py-2 rounded border bg-transparent text-gray-900 dark:text-white" />
        <input v-model="form.student_number" placeholder="Student number" class="px-3 py-2 rounded border bg-transparent text-gray-900 dark:text-white" />
        <input v-model="form.class_name" placeholder="Class / Section" class="px-3 py-2 rounded border bg-transparent text-gray-900 dark:text-white" />
        <input v-model="form.study_year" placeholder="Study year" class="px-3 py-2 rounded border bg-transparent text-gray-900 dark:text-white" />
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
import api from '@/services/api';
import { useAnnexeStore } from '@/stores/useAnnexeStore';
const annexeStore = useAnnexeStore();

const props = defineProps({
  annexes: { type: Array, default: () => [] },
});

const emit = defineEmits(['created', 'close']);

const annexesLocal = ref(props.annexes ?? []);
const form = ref({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  birth_date: '',
  matricule: '',
  student_number: '',
  class_name: '',
  study_year: '',
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
});

const close = () => emit('close');

const submit = async () => {
  error.value = null;
  // minimal validation
  if (!form.value.first_name || !form.value.last_name) {
    error.value = 'First and last name required';
    return;
  }
  if (!form.value.matricule || !form.value.student_number) {
    error.value = 'Matricule and student number are required';
    return;
  }
  if (!form.value.annexes.length && !form.value.primary_annexe) {
    error.value = 'Select at least one annexe';
    return;
  }

  loading.value = true;
  try {
    const payload = {
      first_name: form.value.first_name,
      last_name: form.value.last_name,
      email: form.value.email || null,
      phone: form.value.phone || null,
      birth_date: form.value.birth_date || null,
      matricule: form.value.matricule,
      student_number: form.value.student_number,
      class_name: form.value.class_name || null,
      study_year: form.value.study_year || null,
      tuition_amount: form.value.tuition_amount ?? null,
      is_active: !!form.value.is_active,
      annexe_id: form.value.primary_annexe || (form.value.annexes[0] ?? null),
      annexes: form.value.annexes,
    };
    const res = await studentService.store(payload);
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
