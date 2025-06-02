# Шаблон для создания интеграций с основным приложением PTO

## О данном шаблоне

Данный документ представляет собой универсальную инструкцию для создания микросервисов, интегрированных с основным приложением PTO. Используйте этот шаблон для разработки любых модулей - от систем управления документами до аналитических дашбордов.

**📋 Пример реализации**: См. файл [`DEVELOPMENT_GUIDE.md`](./DEVELOPMENT_GUIDE.md) с подробным описанием создания канбан-досок.

---

## Быстрый старт

### 1. Создание проекта

```bash
# Создайте новый Laravel проект
composer create-project laravel/laravel your-app-name
cd your-app-name

# Установите frontend зависимости
npm install vue@^3.5.14 vue-router@^4.5.1 pinia@^3.0.2
npm install @vitejs/plugin-vue@^5.2.4
npm install tailwindcss@^4.0.0 @tailwindcss/vite@^4.0.0
npm install axios@^1.9.0

# Дополнительные пакеты по необходимости
npm install sortablejs@^1.15.6  # для drag-and-drop
npm install chart.js@^4.0.0     # для графиков
npm install moment@^2.29.0      # для работы с датами
```

### 2. Базовая настройка

**Скопируйте файлы из примера:**
- `vite.config.js` - конфигурация сборки
- `resources/views/app.blade.php` - главный шаблон
- `resources/js/app.js` - точка входа
- `resources/js/bootstrap.js` - настройка axios

---

## Структура приложения

### Обязательные компоненты

```
your-app/
├── app/Http/Controllers/
│   ├── AuthController.php      # Прокси для основного API
│   └── YourMainController.php  # Основная бизнес-логика
├── resources/js/
│   ├── app.js                  # Точка входа
│   ├── router.js               # Маршрутизация
│   ├── stores/
│   │   └── auth.js            # Управление аутентификацией
│   └── views/
│       ├── Login.vue          # Страница входа
│       └── MainView.vue       # Основной компонент
├── routes/
│   ├── api.php                # API маршруты
│   └── web.php                # Веб маршруты (SPA)
└── database/migrations/       # Миграции БД
```

---

## Этап 1: Настройка аутентификации

### 1.1 Создайте AuthController

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    private $apiBaseUrl = 'https://api.staging.pto-app.ru/api/v1';

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ])->post($this->apiBaseUrl . '/auth/login', [
                'email' => $request->email,
                'password' => $request->password,
            ]);

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json(['message' => 'Неверные учетные данные'], 401);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Ошибка подключения к серверу'], 500);
        }
    }

    public function getUser(Request $request): JsonResponse
    {
        $token = $request->header('Authorization');
        
        if (!$token) {
            return response()->json(['message' => 'Токен не предоставлен'], 401);
        }

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => $token,
                'X-Requested-With' => 'XMLHttpRequest',
            ])->get($this->apiBaseUrl . '/user');

            if ($response->successful()) {
                $userData = $response->json();
                return response()->json($userData['data'] ?? $userData);
            }

            return response()->json(['message' => 'Неавторизован'], 401);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Ошибка сервера'], 500);
        }
    }

    // Добавьте дополнительные методы по необходимости:
    // getObjects(), getUsers(), getCompanies(), etc.
}
```

### 1.2 Настройте маршруты API

```php
// routes/api.php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\YourMainController;

// Аутентификация
Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/auth/user', [AuthController::class, 'getUser']);
Route::get('/auth/objects', [AuthController::class, 'getObjects']);

// Ваши API endpoints
Route::prefix('your-module')->group(function () {
    Route::get('/', [YourMainController::class, 'index']);
    Route::post('/', [YourMainController::class, 'store']);
    Route::put('/{id}', [YourMainController::class, 'update']);
    Route::delete('/{id}', [YourMainController::class, 'destroy']);
});
```

### 1.3 Создайте Pinia store

```javascript
// resources/js/stores/auth.js
import { defineStore } from 'pinia';
import axios from 'axios';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        token: localStorage.getItem('token') || null,
        objects: [],
        users: [],
    }),

    getters: {
        isAuthenticated: (state) => !!state.token,
    },

    actions: {
        async login(email, password) {
            try {
                const response = await axios.post('/api/auth/login', {
                    email,
                    password,
                });
                
                this.token = response.data.data.access_token;
                localStorage.setItem('token', this.token);
                axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`;
                
                return true;
            } catch (error) {
                throw error;
            }
        },

        async fetchUser() {
            try {
                const response = await axios.get('/api/auth/user');
                this.user = response.data;
            } catch (error) {
                this.logout();
                throw error;
            }
        },

        logout() {
            this.user = null;
            this.token = null;
            this.objects = [];
            this.users = [];
            localStorage.removeItem('token');
            delete axios.defaults.headers.common['Authorization'];
        },

        initializeAuth() {
            if (this.token) {
                axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`;
            }
        },
    },
});
```

---

## Этап 2: Настройка роутинга

### 2.1 Создайте router.js

```javascript
// resources/js/router.js
import { createRouter, createWebHistory } from 'vue-router';
import Login from './views/Login.vue';
import MainView from './views/MainView.vue';
import { useAuthStore } from './stores/auth';

const routes = [
    {
        path: '/login',
        name: 'Login',
        component: Login,
    },
    {
        path: '/',
        redirect: '/main' // Замените на ваш главный маршрут
    },
    {
        path: '/main', // Замените на ваши маршруты
        name: 'Main',
        component: MainView,
        meta: { requiresAuth: true },
    },
    // Добавьте дополнительные маршруты здесь
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
        next('/main'); // Замените на ваш главный маршрут
    } else {
        next();
    }
});

export default router;
```

### 2.2 Настройте веб-маршруты

```php
// routes/web.php
<?php

use Illuminate\Support\Facades\Route;

Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
```

---

## Этап 3: База данных

### 3.1 Создайте миграции для вашей предметной области

```bash
# Примеры команд для создания миграций
php artisan make:migration create_your_main_table
php artisan make:migration create_your_secondary_table
php artisan make:migration add_fields_to_your_table
```

### 3.2 Структура миграций

```php
// database/migrations/xxxx_create_your_main_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('your_main_table', function (Blueprint $table) {
            $table->id();
            $table->string('object_id'); // Связь с объектом из основного API
            $table->string('title');
            $table->text('description')->nullable();
            // Добавьте ваши специфичные поля
            $table->string('created_by_user_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('your_main_table');
    }
};
```

### 3.3 Создайте модели

```php
// app/Models/YourMainModel.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YourMainModel extends Model
{
    use HasFactory;

    protected $table = 'your_main_table';

    protected $fillable = [
        'object_id',
        'title',
        'description',
        'created_by_user_id',
        // Добавьте ваши поля
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        // Добавьте cast'ы для ваших полей
    ];

    // Добавьте отношения
}
```

---

## Этап 4: Контроллеры и API

### 4.1 Создайте основной контроллер

```php
// app/Http/Controllers/YourMainController.php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\YourMainModel;
use Illuminate\Http\JsonResponse;

class YourMainController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // Логика получения данных
        $data = YourMainModel::where('object_id', $request->object_id)->get();
        
        return response()->json($data);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'object_id' => 'required|string',
            // Добавьте валидацию для ваших полей
        ]);

        $item = YourMainModel::create($request->all());

        return response()->json($item, 201);
    }

    public function update(Request $request, YourMainModel $item): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            // Добавьте валидацию для ваших полей
        ]);

        $item->update($request->all());

        return response()->json($item);
    }

    public function destroy(YourMainModel $item): JsonResponse
    {
        $item->delete();
        return response()->json(['message' => 'Удалено успешно']);
    }
}
```

---

## Этап 5: Frontend компоненты

### 5.1 Создайте главный компонент

```vue
<!-- resources/js/views/MainView.vue -->
<template>
    <div class="min-h-screen" style="background-color: rgb(245, 245, 245)">
        <!-- Боковое меню (опционально) -->
        <div class="flex">
            <aside class="w-64 bg-white border-r border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-6">
                    {{ currentObjectName || 'Загрузка...' }}
                </h2>
                <!-- Навигация -->
            </aside>

            <!-- Основной контент -->
            <main class="flex-1 p-6">
                <div class="bg-white rounded-lg p-6">
                    <h1 class="text-2xl font-bold text-gray-900 mb-6">
                        Ваше приложение
                    </h1>
                    
                    <!-- Ваш контент здесь -->
                    <div v-if="loading" class="text-center">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                        <p class="mt-2 text-gray-600">Загрузка...</p>
                    </div>

                    <div v-else>
                        <!-- Ваш основной контент -->
                    </div>
                </div>
            </main>
        </div>
    </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '../stores/auth';
import axios from 'axios';

export default {
    name: 'MainView',
    setup() {
        const authStore = useAuthStore();
        const loading = ref(false);
        const currentObjectName = ref('');

        const fetchData = async () => {
            loading.value = true;
            try {
                // Загрузите ваши данные
                const response = await axios.get('/api/your-module');
                // Обработайте данные
            } catch (error) {
                console.error('Error fetching data:', error);
            } finally {
                loading.value = false;
            }
        };

        onMounted(() => {
            authStore.initializeAuth();
            fetchData();
        });

        return {
            loading,
            currentObjectName,
            authStore,
        };
    },
};
</script>
```

### 5.2 Скопируйте Login.vue из примера

Используйте готовый компонент Login.vue из [`DEVELOPMENT_GUIDE.md`](./DEVELOPMENT_GUIDE.md) - он универсален для всех приложений.

---

## Этап 6: Специфичные функции

### 6.1 Интеграция с файлами

Если нужна работа с файлами, добавьте:

```php
// app/Http/Controllers/FileController.php
public function store(Request $request)
{
    $request->validate([
        'file' => 'required|file|max:10240', // 10MB max
    ]);

    $file = $request->file('file');
    $path = $file->store('uploads', 'public');

    return response()->json([
        'path' => $path,
        'name' => $file->getClientOriginalName(),
        'size' => $file->getSize(),
    ]);
}
```

### 6.2 Реального времени обновления

Для WebSocket или Server-Sent Events:

```bash
composer require pusher/pusher-php-server
npm install pusher-js
```

### 6.3 Экспорт данных

```php
// Добавьте в контроллер
public function export(Request $request)
{
    $data = YourMainModel::all();
    
    $csv = \League\Csv\Writer::createFromString('');
    $csv->insertOne(['ID', 'Title', 'Created']); // Headers
    
    foreach ($data as $item) {
        $csv->insertOne([$item->id, $item->title, $item->created_at]);
    }

    return response($csv->toString())
        ->header('Content-Type', 'text/csv')
        ->header('Content-Disposition', 'attachment; filename="export.csv"');
}
```

---

## Этап 7: Развертывание

### 7.1 Сборка проекта

```bash
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 7.2 Переменные окружения

```env
# .env
APP_NAME="Your App Name"
APP_URL=https://your-app.domain.com

# API основного приложения
MAIN_API_URL=https://api.staging.pto-app.ru/api/v1

# База данных
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_app_db
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### 7.3 Веб-сервер (Nginx)

```nginx
server {
    listen 80;
    server_name your-app.domain.com;
    root /path/to/your-app/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

---

## Чек-лист для запуска

### ✅ Backend
- [ ] AuthController создан и настроен
- [ ] API маршруты определены
- [ ] Миграции базы данных выполнены
- [ ] Модели созданы с правильными связями
- [ ] Валидация запросов настроена

### ✅ Frontend
- [ ] Vue.js компоненты созданы
- [ ] Роутинг настроен с защитой маршрутов
- [ ] Pinia store для управления состоянием
- [ ] Axios настроен для API запросов
- [ ] Компоненты стилизованы с Tailwind CSS

### ✅ Интеграция
- [ ] Аутентификация через основное API работает
- [ ] Данные пользователя и объектов загружаются
- [ ] Токены правильно передаются и хранятся
- [ ] Обработка ошибок API реализована

### ✅ Деплой
- [ ] Проект собран для продакшена
- [ ] Переменные окружения настроены
- [ ] Веб-сервер сконфигурирован
- [ ] SSL сертификат установлен

---

## Полезные ресурсы

- **Пример реализации**: [`DEVELOPMENT_GUIDE.md`](./DEVELOPMENT_GUIDE.md)
- **Laravel документация**: https://laravel.com/docs
- **Vue.js документация**: https://vuejs.org/guide/
- **Tailwind CSS**: https://tailwindcss.com/docs
- **Pinia документация**: https://pinia.vuejs.org/

---

## Поддержка

При возникновении вопросов или проблем:

1. Изучите пример в [`DEVELOPMENT_GUIDE.md`](./DEVELOPMENT_GUIDE.md)
2. Проверьте логи Laravel: `storage/logs/laravel.log`
3. Используйте Vue DevTools для отладки frontend
4. Проверьте Network tab в браузере для API запросов

**Удачи в разработке! 🚀** 