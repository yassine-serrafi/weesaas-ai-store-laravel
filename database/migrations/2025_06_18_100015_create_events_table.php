<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table `events` — événements analytics (view, add_to_cart, purchase, …).
 * Clé primaire bigint (volume élevé).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('event_name', 100);
            $table->unsignedInteger('product_id')->nullable();
            $table->unsignedInteger('order_id')->nullable();
            $table->string('session_id', 128)->default('');
            $table->text('data')->nullable();
            $table->string('ip', 45)->default('');
            $table->string('user_agent', 255)->default('');
            $table->timestamp('created_at')->nullable()->useCurrent();

            $table->index('event_name', 'idx_event_name');
            $table->index('product_id', 'idx_product');
            $table->index('session_id', 'idx_session');
            $table->index('created_at', 'idx_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
