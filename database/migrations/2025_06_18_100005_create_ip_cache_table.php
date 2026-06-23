<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table `ip_cache` — cache géolocalisation IP (ip-api.com), TTL 24h.
 * Clé primaire = ip (pas d'auto-increment).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('ip_cache', function (Blueprint $table) {
            $table->string('ip', 45)->primary();
            $table->string('country', 100)->default('');
            $table->string('city', 200)->default('');
            $table->string('region', 200)->default('');
            $table->string('country_code', 5)->default('');
            $table->timestamp('created_at')->nullable()->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ip_cache');
    }
};
