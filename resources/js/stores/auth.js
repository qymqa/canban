import { defineStore } from 'pinia';
import axios from 'axios';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        token: localStorage.getItem('token') || null,
        objects: [],
        users: [],
    }),

    getters: {
        isAuthenticated: (state) => !!state.token,
    },

    actions: {
        async login(email, password) {
            try {
                const response = await axios.post('/api/auth/login', {
                    email,
                    password,
                });
                
                this.token = response.data.data.access_token;
                localStorage.setItem('token', this.token);
                axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`;
                
                return true;
            } catch (error) {
                throw error;
            }
        },

        async fetchUser() {
            try {
                const response = await axios.get('/api/auth/user');
                this.user = response.data;
            } catch (error) {
                this.logout();
                throw error;
            }
        },

        async fetchObjects() {
            try {
                const response = await axios.get('/api/auth/objects');
                this.objects = response.data.data || response.data;
            } catch (error) {
                throw error;
            }
        },

        async fetchUsers() {
            try {
                const response = await axios.get('/api/auth/users');
                this.users = response.data.data || response.data;
            } catch (error) {
                throw error;
            }
        },

        logout() {
            this.user = null;
            this.token = null;
            this.objects = [];
            this.users = [];
            localStorage.removeItem('token');
            delete axios.defaults.headers.common['Authorization'];
        },

        initializeAuth() {
            if (this.token) {
                axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`;
            }
        },
    },
}); 