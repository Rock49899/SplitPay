import { defineStore } from 'pinia';
import registrationService from '../services/registrationService';
import authService from '../services/authService';
import { applyToken } from '../services/api';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: localStorage.getItem('api_token') || null,
    user: null,
    loading: false,
    error: null,
  }),
  getters: {
    isAuthenticated: (state) => !!state.token,
  },
  actions: {
    setToken(token) {
      this.token = token;
      if (token) {
        localStorage.setItem('api_token', token);
        applyToken(token);
      } else {
        localStorage.removeItem('api_token');
        applyToken(null);
      }
    },

    async register(payload) {
      this.loading = true;
      this.error = null;
      try {
        const res = await registrationService.register(payload);
        // optional: if API returns token/user, set here
        if (res.data?.token) this.setToken(res.data.token);
        if (res.data?.user) this.user = res.data.user;
        return res;
      } catch (e) {
        this.error = e.response?.data || e.message;
        throw e;
      } finally {
        this.loading = false;
      }
    },

    async login(credentials) {
      this.loading = true;
      this.error = null;
      try {
        const res = await authService.login(credentials);
        const token = res.data?.token ?? null;
        if (token) this.setToken(token);
        if (res.data?.user) this.user = res.data.user;
        return res;
      } catch (e) {
        this.error = e.response?.data || e.message;
        throw e;
      } finally {
        this.loading = false;
      }
    },

    async logout() {
      this.loading = true;
      this.error = null;
      try {
        await authService.logout();
      } catch (e) {
        // ignore server errors on logout
      } finally {
        this.setToken(null);
        this.user = null;
        this.loading = false;
      }
    },

    async fetchMe() {
      if (!this.token) return null;
      this.loading = true;
      try {
        const res = await authService.me();
        this.user = res.data;
        return res;
      } catch (e) {
        // token invalid -> clear
        this.setToken(null);
        this.user = null;
        throw e;
      } finally {
        this.loading = false;
      }
    },
  },
});

const existingToken = localStorage.getItem('api_token');
if (existingToken) applyToken(existingToken);
