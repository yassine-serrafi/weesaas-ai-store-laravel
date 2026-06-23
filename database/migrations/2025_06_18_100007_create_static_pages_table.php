<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table `static_pages` — pages statiques (à propos, etc.) construites par blocs JSON.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('static_pages', function (Blueprint $table) {
            $table->increments('id');
            $table->string('slug')->unique();
            $table->string('titre', 500)->default('');
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft');
            $table->string('type', 50)->default('about');
            $table->string('langue', 20)->default('fr');
            $table->string('variante_arabe', 20)->default('');
            $table->enum('direction', ['rtl', 'ltr'])->default('ltr');
            $table->longText('blocks_json')->nullable();
            $table->text('seo_json')->nullable();
            $table->boolean('show_in_header_menu')->default(false);
            $table->boolean('show_in_footer_menu')->default(false);
            $table->integer('ordre_affichage')->default(0);
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();

            $table->index('status', 'idx_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('static_pages');
    }
};
