<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table `order_history` — historique des changements de statut d'une commande.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_history', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('order_id');
            $table->string('statut', 50);
            $table->text('note')->nullable();
            $table->unsignedInteger('admin_id')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();

            $table->index('order_id', 'idx_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_history');
    }
};
