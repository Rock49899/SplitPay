import { defineStore } from 'pinia';
import userService from '@/services/userService';

export const useUserStore = defineStore('users', {
  state: () => ({
    items: [],          // current page items
    meta: {},           // pagination meta (total, per_page, current_page...)
    loading: false,
    error: null,
    query: '',          // search query
    page: 1,
  }),
  actions: {
    async fetchUsers(params = {}) {
      this.loading = true;
      this.error = null;
      try {
        // send "search" param to backend (not "q")
        const p = { page: this.page, search: this.query, ...params };
        const res = await userService.index(p);
        // expected response: paginated -> data + meta (adjust if backend differs)
        const responseData = res.data.data ?? res.data;
        this.items = Array.isArray(responseData) ? responseData : [];
        this.meta  = res.data.meta ?? {
          current_page: res.data.current_page,
          last_page: res.data.last_page,
          per_page: res.data.per_page,
          total: res.data.total,
          from: res.data.from,
          to: res.data.to,
        };
        return res;
      } catch (e) {
        this.error = e.response?.data || e.message;
        this.items = []; // Reset to empty array on error
        throw e;
      } finally {
        this.loading = false;
      }
    },
    async createUser(payload) {
      this.loading = true;
      try {
        const res = await userService.store(payload);
        await this.fetchUsers(); // refresh
        return res;
      } finally {
        this.loading = false;
      }
    },
    async updateUser(id, payload) {
      this.loading = true;
      try {
        const res = await userService.update(id, payload);
        await this.fetchUsers();
        return res;
      } finally {
        this.loading = false;
      }
    },
    async deleteUser(id) {
      this.loading = true;
      try {
        const res = await userService.destroy(id);
        await this.fetchUsers();
        return res;
      } finally {
        this.loading = false;
      }
    },
    setPage(n) {
      this.page = n;
    },
    setQuery(q) {
      this.query = q;
    },
  },
});
