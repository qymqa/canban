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
        Schema::create('daily_reports', function (Blueprint $table) {
            $table->id();
            $table->string('object_id'); // ID объекта из основного приложения
            $table->string('user_id'); // ID пользователя из основного приложения
            $table->string('user_name'); // Имя пользователя
            $table->string('user_surname'); // Фамилия пользователя
            $table->string('user_position')->nullable(); // Должность пользователя
            $table->text('report_text'); // Текст отчета
            $table->json('attached_tasks')->nullable(); // Прикрепленные задачи из канбана
            $table->timestamps();
            
            // Индексы для быстрого поиска
            $table->index(['object_id', 'user_id']);
            $table->index(['object_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_reports');
    }
};
