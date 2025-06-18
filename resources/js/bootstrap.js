import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Добавляем interceptor для обработки ошибок авторизации
axios.interceptors.response.use(
    (response) => response,
    (error) => {
        // Если получили 401 ошибку, токен невалидный
        if (error.response && error.response.status === 401) {
            // Импортируем store динамически, чтобы избежать циклических зависимостей
            import('./stores/auth').then(({ useAuthStore }) => {
                const authStore = useAuthStore();
                if (authStore.isAuthenticated) {
                    console.log('Получена 401 ошибка, токен невалидный. Выполняется logout.');
                    authStore.logout();
                    
                    // Редиректим на страницу входа, если не находимся на ней
                    if (window.location.pathname !== '/login' && window.location.pathname !== '/auth') {
                        window.location.href = '/login';
                    }
                }
            });
        }
        
        return Promise.reject(error);
    }
);
