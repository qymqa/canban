import { createRouter, createWebHistory } from 'vue-router';
import Login from './views/Login.vue';
import Board from './views/Board.vue';
import TokenAuth from './views/TokenAuth.vue';
import { useAuthStore } from './stores/auth';

const routes = [
    {
        path: '/login',
        name: 'Login',
        component: Login,
    },
    {
        path: '/auth',
        name: 'TokenAuth',
        component: TokenAuth,
    },
    {
        path: '/',
        redirect: '/board/all'
    },
    {
        path: '/manager',
        redirect: '/board/all' // Редирект на основную доску
    },
    {
        path: '/board/:objectId',
        name: 'Board',
        component: Board,
        meta: { requiresAuth: true },
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore();
    
    // Разрешаем доступ к странице токен-авторизации без проверки
    if (to.name === 'TokenAuth') {
        next();
        return;
    }
    
    // Проверяем наличие нового токена в URL
    const urlParams = new URLSearchParams(to.query);
    const urlToken = urlParams.get('token') || to.query.token;
    
    if (urlToken) {
        // Если есть токен в URL
        if (authStore.isAuthenticated && urlToken !== authStore.token) {
            // Пользователь уже авторизован, но токен новый - обновляем
            try {
                console.log('Обнаружен новый токен, обновляем авторизацию');
                await authStore.setTokenFromUrl(urlToken);
                
                // Убираем токен из URL после успешной авторизации
                const newQuery = { ...to.query };
                delete newQuery.token;
                
                next({ 
                    path: to.path, 
                    query: newQuery,
                    replace: true 
                });
                return;
            } catch (error) {
                console.error('Ошибка обновления токена:', error);
                // Если не удалось обновить токен, редиректим на страницу авторизации
                next('/auth?token=' + urlToken);
                return;
            }
        } else if (!authStore.isAuthenticated) {
            // Пользователь не авторизован и есть токен - редиректим на страницу авторизации
            next('/auth?token=' + urlToken);
            return;
        } else {
            // Токен тот же самый - просто убираем его из URL
            const newQuery = { ...to.query };
            delete newQuery.token;
            
            next({ 
                path: to.path, 
                query: newQuery,
                replace: true 
            });
            return;
        }
    }
    
    // Обычная проверка авторизации
    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        next('/login');
    } else if (to.path === '/login' && authStore.isAuthenticated) {
        next('/board/all');
    } else {
        next();
    }
});

export default router; 