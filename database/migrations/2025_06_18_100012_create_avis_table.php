<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table `avis` — avis clients (modération : en_attente / approuve / rejete).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('avis', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('product_id')->nullable();
            $table->string('nom_client', 200)->default('');
            $table->string('email', 200)->default('');
            $table->string('titre', 500)->default('');
            $table->text('commentaire')->nullable();
            $table->unsignedTinyInteger('note')->default(5);
            $table->string('photo_url', 500)->default('');
            $table->enum('statut', ['en_attente', 'approuve', 'rejete'])->default('en_attente');
            $table->string('pays', 30)->default('maroc');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();

            $table->index('product_id', 'idx_product');
            $table->index('statut', 'idx_statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avis');
    }
};
