<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table `ai_jobs` — suivi des générations de page produit par IA.
 *
 * ⚠️ Anciennement nommée `jobs` dans le legacy. Renommée en `ai_jobs` pour
 * éviter la collision avec la table `jobs` de la file d'attente Laravel.
 * Le script d'import mappe legacy.jobs → ai_jobs et products.ai_job_id reste valide.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('ai_jobs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('product_id');
            $table->enum('status', ['pending', 'running', 'completed', 'failed'])->default('pending');
            $table->integer('step_current')->default(0);
            $table->integer('step_total')->default(5);
            $table->integer('progress_pct')->default(0);
            $table->string('step_label', 500)->default('');
            $table->text('error_message')->nullable();
            $table->longText('params_json')->nullable();
            $table->longText('result_data')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();

            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_jobs');
    }
};
