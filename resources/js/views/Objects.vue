<template>
  <div class="min-h-screen" style="background-color: rgb(245, 245, 245)">


    <div class="w-full app-max-width mx-auto px-20 pb-4">
      <div class="px-4 py-6 sm:px-0">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Выберите объект</h2>
        
        <div v-if="loading" class="text-center">
          <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
          <p class="mt-2 text-gray-600">Загрузка объектов...</p>
        </div>

        <div v-else-if="error" class="text-center text-red-600">
          {{ error }}
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                      <div
              v-for="object in objects"
              :key="object.id"
              class="bg-white overflow-hidden rounded-lg"
            >
              <div class="p-6 h-full flex flex-col justify-between">
                <h3 class="text-sm font-bold mb-16" style="font-variation-settings: normal;">{{ object.title }}</h3>
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
        
        <!-- Пагинация -->
        <div v-if="meta && meta.last_page > 1" class="flex justify-center mt-8">
          <nav class="flex items-center space-x-2">
            <!-- Предыдущая страница -->
            <button
              @click="goToPage(meta.current_page - 1)"
              :disabled="meta.current_page === 1"
              class="flex items-center justify-center w-10 h-10 rounded-lg border border-gray-300 bg-white text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
              </svg>
            </button>
            
            <!-- Номера страниц -->
            <button
              v-for="page in getVisiblePages()"
              :key="page"
              @click="goToPage(page)"
              :class="[
                'flex items-center justify-center w-10 h-10 rounded-lg text-sm font-medium',
                page === meta.current_page
                  ? 'bg-blue-600 text-white'
                  : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50'
              ]"
            >
              {{ page }}
            </button>
            
            <!-- Следующая страница -->
            <button
              @click="goToPage(meta.current_page + 1)"
              :disabled="meta.current_page === meta.last_page"
              class="flex items-center justify-center w-10 h-10 rounded-lg border border-gray-300 bg-white text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
              </svg>
            </button>
          </nav>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue';
import { useAuthStore } from '../stores/auth';
import axios from 'axios';

export default {
  name: 'Objects',
  setup() {
    const authStore = useAuthStore();
    
    const loading = ref(false);
    const error = ref('');
    const taskCounts = ref({});
    const currentPage = ref(1);

    const objects = computed(() => authStore.objects);
    const meta = computed(() => authStore.objectsMeta);

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

    const fetchData = async (page = 1) => {
      loading.value = true;
      error.value = '';

      try {
        await authStore.fetchUser();
        await authStore.fetchObjects(page);
        await fetchTaskCounts();
        currentPage.value = page;
      } catch (err) {
        error.value = err.response?.data?.message || 'Ошибка загрузки данных';
      } finally {
        loading.value = false;
      }
    };

    const goToPage = (page) => {
      if (page >= 1 && page <= meta.value?.last_page) {
        fetchData(page);
      }
    };

    const getVisiblePages = () => {
      if (!meta.value) return [];
      
      const current = meta.value.current_page;
      const last = meta.value.last_page;
      const pages = [];
      
      // Показываем максимум 5 страниц
      let start = Math.max(1, current - 2);
      let end = Math.min(last, current + 2);
      
      // Корректируем если в начале или в конце
      if (end - start < 4) {
        if (start === 1) {
          end = Math.min(last, start + 4);
        } else if (end === last) {
          start = Math.max(1, end - 4);
        }
      }
      
      for (let i = start; i <= end; i++) {
        pages.push(i);
      }
      
      return pages;
    };



    onMounted(() => {
      fetchData();
    });

    return {
      loading,
      error,
      objects,
      taskCounts,
      meta,
      currentPage,
      goToPage,
      getVisiblePages,
    };
  },
};
</script> 