# Руководство по разработке интеграций с основным приложением

## Обзор проекта

Данное руководство описывает процесс создания модуля канбан-досок как независимого микросервиса, интегрированного с основным приложением PTO. Приложение построено на архитектуре Laravel + Vue.js 3 и использует API основного приложения для аутентификации и получения данных.

## Архитектура решения

### Технологический стек
- **Backend**: Laravel 12, PHP 8.2+
- **Frontend**: Vue.js 3, Vite 6
- **Стилизация**: Tailwind CSS 4
- **Состояние**: Pinia
- **HTTP клиент**: Axios
- **UI компоненты**: SortableJS для drag-and-drop

### Принципы интеграции
1. **Прокси-аутентификация** - приложение выступает прокси для основного API
2. **Независимая база данных** - собственные таблицы для бизнес-логики
3. **SPA архитектура** - одностраничное приложение с роутингом
4. **API-first подход** - четкое разделение frontend/backend

## Этап 1: Настройка инфраструктуры

### 1.1 Создание Laravel проекта
```bash
composer create-project laravel/laravel canban
cd canban
```

### 1.2 Настройка базы данных
- Создание `.env` файла с настройками БД
- Настройка подключения к основному API

### 1.3 Установка frontend зависимостей
```bash
npm install vue@^3.5.14 vue-router@^4.5.1 pinia@^3.0.2
npm install @vitejs/plugin-vue@^5.2.4
npm install tailwindcss@^4.0.0 @tailwindcss/vite@^4.0.0
npm install sortablejs@^1.15.6
npm install axios@^1.9.0
```

### 1.4 Конфигурация Vite
```javascript
// vite.config.js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue(),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
});
```

## Этап 2: Интеграция с основным API

### 2.1 Создание прокси-контроллера аутентификации

**AuthController.php** выполняет роль прокси между фронтендом и основным API:

```php
class AuthController extends Controller
{
    private $apiBaseUrl = 'https://api.staging.pto-app.ru/api/v1';

    public function login(Request $request): JsonResponse
    {
        // Проксирование запроса авторизации
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->post($this->apiBaseUrl . '/auth/login', [
            'email' => $request->email,
            'password' => $request->password,
        ]);

        return response()->json($response->json());
    }
}
```

### 2.2 Принципы прокси-интеграции

1. **Передача токенов**: Все JWT токены передаются through без изменений
2. **Проксирование заголовков**: Authorization заголовки передаются как есть
3. **Обработка ошибок**: Унифицированная обработка ошибок API
4. **Кеширование**: Возможность кешировать часто запрашиваемые данные

### 2.3 Методы интеграции
- `login()` - проксирование аутентификации
- `getUser()` - получение данных пользователя
- `getObjects()` - получение объектов с пагинацией
- `getUsers()` - получение списка пользователей

## Этап 3: Структура базы данных

### 3.1 Основные таблицы

**Таблица boards**:
```php
Schema::create('boards', function (Blueprint $table) {
    $table->id();
    $table->string('object_id'); // ID объекта из внешнего API
    $table->string('name');
    $table->timestamps();
});
```

**Таблица tasks**:
```php
Schema::create('tasks', function (Blueprint $table) {
    $table->id();
    $table->foreignId('board_id')->constrained()->onDelete('cascade');
    $table->string('title');
    $table->text('description')->nullable();
    $table->enum('status', ['waiting', 'in_progress', 'completed', 'blocked']);
    $table->enum('priority', ['low', 'medium', 'high']);
    $table->date('deadline')->nullable();
    $table->string('created_by_user_id');
    $table->string('assigned_by_user_id')->nullable();
    $table->string('responsible_user_id')->nullable();
    $table->integer('position')->default(0);
    $table->timestamps();
});
```

### 3.2 Расширяемость через custom fields

Система дополнительных полей:
- `custom_fields` - конфигурация полей
- `task_custom_field_values` - значения полей для задач

## Этап 4: Frontend архитектура

### 4.1 Структура приложения

```
resources/js/
├── app.js          # Точка входа
├── router.js       # Маршрутизация
├── App.vue         # Корневой компонент
├── bootstrap.js    # Настройка axios
├── stores/
│   └── auth.js     # Pinia store для аутентификации
└── views/
    ├── Login.vue   # Компонент авторизации
    └── Board.vue   # Основной компонент канбан доски
```

### 4.2 Управление состоянием (Pinia)

```javascript
export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        token: localStorage.getItem('token') || null,
        objects: [],
        users: [],
    }),

    actions: {
        async login(email, password) {
            const response = await axios.post('/api/auth/login', {
                email, password,
            });
            
            this.token = response.data.data.access_token;
            localStorage.setItem('token', this.token);
            axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`;
        },
    },
});
```

### 4.3 Роутинг и защита маршрутов

```javascript
router.beforeEach((to, from, next) => {
    const authStore = useAuthStore();
    
    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        next('/login');
    } else {
        next();
    }
});
```

## Этап 5: API Design

### 5.1 RESTful структура

```
POST   /api/auth/login           # Авторизация
GET    /api/auth/user            # Данные пользователя
GET    /api/auth/objects         # Объекты с пагинацией
GET    /api/auth/users           # Пользователи

GET    /api/boards              # Все доски
GET    /api/boards/{objectId}   # Доска по объекту

POST   /api/tasks               # Создание задачи
PUT    /api/tasks/{task}        # Обновление задачи
PUT    /api/tasks/{task}/status # Изменение статуса (drag-n-drop)
DELETE /api/tasks/{task}        # Удаление задачи

POST   /api/tasks/{task}/comments     # Добавление комментария
DELETE /api/comments/{comment}        # Удаление комментария

POST   /api/tasks/{task}/attachments  # Загрузка файла
GET    /api/attachments/{id}/download # Скачивание файла
DELETE /api/attachments/{attachment}  # Удаление файла
```

### 5.2 Валидация данных

Все API endpoints имеют строгую валидацию:
```php
$request->validate([
    'title' => 'required|string|max:255',
    'status' => 'required|in:waiting,in_progress,completed,blocked',
    'priority' => 'required|in:low,medium,high',
    'custom_fields' => 'nullable|array',
]);
```

## Этап 6: Ключевые компоненты

### 6.1 Канбан доска с drag-and-drop

Использование SortableJS для реализации перетаскивания задач между колонками:

```javascript
const initializeSortable = () => {
    Object.keys(columns).forEach(status => {
        const element = columnRefs.value[status];
        
        if (element) {
            const sortableInstance = new Sortable(element, {
                group: 'tasks',
                animation: 150,
                onEnd: function(evt) {
                    const newStatus = evt.to.dataset.status;
                    const taskId = parseInt(evt.item.dataset.taskId);
                    updateTaskStatus(taskId, newStatus, evt.newIndex);
                }
            });
        }
    });
};
```

### 6.2 Модальные окна

Универсальные модальные окна для:
- Создания задач
- Просмотра/редактирования задач
- Управления дополнительными полями
- Загрузки файлов

### 6.3 Фильтрация и поиск

Реактивная система фильтров:
```javascript
const filteredTasks = computed(() => {
    return filterTasks(allTasks.value);
});

const filterTasks = (taskList) => {
    let filtered = taskList;
    
    if (searchQuery.value.trim()) {
        filtered = filtered.filter(task => 
            task.title.toLowerCase().includes(searchQuery.value.toLowerCase())
        );
    }
    
    return filtered;
};
```

## Этап 7: Развертывание и интеграция

### 7.1 Сборка для продакшена

```bash
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 7.2 Встраивание в основное приложение

Приложение может быть встроено через iframe или как отдельный поддомен:

```html
<iframe 
    src="https://canban.yourdomain.com/board/123" 
    width="100%" 
    height="800px"
    frameborder="0">
</iframe>
```

### 7.3 Передача токена из родительского приложения

```javascript
// В родительском приложении
window.postMessage({
    type: 'AUTH_TOKEN',
    token: 'your-jwt-token'
}, '*');

// В канбан приложении
window.addEventListener('message', (event) => {
    if (event.data.type === 'AUTH_TOKEN') {
        authStore.setToken(event.data.token);
    }
});
```

## Заключение

Данная архитектура обеспечивает:

1. **Независимость** - приложение может работать автономно
2. **Масштабируемость** - легко добавлять новые функции
3. **Интеграция** - бесшовное взаимодействие с основным API
4. **Безопасность** - все запросы проходят валидацию
5. **UX** - современный интерфейс с drag-and-drop

Этот подход может быть использован для создания других модулей интеграции с основным приложением PTO. 