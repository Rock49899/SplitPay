<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="`Student: ${form.first_name || '...'} ${form.last_name || ''}`" />
    <div class="space-y-5 sm:space-y-6">
      <ComponentCard title="Student details">
        <!-- Avatar header -->
        <div class="flex items-center gap-4 mb-6 pb-5 border-b border-gray-100">
          <div class="relative shrink-0">
            <AvatarDisplay :src="avatarPreview || student?.avatar_url" :label="form.first_name" :size="72" />
            <label v-if="editMode" class="absolute -bottom-1 -right-1 w-6 h-6 bg-brand-500 rounded-full flex items-center justify-center cursor-pointer shadow">
              <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <input type="file" class="sr-only" accept="image/jpeg,image/jpg,image/png,image/webp" @change="onAvatarChange" />
            </label>
          </div>
          <div>
            <p class="font-semibold text-gray-900">{{ form.first_name || '—' }} {{ form.last_name }}</p>
            <p class="text-sm text-gray-500">{{ student?.matricule ?? '' }}</p>
            <p v-if="editMode" class="text-xs text-gray-400 mt-0.5">Click the camera icon to change photo</p>
          </div>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">First name</label>
            <template v-if="!editMode"><p class="mt-1 text-gray-900">{{ form.first_name || '—' }}</p></template>
            <template v-else><input v-model="form.first_name" class="mt-1 block w-full rounded-md border px-3 py-2 text-white bg-gray-800" /></template>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">Last name</label>
            <template v-if="!editMode"><p class="mt-1 text-gray-900">{{ form.last_name || '—' }}</p></template>
            <template v-else><input v-model="form.last_name" class="mt-1 block w-full rounded-md border px-3 py-2 text-white bg-gray-800" /></template>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">Email</label>
            <template v-if="!editMode"><p class="mt-1 text-gray-900">{{ form.email || '—' }}</p></template>
            <template v-else><input v-model="form.email" class="mt-1 block w-full rounded-md border px-3 py-2 text-white bg-gray-800" /></template>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">Phone</label>
            <template v-if="!editMode"><p class="mt-1 text-gray-900">{{ form.phone || '—' }}</p></template>
            <template v-else><input v-model="form.phone" class="mt-1 block w-full rounded-md border px-3 py-2 text-white bg-gray-800" /></template>
          </div>

          <!-- <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">Birth date</label>
            <template v-if="!editMode"><p class="mt-1 text-gray-900">{{ form.birth_date || '—' }}</p></template>
            <template v-else><input v-model="form.birth_date" type="date" class="mt-1 block w-full rounded-md border px-3 py-2 text-white bg-gray-800" /></template>
          </div> -->

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">Matricule</label>
            <p class="mt-1 text-gray-900">{{ student?.matricule ?? '—' }}</p>
          </div>

          <!-- <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">Student No.</label>
            <p class="mt-1 text-gray-900">{{ student?.student_number ?? '—' }}</p>
          </div> -->

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">Class / Section</label>
            <p class="mt-1 text-gray-900">{{ student?.class_name ?? student?.class ?? '—' }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">Study Year</label>
            <p class="mt-1 text-gray-900">{{ form.study_year ?? '—' }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">Annexes</label>
            <p class="mt-1 text-gray-900">{{ annexeNames(student) || 'No annexes' }}</p>
          </div>
        </div>

        <!-- Financial summary card -->
        <!-- <div class="mt-6">
          <ComponentCard title="Financial summary">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div>
                <div class="text-sm text-gray-500">Tuition</div>
                <div class="font-semibold">{{ finance.tuition_amount ?? '-' }}</div>
              </div>
              <div>
                <div class="text-sm text-gray-500">Paid</div>
                <div class="font-semibold">{{ finance.amount_paid ?? 0 }}</div>
              </div>
              <div>
                <div class="text-sm text-gray-500">Due</div>
                <div class="font-semibold">{{ finance.amount_due ?? 0 }}</div>
              </div>
              <div>
                <div class="text-sm text-gray-500">Last payment</div>
                <div class="font-semibold">{{ finance.last_payment_date ?? '-' }}</div>
              </div>
            </div>

            <div class="mt-4">
              <button @click="createPaymentLink" class="px-4 py-2 bg-brand-500 text-white rounded">Create payment link</button>
              <div v-if="paymentLink" class="mt-2">
                <a :href="paymentLink" target="_blank" class="text-indigo-600 underline">{{ paymentLink }}</a>
              </div>

              <div v-if="payments.length" class="mt-4">
                <h4 class="text-sm font-medium">Recent payments</h4>
                <ul class="mt-2 space-y-2">
                  <li v-for="p in payments" :key="p.id" class="text-sm text-gray-700">
                    {{ p.amount }} — {{ new Date(p.created_at).toLocaleDateString() }}
                  </li>
                </ul>
              </div>
            </div>
          </ComponentCard>
        </div> -->

        <div class="mt-6 flex gap-3">
            <button v-if="!editMode" @click="enterEdit" class="px-4 py-2 bg-brand-500 text-white rounded">Edit</button>
            <button v-else @click="save" :disabled="saving" class="px-4 py-2 bg-brand-500 text-white rounded">Save</button>
            <button v-if="editMode" @click="cancelEdit" class="px-4 py-2 border rounded">Cancel</button>
            <button @click="toggleActiveStatus" class="px-3 py-2 border rounded">
              <!-- afficher action selon le status courant -->
              {{ student?.status === 'active' ? 'Suspend' : 'Activate' }}
            </button>
            <router-link :to="`/admin/students/${id}/finance`" class="px-3 py-2 bg-indigo-600 text-white rounded">Finance</router-link>
        </div>
      </ComponentCard>
      <div v-if="error" class="text-sm text-red-600 mt-2">{{ error }}</div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AdminLayout from '@/components/layout/AdminLayout.vue';
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue';
import ComponentCard from '@/components/common/ComponentCard.vue';
import AvatarDisplay from '@/components/shared/AvatarDisplay.vue';
import studentService from '@/services/studentService';

const route = useRoute();
const router = useRouter();
const id = route.params.id;

const student = ref(null);
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
});
const finance = ref({});
const payments = ref([]);
const paymentLink = ref(null);
const loading = ref(false);
const saving = ref(false);
const error = ref(null);
const editMode = ref(false);

const load = async () => {
  loading.value = true;
  error.value = null;
  try {
    const res = await studentService.show(id);
    const payload = res.data;
    const s = payload.student ?? payload.data ?? payload;
    student.value = s;
    form.value = {
      first_name: s?.first_name ?? '',
      last_name: s?.last_name ?? '',
      email: s?.email ?? '',
      phone: s?.phone ?? '',
    };

    // load financials
    try {
      const f = await studentService.financials(id);
      finance.value = f.data?.data ?? f.data ?? {};
      payments.value = finance.value.recent_payments ?? [];
    } catch (err) {
      finance.value = {};
      payments.value = [];
    }
  } catch (e) {
    const status = e?.response?.status;
    if (status === 401) { router.push('/signin'); return; }
    error.value = e.response?.data?.message || e.message || 'Failed to load student';
  } finally {
    loading.value = false;
  }
};

const enterEdit = () => { editMode.value = true; };
const cancelEdit = () => {
  if (student.value) {
    form.value = {
      first_name: student.value.first_name ?? '',
      last_name: student.value.last_name ?? '',
      email: student.value.email ?? '',
      phone: student.value.phone ?? '',
    };
  }
  avatarFile.value = null;
  avatarPreview.value = null;
  editMode.value = false;
};

const save = async () => {
  saving.value = true;
  error.value = null;
  try {
    const fd = new FormData();
    Object.entries(form.value).forEach(([k, v]) => {
      if (v != null && v !== '') fd.append(k, v);
    });
    if (avatarFile.value) fd.append('avatar', avatarFile.value);
    await studentService.update(id, fd);
    avatarFile.value = null;
    avatarPreview.value = null;
    await load();
    editMode.value = false;
  } catch (e) {
    error.value = e.response?.data?.message || e.message || 'Update failed';
  } finally {
    saving.value = false;
  }
};

// bascule du statut de l'étudiant : envoie "status" attendu par le backend
const toggleActiveStatus = async () => {
  if (!student.value) return;
  try {
    const newStatus = student.value.status === 'active' ? 'suspended' : 'active';
    await studentService.update(id, { status: newStatus });
    await load();
  } catch (e) {
    console.error(e);
    if (e?.response?.status === 422) {
      const data = e.response.data ?? {};
      let msg = data.message ?? 'Validation failed';
      if (data.errors) {
        const details = Object.values(data.errors).flat().join(' ');
        if (details) msg += ' — ' + details;
      }
      error.value = msg;
    } else {
      error.value = e?.response?.data?.message ?? e.message ?? 'Failed to toggle status';
    }
  }
};

const annexeNames = (s) => {
  if (!s) return '';
  if (Array.isArray(s.annexes) && s.annexes.length) return s.annexes.map(a => a.name ?? a.id).join(', ');
  if (s.annexe) return s.annexe.name ?? s.annexe.id;
  return '';
};

const createPaymentLink = async () => {
  try {
    const amount = finance.value.amount_due ?? finance.value.tuition_amount ?? 0;
    const res = await studentService.createPaymentLink(id, { amount });
    paymentLink.value = res.data?.link ?? res.data?.url ?? res.data;
  } catch (e) {
    console.error(e);
    alert('Failed to create payment link');
  }
};

onMounted(load);
</script>

<style scoped>
/* minimal styles */
</style>
