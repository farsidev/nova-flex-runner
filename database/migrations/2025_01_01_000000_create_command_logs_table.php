<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('command_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('command_name');
            $table->string('command_slug');
            $table->enum('command_type', ['artisan', 'job', 'service', 'shell', 'http']);
            $table->string('category')->nullable();
            $table->json('inputs')->nullable();
            $table->longText('output')->nullable();
            $table->enum('status', ['pending', 'running', 'success', 'failed']);
            $table->decimal('duration', 8, 3)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['command_type', 'status']);
            $table->index(['category', 'created_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('command_logs');
    }
};