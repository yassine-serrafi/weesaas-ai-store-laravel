<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table `page_views` — vues de pages (catalogue + produit) avec géo/device/UTM.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('page_views', function (Blueprint $table) {
            $table->increments('id');
            $table->string('session_id', 64);
            $table->unsignedInteger('product_id')->nullable();
            $table->string('page_type', 50)->default('product');
            $table->string('country_code', 5)->default('');
            $table->string('country_name', 100)->default('');
            $table->string('city', 200)->default('');
            $table->string('region', 200)->default('');
            $table->string('device_type', 20)->default('');
            $table->string('browser', 50)->default('');
            $table->string('os', 50)->default('');
            $table->text('referrer')->nullable();
            $table->string('source', 50)->default('direct');
            $table->string('utm_source', 100)->default('');
            $table->string('utm_medium', 100)->default('');
            $table->string('utm_campaign', 100)->default('');
            $table->string('ip_address', 45)->default('');
            $table->timestamp('created_at')->nullable()->useCurrent();

            $table->index('product_id', 'idx_product_id');
            $table->index('session_id', 'idx_session_id');
            $table->index('created_at', 'idx_created_at');
            $table->index('country_code', 'idx_country');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_views');
    }
};
