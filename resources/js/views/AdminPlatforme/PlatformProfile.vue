<template>
  <AdminPlatformeLayout>
    <section class="space-y-6">
      <div class="rounded-xl bg-[#1b1b1c] p-6 ring-1 ring-[#2a2a2a]">
        <h2 class="text-xl font-extrabold text-[#e5e2e1]">Mon profil plateforme</h2>
        <p class="mt-2 text-sm text-[#bdc9c4]">Informations de votre compte administrateur plateforme.</p>
      </div>

      <div class="rounded-xl bg-[#1b1b1c] p-6 ring-1 ring-[#2a2a2a]">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
          <article class="rounded-lg bg-[#202020] p-4 ring-1 ring-[#2a2a2a]">
            <p class="text-xs uppercase tracking-wider text-[#88938e]">Nom</p>
            <p class="mt-2 text-sm font-semibold text-[#e5e2e1]">{{ user.name || '—' }}</p>
          </article>

          <article class="rounded-lg bg-[#202020] p-4 ring-1 ring-[#2a2a2a]">
            <p class="text-xs uppercase tracking-wider text-[#88938e]">Email</p>
            <p class="mt-2 text-sm font-semibold text-[#e5e2e1]">{{ user.email || '—' }}</p>
          </article>

          <article class="rounded-lg bg-[#202020] p-4 ring-1 ring-[#2a2a2a]">
            <p class="text-xs uppercase tracking-wider text-[#88938e]">Téléphone</p>
            <p class="mt-2 text-sm font-semibold text-[#e5e2e1]">{{ user.phone || '—' }}</p>
          </article>

          <article class="rounded-lg bg-[#202020] p-4 ring-1 ring-[#2a2a2a]">
            <p class="text-xs uppercase tracking-wider text-[#88938e]">Scope</p>
            <p class="mt-2 text-sm font-semibold text-[#e5e2e1]">{{ user.scope || 'platform' }}</p>
          </article>
        </div>
      </div>
    </section>
  </AdminPlatformeLayout>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import api from '@/services/api'
import AdminPlatformeLayout from '@/components/AdminPlatforme/AdminPlatformeLayout.vue'

const user = ref({
  name: '',
  email: '',
  phone: '',
  scope: 'platform',
})

const loadMe = async () => {
  try {
    const res = await api.get('admin/me')
    const payload = res.data?.user ?? res.data ?? {}
    user.value = {
      name: payload.name || '',
      email: payload.email || '',
      phone: payload.phone || '',
      scope: payload.scope || 'platform',
    }
  } catch (error) {
    console.error('Failed to load profile', error)
  }
}

onMounted(loadMe)
</script>
