<template>
  <AdminLayout>
    <PageBreadcrumb :pageTitle="currentPageTitle" />
    <div class="flex items-center gap-3 mb-4">
      <button @click="showCreateModal = true" class="px-4 py-2 bg-brand-500 text-white rounded">Add Student</button>
      <button @click="showCols = true" class="px-3 py-2 border rounded">Columns</button>
    </div>

    <!-- Filters moved here: Annexe / Study Level / Specialization / Apply / Clear -->
    <div class="flex flex-wrap gap-3 items-end mb-4">
      <div class="w-56">
        <label class="block text-xs text-gray-500 mb-1">Annexe</label>
        <select v-model="filters.annexe_id" class="w-full rounded border px-3 py-2">
          <option :value="null">All annexes</option>
          <option v-for="a in annexes" :key="a.id" :value="a.id">{{ a.name }}</option>
        </select>
      </div>
      <div class="w-40">
        <label class="block text-xs text-gray-500 mb-1">Study Level</label>
        <select v-model="filters.study_level_id" class="w-full rounded border px-3 py-2">
          <option :value="null">All levels</option>
          <option v-for="level in studyLevels" :key="level.id" :value="level.id">{{ level.label }}</option>
        </select>
      </div>
      <div class="w-40">
        <label class="block text-xs text-gray-500 mb-1">Specialization</label>
        <select v-model="filters.specialization_id" class="w-full rounded border px-3 py-2">
          <option :value="null">All specializations</option>
          <option v-for="spec in specializations" :key="spec.id" :value="spec.id">{{ spec.label }}</option>
        </select>
      </div>
      <div class="flex items-center gap-2">
        <button @click="applyFilters" class="px-3 py-2 bg-brand-500 text-white rounded">Apply</button>
        <button @click="clearFilters" class="px-3 py-2 border rounded">Clear</button>
      </div>
    </div>

    <CreateStudent v-if="showCreateModal" :annexes="annexes" @created="onCreated" @close="showCreateModal = false" />

    <StudentColumnsSelector
      v-if="showCols"
      :columns="availableColumns"
      :value="visibleColumns"
      @update:value="visibleColumns = $event"
      @close="showCols = false"
    />

    <div class="space-y-5 sm:space-y-6">
      <ComponentCard :title="`${annexeName}`" v-for="(studentsInAnnexe, annexeName) in groupedByAnnexe" :key="annexeName" >
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th v-if="visibleColumns.includes('name')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                <th v-if="visibleColumns.includes('matricule')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Matricule</th>
                <th v-if="visibleColumns.includes('email')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                <th v-if="visibleColumns.includes('phone')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                <th v-if="visibleColumns.includes('study_level')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Study Level</th>
                <th v-if="visibleColumns.includes('specialization')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Specialization</th>
                <th v-if="visibleColumns.includes('annexes')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Annexes</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr 
                v-for="s in (studentsInAnnexe || [])" 
                :key="s.id"
                @click="router.push(`/admin/students/${s.id}`)"
                class="cursor-pointer hover:bg-gray-50 transition-colors"
              >
                <td v-if="visibleColumns.includes('name')" class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  <div class="flex items-center gap-2.5">
                    <AvatarDisplay 
                      :src="s.avatar_url" 
                      :label="s.first_name" 
                      :size="32"
                      :clickable="!!s.avatar_url"
                      @click.stop="() => { selectedStudentAvatar = { url: s.avatar_url, name: `${s.first_name} ${s.last_name}` }; showImageModal = true; }"
                    />
                    <span>{{ s.first_name }} {{ s.last_name }}</span>
                  </div>
                </td>
                <td v-if="visibleColumns.includes('matricule')" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ s.matricule ?? '-' }}</td>
                <td v-if="visibleColumns.includes('email')" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ s.email ?? '-' }}</td>
                <td v-if="visibleColumns.includes('phone')" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ s.phone ?? '-' }}</td>
                <td v-if="visibleColumns.includes('study_level')" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ s?.current_enrollment?.level_fee?.study_level?.label ?? '—' }}</td>
                <td v-if="visibleColumns.includes('specialization')" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ s.specialization?.label ?? s.specialization?.code ?? '-' }}</td>
                <td v-if="visibleColumns.includes('annexes')" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ annexeNames(s) || '-' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                  <button @click.stop="toggleActive(s)" class="text-sm" :class="s.is_active ? 'text-green-600' : 'text-red-600'">
                    {{ s.is_active ? 'Active' : 'Inactive' }}
                  </button>
                </td>
                <td class="px-6 py-4 text-right whitespace-nowrap text-sm">
                  <router-link @click.stop :to="`/admin/students/${s.id}`" class="text-brand-500 mr-3">View</router-link>
                  <router-link @click.stop :to="`/admin/students/${s.id}/finance`" class="text-indigo-600 mr-3">Finance</router-link>
                  <button @click.stop="remove(s.id)" class="text-red-500">Delete</button>
                </td>
              </tr>
              <tr v-if="!studentsInAnnexe || studentsInAnnexe.length === 0">
                <td colspan="10" class="px-6 py-4 text-center text-sm text-gray-500">No students</td>
              </tr>
            </tbody>
          </table>
        </div>
      </ComponentCard>

      <div class="flex items-center justify-between">
        <div></div>
        <div class="flex items-center gap-2">
          <button @click="prevPage" :disabled="students.page <= 1" class="px-3 py-1 border rounded">Prev</button>
          <span>Page {{ students.page }}</span>
          <button @click="nextPage" :disabled="students.meta && students.page >= students.meta.last_page" class="px-3 py-1 border rounded">Next</button>
        </div>
      </div>
    </div>

    <!-- Modal pour agrandir l'avatar -->
    <ImageViewerModal
      v-model="showImageModal"
      :image-src="selectedStudentAvatar.url"
      :image-alt="selectedStudentAvatar.name"
    />
  </AdminLayout>
</template>

<script setup>
import { ref, reactive, onMounted, computed, watch } from 'vue';
import AdminLayout from '@/components/layout/AdminLayout.vue';
import PageBreadcrumb from '@/components/common/PageBreadcrumb.vue';
import ComponentCard from '@/components/common/ComponentCard.vue';
import CreateStudent from '@/components/students/CreateStudent.vue';
import StudentColumnsSelector from '@/components/students/StudentColumnsSelector.vue';
import AvatarDisplay from '@/components/shared/AvatarDisplay.vue';
import ImageViewerModal from '@/components/shared/ImageViewerModal.vue';
import { useStudentStore } from '@/stores/useStudentStore';
import { useActiveYearStore } from '@/stores/useActiveYearStore';
import studentService from '@/services/studentService';
import studyLevelService from '@/services/studyLevelService';
import specializationService from '@/services/specializationService';
import { useRouter, useRoute } from 'vue-router';
import { useAnnexeStore } from '@/stores/useAnnexeStore';
const annexeStore = useAnnexeStore();
const activeYearStore = useActiveYearStore();

const currentPageTitle = ref('Students');
const students = useStudentStore();
students.page = students.page || 1;
const router = useRouter();
const route = useRoute();

const showCreateModal = ref(false);
const showCols = ref(false);
const showImageModal = ref(false);
const selectedStudentAvatar = ref({ url: '', name: '' });

const perPage = ref(15);
const loading = ref(false);

const annexes = ref([]);
const studyLevels = ref([]);
const specializations = ref([]);

// filtres de recherche (utilisables par superadmin)
const filters = reactive({
  annexe_id: null,
  study_level_id: null,
  specialization_id: null,
  q: '',
});

onMounted(async () => {
  try {
    // load annexes first so enrichStudents can map annexe_id -> annexe.name
    await annexeStore.fetchAnnexes();
    annexes.value = annexeStore.items;
    
    // Load study levels and specializations
    await loadStudyLevels();
    await loadSpecializations();
    
    students.setQuery(route.query.search ?? '');
    await students.fetchStudents();
    await enrichStudents(); // enrich now that annexes are available
  } catch (e) {
    const status = e?.response?.status;
    if (status === 401) { router.push('/signin'); return; }
    console.error('Failed fetching students', e);
  }
});

// react to URL search param changes
watch(
  () => route.query.search,
  async (newSearch) => {
    try {
      students.setQuery(newSearch ?? '');
      students.setPage(1);
      await students.fetchStudents();
      await enrichStudents();
    } catch (e) {
      console.error('Search fetch failed', e);
      // optional: show a brief user-friendly message
      alert('Search failed. Please try again or check the server logs.');
    }
  },
  { immediate: false }
);

const availableColumns = [
  { key: 'name', label: 'Name' },
  { key: 'matricule', label: 'Matricule' },
  { key: 'student_number', label: 'Student No.' },
  { key: 'email', label: 'Email' },
  { key: 'phone', label: 'Phone' },
  { key: 'study_level', label: 'Study Level' },
  { key: 'specialization', label: 'Specialization' },
  { key: 'annexes', label: 'Annexes' },
];
// default visible
const visibleColumns = ref(['name','matricule','study_level','specialization','annexes']);

const annexeNames = (s) => {
  // if (!s) return '';
  // if (Array.isArray(s.student_annexes) && s.student_annexes.length) {
  //   return s.student_annexes.map(sa => sa.annexe?.name ?? sa.annexe_name).filter(Boolean).join(', ');
  // }
  // if (Array.isArray(s.annexes)) return s.annexes.map(a=>a.name).filter(Boolean).join(', ');
  if (s.annexe) return s.annexe.name ?? s.annexe.id;
  return '';
};

const annexeGroupKey = (s) => {
  const names = annexeNames(s);
  if (!names) return null;
  return names.split(',')[0].trim();
};

const groupedByAnnexe = computed(() => {
  const map = {};
  const items = Array.isArray(students.items) ? students.items : [];
  items.forEach(s => {
    const key = annexeGroupKey(s) || 'No Annexe';
    if (!map[key]) map[key] = [];
    map[key].push(s);
  });
  return map;
});

const enrichStudents = async () => {
  const items = Array.isArray(students.items) ? students.items : [];
  if (!items.length) return;
  try {
    const details = await Promise.all(items.map(s => studentService.show(s.id).then(r => r.data?.student ?? r.data ?? null).catch(() => null)));
    // merge back important fields
    const merged = items.map((s, i) => {
      const d = details[i] || {};
      const annId = d.annexe_id ?? d.annexe?.id ?? s.annexe_id ?? s.annexe?.id ?? null;
      const annFromStore = annId ? (annexeStore.items || []).find(a => String(a.id) === String(annId)) : null;
      const annObj = d.annexe ?? annFromStore ?? s.annexe ?? null;
      return {
        ...s,
        matricule: s.matricule ?? d.matricule ?? null,
        student_number: s.student_number ?? d.student_number ?? null,
        class_name: s.class_name ?? d.class_name ?? d.class ?? null,
        study_year: s.school_year ?? d.school_year ?? d.study_year ?? null,
        annexe: annObj,
        annexes: Array.isArray(d.annexes) ? d.annexes : (annObj ? [annObj] : (s.annexes ?? [])),
      };
    });
    // update store items (reactive)
    students.items = merged;
  } catch (e) {
    console.error('Failed enriching students', e);
  }
};

const prevPage = async () => {
  if (students.page > 1) {
    students.setPage(students.page - 1);
    await students.fetchStudents();
    await enrichStudents();
  }
};
const nextPage = async () => {
  if (!students.meta || !students.meta.last_page || students.page < students.meta.last_page) {
    students.setPage(students.page + 1);
    await students.fetchStudents();
    await enrichStudents();
  }
};

const remove = async (id) => {
  if (!confirm('Delete this student?')) return;
  try {
    await students.deleteStudent(id);
    await students.fetchStudents();
    await enrichStudents(); // refresh enriched data
    alert('Student deleted');
  } catch (e) {
    console.error('Delete failed', e);
    alert('Failed to delete student');
  }
};

const toggleActive = async (s) => {
  try {
    const newStatus = s.is_active ? 'suspended' : 'active';
    await studentService.update(s.id, { status: newStatus });
     await students.fetchStudents();
   } catch (e) { console.error(e); alert('Failed toggling status'); }
};

const onCreated = async (created) => {
  await students.fetchStudents();
  await enrichStudents();
  showCreateModal.value = false;
};

const applyFilters = () => loadStudents(1);
const clearFilters = () => {
  filters.annexe_id = null;
  filters.study_level_id = null;
  filters.specialization_id = null;
  filters.q = '';
  loadStudents(1);
};

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

// Recharger quand l'année active change (sélecteur AppHeader ou clôture d'année)
watch(() => activeYearStore.activeYear, () => loadStudents(1))

const loadStudents = async (page = 1) => {
  loading.value = true;
  try {
    const params = {
      per_page: perPage.value,
      page,
      school_year: activeYearStore.activeYear || undefined,
      annexe_id: filters.annexe_id || undefined,
      study_level_id: filters.study_level_id || undefined,
      specialization_id: filters.specialization_id || undefined,
      search: filters.q || undefined,
    };
    const res = await studentService.index(params);
    // mettre à jour le store réactif utilisé ailleurs (items, meta, page)
    students.items = res.data?.data ?? res.data ?? [];
    if (res.data?.meta) {
      students.meta = res.data.meta;
      students.page = res.data.meta.current_page ?? students.page;
    } else if (res.data?.current_page) {
      students.page = res.data.current_page;
      students.meta = res.data;
    }
    
    await enrichStudents();
  } catch (e) {
    console.error('Failed to fetch students', e);
  } finally {
    loading.value = false;
  }
};

</script>
