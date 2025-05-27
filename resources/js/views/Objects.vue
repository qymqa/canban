<template>
  <div class="min-h-screen bg-gray-50">
    <nav class="bg-white shadow">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex items-center">
            <h1 class="text-xl font-semibold">Канбан доски</h1>
          </div>
          <div class="flex items-center space-x-4">
            <span class="text-gray-700">{{ user?.name || user?.email }}</span>
            <button
              @click="handleLogout"
              class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium"
            >
              Выйти
            </button>
          </div>
        </div>
      </div>
    </nav>

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
      <div class="px-4 py-6 sm:px-0">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Выберите объект</h2>
        
        <div v-if="loading" class="text-center">
          <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
          <p class="mt-2 text-gray-600">Загрузка объектов...</p>
        </div>

        <div v-else-if="error" class="text-center text-red-600">
          {{ error }}
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div
            v-for="object in objects"
            :key="object.id"
            class="bg-white overflow-hidden shadow rounded-lg hover:shadow-lg transition-shadow duration-200"
          >
            <div class="p-6">
              <h3 class="text-lg font-semibold mb-2">{{ object.title }}</h3>
              <p class="text-gray-600 mb-4">{{ object.description || 'Нет описания' }}</p>
              <div class="flex justify-between items-center">
                <router-link
                  :to="`/board/${object.id}`"
                  class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors"
                >
                  Открыть доску
                </router-link>
                <span class="text-sm text-gray-500">
                  {{ taskCounts[object.id] || 0 }} задач
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import axios from 'axios';

export default {
  name: 'Objects',
  setup() {
    const router = useRouter();
    const authStore = useAuthStore();
    
    const loading = ref(false);
    const error = ref('');
    const taskCounts = ref({});

    const user = computed(() => authStore.user);
    const objects = computed(() => authStore.objects);

    const fetchTaskCounts = async () => {
      if (objects.value.length === 0) return;
      
      try {
        const objectIds = objects.value.map(obj => obj.id);
        const response = await axios.post('/api/boards/task-counts', {
          object_ids: objectIds
        });
        taskCounts.value = response.data;
      } catch (err) {
        console.error('Error fetching task counts:', err);
      }
    };

    const fetchData = async () => {
      loading.value = true;
      error.value = '';

      try {
        await authStore.fetchUser();
        await authStore.fetchObjects();
        await fetchTaskCounts();
      } catch (err) {
        error.value = err.response?.data?.message || 'Ошибка загрузки данных';
      } finally {
        loading.value = false;
      }
    };

    const handleLogout = () => {
      authStore.logout();
      router.push('/login');
    };

    onMounted(() => {
      fetchData();
    });

    return {
      loading,
      error,
      user,
      objects,
      taskCounts,
      handleLogout,
    };
  },
};
</script> 