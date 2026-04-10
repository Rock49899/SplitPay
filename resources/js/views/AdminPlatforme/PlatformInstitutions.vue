<template>
  <AdminPlatformeLayout>
    <section class="space-y-5">
      <div class="rounded-xl bg-[#1b1b1c] p-4 ring-1 ring-[#2a2a2a] md:p-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div class="w-full sm:max-w-md">
            <input
              v-model="search"
              @keyup.enter="loadInstitutions(1)"
              type="text"
              placeholder="Rechercher une institution..."
              class="w-full rounded-full border border-[#3e4945] bg-[#202020] px-4 py-2 text-sm text-[#e5e2e1] placeholder:text-[#88938e] focus:border-[#7bd7bd] focus:outline-none"
            />
          </div>
          <button
            @click="loadInstitutions(1)"
            class="rounded-xl bg-[#0e7c66] px-4 py-2 text-sm font-bold text-white transition hover:brightness-110"
          >
            Rechercher
          </button>
        </div>
      </div>

      <div class="overflow-x-auto rounded-xl bg-[#1b1b1c] ring-1 ring-[#2a2a2a]">
        <table class="min-w-full border-collapse text-left">
          <thead>
            <tr class="border-b border-[#2a2a2a] bg-[#202020]/70 text-[#bdc9c4]">
              <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider">Nom</th>
              <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider">Email</th>
              <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider">Téléphone</th>
              <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider">Statut</th>
              <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#2a2a2a]">
            <tr
              v-for="inst in institutions"
              :key="inst.id"
              class="cursor-pointer transition-colors hover:bg-white/5"
              @click="goToDetails(inst.id)"
            >
              <td class="px-6 py-4 text-sm font-semibold text-[#e5e2e1]">
                <template v-if="editingId !== inst.id">{{ inst.name }}</template>
                <input
                  v-else
                  v-model="editForm.name"
                  @click.stop
                  class="w-full rounded-lg border border-[#3e4945] bg-[#202020] px-3 py-1.5 text-sm text-[#e5e2e1] focus:border-[#7bd7bd] focus:outline-none"
                />
              </td>
              <td class="px-6 py-4 text-sm text-[#bdc9c4]">
                <template v-if="editingId !== inst.id">{{ inst.email || '—' }}</template>
                <input
                  v-else
                  v-model="editForm.email"
                  @click.stop
                  class="w-full rounded-lg border border-[#3e4945] bg-[#202020] px-3 py-1.5 text-sm text-[#e5e2e1] focus:border-[#7bd7bd] focus:outline-none"
                />
              </td>
              <td class="px-6 py-4 text-sm text-[#bdc9c4]">
                <template v-if="editingId !== inst.id">{{ inst.phone || '—' }}</template>
                <input
                  v-else
                  v-model="editForm.phone"
                  @click.stop
                  class="w-full rounded-lg border border-[#3e4945] bg-[#202020] px-3 py-1.5 text-sm text-[#e5e2e1] focus:border-[#7bd7bd] focus:outline-none"
                />
              </td>
              <td class="px-6 py-4 text-sm">
                <span
                  class="inline-flex rounded-full px-3 py-1 text-[10px] font-black uppercase tracking-widest"
                  :class="inst.is_active ? 'bg-[#0e7c66]/20 text-[#7bd7bd] ring-1 ring-[#0e7c66]/30' : 'bg-[#93000a]/20 text-[#ffb4ab] ring-1 ring-[#93000a]/30'"
                >
                  {{ inst.is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
              <td class="px-6 py-4 text-right text-sm">
                <template v-if="editingId !== inst.id">
                  <button @click.stop="goToDetails(inst.id)" class="mr-3 font-semibold text-[#7bd7bd] transition hover:text-white">Détails</button>
                  <button @click.stop="startEdit(inst)" class="mr-3 font-semibold text-[#7bd7bd] transition hover:text-white">Modifier</button>
                  <button @click.stop="toggleInstitutionStatus(inst)" class="font-semibold text-[#ffb95f] transition hover:text-white">
                    {{ inst.is_active ? 'Désactiver' : 'Activer' }}
                  </button>
                </template>
                <template v-else>
                  <button @click.stop="saveEdit(inst.id)" class="mr-3 font-semibold text-[#7bd7bd] transition hover:text-white">Enregistrer</button>
                  <button @click.stop="cancelEdit" class="font-semibold text-slate-300 transition hover:text-white">Annuler</button>
                </template>
              </td>
            </tr>
            <tr v-if="!loading && institutions.length === 0">
              <td colspan="5" class="px-6 py-8 text-center text-sm text-[#bdc9c4]">Aucune institution trouvée.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="flex items-center justify-between rounded-xl bg-[#1b1b1c] px-6 py-4 ring-1 ring-[#2a2a2a]">
        <p class="text-xs text-[#bdc9c4]">Page {{ page }} sur {{ lastPage }}</p>
        <div class="flex items-center gap-2">
          <button
            @click="prevPage"
            :disabled="page <= 1 || loading"
            class="rounded-lg bg-[#202020] px-3 py-1.5 text-xs font-bold text-[#e5e2e1] transition hover:bg-[#2a2a2a] disabled:cursor-not-allowed disabled:opacity-40"
          >Précédent</button>
          <button
            @click="nextPage"
            :disabled="page >= lastPage || loading"
            class="rounded-lg bg-[#202020] px-3 py-1.5 text-xs font-bold text-[#e5e2e1] transition hover:bg-[#2a2a2a] disabled:cursor-not-allowed disabled:opacity-40"
          >Suivant</button>
        </div>
      </div>
    </section>
  </AdminPlatformeLayout>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import AdminPlatformeLayout from '@/components/AdminPlatforme/AdminPlatformeLayout.vue'

const router = useRouter()

const institutions = ref([])
const loading = ref(false)
const search = ref('')
const page = ref(1)
const lastPage = ref(1)
const perPage = ref(15)

const editingId = ref(null)
const editForm = ref({ name: '', email: '', phone: '' })

const loadInstitutions = async (targetPage = page.value) => {
  loading.value = true
  try {
    const params = {
      page: targetPage,
      per_page: perPage.value,
      name: search.value || undefined,
    }

    const res = await api.get('admin/institutions', { params })
    const payload = res.data ?? {}
    institutions.value = payload.data ?? []
    page.value = payload.current_page ?? targetPage
    lastPage.value = payload.last_page ?? 1
  } catch (e) {
    console.error('Failed loading institutions', e)
  } finally {
    loading.value = false
  }
}

const startEdit = (inst) => {
  editingId.value = inst.id
  editForm.value = {
    name: inst.name ?? '',
    email: inst.email ?? '',
    phone: inst.phone ?? '',
  }
}

const cancelEdit = () => {
  editingId.value = null
}

const saveEdit = async (id) => {
  try {
    await api.patch(`admin/institutions/${id}`, {
      name: editForm.value.name,
      email: editForm.value.email || null,
      phone: editForm.value.phone || null,
    })
    editingId.value = null
    await loadInstitutions(page.value)
  } catch (e) {
    console.error('Failed updating institution', e)
    alert('Échec de la mise à jour de l\'institution.')
  }
}

const toggleInstitutionStatus = async (inst) => {
  try {
    await api.patch(`admin/institutions/${inst.id}`, {
      name: inst.name,
      email: inst.email || null,
      phone: inst.phone || null,
      is_active: !inst.is_active,
    })
    await loadInstitutions(page.value)
  } catch (e) {
    console.error('Failed toggling institution status', e)
    alert('Échec du changement de statut.')
  }
}

const prevPage = () => {
  if (page.value > 1) loadInstitutions(page.value - 1)
}

const nextPage = () => {
  if (page.value < lastPage.value) loadInstitutions(page.value + 1)
}

const goToDetails = (id) => {
  router.push({ name: 'PlatformInstitutionDetails', params: { id } })
}

onMounted(() => {
  loadInstitutions(1)
})
</script>
