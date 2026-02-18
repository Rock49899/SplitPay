import { defineStore } from 'pinia';
import annexeService from '@/services/annexeService';
import api from '@/services/api';

export const useAnnexeStore = defineStore('annexes', {
  state: () => ({
    items: [],
    meta: {},
    loading: false,
    error: null,
    query: '',
    page: 1,
  }),
  actions: {
    async fetchAnnexes(params = {}) {
      this.loading = true;
      this.error = null;
      try {
        const res = await annexeService.index(params);
        this.items = res.data?.data ?? res.data ?? [];
        return res;
      } catch (e) {
        this.error = e;
        throw e;
      } finally {
        this.loading = false;
      }
    },
    async createAnnexe(payload) {
      this.loading = true;
      try {
        const res = await annexeService.store(payload);
        const ann = res.data?.annexe ?? res.data ?? null;
        if (ann) this.items.unshift(ann);
        return res;
      } finally {
        this.loading = false;
      }
    },
    async updateAnnexe(id, payload) {
      this.loading = true;
      try {
        this.error = null;
        const res = await annexeService.update(id, payload);
        // refresh list after update
        await this.fetchAnnexes();
        return res;
      } catch (e) {
        // store error for UI and rethrow for caller to handle
        this.error = e.response?.data ?? e.message;
        throw e;
      } finally {
        this.loading = false;
      }
    },
    async deleteAnnexe(id) {
      this.loading = true;
      try {
        const res = await annexeService.destroy(id);
        await this.fetchAnnexes();
        return res;
      } finally {
        this.loading = false;
      }
    },
    // fallback if backend uses separate endpoint to assign users
    async assignUser(annexeId, userId, roleId = null) {
      return api.post(`admin/annexes/${annexeId}/assign-user`, { user_id: userId, role_id: roleId });
    },
  },
});
