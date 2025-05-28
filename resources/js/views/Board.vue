<template>
    <div class="min-h-screen flex" style="background-color: rgb(245, 245, 245)">
    <!-- Боковое меню -->
    <div class="flex-shrink-0" style="width: 280px;">
      <div class="bg-white border border-gray-200 rounded-lg p-6 mt-4">
        <h2 class="text-lg font-semibold text-gray-900 mb-6">{{ currentObjectName || 'Загрузка...' }}</h2>
        <nav class="space-y-2">
          <router-link
            :to="`/board/${route.params.objectId}`"
            class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-indigo-600 bg-indigo-50"
          >
            <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
            </svg>
            Задачи
          </router-link>
          
          <button
            disabled
            class="flex items-center w-full px-4 py-2 text-sm font-medium rounded-md text-gray-400 cursor-not-allowed"
          >
            <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Ежедневный отчет
          </button>
          
          <button
            disabled
            class="flex items-center w-full px-4 py-2 text-sm font-medium rounded-md text-gray-400 cursor-not-allowed"
          >
            <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Табель
          </button>
          
          <a
            href="https://app.staging.pto-app.ru/analytics"
            target="_parent"
            rel="noopener noreferrer"
            class="flex items-center px-4 py-2 text-sm font-medium rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-50"
          >
            <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
            </svg>
            Аналитика
          </a>
        </nav>
      </div>
    </div>

    <!-- Основной контент -->
    <div class="flex-1 min-w-0">
      <div class="w-full max-w-screen-2xl mx-auto px-20 pt-4">
        <nav class="bg-white px-6 rounded-lg">
          <div class="flex justify-between h-16">
            <div class="flex items-center space-x-4">
              <button
                @click="goBack"
                class="text-gray-600 hover:text-gray-900 font-bold px-4 py-2"
              >
                ← Назад
              </button>
            </div>
            <div class="flex items-center space-x-4">
              <button
                @click="openCustomFieldModal()"
                class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-bold"
              >
                Поля
              </button>
              <button
                @click="showCreateModal = true"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-bold flex items-center space-x-2"
              >
                <span>Создать задачу</span>
                <img src="https://app.dev.pto-app.ru/assets/plus.2d709d99.svg" alt="+" class="w-6 h-6" />
              </button>
            </div>
          </div>
        </nav>
      </div>

          <!-- Табы для переключения вида -->
      <div class="w-full max-w-screen-2xl mx-auto px-20 pb-4">
        <div class="border-b border-gray-200">
          <nav class="-mb-px flex">
            <button
              @click="currentView = 'kanban'"
              :class="[
                'w-1/2 py-2 px-1 border-b-2 font-bold text-sm text-center',
                currentView === 'kanban'
                  ? 'border-indigo-500 text-indigo-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              ]"
              style="font-size: 14px;"
            >
              Канбан
            </button>
            <button
              @click="currentView = 'table'"
              :class="[
                'w-1/2 py-2 px-1 border-b-2 font-bold text-sm text-center',
                currentView === 'table'
                  ? 'border-indigo-500 text-indigo-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              ]"
              style="font-size: 14px;"
            >
              Таблица
            </button>
          </nav>
        </div>
      </div>

      <!-- Поиск и фильтры -->
      <div class="w-full max-w-screen-2xl mx-auto px-20 pb-4">
        <div class="bg-white rounded-lg p-4">
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Поиск -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Поиск</label>
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Поиск по названию или описанию..."
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
              />
            </div>
            
            <!-- Фильтр по приоритету -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Приоритет</label>
              <select
                v-model="priorityFilter"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
              >
                <option value="">Все приоритеты</option>
                <option value="low">Низкий</option>
                <option value="medium">Средний</option>
                <option value="high">Высокий</option>
              </select>
            </div>
            
            <!-- Фильтр по ответственному -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Ответственный</label>
              <select
                v-model="responsibleFilter"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
              >
                <option value="">Все пользователи</option>
                <option v-for="user in users" :key="user.id" :value="user.id">
                  {{ user.name }} {{ user.family_name || user.surname || '' }}
                </option>
              </select>
            </div>
            
            <!-- Кнопка сброса фильтров -->
            <div class="flex items-end">
              <button
                @click="clearFilters"
                class="w-full px-4 py-2 text-sm font-bold text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300"
              >
                Сбросить фильтры
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="w-full max-w-screen-2xl mx-auto px-20 pb-4">
        <div v-if="loading" class="text-center">
          <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
          <p class="mt-2 text-gray-600">Загрузка доски...</p>
        </div>

      <!-- Канбан вид -->
      <div v-else-if="currentView === 'kanban'" class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div
          v-for="(column, status) in columns"
          :key="status"
          class="bg-gray-100 rounded-lg p-4 border border-gray-200"
        >
          <h3 class="font-semibold text-gray-900 mb-4">
            {{ column.title }}
            <span class="text-sm text-gray-500">({{ filteredTasks[status]?.length || 0 }})</span>
          </h3>
          
          <div
            :ref="el => setColumnRef(status, el)"
            class="space-y-3 min-h-[200px]"
            :data-status="status"
          >
            <div
              v-for="task in filteredTasks[status]"
              :key="task.id"
              :data-task-id="task.id"
              class="bg-white p-3 rounded-md cursor-move"
            >
              <div class="flex justify-between items-start mb-2">
                <h4 class="font-medium text-gray-900">{{ task.title }}</h4>
                <div class="flex space-x-1">
                  <span 
                    :class="{
                      'bg-blue-100 text-blue-800': task.status === 'waiting',
                      'bg-orange-100 text-orange-800': task.status === 'in_progress', 
                      'bg-green-100 text-green-800': task.status === 'completed',
                      'bg-red-100 text-red-800': task.status === 'blocked'
                    }"
                    class="px-2 py-1 text-xs font-medium rounded-full whitespace-nowrap"
                  >
                    {{ getStatusText(task.status) }}
                  </span>
                  <span 
                    :class="{
                      'bg-red-100 text-red-800': task.priority === 'high',
                      'bg-yellow-100 text-yellow-800': task.priority === 'medium', 
                      'bg-green-100 text-green-800': task.priority === 'low'
                    }"
                    class="px-2 py-1 text-xs font-medium rounded-full"
                  >
                    {{ getPriorityText(task.priority) }}
                  </span>
                </div>
              </div>
              
              <p v-if="task.description" class="text-sm text-gray-600 mb-2">
                {{ task.description }}
              </p>
              
              <div v-if="task.assigned_by_user_id || task.responsible_user_id" class="mb-2 text-xs text-gray-600">
                <div v-if="task.assigned_by_user_id" class="flex items-center">
                  <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11.5V14m0-2.5v-6a1.5 1.5 0 113 0m-3 6a1.5 1.5 0 00-3 0v2a7.5 7.5 0 0015 0v-5a1.5 1.5 0 00-3 0m-6-3V11m0-5.5v-1a1.5 1.5 0 013 0v1m0 0V11m0-5.5a1.5 1.5 0 013 0v3m0 0V11"></path>
                  </svg>
                  Выдал: {{ getUserName(task.assigned_by_user_id) }}
                </div>
                <div v-if="task.responsible_user_id" class="flex items-center">
                  <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                  </svg>
                  Ответственный: {{ getUserName(task.responsible_user_id) }}
                </div>
              </div>

              <div v-if="task.attachments && task.attachments.length > 0" class="mb-2">
                <div class="text-xs text-gray-500 mb-1 flex items-center">
                  <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                  </svg>
                  Файлы ({{ task.attachments.length }}):
                </div>
                <div class="space-y-1">
                  <div v-for="attachment in task.attachments" :key="attachment.id" class="text-xs bg-gray-100 rounded p-1 flex justify-between items-center">
                    <span>{{ attachment.file_name }}</span>
                    <button 
                      @click="downloadFile(attachment)" 
                      class="text-blue-600 hover:text-blue-800"
                    >
                      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                      </svg>
                    </button>
                  </div>
                </div>
              </div>
              
              <div v-if="task.comments && task.comments.length > 0" class="mb-2">
                <div class="text-xs text-gray-500 mb-1 flex items-center">
                  <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                  </svg>
                  Комментарии ({{ task.comments.length }}):
                </div>
                <div class="max-h-20 overflow-y-auto space-y-1">
                  <div v-for="comment in task.comments" :key="comment.id" class="text-xs bg-gray-100 rounded p-1">
                    <div class="flex justify-between items-start">
                      <div class="flex-1">
                        <div class="font-medium">{{ comment.user_name }} {{ comment.user_surname }}</div>
                        <div class="text-gray-600">{{ comment.content }}</div>
                        <div class="text-gray-400 text-xs">{{ formatDate(comment.created_at) }}</div>
                      </div>
                      <button
                        v-if="canDeleteComment(comment)"
                        @click.stop="deleteComment(comment)"
                        class="text-red-600 hover:text-red-800 text-xs ml-1 flex-shrink-0"
                        title="Удалить комментарий"
                      >
                        ✕
                      </button>
                    </div>
                  </div>
                </div>
              </div>
              
              <div v-if="task.deadline" class="text-xs text-gray-500 mb-2 flex items-center">
                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                Дедлайн: {{ formatDate(task.deadline) }}
              </div>
              
              <div class="flex justify-between items-center text-xs text-gray-500">
                <span class="flex items-center">
                  <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                  </svg>
                  {{ getCreatorName(task.created_by_user_id) }}
                </span>
                <span>{{ formatDate(task.created_at) }}</span>
              </div>
              
              <div class="flex justify-end space-x-2 mt-2">
                <button
                  @click="viewTask(task)"
                  class="text-green-600 hover:text-green-800 text-xs font-bold"
                >
                  Подробнее
                </button>
                <button
                  @click="deleteTask(task.id)"
                  class="text-red-600 hover:text-red-800 text-xs font-bold"
                >
                  Удалить
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Табличный вид -->
      <div v-else-if="currentView === 'table'" class="bg-white rounded-lg overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200">
          <div class="flex justify-between items-center">
            <h3 class="text-lg font-medium text-gray-900">
              Всего задач: {{ filteredAllTasks.length }}
              <span v-if="searchQuery || priorityFilter || responsibleFilter" class="text-sm text-gray-500">
                (из {{ allTasks.length }})
              </span>
            </h3>
            <div class="flex space-x-2">
              <button
                v-if="selectedTasks.length > 0"
                @click="deleteSelectedTasks"
                class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded text-sm font-bold"
              >
                Удалить выбранные ({{ selectedTasks.length }})
              </button>
            </div>
          </div>
        </div>

        <div class="overflow-x-auto overflow-y-visible">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-bold text-black uppercase tracking-wider">
                  <input
                    type="checkbox"
                    @change="toggleSelectAll"
                    :checked="filteredAllTasks.length > 0 && selectedTasks.length === filteredAllTasks.length"
                    class="rounded"
                  />
                </th>
                <th class="px-6 py-3 text-left text-xs font-bold text-black uppercase tracking-wider">
                  ID
                </th>
                <th class="px-6 py-3 text-left text-xs font-bold text-black uppercase tracking-wider">
                  Действия
                </th>
                <th class="px-6 py-3 text-left text-xs font-bold text-black uppercase tracking-wider">
                  Название
                </th>
                <th class="px-6 py-3 text-left text-xs font-bold text-black uppercase tracking-wider">
                  Описание
                </th>
                <th class="px-6 py-3 text-left text-xs font-bold text-black uppercase tracking-wider">
                  Статус
                </th>
                <th class="px-6 py-3 text-left text-xs font-bold text-black uppercase tracking-wider">
                  Приоритет
                </th>
                <th class="px-6 py-3 text-left text-xs font-bold text-black uppercase tracking-wider">
                  Создатель
                </th>
                <th class="px-6 py-3 text-left text-xs font-bold text-black uppercase tracking-wider">
                  Выдал
                </th>
                <th class="px-6 py-3 text-left text-xs font-bold text-black uppercase tracking-wider">
                  Ответственный
                </th>
                <th class="px-6 py-3 text-left text-xs font-bold text-black uppercase tracking-wider">
                  Дедлайн
                </th>
                <th class="px-6 py-3 text-left text-xs font-bold text-black uppercase tracking-wider">
                  Файлы
                </th>
                <th class="px-6 py-3 text-left text-xs font-bold text-black uppercase tracking-wider">
                  Комментарии
                </th>
                <th class="px-6 py-3 text-left text-xs font-bold text-black uppercase tracking-wider">
                  Создано
                </th>
                <th v-for="field in activeCustomFields" :key="field.id" class="px-6 py-3 text-left text-xs font-bold text-black uppercase tracking-wider">
                  {{ field.label }}
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr
                v-for="task in filteredAllTasks"
                :key="task.id"
                :data-task-id="task.id"
                :class="{ 'bg-blue-50': selectedTasks.includes(task.id) }"
                class="hover:bg-gray-50"
              >
                <td class="px-6 py-4 whitespace-nowrap">
                  <input
                    type="checkbox"
                    :value="task.id"
                    v-model="selectedTasks"
                    class="rounded"
                  />
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                  {{ task.id }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium relative">
                  <div class="relative">
                    <button
                      @click="toggleTaskMenu(task.id)"
                      class="p-2 hover:bg-gray-100 rounded-md"
                    >
                      <svg class="w-4 h-4 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path>
                      </svg>
                    </button>
                    <div
                      v-if="openTaskMenuId === task.id"
                      class="absolute right-0 w-32 bg-white rounded-md shadow-lg border border-gray-200 z-50"
                      :class="shouldShowMenuAbove(task.id) ? 'bottom-full mb-2' : 'top-full mt-2'"
                      style="min-width: 128px;"
                    >
                      <div class="py-1">
                        <button
                          @click="viewTask(task); closeTaskMenu()"
                          class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                        >
                          Открыть
                        </button>
                        <button
                          @click="deleteTask(task.id); closeTaskMenu()"
                          class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100"
                        >
                          Удалить
                        </button>
                      </div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 text-sm text-black max-w-xs">
                  <div class="truncate" :title="task.title">{{ task.title }}</div>
                </td>
                <td class="px-6 py-4 text-sm text-black max-w-xs">
                  <div class="truncate" :title="task.description || 'Нет описания'">
                    {{ task.description || 'Нет описания' }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span 
                    :class="{
                      'bg-blue-100 text-blue-800': task.status === 'waiting',
                      'bg-orange-100 text-orange-800': task.status === 'in_progress', 
                      'bg-green-100 text-green-800': task.status === 'completed',
                      'bg-red-100 text-red-800': task.status === 'blocked'
                    }"
                    class="px-2 py-1 text-xs font-medium rounded-full whitespace-nowrap"
                  >
                    {{ getStatusText(task.status) }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span 
                    :class="{
                      'bg-red-100 text-red-800': task.priority === 'high',
                      'bg-yellow-100 text-yellow-800': task.priority === 'medium', 
                      'bg-green-100 text-green-800': task.priority === 'low'
                    }"
                    class="px-2 py-1 text-xs font-medium rounded-full"
                  >
                    {{ getPriorityText(task.priority) }}
                  </span>
                </td>
                <td class="px-6 py-4 text-sm text-black max-w-xs">
                  <div class="truncate">{{ getCreatorName(task.created_by_user_id) }}</div>
                </td>
                <td class="px-6 py-4 text-sm text-black max-w-xs">
                  <div class="truncate">{{ getUserName(task.assigned_by_user_id) }}</div>
                </td>
                <td class="px-6 py-4 text-sm text-black max-w-xs">
                  <div class="truncate">{{ getUserName(task.responsible_user_id) }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-black">
                  {{ task.deadline ? formatDate(task.deadline) : '-' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-black">
                  <div class="flex items-center space-x-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                    </svg>
                    <span>{{ task.attachments?.length || 0 }}</span>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-black">
                  <div class="flex items-center space-x-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                    <span>{{ task.comments?.length || 0 }}</span>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-black">
                  {{ formatDate(task.created_at) }}
                </td>
                <td v-for="field in activeCustomFields" :key="field.id" class="px-6 py-4 whitespace-nowrap text-sm text-black">
                  {{ field.type === 'date' && task.custom_fields?.[field.id] 
                      ? formatDate(task.custom_fields[field.id]) 
                      : (task.custom_fields?.[field.id] || 'Не заполнено') }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="filteredAllTasks.length === 0" class="text-center py-12">
          <p class="text-gray-500">
            {{ allTasks.length === 0 ? 'Задач пока нет' : 'Нет задач, соответствующих фильтрам' }}
          </p>
        </div>
      </div>

      <!-- Create Task Modal -->
      <div v-if="showCreateModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-10 mx-auto p-6 border w-4/5 max-w-4xl rounded-md bg-white">
          <h3 class="text-lg font-bold text-gray-900 mb-6">Создать задачу</h3>
          <form @submit.prevent="createTask">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Левая колонка: Название, Описание, Статус, Приоритет -->
              <div class="space-y-4">
                <div>
                  <label class="block text-sm font-bold text-gray-700 mb-2">Название</label>
                  <input
                    v-model="newTask.title"
                    type="text"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                  />
                </div>
                
                <div>
                  <label class="block text-sm font-bold text-gray-700 mb-2">Описание</label>
                  <textarea
                    v-model="newTask.description"
                    rows="4"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                  ></textarea>
                </div>
                
                <div>
                  <label class="block text-sm font-bold text-gray-700 mb-2">Статус</label>
                  <select
                    v-model="newTask.status"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                  >
                    <option value="waiting">Ожидают</option>
                    <option value="in_progress">В работе</option>
                    <option value="completed">Выполнены</option>
                    <option value="blocked">Заблокированы</option>
                  </select>
                </div>
                
                <div>
                  <label class="block text-sm font-bold text-gray-700 mb-2">Приоритет</label>
                  <select
                    v-model="newTask.priority"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                  >
                    <option value="low">Низкий</option>
                    <option value="medium">Средний</option>
                    <option value="high">Высокий</option>
                  </select>
                </div>
              </div>
              
              <!-- Правая колонка: Выдал, Ответственный, Дедлайн, Дополнительные поля -->
              <div class="space-y-4">
                <div>
                  <label class="block text-sm font-bold text-gray-700 mb-2">Выдал</label>
                  <select
                    v-model="newTask.assigned_by_user_id"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                  >
                    <option value="">Не выбрано</option>
                    <option v-for="user in users" :key="user.id" :value="user.id">
                      {{ user.name }} {{ user.family_name || user.surname || '' }}
                    </option>
                  </select>
                </div>
                
                <div>
                  <label class="block text-sm font-bold text-gray-700 mb-2">Ответственный</label>
                  <select
                    v-model="newTask.responsible_user_id"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                  >
                    <option value="">Не выбрано</option>
                    <option v-for="user in users" :key="user.id" :value="user.id">
                      {{ user.name }} {{ user.family_name || user.surname || '' }}
                    </option>
                  </select>
                </div>
                
                <div>
                  <label class="block text-sm font-bold text-gray-700 mb-2">Дедлайн</label>
                  <input
                    v-model="newTask.deadline"
                    type="date"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                  />
                </div>
                
                <!-- Дополнительные поля -->
                <div v-if="activeCustomFields.length > 0">
                  <h4 class="text-base font-bold text-gray-700 mb-3">Дополнительные поля</h4>
                  <div v-for="field in activeCustomFields" :key="field.id" class="mb-4">
                    <label class="block text-sm font-bold text-gray-700 mb-2">
                      {{ field.label }}
                      <span v-if="field.is_required" class="text-red-500 ml-1">*</span>
                    </label>
                    <input
                      v-model="newTask.custom_fields[field.id]"
                      :type="field.type === 'date' ? 'date' : 'text'"
                      :required="field.is_required"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                    />
                  </div>
                </div>
              </div>
            </div>
            
            <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
              <button
                type="button"
                @click="showCreateModal = false"
                class="px-4 py-2 text-sm font-bold text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300"
              >
                Отмена
              </button>
              <button
                type="submit"
                class="px-4 py-2 text-sm font-bold text-white bg-indigo-600 rounded-md hover:bg-indigo-700"
              >
                Создать
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Unified Task Modal (View/Edit) -->
      <div v-if="showTaskModal && viewingTask" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-10 mx-auto p-6 border w-4/5 max-w-6xl rounded-md bg-white">
          <div class="flex justify-between items-start mb-6">
            <h3 class="text-lg font-bold text-gray-900">
              {{ isEditMode ? 'Редактировать задачу' : 'Просмотр задачи' }}
            </h3>
            <div class="flex space-x-2">
              <button
                v-if="!isEditMode"
                @click="enableEditMode"
                class="px-4 py-2 text-sm font-bold bg-blue-600 text-white rounded-md hover:bg-blue-700"
              >
                Редактировать
              </button>
              <button
                v-if="isEditMode"
                @click="cancelEdit"
                class="px-4 py-2 text-sm font-bold bg-gray-600 text-white rounded-md hover:bg-gray-700"
              >
                Отмена
              </button>
              <button
                @click="closeTaskModal"
                class="text-gray-400 hover:text-gray-600 font-bold"
              >
                ✕
              </button>
            </div>
          </div>
          
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Левая колонка: Основная информация -->
            <div>
              <form v-if="isEditMode" @submit.prevent="updateTask" class="space-y-4">
                <div>
                  <label class="block text-sm font-bold text-gray-700 mb-2">Название</label>
                  <input
                    v-model="editingTask.title"
                    type="text"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                  />
                </div>
                
                <div>
                  <label class="block text-sm font-bold text-gray-700 mb-2">Описание</label>
                  <textarea
                    v-model="editingTask.description"
                    rows="4"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                  ></textarea>
                </div>
                
                <div>
                  <label class="block text-sm font-bold text-gray-700 mb-2">Приоритет</label>
                  <select
                    v-model="editingTask.priority"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                  >
                    <option value="low">Низкий</option>
                    <option value="medium">Средний</option>
                    <option value="high">Высокий</option>
                  </select>
                </div>

                <div>
                  <label class="block text-sm font-bold text-gray-700 mb-2">Выдал</label>
                  <select
                    v-model="editingTask.assigned_by_user_id"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                  >
                    <option value="">Не выбрано</option>
                    <option v-for="user in users" :key="user.id" :value="user.id">
                      {{ user.name }} {{ user.family_name || user.surname || '' }}
                    </option>
                  </select>
                </div>

                <div>
                  <label class="block text-sm font-bold text-gray-700 mb-2">Ответственный</label>
                  <select
                    v-model="editingTask.responsible_user_id"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                  >
                    <option value="">Не выбрано</option>
                    <option v-for="user in users" :key="user.id" :value="user.id">
                      {{ user.name }} {{ user.family_name || user.surname || '' }}
                    </option>
                  </select>
                </div>
                
                <div>
                  <label class="block text-sm font-bold text-gray-700 mb-2">Дедлайн</label>
                  <input
                    v-model="editingTask.deadline"
                    type="date"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                  />
                </div>
                
                <!-- Дополнительные поля -->
                <div v-if="activeCustomFields.length > 0">
                  <h4 class="text-base font-bold text-gray-700 mb-3">Дополнительные поля</h4>
                  <div v-for="field in activeCustomFields" :key="field.id" class="mb-4">
                    <label class="block text-sm font-bold text-gray-700 mb-2">
                      {{ field.label }}
                      <span v-if="field.is_required" class="text-red-500 ml-1">*</span>
                    </label>
                    <input
                      v-model="editingTask.custom_fields[field.id]"
                      :type="field.type === 'date' ? 'date' : 'text'"
                      :required="field.is_required"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                    />
                  </div>
                </div>
                
                <div class="flex justify-start space-x-3 pt-4 border-t">
                  <button
                    type="submit"
                    class="px-4 py-2 text-sm font-bold text-white bg-indigo-600 rounded-md hover:bg-indigo-700"
                  >
                    Сохранить
                  </button>
                </div>
              </form>
              
              <div v-else class="space-y-4">
                <div>
                  <label class="block text-sm font-bold text-gray-700 mb-2">Название</label>
                  <p class="text-lg font-medium text-gray-900">{{ viewingTask?.title }}</p>
                </div>
                
                <div>
                  <label class="block text-sm font-bold text-gray-700 mb-2">Описание</label>
                  <p class="text-sm text-gray-600">{{ viewingTask?.description || 'Нет описания' }}</p>
                </div>
                
                <div>
                  <label class="block text-sm font-bold text-gray-700 mb-2">Статус</label>
                  <span 
                    :class="{
                      'bg-blue-100 text-blue-800': viewingTask?.status === 'waiting',
                      'bg-orange-100 text-orange-800': viewingTask?.status === 'in_progress', 
                      'bg-green-100 text-green-800': viewingTask?.status === 'completed',
                      'bg-red-100 text-red-800': viewingTask?.status === 'blocked'
                    }"
                    class="px-2 py-1 text-xs font-medium rounded-full whitespace-nowrap"
                  >
                    {{ getStatusText(viewingTask?.status) }}
                  </span>
                </div>
                
                <div>
                  <label class="block text-sm font-bold text-gray-700 mb-2">Приоритет</label>
                  <span 
                    :class="{
                      'bg-red-100 text-red-800': viewingTask?.priority === 'high',
                      'bg-yellow-100 text-yellow-800': viewingTask?.priority === 'medium', 
                      'bg-green-100 text-green-800': viewingTask?.priority === 'low'
                    }"
                    class="px-2 py-1 text-xs font-medium rounded-full"
                  >
                    {{ getPriorityText(viewingTask?.priority) }}
                  </span>
                </div>

                <div v-if="viewingTask?.assigned_by_user_id">
                  <label class="block text-sm font-bold text-gray-700 mb-2">Выдал</label>
                  <p class="text-sm text-gray-600">{{ getUserName(viewingTask.assigned_by_user_id) }}</p>
                </div>

                <div v-if="viewingTask?.responsible_user_id">
                  <label class="block text-sm font-bold text-gray-700 mb-2">Ответственный</label>
                  <p class="text-sm text-gray-600">{{ getUserName(viewingTask.responsible_user_id) }}</p>
                </div>
                
                <div v-if="viewingTask?.deadline">
                  <label class="block text-sm font-bold text-gray-700 mb-2">Дедлайн</label>
                  <p class="text-sm text-gray-600">{{ formatDate(viewingTask.deadline) }}</p>
                </div>
                
                <div>
                  <label class="block text-sm font-bold text-gray-700 mb-2">Создатель</label>
                  <p class="text-sm text-gray-600">{{ getCreatorName(viewingTask?.created_by_user_id) }}</p>
                </div>
                
                <div>
                  <label class="block text-sm font-bold text-gray-700 mb-2">Создано</label>
                  <p class="text-sm text-gray-600">{{ formatDate(viewingTask?.created_at) }}</p>
                </div>
                
                <!-- Дополнительные поля -->
                <div v-if="activeCustomFields.length > 0">
                  <h4 class="text-base font-bold text-gray-700 mb-3">Дополнительные поля</h4>
                  <div v-for="field in activeCustomFields" :key="field.id" class="mb-3">
                    <label class="block text-sm font-bold text-gray-700 mb-2">{{ field.label }}</label>
                    <p class="text-sm text-gray-600">
                      {{ field.type === 'date' && viewingTask?.custom_fields?.[field.id] 
                          ? formatDate(viewingTask.custom_fields[field.id]) 
                          : (viewingTask?.custom_fields?.[field.id] || 'Не заполнено') }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Правая колонка: Файлы и Комментарии -->
            <div>
              <!-- Файлы -->
              <div class="mb-6">
                <h4 class="text-base font-bold text-gray-700 mb-3">Файлы</h4>
                
                <!-- Upload File Form -->
                <form @submit.prevent="uploadFile" class="mb-4">
                  <div class="border-2 border-dashed border-indigo-300 rounded-lg p-4 bg-indigo-50">
                    <input
                      ref="fileInput"
                      type="file"
                      @change="handleFileSelect"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 mb-3"
                    />
                    <button
                      type="submit"
                      :disabled="!selectedFile || uploading"
                      class="w-full px-3 py-2 text-sm font-bold bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50"
                    >
                      {{ uploading ? 'Загрузка...' : 'Загрузить файл' }}
                    </button>
                  </div>
                </form>
                
                <!-- Files List -->
                <div class="space-y-2 max-h-48 overflow-y-auto">
                  <div v-if="!taskAttachments || taskAttachments.length === 0" class="text-gray-500 text-sm text-center py-4">
                    Файлов пока нет
                  </div>
                  <div v-for="attachment in taskAttachments" :key="attachment.id" class="bg-gray-50 rounded-lg p-3">
                    <div class="mb-2">
                      <span class="font-medium text-sm block">{{ attachment.file_name }}</span>
                      <div class="text-xs text-gray-500">
                        {{ formatFileSize(attachment.file_size) }} • {{ getUserName(attachment.uploaded_by_user_id) }}
                      </div>
                      <div class="text-xs text-gray-400">{{ formatDate(attachment.created_at) }}</div>
                    </div>
                    <div class="flex space-x-2">
                      <button 
                        @click="downloadFile(attachment)" 
                        class="text-blue-600 hover:text-blue-800 text-sm font-bold"
                      >
                        Скачать
                      </button>
                      <button 
                        @click="deleteAttachment(attachment)" 
                        class="text-red-600 hover:text-red-800 text-sm font-bold"
                      >
                        Удалить
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Комментарии -->
              <div>
                <h4 class="text-base font-bold text-gray-700 mb-3">Комментарии</h4>
                
                <!-- Add Comment Form -->
                <form @submit.prevent="addComment" class="mb-4">
                  <textarea
                    v-model="newComment"
                    placeholder="Добавить комментарий..."
                    rows="3"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 mb-2"
                  ></textarea>
                                  <button
                  type="submit"
                  :disabled="!newComment.trim()"
                  class="w-full px-4 py-2 text-sm font-bold bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50"
                >
                    Добавить комментарий
                  </button>
                </form>
                
                <!-- Comments List -->
                <div class="max-h-64 overflow-y-auto space-y-3">
                  <div v-if="!taskComments || taskComments.length === 0" class="text-gray-500 text-sm text-center py-4">
                    Комментариев пока нет
                  </div>
                  <div v-for="comment in taskComments" :key="comment.id" class="bg-gray-50 rounded-lg p-3">
                    <div class="flex justify-between items-start mb-1">
                      <span class="font-medium text-sm">{{ comment.user_name }} {{ comment.user_surname }}</span>
                      <div class="flex items-center space-x-2">
                        <span class="text-xs text-gray-500">{{ formatDate(comment.created_at) }}</span>
                        <button
                          v-if="canDeleteComment(comment)"
                          @click="deleteComment(comment)"
                          class="text-red-600 hover:text-red-800 text-xs font-bold ml-2"
                          title="Удалить комментарий"
                        >
                          ✕
                        </button>
                      </div>
                    </div>
                    <p class="text-sm text-gray-700">{{ comment.content }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Custom Fields Management Modal -->
    <div v-if="showCustomFieldModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-2/3 max-w-2xl rounded-md bg-white">
        <div class="flex justify-between items-start mb-4">
          <h3 class="text-lg font-bold text-gray-900">
            {{ editingCustomField && editingCustomField.id ? 'Редактировать поле' : 'Управление дополнительными полями' }}
          </h3>
          <button
            @click="closeCustomFieldModal"
            class="text-gray-400 hover:text-gray-600"
          >
            ✕
          </button>
        </div>
        
        <div v-if="editingCustomField === null" class="mb-6">
          <div class="flex justify-between items-center mb-4">
            <h4 class="text-md font-semibold text-gray-900">Поля</h4>
            <div class="flex space-x-2">
              <button
                @click="toggleShowInactive"
                :class="[
                  'px-4 py-2 rounded text-sm font-bold',
                  showInactiveFields 
                    ? 'bg-gray-600 text-white hover:bg-gray-700' 
                    : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                ]"
              >
                {{ showInactiveFields ? 'Скрыть неактивные' : 'Показать все' }}
              </button>
              <button
                @click="startCreatingField()"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded text-sm font-bold"
              >
                Добавить поле
              </button>
            </div>
          </div>
          
          <div v-if="displayedFields.length === 0" class="text-gray-500 text-center py-4">
            {{ showInactiveFields ? 'Полей пока нет' : 'Активных полей пока нет' }}
            <br>
            <small>Debug: customFields.length = {{ customFields.length }}, showInactiveFields = {{ showInactiveFields }}</small>
          </div>
          
          <div v-else class="space-y-2">
            <div v-for="field in displayedFields" :key="field.id" class="bg-gray-50 rounded p-3 flex justify-between items-center">
              <div>
                <span class="font-medium" :class="{ 'text-gray-500': !field.is_active }">
                  {{ field.label }}
                </span>
                <span v-if="field.is_required" class="text-red-500 ml-1">*</span>
                <span v-if="!field.is_active" class="text-xs bg-gray-200 text-gray-600 px-2 py-1 rounded ml-2">
                  Неактивно
                </span>
                <div class="text-xs text-gray-500">{{ field.name }} ({{ field.type }})</div>
              </div>
              <div class="flex space-x-2">
                <button 
                  v-if="field.is_active"
                  @click="openCustomFieldModal(field)" 
                  class="text-blue-600 hover:text-blue-800 text-sm font-bold"
                >
                  Редактировать
                </button>
                <button 
                  v-if="field.is_active"
                  @click="deleteCustomField(field)" 
                  class="text-red-600 hover:text-red-800 text-sm font-bold"
                >
                  Удалить
                </button>
                <button 
                  v-if="!field.is_active"
                  @click="reactivateCustomField(field)" 
                  class="text-green-600 hover:text-green-800 text-sm font-bold"
                >
                  Активировать
                </button>
              </div>
            </div>
          </div>
        </div>
        
        <div v-if="editingCustomField !== null">
          <form @submit.prevent="saveCustomField">
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">Название поля (name)</label>
              <input
                v-model="customFieldForm.name"
                type="text"
                required
                placeholder="field_name"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
              />
            </div>
            
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">Подпись поля (label)</label>
              <input
                v-model="customFieldForm.label"
                type="text"
                required
                placeholder="Название поля"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
              />
            </div>
            
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">Тип поля</label>
              <select
                v-model="customFieldForm.type"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
              >
                <option value="text">Текст</option>
                <option value="date">Дата</option>
              </select>
            </div>
            
            <div class="mb-4">
              <label class="flex items-center">
                <input
                  v-model="customFieldForm.is_required"
                  type="checkbox"
                  class="rounded"
                />
                <span class="ml-2 text-sm text-gray-700">Обязательное поле</span>
              </label>
            </div>
            
            <div class="flex justify-end space-x-3">
              <button
                type="button"
                @click="closeCustomFieldModal"
                class="px-4 py-2 text-sm font-bold text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300"
              >
                Отмена
              </button>
              <button
                type="submit"
                class="px-4 py-2 text-sm font-bold text-white bg-indigo-600 rounded-md hover:bg-indigo-700"
              >
                {{ editingCustomField && editingCustomField.id ? 'Сохранить' : 'Создать' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, reactive, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import axios from 'axios';
import Sortable from 'sortablejs';

export default {
  name: 'Board',
  setup() {
    const route = useRoute();
    const router = useRouter();
    const authStore = useAuthStore();
    
    const loading = ref(false);
    const board = ref(null);
    const currentObjectName = ref('');
    const currentView = ref('kanban'); // 'kanban' или 'table'
    const selectedTasks = ref([]);
    const searchQuery = ref('');
    const priorityFilter = ref('');
    const responsibleFilter = ref('');
    const tasks = ref({
      waiting: [],
      in_progress: [],
      completed: [],
      blocked: [],
    });
    
    const showCreateModal = ref(false);
    const showTaskModal = ref(false);
    const isEditMode = ref(false);
    const newTask = reactive({
      title: '',
      description: '',
      status: 'waiting',
      priority: 'medium',
      deadline: '',
      assigned_by_user_id: '',
      responsible_user_id: '',
      custom_fields: {},
    });
    const editingTask = reactive({
      id: null,
      title: '',
      description: '',
      priority: 'medium',
      deadline: '',
      assigned_by_user_id: '',
      responsible_user_id: '',
      custom_fields: {},
    });
    const viewingTask = ref(null);
    const newComment = ref('');
    
    // File upload state
    const selectedFile = ref(null);
    const uploading = ref(false);
    const fileInput = ref(null);
    
    // Custom fields state
    const customFields = ref([]);
    const showCustomFieldModal = ref(false);
    const customFieldForm = reactive({
      name: '',
      label: '',
      type: 'text',
      is_required: false,
    });
    const editingCustomField = ref(null);
    const showInactiveFields = ref(false);
    
    // Task menu state
    const openTaskMenuId = ref(null);

    const taskComments = computed(() => {
      return viewingTask.value?.comments || [];
    });

    const taskAttachments = computed(() => {
      return viewingTask.value?.attachments || [];
    });

    const users = computed(() => {
      return authStore.users || [];
    });

    const activeCustomFields = computed(() => {
      return customFields.value.filter(field => field.is_active);
    });

    const allTasks = computed(() => {
      return [
        ...tasks.value.waiting,
        ...tasks.value.in_progress,
        ...tasks.value.completed,
        ...tasks.value.blocked,
      ];
    });

    // Общая функция фильтрации задач
    const filterTasks = (taskList) => {
      let filtered = taskList;

      // Поиск по названию и описанию
      if (searchQuery.value.trim()) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(task => 
          task.title.toLowerCase().includes(query) ||
          (task.description && task.description.toLowerCase().includes(query))
        );
      }

      // Фильтр по приоритету
      if (priorityFilter.value) {
        filtered = filtered.filter(task => task.priority === priorityFilter.value);
      }

      // Фильтр по ответственному
      if (responsibleFilter.value) {
        filtered = filtered.filter(task => task.responsible_user_id === responsibleFilter.value);
      }

      return filtered;
    };

    // Фильтрованные задачи для табличного вида
    const filteredAllTasks = computed(() => {
      return filterTasks(allTasks.value);
    });

    // Фильтрованные задачи для канбан вида (по колонкам)
    const filteredTasks = computed(() => {
      const result = {
        waiting: [],
        in_progress: [],
        completed: [],
        blocked: [],
      };

      Object.keys(result).forEach(status => {
        result[status] = filterTasks(tasks.value[status] || []);
      });

      return result;
    });

    const columns = {
      waiting: { title: 'Ожидают' },
      in_progress: { title: 'В работе' },
      completed: { title: 'Выполнены' },
      blocked: { title: 'Заблокированы' },
    };

    const columnRefs = ref({});

    const setColumnRef = (status, el) => {
      if (el) {
        columnRefs.value[status] = el;
      }
    };

    const fetchObjectName = async () => {
      try {
        // Получаем список объектов и находим нужный по ID
        await authStore.fetchObjects();
        const currentObject = authStore.objects.find(obj => obj.id.toString() === route.params.objectId);
        if (currentObject) {
          currentObjectName.value = currentObject.title || `Объект ${currentObject.id}`;
        } else {
          currentObjectName.value = `Объект ${route.params.objectId}`;
        }
      } catch (error) {
        console.error('Error fetching object name:', error);
        currentObjectName.value = `Объект ${route.params.objectId}`;
      }
    };

    const fetchBoard = async () => {
      loading.value = true;
      try {
        // Загружаем пользователя если его нет
        if (!authStore.user) {
          await authStore.fetchUser();
        }
        
        // Загружаем пользователей если их нет
        if (!authStore.users || authStore.users.length === 0) {
          await authStore.fetchUsers();
        }
        
        // Загружаем название объекта
        await fetchObjectName();
        
        const response = await axios.get(`/api/boards/${route.params.objectId}`);
        board.value = response.data.board;
        tasks.value = response.data.tasks;
        
        // Initialize sortable after data is loaded
        setTimeout(initializeSortable, 100);
      } catch (error) {
        console.error('Error fetching board:', error);
      } finally {
        loading.value = false;
      }
    };

    const initializeSortable = () => {
      Object.keys(columns).forEach(status => {
        const element = columnRefs.value[status];
        if (element) {
          new Sortable(element, {
            group: 'tasks',
            animation: 150,
            onEnd: async (evt) => {
              const taskId = evt.item.dataset.taskId;
              const newStatus = evt.to.dataset.status;
              const newIndex = evt.newIndex;
              
              await updateTaskStatus(taskId, newStatus, newIndex);
            },
          });
        }
      });
    };

    const updateTaskStatus = async (taskId, newStatus, newIndex) => {
      try {
        await axios.put(`/api/tasks/${taskId}/status`, {
          status: newStatus,
          position: newIndex,
        });
        
        // Refresh board data
        await fetchBoard();
      } catch (error) {
        console.error('Error updating task status:', error);
        // Refresh to revert changes
        await fetchBoard();
      }
    };

    const createTask = async () => {
      try {
        await axios.post('/api/tasks', {
          board_id: board.value.id,
          title: newTask.title,
          description: newTask.description,
          status: newTask.status,
          priority: newTask.priority,
          deadline: newTask.deadline,
          created_by_user_id: authStore.user.id,
          assigned_by_user_id: newTask.assigned_by_user_id || null,
          responsible_user_id: newTask.responsible_user_id || null,
          custom_fields: newTask.custom_fields,
        });
        
        resetNewTask();
        showCreateModal.value = false;
        await fetchBoard();
      } catch (error) {
        console.error('Error creating task:', error);
      }
    };

    const enableEditMode = () => {
      if (viewingTask.value) {
        editingTask.id = viewingTask.value.id;
        editingTask.title = viewingTask.value.title;
        editingTask.description = viewingTask.value.description;
        editingTask.priority = viewingTask.value.priority;
        editingTask.deadline = viewingTask.value.deadline;
        editingTask.assigned_by_user_id = viewingTask.value.assigned_by_user_id || '';
        editingTask.responsible_user_id = viewingTask.value.responsible_user_id || '';
        editingTask.custom_fields = { ...viewingTask.value.custom_fields } || {};
      }
      isEditMode.value = true;
    };

    const cancelEdit = () => {
      isEditMode.value = false;
      resetEditingTask();
    };

    const updateTask = async () => {
      try {
        await axios.put(`/api/tasks/${editingTask.id}`, {
          title: editingTask.title,
          description: editingTask.description,
          priority: editingTask.priority,
          deadline: editingTask.deadline,
          assigned_by_user_id: editingTask.assigned_by_user_id || null,
          responsible_user_id: editingTask.responsible_user_id || null,
          custom_fields: editingTask.custom_fields,
        });
        
        isEditMode.value = false;
        await fetchBoard();
        
        updateViewingTask();
      } catch (error) {
        console.error('Error updating task:', error);
      }
    };

    const deleteTask = async (taskId) => {
      if (confirm('Вы уверены, что хотите удалить эту задачу?')) {
        try {
          await axios.delete(`/api/tasks/${taskId}`);
          await fetchBoard();
          
          // Close modal if we're viewing the deleted task
          if (viewingTask.value && viewingTask.value.id === taskId) {
            closeTaskModal();
          }
        } catch (error) {
          console.error('Error deleting task:', error);
        }
      }
    };

    const handleFileSelect = (event) => {
      selectedFile.value = event.target.files[0];
    };

    const uploadFile = async () => {
      if (!selectedFile.value || !viewingTask.value) {
        return;
      }

      uploading.value = true;
      const formData = new FormData();
      formData.append('file', selectedFile.value);
      formData.append('uploaded_by_user_id', authStore.user.id);

      try {
        await axios.post(`/api/tasks/${viewingTask.value.id}/attachments`, formData, {
          headers: {
            'Content-Type': 'multipart/form-data',
          },
        });
        
        selectedFile.value = null;
        if (fileInput.value) {
          fileInput.value.value = '';
        }
        
        // Refresh board data
        await fetchBoard();
        
        updateViewingTask();
      } catch (error) {
        console.error('Error uploading file:', error);
        alert('Ошибка загрузки файла');
      } finally {
        uploading.value = false;
      }
    };

    const downloadFile = async (attachment) => {
      try {
        const response = await axios.get(`/api/attachments/${attachment.id}/download`);
        const { url, file_name } = response.data;
        
        // Create a temporary link to download the file directly
        const link = document.createElement('a');
        link.href = url;
        link.download = file_name;
        link.target = '_blank'; // Открыть в новой вкладке
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
      } catch (error) {
        console.error('Error downloading file:', error);
        alert('Ошибка скачивания файла');
      }
    };

    const deleteAttachment = async (attachment) => {
      if (confirm('Вы уверены, что хотите удалить этот файл?')) {
        try {
          await axios.delete(`/api/attachments/${attachment.id}`);
          
          // Refresh board data
          await fetchBoard();
          
          updateViewingTask();
        } catch (error) {
          console.error('Error deleting attachment:', error);
          alert('Ошибка удаления файла');
        }
      }
    };

    const formatFileSize = (bytes) => {
      if (bytes === 0) return '0 Bytes';
      const k = 1024;
      const sizes = ['Bytes', 'KB', 'MB', 'GB'];
      const i = Math.floor(Math.log(bytes) / Math.log(k));
      return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    };

    const formatDate = (dateString) => {
      if (!dateString) return '';
      const date = new Date(dateString);
      return date.toLocaleDateString('ru-RU');
    };

    const goBack = () => {
      router.push('/');
    };

    const getPriorityText = (priority) => {
      switch (priority) {
        case 'low':
          return 'Низкий';
        case 'medium':
          return 'Средний';
        case 'high':
          return 'Высокий';
        default:
          return 'Неизвестный';
      }
    };

    const getUserName = (userId) => {
      if (!userId) return 'Не указан';
      const user = users.value.find(u => u.id === userId);
      if (user) {
        return `${user.name} ${user.family_name || user.surname || ''}`.trim();
      }
      return 'Пользователь не найден';
    };

    const getCreatorName = (userId) => {
      // Если это текущий пользователь
      if (authStore.user && authStore.user.id === userId) {
        const fullName = `${authStore.user.name || ''} ${authStore.user.family_name || authStore.user.surname || ''}`.trim();
        return fullName || authStore.user.email || 'Вы';
      }
      
      // Ищем среди загруженных пользователей
      const user = users.value.find(u => u.id === userId);
      if (user) {
        return `${user.name} ${user.family_name || user.surname || ''}`.trim();
      }
      
      return 'Пользователь';
    };

    const getStatusText = (status) => {
      switch (status) {
        case 'waiting':
          return 'Ожидают';
        case 'in_progress':
          return 'В работе';
        case 'completed':
          return 'Выполнены';
        case 'blocked':
          return 'Заблокированы';
        default:
          return 'Неизвестный';
      }
    };

    const viewTask = (task) => {
      viewingTask.value = { ...task };
      isEditMode.value = false;
      showTaskModal.value = true;
    };

    const addComment = async () => {
      if (!viewingTask.value || !newComment.value.trim()) {
        return;
      }
      
      // Проверяем и загружаем пользователя при необходимости
      if (!authStore.user) {
        try {
          await authStore.fetchUser();
        } catch (error) {
          console.error('Failed to fetch user:', error);
          return;
        }
      }
      
      if (!authStore.user) {
        console.error('User is still null after fetch attempt');
        return;
      }
      
      try {
        await axios.post(`/api/tasks/${viewingTask.value.id}/comments`, {
          content: newComment.value,
          user_id: authStore.user.id,
          user_name: authStore.user.name || 'Пользователь',
          user_surname: authStore.user.family_name || authStore.user.surname || '',
        });
        
        newComment.value = '';
        
        // Обновляем данные доски
        await fetchBoard();
        
        updateViewingTask();
      } catch (error) {
        console.error('Error adding comment:', error);
      }
    };

    const closeTaskModal = () => {
      showTaskModal.value = false;
      isEditMode.value = false;
      viewingTask.value = null;
      selectedFile.value = null;
      if (fileInput.value) {
        fileInput.value.value = '';
      }
      resetEditingTask();
    };

    // Функции для табличного вида
    const toggleSelectAll = () => {
      if (selectedTasks.value.length === filteredAllTasks.value.length) {
        selectedTasks.value = [];
      } else {
        selectedTasks.value = filteredAllTasks.value.map(task => task.id);
      }
    };

    const deleteSelectedTasks = async () => {
      if (confirm(`Вы уверены, что хотите удалить ${selectedTasks.value.length} задач?`)) {
        try {
          await Promise.all(selectedTasks.value.map(taskId => 
            axios.delete(`/api/tasks/${taskId}`)
          ));
          selectedTasks.value = [];
          await fetchBoard();
        } catch (error) {
          console.error('Error deleting tasks:', error);
          alert('Ошибка удаления задач');
        }
      }
    };

    const clearFilters = () => {
      searchQuery.value = '';
      priorityFilter.value = '';
      responsibleFilter.value = '';
    };

    // Функция для обновления viewingTask после изменений
    const updateViewingTask = () => {
      if (viewingTask.value) {
        const updatedTask = allTasks.value.find(t => t.id === viewingTask.value.id);
        if (updatedTask) {
          viewingTask.value = updatedTask;
        }
      }
    };

    // Функция для сброса данных новой задачи
    const resetNewTask = () => {
      Object.assign(newTask, {
        title: '',
        description: '',
        status: 'waiting',
        priority: 'medium',
        deadline: '',
        assigned_by_user_id: '',
        responsible_user_id: '',
        custom_fields: {},
      });
    };

    // Функция для сброса данных редактирования
    const resetEditingTask = () => {
      Object.assign(editingTask, {
        id: null,
        title: '',
        description: '',
        priority: 'medium',
        deadline: '',
        assigned_by_user_id: '',
        responsible_user_id: '',
        custom_fields: {},
      });
    };

    // Custom fields methods
    const fetchCustomFields = async (includeInactive = false) => {
      try {
        const endpoint = includeInactive ? '/api/custom-fields/all' : '/api/custom-fields';
        console.log('fetchCustomFields:', { endpoint, includeInactive });
        const response = await axios.get(endpoint);
        console.log('fetchCustomFields response:', response.data);
        customFields.value = response.data;
      } catch (error) {
        console.error('Error fetching custom fields:', error);
      }
    };

    const openCustomFieldModal = async (field = null) => {
      console.log('openCustomFieldModal called with:', field);
      
      if (field) {
        editingCustomField.value = field;
        Object.assign(customFieldForm, {
          name: field.name,
          label: field.label,
          type: field.type,
          is_required: field.is_required,
        });
      } else {
        editingCustomField.value = null; // null для показа списка полей
        Object.assign(customFieldForm, {
          name: '',
          label: '',
          type: 'text',
          is_required: false,
        });
      }
      showCustomFieldModal.value = true;
      
      // Загружаем все поля при открытии модального окна
      await fetchCustomFields(true);
    };

    const saveCustomField = async () => {
      try {
        if (editingCustomField.value && editingCustomField.value.id) {
          // Редактирование существующего поля
          const response = await axios.put(`/api/custom-fields/${editingCustomField.value.id}`, customFieldForm);
          const fieldIndex = customFields.value.findIndex(f => f.id === editingCustomField.value.id);
          if (fieldIndex !== -1) {
            customFields.value[fieldIndex] = response.data;
          }
        } else {
          // Создание нового поля
          const fieldData = {
            ...customFieldForm,
            sort_order: customFields.value.filter(f => f.is_active).length
          };
          const response = await axios.post('/api/custom-fields', fieldData);
          customFields.value.push(response.data);
        }
        
        editingCustomField.value = null;
      } catch (error) {
        console.error('Error saving custom field:', error);
        alert('Ошибка сохранения поля');
      }
    };

    const deleteCustomField = async (field) => {
      if (confirm(`Вы уверены, что хотите удалить поле "${field.label}"?`)) {
        try {
          await axios.delete(`/api/custom-fields/${field.id}`);
          // Обновляем только конкретное поле в массиве
          const fieldIndex = customFields.value.findIndex(f => f.id === field.id);
          if (fieldIndex !== -1) {
            customFields.value[fieldIndex].is_active = false;
          }
        } catch (error) {
          console.error('Error deleting custom field:', error);
          alert('Ошибка удаления поля');
        }
      }
    };

    const closeCustomFieldModal = () => {
      showCustomFieldModal.value = false;
      editingCustomField.value = null;
      Object.assign(customFieldForm, {
        name: '',
        label: '',
        type: 'text',
        is_required: false,
      });
    };

    const toggleShowInactive = async () => {
      showInactiveFields.value = !showInactiveFields.value;
      // Всегда загружаем все поля (включая неактивные) для корректного отображения
      await fetchCustomFields(true);
    };

    const reactivateCustomField = async (field) => {
      try {
        await axios.patch(`/api/custom-fields/${field.id}/reactivate`);
        // Обновляем только конкретное поле в массиве
        const fieldIndex = customFields.value.findIndex(f => f.id === field.id);
        if (fieldIndex !== -1) {
          customFields.value[fieldIndex].is_active = true;
        }
      } catch (error) {
        console.error('Error reactivating custom field:', error);
        alert('Ошибка активации поля');
      }
    };

    const startCreatingField = () => {
      editingCustomField.value = {};
      Object.assign(customFieldForm, {
        name: '',
        label: '',
        type: 'text',
        is_required: false,
      });
    };

    const displayedFields = computed(() => {
      console.log('displayedFields computed:', {
        customFields: customFields.value,
        showInactiveFields: showInactiveFields.value,
        length: customFields.value.length
      });
      return customFields.value.filter(field => showInactiveFields.value || field.is_active);
    });

    // Task menu methods
    const toggleTaskMenu = (taskId) => {
      openTaskMenuId.value = openTaskMenuId.value === taskId ? null : taskId;
    };

    const closeTaskMenu = () => {
      openTaskMenuId.value = null;
    };

    const shouldShowMenuAbove = (taskId) => {
      // Находим индекс задачи в отфильтрованном списке
      const taskIndex = filteredAllTasks.value.findIndex(task => task.id === taskId);
      const totalTasks = filteredAllTasks.value.length;
      
      // Если всего задач меньше 4, проверяем позицию относительно viewport
      if (totalTasks <= 3) {
        // Находим элемент строки таблицы
        const taskRow = document.querySelector(`tr[data-task-id="${taskId}"]`);
        if (taskRow) {
          const rect = taskRow.getBoundingClientRect();
          const viewportHeight = window.innerHeight;
          // Если расстояние от нижней части строки до низа экрана меньше 150px, показываем меню сверху
          return (viewportHeight - rect.bottom) < 150;
        }
        // Fallback: если элемент не найден и это единственная задача, показываем меню снизу
        return false;
      }
      
      // Если задача в последних 3 строках, показываем меню сверху
      return taskIndex >= totalTasks - 3;
    };

    const canDeleteComment = (comment) => {
      // Пользователь может удалить только свои комментарии
      return authStore.user && authStore.user.id === comment.user_id;
    };

    const deleteComment = async (comment) => {
      if (confirm('Вы уверены, что хотите удалить этот комментарий?')) {
        try {
          await axios.delete(`/api/comments/${comment.id}`, {
            data: {
              user_id: authStore.user.id
            }
          });
          
          // Обновляем данные доски
          await fetchBoard();
          
          // Обновляем просматриваемую задачу
          updateViewingTask();
        } catch (error) {
          console.error('Error deleting comment:', error);
          if (error.response && error.response.status === 403) {
            alert('Вы можете удалять только свои комментарии');
          } else {
            alert('Ошибка удаления комментария');
          }
        }
      }
    };

    onMounted(() => {
      fetchBoard();
      fetchCustomFields(true); // Загружаем все поля включая неактивные
      
      // Close task menu when clicking outside
      document.addEventListener('click', (event) => {
        if (!event.target.closest('.relative')) {
          closeTaskMenu();
        }
      });
    });

    return {
      route,
      loading,
      board,
      currentObjectName,
      currentView,
      searchQuery,
      priorityFilter,
      responsibleFilter,
      allTasks,
      selectedTasks,
      columns,
      showCreateModal,
      showTaskModal,
      isEditMode,
      newTask,
      editingTask,
      viewingTask,
      newComment,
      taskComments,
      taskAttachments,
      users,
      selectedFile,
      uploading,
      fileInput,
      setColumnRef,
      createTask,
      enableEditMode,
      cancelEdit,
      updateTask,
      deleteTask,
      handleFileSelect,
      uploadFile,
      downloadFile,
      deleteAttachment,
      formatFileSize,
      formatDate,
      goBack,
      getPriorityText,
      getUserName,
      getCreatorName,
      getStatusText,
      viewTask,
      addComment,
      closeTaskModal,
      toggleSelectAll,
      deleteSelectedTasks,
      filteredAllTasks,
      filteredTasks,
      clearFilters,
      customFields,
      activeCustomFields,
      showCustomFieldModal,
      customFieldForm,
      editingCustomField,
      fetchCustomFields,
      openCustomFieldModal,
      saveCustomField,
      deleteCustomField,
      closeCustomFieldModal,
      toggleShowInactive,
      reactivateCustomField,
      displayedFields,
      showInactiveFields,
      startCreatingField,
      openTaskMenuId,
      toggleTaskMenu,
      closeTaskMenu,
      shouldShowMenuAbove,
      canDeleteComment,
      deleteComment,
    };
  },
};
</script> 