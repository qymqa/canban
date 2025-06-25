<template>
    <div class="bg-white rounded-lg p-6">
        <!-- Заголовок и управление -->
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-semibold text-gray-900">Табель рабочего времени</h2>
            
            <div class="flex items-center space-x-4">
                <!-- Кнопки экспорта -->
                <div class="flex items-center space-x-2">
                    <button
                        @click="exportExcel"
                        :disabled="loading"
                        class="px-3 py-2 bg-green-600 text-white text-sm rounded hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-1"
                    >
                        <span>📊</span>
                        <span>Excel</span>
                    </button>
                    <button
                        @click="exportPdf"
                        :disabled="loading"
                        class="px-3 py-2 bg-red-600 text-white text-sm rounded hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-1"
                    >
                        <span>📄</span>
                        <span>PDF</span>
                    </button>
                </div>
                
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
                
                <!-- Статистика -->
                <div v-if="stats" class="text-sm text-gray-600">
                    Всего часов: {{ stats.total_hours }} | 
                    Отчетов: {{ stats.total_reports }} |
                    Рабочих дней: {{ stats.work_days_in_month }}
                </div>
            </div>
        </div>

        <!-- Настройки работы -->
        <div v-if="workSettings" class="mb-6 p-4 bg-gray-50 rounded-lg">
            <h3 class="text-sm font-medium text-gray-900 mb-2">Настройки рабочего времени:</h3>
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 text-sm text-gray-600">
                <div><strong>Часов в день:</strong> {{ workSettings.work_hours_per_day }}</div>
                <div><strong>Начало работы:</strong> {{ workSettings.work_start_time }}</div>
                <div><strong>Часовой пояс:</strong> {{ workSettings.timezone }}</div>
                <div><strong>Рабочие дни:</strong> {{ getWorkDaysFromWeekends(workSettings.weekend_days) }}</div>
                <div><strong>Выходные:</strong> {{ getWeekendDaysText(workSettings.weekend_days) }}</div>
            </div>
        </div>

        <!-- Календарная таблица табеля -->
        <div v-if="loading" class="text-center py-8">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <p class="mt-2 text-gray-600">Загрузка табеля...</p>
        </div>

        <div v-else class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-3 px-4 font-medium text-gray-900 min-w-[200px] sticky left-0 bg-white">
                            Сотрудник
                        </th>
                        <th
                            v-for="day in calendar"
                            :key="day.date"
                            class="text-center py-3 px-2 font-medium text-gray-900 min-w-[60px]"
                            :class="{
                                'bg-red-50': day.is_weekend,
                                'bg-yellow-50': day.is_holiday
                            }"
                        >
                            <div class="text-xs text-gray-500">{{ getDayName(day.day_of_week) }}</div>
                            <div>{{ day.day }}</div>
                        </th>
                        <th class="text-center py-3 px-4 font-medium text-gray-900 min-w-[100px] sticky right-0 bg-white">
                            Итого часов
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="userTimesheet in timesheet"
                        :key="userTimesheet.user.id"
                        class="border-b border-gray-100 hover:bg-gray-50"
                    >
                        <!-- Информация о сотруднике -->
                        <td class="py-4 px-4 sticky left-0 bg-white">
                            <div class="font-medium text-gray-900">
                                {{ userTimesheet.user.name }} {{ userTimesheet.user.family_name || userTimesheet.user.surname || '' }}
                            </div>
                            <div class="text-sm text-gray-500">
                                {{ userTimesheet.user.position || 'Должность не указана' }}
                            </div>
                        </td>
                        
                        <!-- Дни месяца -->
                        <td
                            v-for="day in userTimesheet.days"
                            :key="`${userTimesheet.user.id}-${day.date}`"
                            class="py-2 px-2 text-center relative"
                            :class="{
                                'bg-red-50': day.is_weekend,
                                'bg-yellow-50': day.is_holiday
                            }"
                        >
                            <div
                                v-if="!day.is_weekend && !day.is_holiday"
                                class="relative"
                            >
                                <!-- Часы работы -->
                                <input
                                    v-if="isAdmin"
                                    type="number"
                                    :value="day.hours_worked"
                                    @change="updateHours(userTimesheet.user.id, day.date, $event.target.value)"
                                    min="0"
                                    max="24"
                                    step="0.5"
                                    class="w-full text-center border-0 bg-transparent text-sm font-medium focus:ring-1 focus:ring-blue-500 rounded"
                                    :class="{
                                        'text-green-600': day.has_report && day.hours_worked > 0,
                                        'text-red-600': !day.has_report && day.hours_worked === 0,
                                        'text-gray-900': day.hours_worked > 0 && !day.has_report
                                    }"
                                />
                                <span
                                    v-else
                                    class="text-sm font-medium"
                                    :class="{
                                        'text-green-600': day.has_report && day.hours_worked > 0,
                                        'text-red-600': !day.has_report && day.hours_worked === 0,
                                        'text-gray-900': day.hours_worked > 0 && !day.has_report
                                    }"
                                >
                                    {{ day.hours_worked || '0' }}
                                </span>
                                
                                <!-- Индикатор отчета -->
                                <div
                                    v-if="day.has_report"
                                    class="absolute -top-1 -right-1 w-2 h-2 bg-green-500 rounded-full"
                                    title="Есть ежедневный отчет"
                                ></div>
                            </div>
                            
                            <!-- Выходные и праздники -->
                            <div v-else class="text-gray-400 text-xs">
                                {{ day.is_weekend ? 'В' : 'П' }}
                            </div>
                        </td>
                        
                        <!-- Итого часов -->
                        <td class="py-4 px-4 text-center font-medium sticky right-0 bg-white">
                            <div class="text-lg">{{ formatHours(userTimesheet.stats.total_hours) }}</div>
                            <div class="text-xs text-gray-500">
                                Отчетов: {{ userTimesheet.stats.days_with_reports }}
                            </div>
                        </td>
                    </tr>
                </tbody>
                
                <!-- Итоговая строка -->
                <tfoot v-if="timesheet.length">
                    <tr class="border-t-2 border-gray-300 bg-gray-50">
                        <td class="py-3 px-4 font-medium text-gray-900 sticky left-0 bg-gray-50">
                            Итого по всем сотрудникам
                        </td>
                        <td
                            v-for="day in calendar"
                            :key="`total-${day.date}`"
                            class="py-3 px-2 text-center font-medium text-gray-900"
                            :class="{
                                'bg-red-100': day.is_weekend,
                                'bg-yellow-100': day.is_holiday
                            }"
                        >
                            {{ getTotalHoursForDate(day.date) }}
                        </td>
                        <td class="py-3 px-4 text-center font-bold text-lg sticky right-0 bg-gray-50">
                            {{ formatHours(totalHours) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Легенда -->
        <div class="mt-6 flex flex-wrap items-center gap-6 text-sm text-gray-600">
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                <span>Есть ежедневный отчет</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 bg-red-50 border border-red-200 rounded"></div>
                <span>Выходной день</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 bg-yellow-50 border border-yellow-200 rounded"></div>
                <span>Праздничный день</span>
            </div>
            <div class="text-green-600">
                <span class="font-medium">Зеленый текст:</span> Есть отчет
            </div>
            <div class="text-red-600">
                <span class="font-medium">Красный текст:</span> Нет отчета, 0 часов
            </div>
        </div>
    </div>
</template>

<script>
import { ref, onMounted, computed, watch } from 'vue';
import axios from 'axios';

export default {
    name: 'Timesheet',
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
        const loading = ref(false);
        const timesheet = ref([]);
        const calendar = ref([]);
        const workSettings = ref(null);
        const stats = ref(null);
        const currentYear = ref(new Date().getFullYear());
        const currentMonth = ref(new Date().getMonth() + 1);

        // Получить название месяца
        const currentMonthName = computed(() => {
            const months = [
                'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь',
                'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'
            ];
            return months[currentMonth.value - 1];
        });

        // Подсчет общих часов
        const totalHours = computed(() => {
            return timesheet.value.reduce((total, userTimesheet) => {
                return total + userTimesheet.stats.total_hours;
            }, 0);
        });

        // Загрузить табель
        const loadTimesheet = async () => {
            loading.value = true;
            try {
                const response = await axios.get('/api/timesheet/monthly', {
                    params: {
                        object_id: props.objectId,
                        year: currentYear.value,
                        month: currentMonth.value,
                    },
                });
                
                timesheet.value = response.data.timesheet;
                calendar.value = response.data.calendar;
                workSettings.value = response.data.work_settings;
                
                // Загружаем статистику
                await loadStats();
            } catch (error) {
                console.error('Error loading timesheet:', error);
            } finally {
                loading.value = false;
            }
        };

        // Загрузить статистику
        const loadStats = async () => {
            try {
                const response = await axios.get('/api/timesheet/stats', {
                    params: {
                        object_id: props.objectId,
                        year: currentYear.value,
                        month: currentMonth.value,
                    },
                });
                
                stats.value = response.data;
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        };

        // Обновить часы работы
        const updateHours = async (userId, date, hours) => {
            if (!props.isAdmin) {
                alert('Только администратор может редактировать табель');
                return;
            }

            try {
                await axios.put('/api/timesheet/update', {
                    object_id: props.objectId,
                    user_id: userId,
                    date: date,
                    hours_worked: parseFloat(hours) || 0,
                });
                
                // Обновляем локальные данные
                await loadTimesheet();
            } catch (error) {
                console.error('Error updating hours:', error);
                alert('Ошибка обновления часов');
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

        // Получить общее количество часов за дату
        const getTotalHoursForDate = (date) => {
            const total = timesheet.value.reduce((total, userTimesheet) => {
                const dayData = userTimesheet.days.find(day => day.date === date);
                return total + (dayData?.hours_worked || 0);
            }, 0);
            return formatHours(total);
        };

        // Форматировать часы (убрать ведущий ноль)
        const formatHours = (hours) => {
            if (hours === 0) return 0;
            const num = parseFloat(hours);
            return num % 1 === 0 ? num.toString() : num.toFixed(1);
        };

        // Получить название дня недели
        const getDayName = (dayOfWeek) => {
            const dayNames = ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'];
            return dayNames[dayOfWeek];
        };

        // Получить текст выходных дней
        const getWeekendDaysText = (weekendDays) => {
            const dayNames = ['Воскресенье', 'Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота'];
            return weekendDays.map(day => dayNames[day]).join(', ');
        };

        // Получить текст рабочих дней из выходных
        const getWorkDaysFromWeekends = (weekendDays) => {
            const allDays = [0, 1, 2, 3, 4, 5, 6];
            const workDays = allDays.filter(day => !weekendDays.includes(day));
            const dayNames = ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'];
            return workDays.map(day => dayNames[day]).join(', ');
        };

        // Получить текст рабочих дней (старый метод, может пригодиться)
        const getWorkDaysText = (workDaysList) => {
            if (!workDaysList || !Array.isArray(workDaysList)) {
                return 'Не указано';
            }
            
            const dayMap = {
                'mon': 'Пн', 'monday': 'Пн',
                'tue': 'Вт', 'tuesday': 'Вт', 
                'wed': 'Ср', 'wednesday': 'Ср',
                'thu': 'Чт', 'thursday': 'Чт',
                'fri': 'Пт', 'friday': 'Пт',
                'sat': 'Сб', 'saturday': 'Сб',
                'sun': 'Вс', 'sunday': 'Вс',
            };
            
            return workDaysList.map(day => dayMap[day.toLowerCase()] || day).join(', ');
        };

        // Экспорт в Excel
        const exportExcel = async () => {
            if (loading.value) return;
            
            try {
                const response = await axios.get('/api/timesheet/export/excel', {
                    params: {
                        object_id: props.objectId,
                        year: currentYear.value,
                        month: currentMonth.value,
                    },
                    responseType: 'blob',
                });
                
                // Создаем ссылку для скачивания
                const url = window.URL.createObjectURL(new Blob([response.data]));
                const link = document.createElement('a');
                link.href = url;
                
                // Получаем имя файла из заголовков или создаем по умолчанию
                const contentDisposition = response.headers['content-disposition'];
                let filename = `timesheet_${currentMonthName.value}_${currentYear.value}.csv`;
                if (contentDisposition) {
                    const matches = /filename="([^"]*)"/.exec(contentDisposition);
                    if (matches && matches[1]) {
                        filename = matches[1];
                    }
                }
                
                link.setAttribute('download', filename);
                document.body.appendChild(link);
                link.click();
                link.remove();
                window.URL.revokeObjectURL(url);
            } catch (error) {
                console.error('Error exporting Excel:', error);
                alert('Ошибка экспорта в Excel');
            }
        };

        // Экспорт в PDF
        const exportPdf = async () => {
            if (loading.value) return;
            
            try {
                const response = await axios.get('/api/timesheet/export/pdf', {
                    params: {
                        object_id: props.objectId,
                        year: currentYear.value,
                        month: currentMonth.value,
                    },
                    responseType: 'blob',
                });
                
                // Создаем ссылку для скачивания
                const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }));
                const link = document.createElement('a');
                link.href = url;
                
                // Получаем имя файла из заголовков или создаем по умолчанию
                const contentDisposition = response.headers['content-disposition'];
                let filename = `timesheet_${currentMonthName.value}_${currentYear.value}.pdf`;
                if (contentDisposition) {
                    const matches = /filename="([^"]*)"/.exec(contentDisposition);
                    if (matches && matches[1]) {
                        filename = matches[1];
                    }
                }
                
                link.setAttribute('download', filename);
                document.body.appendChild(link);
                link.click();
                link.remove();
                window.URL.revokeObjectURL(url);
            } catch (error) {
                console.error('Error exporting PDF:', error);
                alert('Ошибка экспорта в PDF');
            }
        };

        // Следим за изменением месяца/года
        watch([currentYear, currentMonth], () => {
            loadTimesheet();
        });

        // Следим за изменением объекта
        watch(() => props.objectId, () => {
            loadTimesheet();
        });

        onMounted(() => {
            loadTimesheet();
        });

        return {
            loading,
            timesheet,
            calendar,
            workSettings,
            stats,
            currentYear,
            currentMonth,
            currentMonthName,
            totalHours,
            
            // Методы
            loadTimesheet,
            updateHours,
            previousMonth,
            nextMonth,
            getTotalHoursForDate,
            getDayName,
            getWeekendDaysText,
            getWorkDaysFromWeekends,
            getWorkDaysText,
            formatHours,
            exportExcel,
            exportPdf,
        };
    },
};
</script>

<style scoped>
/* Стили для фиксированных колонок */
.sticky {
    position: sticky;
    z-index: 10;
}

/* Стили для таблицы */
table {
    font-size: 0.875rem;
}

/* Стили для инпутов часов */
input[type="number"] {
    -moz-appearance: textfield;
}

input[type="number"]::-webkit-outer-spin-button,
input[type="number"]::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

/* Улучшенная видимость при фокусе */
input[type="number"]:focus {
    background-color: white;
    border: 1px solid #3b82f6;
}
</style> 