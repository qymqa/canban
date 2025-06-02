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

router.beforeEach((to, from, next) => {
    const authStore = useAuthStore();
    
    // Разрешаем доступ к странице токен-авторизации без проверки
    if (to.name === 'TokenAuth') {
        next();
        return;
    }
    
    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        next('/login');
    } else if (to.path === '/login' && authStore.isAuthenticated) {
        next('/board/all');
    } else {
        next();
    }
});

export default router; 