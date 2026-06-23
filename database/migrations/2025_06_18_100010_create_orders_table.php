<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table `orders` — commandes (cash on delivery). Fidèle au schéma legacy.
 *
 * ⚠️ Doublons legacy conservés : nom_client / (prenom + nom),
 *    note / notes_client, total / total_ttc.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('product_id');
            $table->string('reference', 30)->unique();

            // Client (doublon legacy nom_client vs prenom+nom)
            $table->string('nom_client', 200)->default('');
            $table->string('prenom', 100)->default('');
            $table->string('nom', 100)->default('');
            $table->string('telephone', 20);
            $table->string('indicatif_pays', 10)->default('+212');
            $table->string('pays', 30)->default('maroc');
            $table->string('ville', 200);
            $table->text('adresse')->nullable();

            // Montants (doublon legacy total vs total_ttc)
            $table->integer('quantite')->default(1);
            $table->decimal('prix_unitaire', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->decimal('total_ttc', 10, 2)->default(0);
            $table->decimal('frais_livraison', 10, 2)->default(0);
            $table->string('devise', 10)->default('MAD');
            $table->string('symbole_devise', 20)->default('DH');

            // Attributs / notes (doublons legacy)
            $table->string('attribut_choisi', 255)->default('');
            $table->text('attributs')->nullable();
            $table->text('notes_client')->nullable();
            $table->text('note')->nullable();

            $table->enum('statut', ['nouvelle', 'confirmee', 'expediee', 'livree', 'annulee', 'retour'])->default('nouvelle');
            $table->text('notes_admin')->nullable();

            // Traçabilité / analytics
            $table->string('ip_address', 45)->default('');
            $table->string('ip_country', 100)->default('');
            $table->string('ip_city', 200)->default('');
            $table->string('ip_region', 200)->default('');
            $table->string('device_type', 20)->default('');
            $table->string('browser', 50)->default('');
            $table->string('os', 50)->default('');
            $table->string('user_agent', 255)->default('');
            $table->text('referrer')->nullable();
            $table->string('source', 50)->default('direct');
            $table->string('session_id', 128)->default('');
            $table->string('utm_source', 100)->default('');
            $table->string('utm_medium', 100)->default('');
            $table->string('utm_campaign', 100)->default('');
            $table->integer('temps_avant_commande')->default(0);
            $table->unsignedInteger('upsell_product_id')->nullable();

            $table->dateTime('date_commande')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();

            $table->index('statut', 'idx_statut');
            $table->index('product_id', 'idx_product');
            $table->index('created_at', 'idx_created');
            $table->index('telephone', 'idx_tel');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
