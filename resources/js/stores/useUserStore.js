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
        const p = { page: this.page, q: this.query, ...params };
        const res = await userService.index(p);
        // expected response: paginated -> data + meta (adjust if backend differs)
        this.items = res.data.data ?? res.data; 
        this.meta  = res.data.meta ?? {};
        return res;
      } catch (e) {
        this.error = e.response?.data || e.message;
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
