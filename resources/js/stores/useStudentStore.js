import { defineStore } from 'pinia';
import studentService from '@/services/studentService';

export const useStudentStore = defineStore('students', {
  state: () => ({
    items: [],
    meta: {},
    loading: false,
    error: null,
    query: '',
    page: 1,
  }),
  actions: {
    async fetchStudents(params = {}) {
      this.loading = true;
      this.error = null;
      try {
        const p = { page: this.page, q: this.query, ...params };
        const res = await studentService.index(p);
        this.items = res.data.data ?? res.data;
        this.meta = res.data.meta ?? {};
        return res;
      } catch (e) {
        this.error = e.response?.data || e.message;
        throw e;
      } finally {
        this.loading = false;
      }
    },
    async createStudent(payload) {
      this.loading = true;
      try {
        const res = await studentService.store(payload);
        await this.fetchStudents();
        return res;
      } finally {
        this.loading = false;
      }
    },
    async updateStudent(id, payload) {
      this.loading = true;
      try {
        const res = await studentService.update(id, payload);
        await this.fetchStudents();
        return res;
      } finally {
        this.loading = false;
      }
    },
    async deleteStudent(id) {
      this.loading = true;
      try {
        const res = await studentService.destroy(id);
        await this.fetchStudents();
        return res;
      } finally {
        this.loading = false;
      }
    },
    setPage(n) { this.page = n; },
    setQuery(q) { this.query = q; },
  },
});
