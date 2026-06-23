<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table `generation_logs` — journal d'événements ultra détaillé.
 *
 * Trace pas-à-pas chaque génération (produit IA, page IA) et les événements
 * système. Un `run_id` regroupe toutes les lignes d'une même exécution, ce qui
 * permet d'afficher une timeline complète dans l'admin (page Logs).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('generation_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('run_id')->index();
            $table->string('source', 20)->default('product'); // product | page | system
            $table->unsignedInteger('ref_id')->nullable();     // product_id / page_id / ai_job_id
            $table->string('ref_label', 255)->default('');     // nom produit / titre page
            $table->string('level', 10)->default('info');      // info | success | warning | error
            $table->unsignedSmallInteger('step')->default(0);
            $table->string('step_label', 255)->default('');
            $table->text('message')->nullable();
            $table->longText('context_json')->nullable();       // params, extrait réponse IA, trace erreur…
            $table->unsignedInteger('duration_ms')->default(0);
            $table->timestamp('created_at')->nullable()->useCurrent();

            $table->index('source');
            $table->index('level');
            $table->index('created_at');
            $table->index(['run_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generation_logs');
    }
};
