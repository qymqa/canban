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
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->onDelete('cascade');
            $table->string('file_name'); // Оригинальное имя файла
            $table->string('file_path'); // Путь к файлу во внешнем API
            $table->string('file_size'); // Размер файла
            $table->string('file_type'); // MIME тип файла
            $table->string('uploaded_by_user_id'); // ID пользователя, который загрузил файл
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
