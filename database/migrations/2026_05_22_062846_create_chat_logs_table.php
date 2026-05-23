<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_logs', function (Blueprint $table) {
            $table->id();
            $table->text('message');
            $table->text('answer');
            $table->string('intent', 50)->nullable();
            $table->integer('response_time')->nullable()->comment('dalam milidetik');
            $table->string('session_id', 100)->nullable();
            $table->boolean('rag_enabled')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_logs');
    }
};