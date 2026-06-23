<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Fil de notifications admin (commandes, générations, statuts…).
 */
class NotificationController extends Controller
{
    public function index(): View
    {
        $notifications = Notification::orderByDesc('id')->paginate(30);
        $unread = Notification::where('lu', false)->count();

        return view('admin.notifications.index', compact('notifications', 'unread'));
    }

    /** Marque comme lue puis redirige vers la cible (ou retour). */
    public function markRead(Notification $notification): RedirectResponse
    {
        $notification->update(['lu' => true]);

        return $notification->lien
            ? redirect()->to($notification->lien)
            : back();
    }

    public function markAllRead(): RedirectResponse
    {
        Notification::where('lu', false)->update(['lu' => true]);

        return back()->with('success', 'Toutes les notifications sont marquées comme lues.');
    }

    public function destroy(Notification $notification): RedirectResponse
    {
        $notification->delete();

        return back()->with('success', 'Notification supprimée.');
    }

    public function clearRead(): RedirectResponse
    {
        $n = Notification::where('lu', true)->delete();

        return back()->with('success', "$n notification(s) lue(s) supprimée(s).");
    }
}
