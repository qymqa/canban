<template>
    <div class="min-h-screen" style="background-color: rgb(245, 245, 245)">
        <!-- Модальное окно выбора объекта -->
        <div 
            v-if="showObjectSelector" 
            class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50"
        >
            <div class="bg-white rounded-lg p-6 w-96 max-w-full">
                <h2 class="text-xl font-semibold mb-4">Выберите объект для отчетов</h2>
                <p class="text-gray-600 mb-4">
                    Отчеты и табель ведутся по конкретному объекту. Выберите объект для просмотра отчетов.
                </p>
                
                <div class="space-y-2 max-h-60 overflow-y-auto">
                    <button
                        v-for="object in availableObjects"
                        :key="object.id"
                        @click="selectObject(object.id)"
                        class="w-full text-left px-4 py-3 border border-gray-200 rounded-md hover:bg-gray-50 hover:border-indigo-300 transition-colors"
                    >
                        <div class="font-medium" :title="object.title || `Объект ${object.id}`">{{ limitText(object.title || `Объект ${object.id}`, 50) }}</div>
                        <div v-if="object.description" class="text-sm text-gray-500">{{ object.description }}</div>
                    </button>
                </div>
                
                <div class="mt-4 flex justify-end space-x-3">
                    <button
                        @click="goBackToBoard"
                        class="px-4 py-2 text-gray-600 hover:text-gray-800"
                    >
                        Отмена
                    </button>
                </div>
            </div>
        </div>

        <!-- Основное содержимое -->
        <div v-if="!showObjectSelector">
            <!-- Навигация -->
            <nav class="bg-white border-b border-gray-200">
                <div class="w-full app-max-width mx-auto px-20 pb-4">
                    <div class="flex items-center justify-between py-4">
                        <div class="flex items-center space-x-8">
                            <router-link to="/board/all" class="text-gray-600 hover:text-gray-800">
                                ← Назад к меню
                            </router-link>
                            <h1 class="text-lg font-semibold text-gray-900 truncate max-w-md" :title="currentObjectName || 'Загрузка...'">
                                {{ currentObjectName || 'Загрузка...' }}
                            </h1>
                        </div>
                        
                        <!-- Выбор объекта -->
                        <div class="flex items-center space-x-4">
                            <div class="flex items-center space-x-2">
                                <label class="text-sm font-medium text-gray-700">Объект:</label>
                                <select
                                    :value="currentObjectId"
                                    @change="changeObject($event.target.value)"
                                    class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                                    style="width: 300px;"
                                >
                                    <option v-for="object in availableObjects" :key="object.id" :value="object.id" :title="object.title || `Объект ${object.id}`">
                                        {{ limitText(object.title || `Объект ${object.id}`, 40) }}
                                    </option>
                                </select>
                            </div>
                            
                            <!-- Переключатель между отчетами и табелем -->
                            <div class="flex bg-gray-100 rounded-lg p-1">
                                <button
                                    @click="currentView = 'reports'"
                                    :class="[
                                        'px-4 py-2 rounded-md text-sm font-medium transition-colors',
                                        currentView === 'reports'
                                            ? 'bg-white text-gray-900'
                                            : 'text-gray-600 hover:text-gray-900'
                                    ]"
                                >
                                    Ежедневные отчеты
                                </button>
                                <button
                                    @click="currentView = 'timesheet'"
                                    :class="[
                                        'px-4 py-2 rounded-md text-sm font-medium transition-colors',
                                        currentView === 'timesheet'
                                            ? 'bg-white text-gray-900'
                                            : 'text-gray-600 hover:text-gray-900'
                                    ]"
                                >
                                    Табель
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Основной контент -->
            <main v-if="currentObjectId && currentObjectId !== 'all'" class="w-full app-max-width mx-auto pb-4">
                <div class="py-6">
                    <!-- Компонент ежедневных отчетов -->
                    <DailyReports
                        v-if="currentView === 'reports'"
                        :object-id="currentObjectId"
                        :current-user="currentUser"
                        :is-admin="isAdmin"
                    />
                    
                    <!-- Компонент табеля -->
                    <Timesheet
                        v-if="currentView === 'timesheet'"
                        :object-id="currentObjectId"
                        :current-user="currentUser"
                        :is-admin="isAdmin"
                    />
                </div>
            </main>
        </div>
    </div>
</template>

<script>
import { ref, onMounted, computed, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import DailyReports from '../components/DailyReports.vue';
import Timesheet from '../components/Timesheet.vue';
import axios from 'axios';

export default {
    name: 'Reports',
    components: {
        DailyReports,
        Timesheet,
    },
    setup() {
        const route = useRoute();
        const router = useRouter();
        const authStore = useAuthStore();
        
        const currentView = ref(route.query.tab === 'timesheet' ? 'timesheet' : 'reports');
        const currentObjectName = ref('');
        const currentObjectId = ref(route.params.objectId);
        const currentUser = ref(null);
        const isAdmin = ref(false);
        const availableObjects = ref([]);
        const showObjectSelector = ref(false);

        // Если objectId равен "all", показываем модальное окно выбора объекта
        const checkObjectId = () => {
            if (currentObjectId.value === 'all') {
                showObjectSelector.value = true;
            } else {
                showObjectSelector.value = false;
            }
        };

        const fetchObjects = async () => {
            try {
                await authStore.fetchObjects();
                availableObjects.value = authStore.objects || [];
            } catch (error) {
                console.error('Error fetching objects:', error);
            }
        };

        const fetchObjectName = async () => {
            if (currentObjectId.value === 'all') {
                return;
            }
            
            try {
                await authStore.fetchObjects();
                const currentObject = authStore.objects.find(obj => obj.id.toString() === currentObjectId.value);
                if (currentObject) {
                    currentObjectName.value = currentObject.title || `Объект ${currentObject.id}`;
                } else {
                    currentObjectName.value = `Объект ${currentObjectId.value}`;
                }
            } catch (error) {
                console.error('Error fetching object name:', error);
                currentObjectName.value = `Объект ${currentObjectId.value}`;
            }
        };

        const fetchUserData = async () => {
            try {
                if (!authStore.user) {
                    await authStore.fetchUser();
                }
                currentUser.value = authStore.user;
                isAdmin.value = authStore.user?.role === 'admin' || authStore.user?.is_admin || false;
            } catch (error) {
                console.error('Error fetching user data:', error);
            }
        };

        const selectObject = (objectId) => {
            const currentQuery = { ...route.query };
            router.push({
                name: 'Reports',
                params: { objectId: objectId },
                query: currentQuery
            });
        };

        const changeObject = (objectId) => {
            if (objectId && objectId !== currentObjectId.value) {
                selectObject(objectId);
            }
        };

        const goBackToBoard = () => {
            router.push('/board/all');
        };

        // Функция для ограничения длины текста
        const limitText = (text, maxLength) => {
            if (!text) return '';
            if (text.length <= maxLength) return text;
            return text.substring(0, maxLength) + '...';
        };

        // Отслеживаем изменения в роуте
        watch(() => route.params.objectId, (newObjectId) => {
            currentObjectId.value = newObjectId;
            checkObjectId();
            if (newObjectId !== 'all') {
                fetchObjectName();
            }
        });

        // Отслеживаем изменения tab в query
        watch(() => route.query.tab, (newTab) => {
            currentView.value = newTab === 'timesheet' ? 'timesheet' : 'reports';
        });

        onMounted(async () => {
            checkObjectId();
            await Promise.all([
                fetchObjects(),
                fetchUserData()
            ]);
            
            if (currentObjectId.value !== 'all') {
                await fetchObjectName();
            }
        });

        return {
            currentView,
            currentObjectName,
            currentObjectId,
            currentUser,
            isAdmin,
            availableObjects,
            showObjectSelector,
            selectObject,
            changeObject,
            goBackToBoard,
            limitText,
        };
    },
};
</script>

<style scoped>
.app-max-width {
    max-width: 2400px;
}
</style> 