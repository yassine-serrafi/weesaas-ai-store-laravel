<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table `settings` — paramètres boutique (clé/valeur).
 * Certaines valeurs sont chiffrées (clés API, SMTP) : colonne `chiffre`.
 * Fidèle au schéma legacy WeeSaaS.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->increments('id');
            $table->string('cle', 100)->unique();
            $table->longText('valeur')->nullable();
            $table->boolean('chiffre')->default(false);
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
