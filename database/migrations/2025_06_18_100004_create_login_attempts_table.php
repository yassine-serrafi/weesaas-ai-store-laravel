<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table `login_attempts` — anti-bruteforce de la connexion admin (par IP).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('login_attempts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ip', 45);
            $table->string('username', 100)->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();

            $table->index(['ip', 'created_at'], 'idx_ip_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
    }
};
