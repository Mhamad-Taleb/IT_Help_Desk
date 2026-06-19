<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_log_clears', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_log_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('cleared_at');
            $table->timestamps();

            $table->unique(['audit_log_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_log_clears');
    }
};
