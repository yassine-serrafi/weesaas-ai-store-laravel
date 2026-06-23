<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Gestion des liens de menu header/footer (port de weeadmin/menu.php).
 */
class MenuController extends Controller
{
    public function index(): View
    {
        $menus = Menu::orderBy('position')->orderBy('ordre')->get()->groupBy('position');
        return view('admin.menus.index', compact('menus'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'label_fr' => ['required', 'string', 'max:100'],
            'label_ar' => ['nullable', 'string', 'max:100'],
            'label_en' => ['nullable', 'string', 'max:100'],
            'url'      => ['required', 'string', 'max:255'],
            'position' => ['required', 'in:header,footer'],
            'ordre'    => ['nullable', 'integer'],
        ]);

        Menu::create([
            'label_fr' => $data['label_fr'],
            'label_ar' => ($data['label_ar'] ?? '') ?: $data['label_fr'],
            'label_en' => ($data['label_en'] ?? '') ?: $data['label_fr'],
            'url'      => $data['url'],
            'position' => $data['position'],
            'ordre'    => $data['ordre'] ?? 0,
            'type'     => 'custom',
            'statut'   => true,
        ]);

        return back()->with('success', 'Lien de menu ajouté.');
    }

    public function update(Request $request, Menu $menu): RedirectResponse
    {
        $data = $request->validate([
            'label_fr' => ['required', 'string', 'max:100'],
            'label_ar' => ['nullable', 'string', 'max:100'],
            'label_en' => ['nullable', 'string', 'max:100'],
            'url'      => ['required', 'string', 'max:255'],
            'position' => ['required', 'in:header,footer'],
            'ordre'    => ['nullable', 'integer'],
        ]);

        $menu->update([
            'label_fr' => $data['label_fr'],
            'label_ar' => ($data['label_ar'] ?? '') ?: $data['label_fr'],
            'label_en' => ($data['label_en'] ?? '') ?: $data['label_fr'],
            'url'      => $data['url'],
            'position' => $data['position'],
            'ordre'    => $data['ordre'] ?? 0,
        ]);

        return back()->with('success', 'Lien mis à jour.');
    }

    public function toggle(Menu $menu): RedirectResponse
    {
        $menu->statut = ! $menu->statut;
        $menu->save();
        return back()->with('success', 'Lien ' . ($menu->statut ? 'affiché' : 'masqué'));
    }

    public function destroy(Menu $menu): RedirectResponse
    {
        $menu->delete();
        return back()->with('success', 'Lien supprimé.');
    }
}
