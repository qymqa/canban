import { createRouter, createWebHistory } from 'vue-router';
import Login from './views/Login.vue';
import Objects from './views/Objects.vue';
import Board from './views/Board.vue';
import { useAuthStore } from './stores/auth';

const routes = [
    {
        path: '/login',
        name: 'Login',
        component: Login,
    },
    {
        path: '/',
        name: 'Objects',
        component: Objects,
        meta: { requiresAuth: true },
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
    
    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        next('/login');
    } else if (to.path === '/login' && authStore.isAuthenticated) {
        next('/');
    } else {
        next();
    }
});

export default router; 