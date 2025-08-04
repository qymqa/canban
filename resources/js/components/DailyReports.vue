<template>
    <div class="bg-white rounded-lg p-6">
        <!-- Заголовок и управление -->
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-semibold text-gray-900">Ежедневные отчеты</h2>
            
            <div class="flex items-center space-x-4">
                <!-- Навигация по месяцам -->
                <div class="flex items-center space-x-2">
                    <button
                        @click="previousMonth"
                        class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded"
                    >
                        ←
                    </button>
                    <span class="text-lg font-medium min-w-[200px] text-center">
                        {{ currentMonthName }} {{ currentYear }}
                    </span>
                    <button
                        @click="nextMonth"
                        class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded"
                    >
                        →
                    </button>
                </div>
                
                <!-- Кнопка создания отчета -->
                <button
                    v-if="canCreateReport"
                    @click="openCreateModal"
                    :disabled="hasTodayReport"
                    :class="[
                        'px-4 py-2 rounded-lg text-sm font-medium',
                        hasTodayReport 
                            ? 'bg-gray-400 text-gray-200 cursor-not-allowed' 
                            : 'bg-blue-600 hover:bg-blue-700 text-white'
                    ]"
                    :title="hasTodayReport ? 'Отчет за сегодня уже создан' : 'Создать новый отчет'"
                >
                    Создать отчет
                </button>
            </div>
        </div>

        <!-- Календарная таблица -->
        <div v-if="loading" class="text-center py-8">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <p class="mt-2 text-gray-600">Загрузка отчетов...</p>
        </div>

        <div v-else class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-3 px-4 font-medium text-gray-900 min-w-[200px]">
                            ФИО + Должность
                        </th>
                        <th
                            v-for="day in calendarDays"
                            :key="day.date"
                            class="text-center py-3 px-2 font-medium text-gray-900 min-w-[80px]"
                            :class="{ 'bg-gray-50': day.isWeekend }"
                        >
                            <div class="text-xs text-gray-500">{{ day.dayName }}</div>
                            <div>{{ day.day }}</div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="user in uniqueUsers"
                        :key="user.id"
                        class="border-b border-gray-100 hover:bg-gray-50"
                    >
                        <td class="py-4 px-4">
                            <div class="font-medium text-gray-900">
                                {{ user.name }} {{ user.family_name || user.surname || '' }}
                            </div>
                            <div class="text-sm text-gray-500">
                                {{ user.position || 'Должность не указана' }}
                            </div>
                        </td>
                        <td
                            v-for="day in calendarDays"
                            :key="`${user.id}-${day.date}`"
                            class="py-2 px-2 text-center relative"
                            :class="{ 'bg-gray-50': day.isWeekend }"
                        >
                            <div
                                v-if="getUserReportForDate(user.id, day.date)"
                                @dblclick="editReport(getUserReportForDate(user.id, day.date))"
                                class="cursor-pointer p-2 bg-green-100 hover:bg-green-200 rounded text-xs"
                                :title="getUserReportForDate(user.id, day.date).report_text"
                            >
                                <div class="font-medium text-green-800">Отчет</div>
                                <div class="text-green-600 truncate max-w-[60px]">
                                    {{ getUserReportForDate(user.id, day.date).report_text.substring(0, 20) }}...
                                </div>
                                <!-- Прикрепленные задачи -->
                                <div
                                    v-if="getUserReportForDate(user.id, day.date).attached_tasks?.length"
                                    class="mt-1"
                                >
                                    <div class="text-xs text-green-700">
                                        Задач: {{ getUserReportForDate(user.id, day.date).attached_tasks.length }}
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-gray-300 text-xs">—</div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Модальное окно создания отчета -->
        <div
            v-if="showCreateModal"
            class="fixed inset-0 flex items-center justify-center z-50"
            style="background: rgba(245, 245, 245, 0.75);"
            @click.self="closeCreateModal"
        >
            <div class="bg-white rounded-lg w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        Создать ежедневный отчет
                    </h3>
                    
                    <form @submit.prevent="createReport">
                        <!-- Дата и время (автоматически) -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Дата заполнения отчета
                            </label>
                            <input
                                type="text"
                                :value="new Date().toLocaleString('ru-RU')"
                                disabled
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50"
                            />
                        </div>

                        <!-- ФИО + должность (автоматически) -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                ФИО + должность
                            </label>
                            <input
                                type="text"
                                :value="`${currentUser?.name || ''} ${currentUser?.family_name || currentUser?.surname || ''} - ${currentUser?.position || 'Должность не указана'}`"
                                disabled
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50"
                            />
                        </div>

                        <!-- Кнопка добавить задачу -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Прикрепленные задачи
                            </label>
                            <button
                                type="button"
                                @click="loadAvailableTasks"
                                class="mb-2 bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded-lg text-sm"
                            >
                                Добавить задачу из канбана
                            </button>
                            
                            <!-- Список выбранных задач -->
                            <div v-if="newReport.attached_tasks.length" class="space-y-2">
                                <div
                                    v-for="task in newReport.attached_tasks"
                                    :key="task.id"
                                    class="flex items-center justify-between p-2 bg-gray-50 rounded"
                                >
                                    <span class="text-sm">{{ task.title }}</span>
                                    <button
                                        type="button"
                                        @click="removeTask(task.id)"
                                        class="text-red-600 hover:text-red-800 text-sm"
                                    >
                                        Удалить
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Текст отчета -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Отчет *
                            </label>
                            <textarea
                                v-model="newReport.report_text"
                                rows="6"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Опишите выполненную работу..."
                            ></textarea>
                        </div>

                        <!-- Кнопки -->
                        <div class="flex justify-end space-x-3">
                            <button
                                type="button"
                                @click="closeCreateModal"
                                class="px-4 py-2 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50"
                            >
                                Отмена
                            </button>
                            <button
                                type="submit"
                                :disabled="!newReport.report_text.trim()"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Создать отчет
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Модальное окно редактирования отчета -->
        <div
            v-if="showEditModal && editingReport"
            class="fixed inset-0 flex items-center justify-center z-50"
            style="background: rgba(245, 245, 245, 0.75);"
            @click.self="closeEditModal"
        >
            <div class="bg-white rounded-lg w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        Редактировать отчет
                    </h3>
                    
                    <form @submit.prevent="updateReport">
                        <!-- Дата и время -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Дата заполнения отчета
                            </label>
                            <input
                                type="text"
                                :value="new Date(editingReport.created_at).toLocaleString('ru-RU')"
                                disabled
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50"
                            />
                        </div>

                        <!-- ФИО + должность -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                ФИО + должность
                            </label>
                            <input
                                type="text"
                                :value="`${editingReport.user_name} ${editingReport.user_surname} - ${editingReport.user_position || 'Должность не указана'}`"
                                disabled
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50"
                            />
                        </div>

                        <!-- Прикрепленные задачи -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Прикрепленные задачи
                            </label>
                            <button
                                type="button"
                                @click="loadAvailableTasks"
                                class="mb-2 bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded-lg text-sm"
                            >
                                Добавить задачу из канбана
                            </button>
                            
                            <!-- Список выбранных задач -->
                            <div v-if="editingReport.attached_tasks?.length" class="space-y-2">
                                <div
                                    v-for="task in editingReport.attached_tasks"
                                    :key="task.id"
                                    class="flex items-center justify-between p-2 bg-gray-50 rounded"
                                >
                                    <span class="text-sm">{{ task.title }}</span>
                                    <button
                                        type="button"
                                        @click="removeTaskFromEdit(task.id)"
                                        class="text-red-600 hover:text-red-800 text-sm"
                                    >
                                        Удалить
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Текст отчета -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Отчет *
                            </label>
                            <textarea
                                v-model="editingReport.report_text"
                                rows="6"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Опишите выполненную работу..."
                            ></textarea>
                        </div>

                        <!-- Кнопки -->
                        <div class="flex justify-between">
                            <button
                                type="button"
                                @click="deleteReport"
                                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700"
                            >
                                Удалить отчет
                            </button>
                            <div class="flex space-x-3">
                                <button
                                    type="button"
                                    @click="closeEditModal"
                                    class="px-4 py-2 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50"
                                >
                                    Отмена
                                </button>
                                <button
                                    type="submit"
                                    :disabled="!editingReport.report_text.trim()"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    Сохранить отчет
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Модальное окно выбора задач -->
        <div
            v-if="showTasksModal"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
            @click.self="closeTasksModal"
        >
            <div class="bg-white rounded-lg w-full max-w-lg mx-4 max-h-[70vh] overflow-y-auto">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        Выберите задачи
                    </h3>
                    
                    <div v-if="loadingTasks" class="text-center py-4">
                        <div class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
                        <p class="mt-2 text-gray-600">Загрузка задач...</p>
                    </div>
                    
                    <div v-else-if="availableTasks.length">
                        <div class="space-y-2 max-h-60 overflow-y-auto">
                            <div
                                v-for="task in availableTasks"
                                :key="task.id"
                                class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer"
                                @click="toggleTaskSelection(task)"
                            >
                                <input
                                    type="checkbox"
                                    :checked="isTaskSelected(task.id)"
                                    class="mr-3"
                                    @click.stop="toggleTaskSelection(task)"
                                />
                                <div class="flex-1">
                                    <div class="font-medium text-gray-900">{{ task.title }}</div>
                                    <div class="text-sm text-gray-500">
                                        Статус: {{ getStatusText(task.status) }} | 
                                        Приоритет: {{ getPriorityText(task.priority) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-end space-x-3 mt-4">
                            <button
                                type="button"
                                @click="closeTasksModal"
                                class="px-4 py-2 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50"
                            >
                                Отмена
                            </button>
                            <button
                                type="button"
                                @click="addSelectedTasks"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                            >
                                Добавить выбранные
                            </button>
                        </div>
                    </div>
                    
                    <div v-else class="text-center py-4 text-gray-500">
                        Нет доступных задач
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, onMounted, computed, watch } from 'vue';
import { useAuthStore } from '../stores/auth';
import axios from 'axios';

export default {
    name: 'DailyReports',
    props: {
        objectId: {
            type: String,
            required: true,
        },
        currentUser: {
            type: Object,
            default: null,
        },
        isAdmin: {
            type: Boolean,
            default: false,
        },
    },
    setup(props) {
        const authStore = useAuthStore();
        const loading = ref(false);
        const reports = ref({});
        const currentYear = ref(new Date().getFullYear());
        const currentMonth = ref(new Date().getMonth() + 1);
        
        // Модальные окна
        const showCreateModal = ref(false);
        const showEditModal = ref(false);
        const showTasksModal = ref(false);
        
        // Данные для создания отчета
        const newReport = ref({
            report_text: '',
            attached_tasks: [],
        });
        
        // Данные для редактирования отчета
        const editingReport = ref(null);
        
        // Задачи
        const availableTasks = ref([]);
        const loadingTasks = ref(false);
        const selectedTasks = ref([]);

        // Получить название месяца
        const currentMonthName = computed(() => {
            const months = [
                'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь',
                'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'
            ];
            return months[currentMonth.value - 1];
        });

        // Получить дни календаря
        const calendarDays = computed(() => {
            const year = currentYear.value;
            const month = currentMonth.value;
            const daysInMonth = new Date(year, month, 0).getDate();
            const days = [];
            
            const dayNames = ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'];
            
            for (let day = 1; day <= daysInMonth; day++) {
                const date = new Date(year, month - 1, day);
                const dayOfWeek = date.getDay();
                
                days.push({
                    day,
                    date: `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`,
                    dayName: dayNames[dayOfWeek],
                    isWeekend: dayOfWeek === 0 || dayOfWeek === 6,
                });
            }
            
            return days;
        });

        // Получить уникальных пользователей
        const uniqueUsers = computed(() => {
            const users = new Map();
            
            Object.values(reports.value).forEach(dayReports => {
                dayReports.forEach(report => {
                    if (!users.has(report.user_id)) {
                        users.set(report.user_id, {
                            id: report.user_id,
                            name: report.user_name,
                            family_name: report.user_surname,
                            position: report.user_position,
                        });
                    }
                });
            });
            
            return Array.from(users.values());
        });

        // Получить отчет пользователя за дату
        const getUserReportForDate = (userId, date) => {
            const dayReports = reports.value[date] || [];
            return dayReports.find(report => report.user_id === userId);
        };

        // Проверить, есть ли уже отчет за сегодня для текущего пользователя
        const hasTodayReport = computed(() => {
            if (!props.currentUser) return false;
            
            const today = new Date().toISOString().split('T')[0]; // YYYY-MM-DD format
            const todayReports = reports.value[today] || [];
            
            return todayReports.some(report => report.user_id === props.currentUser.id);
        });

        // Проверка прав на создание отчетов - инспектор не может создавать отчеты
        const canCreateReport = computed(() => {
            const userRole = authStore.user?.role || 'user';
            return userRole !== 'inspector';
        });

        // Загрузить отчеты
        const loadReports = async () => {
            loading.value = true;
            try {
                const response = await axios.get('/api/daily-reports/monthly', {
                    params: {
                        object_id: props.objectId,
                        year: currentYear.value,
                        month: currentMonth.value,
                    },
                });
                
                reports.value = response.data.reports;
            } catch (error) {
                console.error('Error loading reports:', error);
            } finally {
                loading.value = false;
            }
        };

        // Навигация по месяцам
        const previousMonth = () => {
            if (currentMonth.value === 1) {
                currentMonth.value = 12;
                currentYear.value--;
            } else {
                currentMonth.value--;
            }
        };

        const nextMonth = () => {
            if (currentMonth.value === 12) {
                currentMonth.value = 1;
                currentYear.value++;
            } else {
                currentMonth.value++;
            }
        };

        // Модальные окна
        const openCreateModal = () => {
            if (hasTodayReport.value) {
                alert('Отчет за сегодня уже создан');
                return;
            }
            
            newReport.value = {
                report_text: '',
                attached_tasks: [],
            };
            showCreateModal.value = true;
        };

        const closeCreateModal = () => {
            showCreateModal.value = false;
            newReport.value = {
                report_text: '',
                attached_tasks: [],
            };
        };

        const editReport = (report) => {
            if (report.user_id !== props.currentUser?.id) {
                alert('Вы можете редактировать только свои отчеты');
                return;
            }
            
            editingReport.value = { ...report };
            showEditModal.value = true;
        };

        const closeEditModal = () => {
            showEditModal.value = false;
            editingReport.value = null;
        };

        // Создать отчет
        const createReport = async () => {
            try {
                await axios.post('/api/daily-reports', {
                    object_id: props.objectId,
                    report_text: newReport.value.report_text,
                    attached_tasks: newReport.value.attached_tasks,
                });
                
                closeCreateModal();
                await loadReports();
            } catch (error) {
                console.error('Error creating report:', error);
                alert('Ошибка создания отчета');
            }
        };

        // Обновить отчет
        const updateReport = async () => {
            try {
                await axios.put(`/api/daily-reports/${editingReport.value.id}`, {
                    report_text: editingReport.value.report_text,
                    attached_tasks: editingReport.value.attached_tasks,
                });
                
                closeEditModal();
                await loadReports();
            } catch (error) {
                console.error('Error updating report:', error);
                alert('Ошибка обновления отчета');
            }
        };

        // Удалить отчет
        const deleteReport = async () => {
            if (!confirm('Вы уверены, что хотите удалить этот отчет?')) {
                return;
            }
            
            try {
                await axios.delete(`/api/daily-reports/${editingReport.value.id}`);
                closeEditModal();
                await loadReports();
            } catch (error) {
                console.error('Error deleting report:', error);
                alert('Ошибка удаления отчета');
            }
        };

        // Работа с задачами
        const loadAvailableTasks = async () => {
            showTasksModal.value = true;
            loadingTasks.value = true;
            selectedTasks.value = [];
            
            try {
                const response = await axios.get('/api/daily-reports/tasks', {
                    params: { object_id: props.objectId },
                });
                availableTasks.value = response.data;
            } catch (error) {
                console.error('Error loading tasks:', error);
            } finally {
                loadingTasks.value = false;
            }
        };

        const closeTasksModal = () => {
            showTasksModal.value = false;
            selectedTasks.value = [];
        };

        const toggleTaskSelection = (task) => {
            const index = selectedTasks.value.findIndex(t => t.id === task.id);
            if (index > -1) {
                selectedTasks.value.splice(index, 1);
            } else {
                selectedTasks.value.push(task);
            }
        };

        const isTaskSelected = (taskId) => {
            return selectedTasks.value.some(task => task.id === taskId);
        };

        const addSelectedTasks = () => {
            if (showEditModal.value) {
                // Добавляем к редактируемому отчету
                selectedTasks.value.forEach(task => {
                    if (!editingReport.value.attached_tasks.some(t => t.id === task.id)) {
                        editingReport.value.attached_tasks.push(task);
                    }
                });
            } else {
                // Добавляем к новому отчету
                selectedTasks.value.forEach(task => {
                    if (!newReport.value.attached_tasks.some(t => t.id === task.id)) {
                        newReport.value.attached_tasks.push(task);
                    }
                });
            }
            closeTasksModal();
        };

        const removeTask = (taskId) => {
            newReport.value.attached_tasks = newReport.value.attached_tasks.filter(t => t.id !== taskId);
        };

        const removeTaskFromEdit = (taskId) => {
            editingReport.value.attached_tasks = editingReport.value.attached_tasks.filter(t => t.id !== taskId);
        };

        // Утилиты
        const getStatusText = (status) => {
            const statusMap = {
                waiting: 'Ожидают',
                in_progress: 'В работе',
                completed: 'Выполнены',
                blocked: 'Заблокированы',
            };
            return statusMap[status] || status;
        };

        const getPriorityText = (priority) => {
            const priorityMap = {
                low: 'Низкий',
                medium: 'Средний',
                high: 'Высокий',
            };
            return priorityMap[priority] || priority;
        };

        // Следим за изменением месяца/года
        watch([currentYear, currentMonth], () => {
            loadReports();
        });

        // Следим за изменением объекта
        watch(() => props.objectId, () => {
            loadReports();
        });

        onMounted(() => {
            loadReports();
        });

        return {
            loading,
            reports,
            currentYear,
            currentMonth,
            currentMonthName,
            calendarDays,
            uniqueUsers,
            getUserReportForDate,
            hasTodayReport,
            canCreateReport,
            previousMonth,
            nextMonth,
            
            // Модальные окна
            showCreateModal,
            showEditModal,
            showTasksModal,
            openCreateModal,
            closeCreateModal,
            editReport,
            closeEditModal,
            
            // Отчеты
            newReport,
            editingReport,
            createReport,
            updateReport,
            deleteReport,
            
            // Задачи
            availableTasks,
            loadingTasks,
            selectedTasks,
            loadAvailableTasks,
            closeTasksModal,
            toggleTaskSelection,
            isTaskSelected,
            addSelectedTasks,
            removeTask,
            removeTaskFromEdit,
            
            // Утилиты
            getStatusText,
            getPriorityText,
        };
    },
};
</script>

<style scoped>
/* Дополнительные стили для таблицы */
table {
    font-size: 0.875rem;
}

.truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
</style> 