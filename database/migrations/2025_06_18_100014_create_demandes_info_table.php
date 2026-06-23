<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table `demandes_info` — demandes de rappel / d'information (lead capture).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('demandes_info', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('product_id')->nullable();
            $table->string('nom', 200)->default('');
            $table->string('telephone', 30);
            $table->string('ip', 45)->default('');
            $table->boolean('traite')->default(false);
            $table->timestamp('created_at')->nullable()->useCurrent();

            $table->index('product_id', 'idx_product');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demandes_info');
    }
};
