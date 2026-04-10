<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center">
    <div class="fixed inset-0 bg-black/50" @click="close"></div>
    <div class="bg-white dark:bg-slate-800 border border-transparent dark:border-slate-700 rounded-xl p-6 z-50 w-full max-w-3xl shadow-xl overflow-auto max-h-[88vh]">
      <div class="flex items-center justify-between mb-5">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Créer une annexe</h3>
        <button @click="close" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">✕</button>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="sm:col-span-2">
          <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Nom de l'annexe *</label>
          <input v-model="form.name" placeholder="Nom de l'annexe" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-transparent text-gray-900 dark:text-white" />
        </div>

        <div>
          <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Adresse</label>
          <input v-model="form.address" placeholder="Adresse" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-transparent text-gray-900 dark:text-white" />
        </div>

        <div>
          <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Ville</label>
          <input v-model="form.city" placeholder="Ville" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-transparent text-gray-900 dark:text-white" />
        </div>
        
        <!-- Contact Details -->
        <div class="sm:col-span-2 border-t border-gray-100 dark:border-gray-800 pt-3 mt-1">
          <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Informations de contact</h4>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <input v-model="form.email" type="email" placeholder="Email de contact" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-transparent text-gray-900 dark:text-white" />
            <input v-model="form.phone" type="tel" placeholder="Téléphone" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-transparent text-gray-900 dark:text-white" />
            <input v-model="form.fax" type="tel" placeholder="Fax (optionnel)" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-transparent text-gray-900 dark:text-white" />
            <input v-model="form.website" type="url" placeholder="Site web (optionnel)" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-transparent text-gray-900 dark:text-white" />
          </div>
        </div>
<!-- 
        <div class="sm:col-span-2">
          <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Responsable (super admin annexe)</label>
          <select v-model="form.manager_id" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-700 bg-transparent text-gray-900 dark:text-white">
            <option value="">-- sélectionner un responsable --</option>
            <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name ?? u.email }}</option>
          </select>
        </div>

        <div class="sm:col-span-2">
          <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Ajouter des utilisateurs à cette annexe (sélectionner utilisateurs et rôle)</label>
          <div class="max-h-56 overflow-auto border border-gray-200 dark:border-slate-700 rounded-lg p-2 bg-gray-50 dark:bg-slate-900/40 space-y-2">
            <div v-for="u in users" :key="u.id" class="flex items-center gap-3">
              <input type="checkbox" :value="u.id" v-model="selectedUsers" />
              <div class="flex-1 text-sm text-gray-900 dark:text-white">{{ u.name ?? u.email }}</div>
              <select v-model="userRole[u.id]" class="rounded-lg border border-gray-300 dark:border-gray-700 px-2 py-1 bg-transparent text-gray-900 dark:text-white">
                <option value="" :style="optionStyle">-- rôle --</option>
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
        </div> -->

        <div v-if="error" class="sm:col-span-2 text-sm text-red-600 bg-red-50 dark:bg-red-900/20 rounded-lg px-3 py-2">{{ error }}</div>

        <div class="sm:col-span-2 flex gap-2 justify-end mt-1">
          <button @click="close" class="px-4 py-2 border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-slate-200 rounded-lg text-sm">Annuler</button>
          <button @click="submit" :disabled="loading" class="px-5 py-2 bg-brand-500 text-white rounded-lg text-sm font-medium disabled:opacity-60">
            <span v-if="!loading">Créer</span><span v-else>Création...</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useAnnexeStore } from '@/stores/useAnnexeStore';

const annexeStore = useAnnexeStore();

const emit = defineEmits(['created','close']);


const form = ref({
  name: '',
  address: '',
  city: '',
  email: '',
  phone: '',
  fax: '',
  website: '',
});

const loading = ref(false);
const error = ref(null);
const isDark = ref(false);

onMounted(async () => {
  // detect theme
  isDark.value = document.documentElement.classList.contains('dark');
});

const close = () => emit('close');

const submit = async () => {
  error.value = null;
  if (!form.value.name) { error.value = 'Nom obligatoire'; return; }

  loading.value = true;
  try {
    // Construire annexe_details comme objet JSON structuré
    const annexeDetails = {
      email: form.value.email || null,
      phone: form.value.phone || null,
      fax: form.value.fax || null,
      website: form.value.website || null,
    };
    
    const res = await annexeStore.createAnnexe({
      name: form.value.name,
      address: form.value.address ?? null,
      city: form.value.city ?? null,
      annexe_details: annexeDetails,
      status: 'active',
    });
    const annexe = res.data?.annexe ?? res.data ?? res;

    emit('created', annexe);
    close();
  } catch (e) {
    console.error(e);
    error.value = e.response?.data?.message || e.message || 'Création échouée';
  } finally {
    loading.value = false;
  }
};
</script>

