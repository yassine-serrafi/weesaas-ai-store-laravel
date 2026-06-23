<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table `admin_sessions` — sessions admin persistées en base avec binding IP.
 * Reproduit le mécanisme de auth.php (token + ip_address + expires_at).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('admin_sessions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('admin_id');
            $table->string('token', 64)->unique();
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->timestamp('last_activity')->nullable()->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();

            $table->index('admin_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_sessions');
    }
};
