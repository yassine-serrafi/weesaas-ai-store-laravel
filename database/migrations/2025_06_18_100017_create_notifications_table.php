<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table `notifications` — fil de notifications admin (commande, stock, etc.).
 *
 * NB : c'est la table métier legacy, distincte du système de notifications
 * d'Eloquent (qui utiliserait une table à PK uuid). On garde ce nom car aucune
 * notification Laravel native n'est utilisée ici.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->increments('id');
            $table->string('type', 50);
            $table->string('titre', 500);
            $table->text('message')->nullable();
            $table->boolean('lu')->default(false);
            $table->string('lien', 500)->default('');
            $table->timestamp('created_at')->nullable()->useCurrent();

            $table->index('lu', 'idx_lu');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
