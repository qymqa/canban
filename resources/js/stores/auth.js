import { defineStore } from 'pinia';
import axios from 'axios';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        token: localStorage.getItem('token') || null,
        objects: [],
        objectsMeta: null,
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

        async setTokenFromUrl(token) {
            try {
                // Сначала проверяем валидность токена
                const response = await axios.post('/api/auth/validate-token', {
                    token: token
                });

                if (!response.data.valid) {
                    throw new Error(response.data.message || 'Неверный токен');
                }

                // Если токен валидный, сохраняем его
                this.token = token;
                this.user = response.data.user;
                localStorage.setItem('token', this.token);
                axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`;
                
                return true;
            } catch (error) {
                // Очищаем данные при ошибке
                this.logout();
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

        async fetchObjects(page = 1) {
            try {
                const response = await axios.get('/api/auth/objects', {
                    params: { page }
                });
                this.objects = response.data.data || response.data;
                this.objectsMeta = response.data.meta || null;
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
            this.objectsMeta = null;
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