<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('timesheets', function (Blueprint $table) {
            $table->id();
            $table->string('object_id'); // ID объекта из основного приложения
            $table->string('user_id'); // ID пользователя из основного приложения
            $table->string('user_name'); // Имя пользователя
            $table->string('user_surname'); // Фамилия пользователя
            $table->date('date'); // Дата работы
            $table->decimal('hours_worked', 4, 2)->default(0); // Отработанные часы
            $table->boolean('has_report')->default(false); // Есть ли отчет за этот день
            $table->timestamps();
            
            // Уникальный индекс - один пользователь может иметь только одну запись на день на объекте
            $table->unique(['object_id', 'user_id', 'date']);
            $table->index(['object_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timesheets');
    }
};
