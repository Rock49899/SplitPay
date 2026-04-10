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
              @keyup.enter="loadUsers(1)"
              type="text"
              placeholder="Rechercher un utilisateur..."
              class="w-full rounded-full border border-[#3e4945] bg-[#202020] px-4 py-2 text-sm text-[#e5e2e1] placeholder:text-[#88938e] focus:border-[#7bd7bd] focus:outline-none"
            />
          </div>
          <button
            @click="loadUsers(1)"
            class="rounded-xl bg-[#0e7c66] px-4 py-2 text-sm font-bold text-white transition hover:brightness-110"
          >
            Rechercher
          </button>
        </div>
      </div>

      <div class="space-y-4">
        <section
          v-for="institutionGroup in groupedUsers"
          :key="institutionGroup.institutionName"
          class="rounded-xl bg-[#1b1b1c] ring-1 ring-[#2a2a2a]"
        >
          <div class="border-b border-[#2a2a2a] bg-[#202020] px-6 py-3">
            <h3 class="text-sm font-bold uppercase tracking-wider text-[#7bd7bd]">
              {{ institutionGroup.institutionName }}
            </h3>
          </div>

          <div class="space-y-4 p-4">
            <div
              v-for="annexeGroup in institutionGroup.annexes"
              :key="`${institutionGroup.institutionName}-${annexeGroup.annexeName}`"
              class="overflow-x-auto rounded-lg ring-1 ring-[#2a2a2a]"
            >
              <div class="border-b border-[#2a2a2a] bg-[#202020]/80 px-4 py-2">
                <h4 class="text-xs font-bold uppercase tracking-wider text-[#bdc9c4]">
                  {{ annexeGroup.annexeName }} ({{ annexeGroup.users.length }} utilisateurs)
                </h4>
              </div>

              <table class="min-w-full border-collapse text-left">
                <thead>
                  <tr class="border-b border-[#2a2a2a] bg-[#202020]/60 text-[#bdc9c4]">
                    <th class="px-4 py-3 text-xs font-bold uppercase tracking-wider">Nom</th>
                    <th class="px-4 py-3 text-xs font-bold uppercase tracking-wider">Email</th>
                    <th class="px-4 py-3 text-xs font-bold uppercase tracking-wider">Téléphone</th>
                    <th class="px-4 py-3 text-xs font-bold uppercase tracking-wider">Rôle</th>
                    <th class="px-4 py-3 text-xs font-bold uppercase tracking-wider">Scope</th>
                    <th class="px-4 py-3 text-xs font-bold uppercase tracking-wider">Statut</th>
                    <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider">Actions</th>
                  </tr>
                </thead>

                <tbody class="divide-y divide-[#2a2a2a]">
                  <tr v-for="u in annexeGroup.users" :key="u.id" class="transition-colors hover:bg-white/5">
                    <td class="px-4 py-3 text-sm font-semibold text-[#e5e2e1]">
                      <template v-if="editingId !== u.id">{{ u.name || '—' }}</template>
                      <input
                        v-else
                        v-model="editForm.name"
                        class="w-full rounded-lg border border-[#3e4945] bg-[#202020] px-3 py-1.5 text-sm text-[#e5e2e1] focus:border-[#7bd7bd] focus:outline-none"
                      />
                    </td>
                    <td class="px-4 py-3 text-sm text-[#bdc9c4]">
                      <template v-if="editingId !== u.id">{{ u.email || '—' }}</template>
                      <input
                        v-else
                        v-model="editForm.email"
                        class="w-full rounded-lg border border-[#3e4945] bg-[#202020] px-3 py-1.5 text-sm text-[#e5e2e1] focus:border-[#7bd7bd] focus:outline-none"
                      />
                    </td>
                    <td class="px-4 py-3 text-sm text-[#bdc9c4]">
                      <template v-if="editingId !== u.id">{{ u.phone || '—' }}</template>
                      <input
                        v-else
                        v-model="editForm.phone"
                        class="w-full rounded-lg border border-[#3e4945] bg-[#202020] px-3 py-1.5 text-sm text-[#e5e2e1] focus:border-[#7bd7bd] focus:outline-none"
                      />
                    </td>
                    <td class="px-4 py-3 text-sm text-[#bdc9c4]">{{ getPrimaryRoleLabel(u) }}</td>
                    <td class="px-4 py-3 text-sm text-[#bdc9c4]">{{ u.scope || 'annexe' }}</td>
                    <td class="px-4 py-3 text-sm">
                      <span
                        class="inline-flex rounded-full px-3 py-1 text-[10px] font-black uppercase tracking-widest"
                        :class="u.is_active ? 'bg-[#0e7c66]/20 text-[#7bd7bd] ring-1 ring-[#0e7c66]/30' : 'bg-[#93000a]/20 text-[#ffb4ab] ring-1 ring-[#93000a]/30'"
                      >
                        {{ u.is_active ? 'Actif' : 'Inactif' }}
                      </span>
                    </td>
                    <td class="px-4 py-3 text-right text-sm">
                      <template v-if="editingId !== u.id">
                        <button @click="startEdit(u)" class="mr-3 font-semibold text-[#7bd7bd] transition hover:text-white">Modifier</button>
                        <button @click="toggleUserStatus(u)" class="mr-3 font-semibold text-[#ffb95f] transition hover:text-white">
                          {{ u.is_active ? 'Désactiver' : 'Activer' }}
                        </button>
                        <button @click="removeUser(u)" class="font-semibold text-[#ffb4ab] transition hover:text-white">Supprimer</button>
                      </template>
                      <template v-else>
                        <button @click="saveEdit(u)" class="mr-3 font-semibold text-[#7bd7bd] transition hover:text-white">Enregistrer</button>
                        <button @click="cancelEdit" class="font-semibold text-slate-300 transition hover:text-white">Annuler</button>
                      </template>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <div v-if="!loading && groupedUsers.length === 0" class="rounded-xl bg-[#1b1b1c] px-6 py-8 text-center text-sm text-[#bdc9c4] ring-1 ring-[#2a2a2a]">
          Aucun utilisateur trouvé.
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
import { computed, onMounted, ref, watch } from 'vue'
import userService from '@/services/userService'
import api from '@/services/api'
import AdminPlatformeLayout from '@/components/AdminPlatforme/AdminPlatformeLayout.vue'

const users = ref([])
const institutions = ref([])
const loading = ref(false)
const search = ref('')
const institutionId = ref('')
const page = ref(1)
const lastPage = ref(1)
const perPage = ref(15)
const editingId = ref(null)
const editForm = ref({ name: '', email: '', phone: '' })

const groupedUsers = computed(() => {
  const institutionMap = new Map()

  for (const user of users.value) {
    const institutionName = getInstitutionName(user)
    const annexeName = getAnnexeName(user)

    if (!institutionMap.has(institutionName)) {
      institutionMap.set(institutionName, new Map())
    }

    const annexeMap = institutionMap.get(institutionName)
    if (!annexeMap.has(annexeName)) {
      annexeMap.set(annexeName, [])
    }

    annexeMap.get(annexeName).push(user)
  }

  return Array.from(institutionMap.entries()).map(([institutionName, annexeMap]) => ({
    institutionName,
    annexes: Array.from(annexeMap.entries()).map(([annexeName, grouped]) => ({
      annexeName,
      users: grouped,
    })),
  }))
})

const loadUsers = async (targetPage = page.value) => {
  loading.value = true
  try {
    const res = await userService.index({
      page: targetPage,
      per_page: perPage.value,
      search: search.value || undefined,
      institution_id: institutionId.value || undefined,
    })

    const payload = res.data ?? {}
    users.value = payload.data ?? []
    page.value = payload.current_page ?? targetPage
    lastPage.value = payload.last_page ?? 1
  } catch (e) {
    console.error('Failed loading users', e)
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
    console.error('Failed loading institutions for users filter', error)
  }
}

const getAnnexeName = (user) => {
  if (user?.annexe?.name) return user.annexe.name

  const principal = (user?.user_annexes ?? []).find((ua) => ua?.is_principal)
  if (principal?.annexe?.name) return principal.annexe.name

  return (user?.user_annexes ?? [])[0]?.annexe?.name || '—'
}

const getInstitutionName = (user) => {
  if (user?.annexe?.institution?.name) return user.annexe.institution.name

  const principal = (user?.user_annexes ?? []).find((ua) => ua?.is_principal)
  if (principal?.annexe?.institution?.name) return principal.annexe.institution.name

  return (user?.user_annexes ?? [])[0]?.annexe?.institution?.name || '—'
}

const getPrimaryRoleLabel = (user) => {
  const principal = (user?.user_annexes ?? []).find((ua) => ua?.is_principal)
  if (principal?.role?.name) return principal.role.name
  return (user?.user_annexes ?? [])[0]?.role?.name || '—'
}

const startEdit = (user) => {
  editingId.value = user.id
  editForm.value = {
    name: user.name || '',
    email: user.email || '',
    phone: user.phone || '',
  }
}

const cancelEdit = () => {
  editingId.value = null
}

const saveEdit = async (user) => {
  try {
    await userService.update(user.id, {
      name: editForm.value.name,
      email: editForm.value.email,
      phone: editForm.value.phone || null,
      annexe_id: user.annexe_id ?? null,
      is_active: user.is_active,
    })
    editingId.value = null
    await loadUsers(page.value)
  } catch (e) {
    console.error('Failed updating user', e)
    alert('Échec de mise à jour utilisateur.')
  }
}

const toggleUserStatus = async (u) => {
  try {
    await userService.update(u.id, {
      name: u.name,
      email: u.email,
      annexe_id: u.annexe_id ?? null,
      is_active: !u.is_active,
    })
    await loadUsers(page.value)
  } catch (e) {
    console.error('Failed toggling user status', e)
    alert('Échec du changement de statut utilisateur.')
  }
}

const removeUser = async (u) => {
  if (!confirm(`Supprimer l'utilisateur ${u.name || u.email} ?`)) return
  try {
    await userService.destroy(u.id)
    await loadUsers(page.value)
  } catch (e) {
    console.error('Failed deleting user', e)
    alert('Échec de suppression utilisateur.')
  }
}

const prevPage = () => {
  if (page.value > 1) loadUsers(page.value - 1)
}

const nextPage = () => {
  if (page.value < lastPage.value) loadUsers(page.value + 1)
}

onMounted(() => {
  loadInstitutions()
  loadUsers(1)
})

watch(institutionId, () => {
  loadUsers(1)
})
</script>
