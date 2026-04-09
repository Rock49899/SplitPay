<template>
  <AdminPlatformeLayout>
    <div class="rounded-xl bg-[#1b1b1c] p-6 ring-1 ring-[#2a2a2a]">
      <h3 class="text-base font-extrabold text-[#e5e2e1]">Paramètres globaux plateforme</h3>
      <p class="mt-2 text-sm text-[#bdc9c4]">
        Zone de configuration globale (branding plateforme, politiques globales, options transversales).
      </p>

      <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2">
        <article class="rounded-xl bg-[#202020] p-4 ring-1 ring-[#2a2a2a]">
          <p class="text-xs font-semibold uppercase tracking-wider text-[#88938e]">Identité plateforme</p>
          <p class="mt-2 text-sm text-[#e5e2e1]">Palette, logos, intitulés du portail plateforme.</p>
        </article>
        <article class="rounded-xl bg-[#202020] p-4 ring-1 ring-[#2a2a2a]">
          <p class="text-xs font-semibold uppercase tracking-wider text-[#88938e]">Règles globales</p>
          <p class="mt-2 text-sm text-[#e5e2e1]">Paramètres métiers transversaux et conformité multi-tenant.</p>
        </article>
      </div>
    </div>
  </AdminPlatformeLayout>
</template>

<script setup>
import AdminPlatformeLayout from '@/components/AdminPlatforme/AdminPlatformeLayout.vue'
</script>
