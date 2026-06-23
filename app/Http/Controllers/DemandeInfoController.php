<?php

namespace App\Http\Controllers;

use App\Models\DemandeInfo;
use App\Models\Product;
use App\Services\MailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Demande d'alerte « produit disponible » (port de weeadmin/ajax/demande_info.php).
 * Protégé par CSRF (jeton @csrf du formulaire).
 */
class DemandeInfoController extends Controller
{
    public function store(Request $request, MailService $mail): JsonResponse
    {
        $productId = (int) $request->input('product_id', 0);
        $nom = htmlspecialchars(trim((string) $request->input('nom')), ENT_QUOTES, 'UTF-8');
        $tel = htmlspecialchars(trim((string) $request->input('telephone')), ENT_QUOTES, 'UTF-8');

        if (! $productId || ! $tel) {
            return response()->json(['success' => false, 'error' => 'Données incomplètes']);
        }

        // Éviter les doublons (même produit + même téléphone).
        $exists = DemandeInfo::where('product_id', $productId)->where('telephone', $tel)->exists();
        if (! $exists) {
            DemandeInfo::create([
                'product_id' => $productId,
                'nom'        => $nom,
                'telephone'  => $tel,
                'ip'         => $request->ip() ?? '',
            ]);
        }

        $product = Product::find($productId);
        if ($adminEmail = $mail->adminEmail()) {
            $mail->sendRaw(
                $adminEmail,
                "📩 Nouvelle demande d'alerte — " . ($product->nom_produit ?? ''),
                "Nouveau client intéressé :\n\nNom: $nom\nTel: $tel\nProduit: " . ($product->nom_produit ?? '') . "\n\nAllez répondre depuis votre admin."
            );
        }

        return response()->json(['success' => true]);
    }
}
