<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table `product_images` — galerie d'images produit (original + WebP).
 * position 0 = image principale.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_images', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('product_id');
            $table->string('url_originale', 500);
            $table->string('url_webp', 500)->nullable();
            $table->string('alt_text', 500)->nullable();
            $table->integer('position')->default(0);
            $table->boolean('statut')->default(true);
            $table->timestamp('created_at')->nullable()->useCurrent();

            $table->index('product_id', 'idx_product');
            $table->index('position', 'idx_position');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};
