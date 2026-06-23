<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\AI\GeminiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $status = $request->query('status', '');

        $products = Product::withCount('orders')
            ->with('mainImage')
            ->when($q !== '', fn ($query) => $query->where('nom_produit', 'like', "%$q%")->orWhere('slug', 'like', "%$q%"))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.products.index', compact('products', 'q', 'status'));
    }

    public function edit(Product $product): View
    {
        return view('admin.products.edit', compact('product'));
    }

    /** Édition des champs commerciaux essentiels (le contenu IA reste géré en Phase 6). */
    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'nom_produit'     => ['required', 'string', 'max:500'],
            'prix'            => ['required', 'numeric', 'min:0'],
            'prix_barre'      => ['nullable', 'numeric', 'min:0'],
            'stock_quantite'  => ['required', 'integer', 'min:0'],
            'frais_livraison' => ['nullable', 'numeric', 'min:0'],
            'badge_hero'      => ['nullable', 'string', 'max:200'],
            'status'          => ['required', 'in:draft,generating,active,paused,archived'],
        ]);

        // setStockQuantite/setStatus synchronisent les colonnes dupliquées legacy.
        $product->fill($data);
        $product->stock_quantite = $data['stock_quantite'];
        $product->status = $data['status'];
        $product->save();

        return redirect()->route('admin.products.index')->with('success', 'Produit mis à jour.');
    }

    public function toggleStatus(Product $product): RedirectResponse
    {
        $product->status = $product->status === 'active' ? 'paused' : 'active';
        $product->save();

        return back()->with('success', "Produit « {$product->nom_produit} » → {$product->status}");
    }

    /**
     * Régénère UNE image du produit (par position) et remplace l'ancienne.
     * Réutilise l'image originale uploadée + le prompt courant (décor selon la position).
     */
    public function regenerateImage(Product $product, int $index, GeminiService $gemini): RedirectResponse
    {
        $job = $product->aiJob;
        $imagePath = $job?->params_json['image_path'] ?? null;
        if (! $imagePath || ! is_file($imagePath)) {
            return back()->with('error', "Image originale introuvable — régénération impossible (le fichier source a été supprimé).");
        }

        $type  = $job?->result_data['type_produit'] ?? 'autre';
        $style = $product->style_page ?: 'moderne';

        $res = $gemini->generateLifestyleImage($imagePath, '', $style, $index, $type);
        if (isset($res['error'])) {
            return back()->with('error', 'Échec de régénération : ' . $res['error']);
        }

        $rel = $gemini->saveGeneratedImage($res['data'], $res['mimeType'], $product->id, $index + 1, true);
        if (! $rel) {
            return back()->with('error', "Échec de l'enregistrement de l'image générée.");
        }
        $url = site_url($rel);

        // Remplace (ou crée) la ProductImage à cette position et supprime l'ancien fichier.
        $img = ProductImage::where('product_id', $product->id)->where('position', $index)->first();
        if ($img) {
            $this->deleteLocalFile($img->url_webp ?: $img->url_originale);
            $img->update(['url_originale' => $url, 'url_webp' => $url]);
        } else {
            ProductImage::create([
                'product_id'    => $product->id,
                'url_originale' => $url,
                'url_webp'      => $url,
                'alt_text'      => $product->nom_produit,
                'position'      => $index,
                'statut'        => 1,
            ]);
        }

        if ($index === 0) {
            $product->update(['image_principale' => $rel]);
        }

        return back()->with('success', 'Image ' . ($index + 1) . ' régénérée ✓');
    }

    /** Supprime un fichier local généré à partir de son URL (sécurisé : uniquement uploads/generated). */
    private function deleteLocalFile(?string $url): void
    {
        if (! $url) {
            return;
        }
        $path = ltrim((string) parse_url($url, PHP_URL_PATH), '/');
        if ($path && str_contains($path, 'uploads/generated/') && is_file(public_path($path))) {
            @unlink(public_path($path));
        }
    }

    public function destroy(Product $product): RedirectResponse
    {
        $nom = $product->nom_produit;

        // Si des commandes référencent ce produit, on archive (préserve l'historique)
        // au lieu de supprimer pour de bon.
        if ($product->orders()->exists()) {
            $product->status = 'archived';
            $product->save();

            return back()->with('success', "« {$nom} » a des commandes : il a été archivé (masqué de la boutique) au lieu d'être supprimé.");
        }

        // Suppression réelle + nettoyage des données liées.
        \App\Models\ProductImage::where('product_id', $product->id)->delete();
        \App\Models\AiJob::where('product_id', $product->id)->delete();
        \App\Models\GenerationLog::where('source', 'product')->where('ref_id', $product->id)->delete();
        $product->delete();

        return back()->with('success', "Produit « {$nom} » supprimé définitivement.");
    }
}
