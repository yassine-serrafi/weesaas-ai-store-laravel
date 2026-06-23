<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table `menus` — liens de navigation header/footer, multilingues (fr/ar/en).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->increments('id');
            $table->string('label_fr', 100);
            $table->string('label_ar', 100);
            $table->string('label_en', 100);
            $table->string('url');
            $table->integer('ordre')->default(0);
            $table->enum('type', ['custom', 'page'])->default('custom');
            $table->unsignedInteger('target_id')->nullable();
            $table->enum('position', ['header', 'footer'])->default('header');
            $table->boolean('statut')->default(true);
            $table->timestamp('created_at')->nullable()->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};
