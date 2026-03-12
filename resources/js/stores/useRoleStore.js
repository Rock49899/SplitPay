import { defineStore } from 'pinia';
import roleService from '@/services/roleService';

export const useRoleStore = defineStore('roles', {
  state: () => ({
    items: [],
    loading: false,
    error: null,
  }),
  actions: {
    async fetchRoles(params = {}) {
      this.loading = true;
      this.error = null;
      try {
        const res = await roleService.index(params);
        this.items = res.data?.data ?? res.data ?? [];
        return res;
      } catch (e) {
        this.error = e;
        throw e;
      } finally {
        this.loading = false;
      }
    },
  },
});
