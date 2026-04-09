<template>
  <AdminPlatformeLayout>
    <section class="space-y-5">
      <div class="rounded-xl bg-[#1b1b1c] p-4 ring-1 ring-[#2a2a2a] md:p-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div class="grid w-full grid-cols-1 gap-3 sm:max-w-3xl sm:grid-cols-2">
            <select
              v-model="institutionId"
              class="w-full rounded-full border border-[#3e4945] bg-[#202020] px-4 py-2 text-sm text-[#e5e2e1] focus:border-[#7bd7bd] focus:outline-none"
            >
              <option value="">Toutes les institutions</option>
              <option v-for="inst in institutions" :key="inst.id" :value="inst.id">{{ inst.name }}</option>
            </select>
            <input
              v-model="search"
              @keyup.enter="loadAnnexes(1)"
              type="text"
              placeholder="Rechercher une annexe..."
              class="w-full rounded-full border border-[#3e4945] bg-[#202020] px-4 py-2 text-sm text-[#e5e2e1] placeholder:text-[#88938e] focus:border-[#7bd7bd] focus:outline-none"
            />
          </div>
          <button
            @click="loadAnnexes(1)"
            class="rounded-xl bg-[#0e7c66] px-4 py-2 text-sm font-bold text-white transition hover:brightness-110"
          >
            Rechercher
          </button>
        </div>
      </div>

      <div class="space-y-4">
        <section
          v-for="group in groupedAnnexes"
          :key="group.institutionName"
          class="overflow-x-auto rounded-xl bg-[#1b1b1c] ring-1 ring-[#2a2a2a]"
        >
          <div class="border-b border-[#2a2a2a] bg-[#202020] px-6 py-3">
            <h3 class="text-sm font-bold uppercase tracking-wider text-[#7bd7bd]">{{ group.institutionName }}</h3>
          </div>
          <table class="min-w-full border-collapse text-left">
            <thead>
              <tr class="border-b border-[#2a2a2a] bg-[#202020]/70 text-[#bdc9c4]">
                <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider">Nom</th>
                <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider">Ville</th>
                <th class="px-6 py-4 text-xs font-bold uppercase tracking-wider">Statut</th>
                <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-[#2a2a2a]">
              <tr v-for="annexe in group.items" :key="annexe.id" class="transition-colors hover:bg-white/5">
                <td class="px-6 py-4 text-sm font-semibold text-[#e5e2e1]">{{ annexe.name }}</td>
                <td class="px-6 py-4 text-sm text-[#bdc9c4]">{{ annexe.city || '—' }}</td>
                <td class="px-6 py-4 text-sm">
                  <span
                    class="inline-flex rounded-full px-3 py-1 text-[10px] font-black uppercase tracking-widest"
                    :class="annexe.is_active ? 'bg-[#0e7c66]/20 text-[#7bd7bd] ring-1 ring-[#0e7c66]/30' : 'bg-[#93000a]/20 text-[#ffb4ab] ring-1 ring-[#93000a]/30'"
                  >
                    {{ annexe.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </td>
                <td class="px-6 py-4 text-right text-sm">
                  <button @click="toggleAnnexeStatus(annexe)" class="font-semibold text-[#ffb95f] transition hover:text-white">
                    {{ annexe.is_active ? 'Désactiver' : 'Activer' }}
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </section>

        <div v-if="!loading && groupedAnnexes.length === 0" class="rounded-xl bg-[#1b1b1c] px-6 py-8 text-center text-sm text-[#bdc9c4] ring-1 ring-[#2a2a2a]">
          Aucune annexe trouvée.
        </div>
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
import { computed, onMounted, ref } from 'vue'
import annexeService from '@/services/annexeService'
import api from '@/services/api'
import AdminPlatformeLayout from '@/components/AdminPlatforme/AdminPlatformeLayout.vue'

const annexes = ref([])
const institutions = ref([])
const loading = ref(false)
const search = ref('')
const institutionId = ref('')
const page = ref(1)
const lastPage = ref(1)
const perPage = ref(15)

const groupedAnnexes = computed(() => {
  const groups = new Map()
  for (const annexe of annexes.value) {
    const institutionName = annexe?.institution?.name || 'Institution non renseignée'
    if (!groups.has(institutionName)) {
      groups.set(institutionName, [])
    }
    groups.get(institutionName).push(annexe)
  }

  return Array.from(groups.entries()).map(([institutionName, items]) => ({
    institutionName,
    items,
  }))
})

const loadAnnexes = async (targetPage = page.value) => {
  loading.value = true
  try {
    const res = await annexeService.index({
      page: targetPage,
      per_page: perPage.value,
      search: search.value || undefined,
      institution_id: institutionId.value || undefined,
    })

    const payload = res.data ?? {}
    annexes.value = payload.data ?? []
    page.value = payload.current_page ?? targetPage
    lastPage.value = payload.last_page ?? 1
  } catch (e) {
    console.error('Failed loading annexes', e)
  } finally {
    loading.value = false
  }
}

const loadInstitutions = async () => {
  try {
    const { data } = await api.get('admin/institutions', {
      params: { per_page: 200 },
    })
    institutions.value = data?.data ?? []
  } catch (error) {
    console.error('Failed loading institutions for annexes filter', error)
  }
}

const toggleAnnexeStatus = async (annexe) => {
  try {
    await annexeService.update(annexe.id, {
      name: annexe.name,
      city: annexe.city || null,
      address: annexe.address || null,
      annexe_details: annexe.annexe_details || null,
      is_active: !annexe.is_active,
    })
    await loadAnnexes(page.value)
  } catch (e) {
    console.error('Failed toggling annexe status', e)
    alert('Échec du changement de statut de l\'annexe.')
  }
}

const prevPage = () => {
  if (page.value > 1) loadAnnexes(page.value - 1)
}

const nextPage = () => {
  if (page.value < lastPage.value) loadAnnexes(page.value + 1)
}

onMounted(() => {
  loadInstitutions()
  loadAnnexes(1)
})
</script>
