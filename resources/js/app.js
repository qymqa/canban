import './bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import router from './router';
import App from './App.vue';
import '../css/app.css';

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(router);

// Добавляем обработчик PostMessage для интеграции через iframe
window.addEventListener('message', async (event) => {
    // Проверяем источник сообщения (опционально)
    // if (event.origin !== 'https://trusted-domain.com') return;
    
    if (event.data && event.data.type === 'AUTH_TOKEN') {
        const { useAuthStore } = await import('./stores/auth');
        const authStore = useAuthStore();
        
        try {
            console.log('Получен токен через PostMessage API');
            await authStore.setTokenFromUrl(event.data.token);
            
            // Редиректим на главную страницу после успешной авторизации
            if (router.currentRoute.value.path === '/login') {
                router.push('/board/all');
            }
        } catch (error) {
            console.error('Ошибка установки токена через PostMessage:', error);
        }
    }
});

app.mount('#app');
