<template>
  <div class="min-h-screen flex items-center justify-center" style="background-color: rgb(245, 245, 245)">
    <div class="max-w-md w-full space-y-8">
      <div class="text-center">
        <div v-if="loading" class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mb-4"></div>
        <h2 class="mt-6 text-center text-2xl font-bold text-gray-900">
          {{ loading ? 'Авторизация...' : 'Ошибка авторизации' }}
        </h2>
        <p v-if="loading" class="mt-2 text-center text-sm text-gray-600">
          Проверяем токен и настраиваем доступ
        </p>
        <div v-if="error" class="mt-4 p-4 bg-red-50 border border-red-200 rounded-md">
          <p class="text-red-700 text-sm">{{ error }}</p>
          <button
            @click="retryAuth"
            class="mt-2 px-4 py-2 text-sm font-medium text-red-700 bg-red-100 border border-red-300 rounded-md hover:bg-red-200"
          >
            Попробовать снова
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';

export default {
  name: 'TokenAuth',
  setup() {
    const route = useRoute();
    const router = useRouter();
    const authStore = useAuthStore();
    
    const loading = ref(true);
    const error = ref('');

    const performAuth = async () => {
      try {
        loading.value = true;
        error.value = '';

        // Получаем токен из URL параметров
        const token = route.query.token;
        
        if (!token) {
          throw new Error('Токен не найден в URL');
        }

        // Устанавливаем токен в store и localStorage
        await authStore.setTokenFromUrl(token);
        
        // Проверяем токен, загружая данные пользователя
        await authStore.fetchUser();
        
        // Если все успешно, редиректим на /manager
        router.push('/manager');
        
      } catch (err) {
        console.error('Auth error:', err);
        error.value = err.message || 'Ошибка авторизации. Проверьте токен.';
        loading.value = false;
      }
    };

    const retryAuth = () => {
      performAuth();
    };

    onMounted(() => {
      // Проверяем, есть ли уже токен в localStorage
      if (authStore.isAuthenticated) {
        // Если уже авторизован, сразу редиректим
        router.push('/manager');
        return;
      }

      // Выполняем авторизацию по токену из URL
      performAuth();
    });

    return {
      loading,
      error,
      retryAuth,
    };
  },
};
</script> 