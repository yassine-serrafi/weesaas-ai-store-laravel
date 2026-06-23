<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table `codes_promo` — codes de réduction (pourcentage / fixe / livraison).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('codes_promo', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code', 50)->unique();
            $table->enum('type', ['pct', 'fixe', 'livraison_gratuite'])->default('pct');
            $table->decimal('valeur', 10, 2)->default(0);
            $table->decimal('min_achat', 10, 2)->default(0);
            $table->integer('max_usage')->default(0);
            $table->integer('nb_usage')->default(0);
            $table->unsignedInteger('product_id')->nullable();
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamp('created_at')->nullable()->useCurrent();

            $table->index('product_id');
            $table->index('actif', 'idx_actif');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('codes_promo');
    }
};
