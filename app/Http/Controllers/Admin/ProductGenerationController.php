<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateProductPage;
use App\Models\AiJob;
use App\Models\Product;
use App\Services\SettingsRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Création de produit assistée par IA (port de weeadmin/creer-produit.php + jobs).
 *
 * Upload d'une image → produit brouillon + AiJob → dispatch du Job en queue.
 * La progression est suivie via /weeadmin/produits/generer/{aiJob}/status.
 */
class ProductGenerationController extends Controller
{
    public function create(SettingsRepository $settings): View
    {
        $fraisDefaut = (string) $settings->get('livraison_gratuite_defaut') === '1'
            ? 0.0
            : (float) ($settings->get('frais_livraison_defaut') ?? 0);

        return view('admin.products.create', ['fraisDefaut' => $fraisDefaut]);
    }

    public function store(Request $request, SettingsRepository $settings): RedirectResponse
    {
        $data = $request->validate([
            'image'              => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'prix'               => ['required', 'numeric', 'min:0'],
            'prix_barre'         => ['nullable', 'numeric', 'min:0'],
            'frais_livraison'    => ['nullable', 'numeric', 'min:0'],
            'nb_images'          => ['nullable', 'integer', 'min:0', 'max:4'],
            'langue'             => ['required', 'string', 'max:20'],
            'pays_vente'         => ['required', 'string', 'max:20'],
            'garantie_jours'     => ['nullable', 'integer', 'min:0'],
            'stock_quantite'     => ['nullable', 'integer', 'min:0'],
            'style_page'         => ['nullable', 'string', 'max:20'],
            'nom_produit'        => ['nullable', 'string', 'max:500'],
            'instructions_libres' => ['nullable', 'string', 'max:2000'],
            'description_ia_input' => ['nullable', 'string', 'max:5000'],
            'disable_colors'     => ['nullable', 'boolean'],
            'mode_rapide'        => ['nullable', 'boolean'],
        ]);

        // Sauvegarde de l'image originale dans public/uploads/originals.
        $dir = public_path('uploads/originals');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $ext = $request->file('image')->getClientOriginalExtension() ?: 'jpg';
        $filename = 'img_' . uniqid('', true) . '.' . $ext;
        $request->file('image')->move($dir, $filename);
        $imagePath = $dir . DIRECTORY_SEPARATOR . $filename;

        // Devise : on prend la devise principale de la boutique (réglages),
        // avec repli sur la déduction par langue si le réglage est vide.
        $devise = $settings->get('devise_defaut') ?: getDeviseParLangue($data['langue']);
        $sym = getSymboleDevise($devise);

        // Frais de livraison : valeur saisie au formulaire, sinon repli sur les réglages.
        // Si la livraison gratuite est activée par défaut, frais = 0.
        if ($request->filled('frais_livraison')) {
            $fraisLivraison = (float) $data['frais_livraison'];
        } elseif ((string) $settings->get('livraison_gratuite_defaut') === '1') {
            $fraisLivraison = 0.0;
        } else {
            $fraisLivraison = (float) ($settings->get('frais_livraison_defaut') ?? 0);
        }
        $livraisonGratuite = $fraisLivraison <= 0;

        // Produit brouillon (slug temporaire unique, remplacé par le Job).
        $product = Product::create([
            'slug'               => 'draft-' . Str::random(10),
            'status'             => 'generating',
            'nom_produit'        => $data['nom_produit'] ?? 'Produit en cours…',
            'prix'               => $data['prix'],
            'prix_barre'         => $data['prix_barre'] ?? 0,
            'frais_livraison'    => $fraisLivraison,
            'livraison_gratuite' => $livraisonGratuite,
            'devise'             => $devise,
            'symbole_devise'     => $sym['symbole'],
            'position_symbole'   => $sym['position'],
            'langue'             => $data['langue'],
            'pays_vente'         => $data['pays_vente'],
            'garantie_jours'     => $data['garantie_jours'] ?? 30,
            'style_page'         => $data['style_page'] ?? 'moderne',
            'stock_quantite'     => $data['stock_quantite'] ?? 100,
        ]);

        $aiJob = AiJob::create([
            'product_id'  => $product->id,
            'status'      => 'pending',
            'step_total'  => 5,
            'step_label'  => 'En file d\'attente…',
            'params_json' => [
                'image_path'           => $imagePath,
                'nb_images'            => (int) ($data['nb_images'] ?? 3),
                'langue'               => $data['langue'],
                'pays_vente'           => $data['pays_vente'],
                'devise'               => $devise,
                'symbole_devise'       => $sym['symbole'],
                'position_symbole'     => $sym['position'],
                'prix'                 => (float) $data['prix'],
                'prix_barre'           => (float) ($data['prix_barre'] ?? 0),
                'frais_livraison'      => $fraisLivraison,
                'garantie_jours'       => (int) ($data['garantie_jours'] ?? 30),
                'style_page'           => $data['style_page'] ?? 'moderne',
                'nom_produit'          => $data['nom_produit'] ?? '',
                'instructions_libres'  => $data['instructions_libres'] ?? '',
                'description_ia_input' => $data['description_ia_input'] ?? '',
                'disable_colors'       => (bool) ($data['disable_colors'] ?? false),
                'mode_rapide'          => (bool) ($data['mode_rapide'] ?? false),
            ],
        ]);

        $product->update(['ai_job_id' => $aiJob->id]);

        GenerateProductPage::dispatch($aiJob->id, $product->id);

        // Démarre un worker en arrière-plan pour traiter le job sans worker permanent.
        \App\Support\QueueRunner::spawn();

        return redirect()->route('admin.products.generate.progress', $aiJob->id);
    }

    public function progress(AiJob $aiJob): View
    {
        return view('admin.products.progress', ['aiJob' => $aiJob]);
    }

    /** Endpoint de polling JSON (remplace jobs/status.php). */
    public function status(AiJob $aiJob): JsonResponse
    {
        return response()->json([
            'status'       => $aiJob->status,
            'progress_pct' => $aiJob->progress_pct,
            'step_current' => $aiJob->step_current,
            'step_total'   => $aiJob->step_total,
            'step_label'   => $aiJob->step_label,
            'error'        => $aiJob->error_message,
            'product_id'   => $aiJob->product_id,
            'product_slug' => optional($aiJob->product)->slug,
        ]);
    }
}
