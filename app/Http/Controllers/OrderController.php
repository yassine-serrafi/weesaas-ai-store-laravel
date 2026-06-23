<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Order;
use App\Models\Product;
use App\Services\MailService;
use App\Services\TrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Création de commande COD (port de commande.php).
 *
 * Contrat JSON IDENTIQUE à l'ancien endpoint (consommé par front.js) :
 *   { success:true, reference, total, devise, order_id }
 *   { success:false, error }
 * La protection CSRF est assurée par le middleware web (jeton @csrf du formulaire).
 */
class OrderController extends Controller
{
    public function store(Request $request, TrackingService $tracker, MailService $mail): JsonResponse
    {
        // Rate limiting par IP (5 commandes / 15 min) — comme le legacy.
        $ip = $tracker->realIp();
        $recent = Order::where('ip_address', $ip)
            ->where('created_at', '>=', Carbon::now()->subMinutes(15))
            ->count();
        if ($recent >= 5) {
            return response()->json(['success' => false, 'error' => 'Trop de tentatives. Réessayez dans 15 minutes.']);
        }

        // Validation (équivalente aux règles legacy).
        $product_id = (int) $request->input('product_id', 0);
        $nom        = $this->clean($request->input('nom'));
        $telephone  = $this->clean($request->input('telephone'));
        $ville      = $this->clean($request->input('ville'));
        $adresse    = $this->clean($request->input('adresse'));
        $note       = $this->clean($request->input('note'));
        $quantite   = max(1, min(99, (int) $request->input('quantite', 1)));

        $errors = [];
        if (! $product_id) $errors[] = 'Produit invalide';
        if (mb_strlen($nom) < 2) $errors[] = 'Nom invalide';
        if (! preg_match('/^[0-9+\s\-]{6,15}$/', str_replace(' ', '', $telephone))) $errors[] = 'Téléphone invalide';
        if (mb_strlen($ville) < 2) $errors[] = 'Ville requise';
        if ($errors) {
            return response()->json(['success' => false, 'error' => implode(', ', $errors)]);
        }

        $product = Product::active()->find($product_id);
        if (! $product) {
            return response()->json(['success' => false, 'error' => 'Produit non disponible']);
        }

        // Vérification du stock (sauf si désactivé pour ce produit).
        if (! $product->stock_desactive) {
            $stock = $product->stock_dispo;
            if ($stock <= 0) {
                return response()->json(['success' => false, 'error' => 'Stock épuisé']);
            }
            if ($stock < $quantite) {
                return response()->json(['success' => false, 'error' => "Quantité insuffisante. Il ne reste que $stock exemplaire(s)."]);
            }
        }

        $prix_unitaire   = (float) $product->prix;
        // Respecte le flag « livraison gratuite » (cohérence avec le total affiché au client).
        $frais_livraison = $product->livraison_gratuite ? 0.0 : (float) $product->frais_livraison;
        $total_ttc       = ($prix_unitaire * $quantite) + $frais_livraison;

        // Attributs (champs attr_*)
        $attrs = [];
        foreach ($request->all() as $k => $v) {
            if (str_starts_with($k, 'attr_')) {
                $attrs[substr($k, 5)] = $this->clean($v);
            }
        }

        // Référence unique
        $paysCode = strtoupper(substr($product->pays_effectif ?: 'MA', 0, 2));
        do {
            $ref = $paysCode . '-' . date('ymd') . '-' . strtoupper(substr(Str::uuid()->toString(), 0, 5));
        } while (Order::where('reference', $ref)->exists());

        $sessionId = $request->cookie('wee_sid') ?: $request->session()->getId();

        try {
            $order = Order::create([
                'reference'            => $ref,
                'product_id'           => $product_id,
                'nom_client'           => $nom,
                'telephone'            => $telephone,
                'ville'                => $ville,
                'adresse'              => $adresse,
                'note'                 => $note,
                'attributs'            => $attrs,
                'quantite'             => $quantite,
                'prix_unitaire'        => $prix_unitaire,
                'total'                => $total_ttc,
                'total_ttc'            => $total_ttc,
                'frais_livraison'      => $frais_livraison,
                'devise'               => $product->devise,
                'symbole_devise'       => $product->symbole_devise,
                'pays'                 => $product->pays_effectif,
                'statut'               => 'nouvelle',
                'ip_address'           => $ip,
                'user_agent'           => substr((string) $request->userAgent(), 0, 255),
                'session_id'           => $sessionId,
                'utm_source'           => $this->clean($request->input('utm_source')),
                'utm_medium'           => $this->clean($request->input('utm_medium')),
                'utm_campaign'         => $this->clean($request->input('utm_campaign')),
                'temps_avant_commande' => (int) $request->input('temps_avant_commande', 0),
                'date_commande'        => now(),
            ]);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'error' => 'Erreur interne de prise de commande']);
        }

        // Décrément stock (les 2 colonnes, plancher 0) — comme le legacy.
        try {
            DB::table('products')->where('id', $product_id)->update([
                'stock_quantite' => DB::raw("GREATEST(0, stock_quantite - $quantite)"),
                'stock'          => DB::raw("GREATEST(0, stock - $quantite)"),
            ]);
        } catch (Throwable $e) {
            report($e);
        }

        // Notification admin dans le fil (best-effort).
        try {
            \App\Models\Notification::create([
                'type'    => 'order',
                'titre'   => "🛒 Nouvelle commande {$ref}",
                'message' => "{$nom} · {$ville} · {$product->nom_produit} ×{$quantite} — {$total_ttc} {$product->symbole_devise}",
                'lien'    => route('admin.orders.show', $order->id),
            ]);
        } catch (Throwable $e) {
            report($e);
        }

        // Notification email admin (best-effort).
        if ($adminEmail = $mail->adminEmail()) {
            $attrsStr = implode(', ', array_map(fn ($k, $v) => "$k: $v", array_keys($attrs), $attrs));
            $msg = "🛒 Nouvelle commande !\n\nRéférence: $ref\nClient: $nom\nTél: $telephone\nVille: $ville\n"
                 . "Produit: {$product->nom_produit}\nAttributs: $attrsStr\nQté: $quantite\n"
                 . "Total: $total_ttc {$product->symbole_devise}\n\n→ Admin: " . url('/weeadmin/commandes/' . $order->id);
            $mail->sendRaw($adminEmail, "🛒 Nouvelle commande — $ref", $msg);
        }

        // Événement analytics "purchase".
        try {
            Event::create([
                'event_name' => 'purchase',
                'product_id' => $product_id,
                'order_id'   => $order->id,
                'session_id' => $sessionId,
                'data'       => ['reference' => $ref, 'total' => $total_ttc, 'devise' => $product->devise],
            ]);
        } catch (Throwable $e) {
            report($e);
        }

        return response()->json([
            'success'   => true,
            'reference' => $ref,
            'total'     => $total_ttc,
            'devise'    => $product->devise,
            'order_id'  => $order->id,
        ]);
    }

    /** Nettoyage équivalent à sanitize() legacy. */
    private function clean(mixed $value): string
    {
        if (is_array($value)) {
            return '';
        }
        return htmlspecialchars(trim((string) $value), ENT_QUOTES, 'UTF-8');
    }
}
