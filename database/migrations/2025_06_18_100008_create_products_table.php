<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table `products` — fidèle au schéma legacy WeeSaaS (~90 colonnes).
 *
 * ⚠️ Dette de schéma volontairement conservée pour ne rien casser à l'import :
 *   - status / statut          (doublon de statut produit)
 *   - stock / stock_quantite   (doublon de stock)
 *   - pays_vente / pays / pays_cible
 *   - rating / note_produit, nb_avis / reviews_count
 *   - delai_livraison / livraison_delai
 *   - testimonials / temoignages_json, faqs / faq_json, gallery / images_json …
 * Le modèle Product expose des accesseurs pour lire la « bonne » colonne et
 * synchronise les doublons en écriture. Le nettoyage est une phase ultérieure.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->string('slug')->unique();

            // Statut (doublon legacy)
            $table->enum('status', ['draft', 'generating', 'active', 'paused', 'archived'])->default('draft');
            $table->enum('statut', ['draft', 'generating', 'active', 'paused', 'archived', 'archive'])->default('draft');

            // Identité & prix
            $table->string('nom_produit', 500)->default('');
            $table->decimal('prix', 10, 2)->default(0);
            $table->decimal('prix_barre', 10, 2)->default(0);

            // Stock (doublon legacy)
            $table->integer('stock')->default(100);
            $table->integer('stock_quantite')->default(100);
            $table->integer('quantite_max')->default(10);
            $table->integer('nb_images')->default(3);

            // Devise
            $table->string('devise', 10)->default('MAD');
            $table->string('symbole_devise', 20)->default('درهم');
            $table->string('symbole_devise_affiche', 20)->default('DH');
            $table->enum('position_symbole', ['avant', 'apres'])->default('apres');

            // Livraison
            $table->decimal('frais_livraison', 10, 2)->default(0);
            $table->boolean('livraison_gratuite')->default(false);

            // Langue / direction
            $table->string('langue', 20)->default('ar_marocain');
            $table->enum('variante_arabe', ['marocain', 'standard', 'golfe', 'mixte', ''])->default('marocain');
            $table->enum('direction', ['rtl', 'ltr'])->default('rtl');

            // Pays (doublon legacy)
            $table->string('pays_vente', 20)->default('maroc');
            $table->string('pays', 30)->default('maroc');
            $table->string('pays_cible', 100)->default('Maroc');

            // Thème
            $table->string('couleur_theme', 7)->default('#FF6B00');
            $table->string('couleur_accent', 7)->default('#FF6B00');
            $table->string('couleur_secondaire', 7)->default('#111111');
            $table->enum('style_page', ['moderne', 'luxe', 'minimaliste', 'energique', 'confiance'])->default('moderne');

            // Timer / urgence
            $table->boolean('timer_actif')->default(false);
            $table->integer('timer_heures')->default(24);
            $table->string('timer_label', 255)->default('');
            $table->boolean('stock_affiche')->default(false);
            $table->boolean('stock_dynamique')->default(false);
            $table->integer('garantie_jours')->default(30);
            $table->string('badge_promo', 100)->default('');
            $table->string('badge_hero', 200)->default('');
            $table->text('texte_hero')->nullable();
            $table->text('texte_hero2')->nullable();
            $table->boolean('urgency_actif')->default(false);
            $table->string('urgency_text', 255)->default('');
            $table->string('urgency_sub', 255)->default('');

            // SEO
            $table->string('meta_title', 255)->default('');
            $table->text('meta_description')->nullable();

            // Délais (doublon legacy)
            $table->string('livraison_delai', 100)->default('2-4 jours ouvrés');
            $table->string('delai_livraison', 100)->default('48-72h');

            // Notes / avis (doublon legacy)
            $table->decimal('note_produit', 2, 1)->default(4.8);
            $table->decimal('rating', 2, 1)->default(4.8);
            $table->integer('nb_avis')->default(0);
            $table->integer('reviews_count')->default(0);
            $table->integer('nb_clients')->default(0);

            // Commande / paiement
            $table->enum('mode_commande', ['cod', 'paiement', 'les_deux'])->default('cod');
            $table->string('lien_paiement', 500)->default('');
            $table->boolean('whatsapp_actif')->default(false);
            $table->string('whatsapp_numero', 20)->default('');

            // Catalogue
            $table->boolean('featured')->default(false);
            $table->integer('ordre_affichage')->default(0);
            $table->string('image_principale', 500)->default('');
            $table->string('promo_bar_text', 500)->default('');

            // Sections (configuration de la page de vente)
            $table->longText('sections_json')->nullable();
            $table->text('sections_order')->nullable();
            $table->text('sections_disabled')->nullable();

            // Contenu IA / manuel (doublons legacy)
            $table->text('description_ia_input')->nullable();
            $table->longText('description_html')->nullable();
            $table->longText('description_ar')->nullable();
            $table->text('attrs_json')->nullable();
            $table->text('attributs')->nullable();
            $table->longText('temoignages_json')->nullable();
            $table->longText('testimonials')->nullable();
            $table->string('testimonials_title', 255)->default('');
            $table->longText('features')->nullable();
            $table->string('features_title', 255)->default('');
            $table->longText('faq_json')->nullable();
            $table->longText('faqs')->nullable();
            $table->string('faq_title', 255)->default('');
            $table->text('images_json')->nullable();
            $table->text('gallery')->nullable();
            $table->string('gallery_title', 255)->default('');
            $table->text('stats')->nullable();
            $table->longText('comparison_json')->nullable();
            $table->string('comparison_title', 255)->default('');
            $table->string('guarantee', 255)->default('');
            $table->longText('garanties_json')->nullable();
            $table->text('seo_json')->nullable();
            $table->text('preuve_sociale_json')->nullable();

            // Traçabilité IA
            $table->longText('prompt_utilise')->nullable();
            $table->longText('historique_json')->nullable();
            $table->unsignedInteger('ai_job_id')->nullable();

            // Statistiques produit
            $table->unsignedInteger('vues_total')->default(0);
            $table->unsignedInteger('vues_uniques')->default(0);
            $table->unsignedInteger('commandes_total')->default(0);
            $table->decimal('ca_total', 12, 2)->default(0);
            $table->dateTime('page_generated_at')->nullable();

            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
            $table->boolean('stock_desactive')->default(false);

            $table->index('status', 'idx_status');
            $table->index('statut', 'idx_statut');
            $table->index('ordre_affichage', 'idx_ordre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
