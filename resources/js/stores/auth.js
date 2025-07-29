import { defineStore } from 'pinia';
import axios from 'axios';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        token: localStorage.getItem('token') || null,
        objects: [],
        objectsMeta: null,
        users: [],
        tokenCheckInterval: null,
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
                
                const newToken = response.data.data.access_token;
                await this.setToken(newToken);
                
                return true;
            } catch (error) {
                throw error;
            }
        },

        async setToken(newToken) {
            // Проверяем, отличается ли новый токен от текущего
            if (this.token === newToken) {
                return;
            }

            // Останавливаем предыдущий интервал проверки
            this.stopTokenValidation();

            // Устанавливаем новый токен
            this.token = newToken;
            localStorage.setItem('token', this.token);
            axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`;
            
            // Загружаем данные пользователя с новым токеном
            try {
                await this.fetchUser();
                
                // Запускаем периодическую проверку токена
                this.startTokenValidation();
            } catch (error) {
                this.logout();
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

                // Если токен валидный, устанавливаем его
                await this.setToken(token);
                this.user = response.data.user;
                
                return true;
            } catch (error) {
                // Очищаем данные при ошибке
                this.logout();
                throw error;
            }
        },

        async validateCurrentToken() {
            if (!this.token) {
                return false;
            }

            try {
                const response = await axios.post('/api/auth/validate-token', {
                    token: this.token
                });

                if (!response.data.valid) {
                    console.log('Токен стал невалидным, выполняется logout');
                    this.logout();
                    return false;
                }

                // Обновляем данные пользователя, если они изменились
                if (response.data.user) {
                    this.user = response.data.user;
                }

                return true;
            } catch (error) {
                console.error('Ошибка проверки токена:', error);
                this.logout();
                return false;
            }
        },

        startTokenValidation() {
            // Проверяем токен каждые 5 минут
            this.tokenCheckInterval = setInterval(() => {
                this.validateCurrentToken();
            }, 5 * 60 * 1000);
        },

        stopTokenValidation() {
            if (this.tokenCheckInterval) {
                clearInterval(this.tokenCheckInterval);
                this.tokenCheckInterval = null;
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
                    params: { page, perPage: 1000 }
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
            this.stopTokenValidation();
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
                // Запускаем периодическую проверку токена
                this.startTokenValidation();
            }
        },

        // Проверка URL на новый токен (для случаев, когда пользователь уже авторизован)
        checkUrlForNewToken() {
            const urlParams = new URLSearchParams(window.location.search);
            const urlToken = urlParams.get('token');
            
            if (urlToken && urlToken !== this.token) {
                console.log('Обнаружен новый токен в URL, обновляем авторизацию');
                return this.setTokenFromUrl(urlToken);
            }
            
            return null;
        },
    },
}); 