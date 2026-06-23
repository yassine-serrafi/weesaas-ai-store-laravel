<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GenerationLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Page Logs admin — journal ultra détaillé des générations (produit / page)
 * et console système (storage/logs/laravel.log).
 */
class LogsController extends Controller
{
    public function index(Request $request): View
    {
        $tab    = in_array($request->query('tab'), ['generations', 'system'], true) ? $request->query('tab') : 'generations';
        $source = in_array($request->query('source'), ['product', 'page'], true) ? $request->query('source') : '';
        $status = in_array($request->query('status'), ['completed', 'failed', 'running'], true) ? $request->query('status') : '';
        $search = trim((string) $request->query('q', ''));

        $runs = collect();
        if ($tab === 'generations') {
            $runs = $this->runsQuery($source, $search)
                ->map(fn ($r) => $this->decorateRun($r))
                ->when($status !== '', fn ($c) => $c->where('status', $status))
                ->values();
        }

        $system = $tab === 'system' ? $this->readSystemLog(300, $search) : [];

        $stats = [
            'total'     => GenerationLog::distinct('run_id')->count('run_id'),
            'errors'    => GenerationLog::where('level', 'error')->count(),
            'today'     => GenerationLog::whereDate('created_at', Carbon::today())->distinct('run_id')->count('run_id'),
            'lines'     => GenerationLog::count(),
        ];

        return view('admin.logs.index', compact('tab', 'source', 'status', 'search', 'runs', 'system', 'stats'));
    }

    /** Détail d'un run : timeline complète (vue HTML ou JSON pour le live). */
    public function show(Request $request, string $runId): View|JsonResponse
    {
        $lines = GenerationLog::where('run_id', $runId)->orderBy('id')->get();
        abort_if($lines->isEmpty(), 404);

        $run = $this->decorateRun($this->aggregateFrom($lines));

        if ($request->wantsJson() || $request->query('json')) {
            return response()->json([
                'run'   => $run,
                'lines' => $lines->map(fn ($l) => [
                    'level'       => $l->level,
                    'step'        => $l->step,
                    'step_label'  => $l->step_label,
                    'message'     => $l->message,
                    'duration_ms' => $l->duration_ms,
                    'context'     => $l->context_json,
                    'at'          => $l->created_at?->format('H:i:s'),
                ]),
            ]);
        }

        return view('admin.logs.show', compact('run', 'lines'));
    }

    /** Flux JSON des runs récents (rafraîchissement live de la liste). */
    public function feed(): JsonResponse
    {
        $runs = $this->runsQuery('', '')->take(20)->map(fn ($r) => $this->decorateRun($r))->values();
        return response()->json(['runs' => $runs, 'at' => now()->format('H:i:s')]);
    }

    /** Purge manuelle des logs de plus de 30 jours. */
    public function purge(): RedirectResponse
    {
        $n = GenerationLog::where('created_at', '<', Carbon::now()->subDays(30))->delete();
        return back()->with('success', "$n ligne(s) de log supprimée(s) (> 30 jours).");
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private function runsQuery(string $source, string $search)
    {
        return GenerationLog::query()
            ->selectRaw(
                'run_id, source, MAX(ref_id) ref_id, MAX(ref_label) ref_label, '
                . 'MIN(created_at) started_at, MAX(created_at) ended_at, '
                . 'SUM(duration_ms) total_ms, MAX(step) max_step, COUNT(*) nb_lines, '
                . "MAX(CASE WHEN level='error' THEN 1 ELSE 0 END) has_error, "
                . "MAX(CASE WHEN message LIKE '%\u{2713}%' THEN 1 ELSE 0 END) done"
            )
            ->where('source', '!=', 'system')
            ->when($source !== '', fn ($q) => $q->where('source', $source))
            ->when($search !== '', fn ($q) => $q->where('ref_label', 'like', "%{$search}%"))
            ->groupBy('run_id', 'source')
            ->orderByDesc('started_at')
            ->limit(200)
            ->get();
    }

    /** Reconstruit un agrégat de run à partir d'une collection de lignes. */
    private function aggregateFrom($lines): object
    {
        $first = $lines->first();
        return (object) [
            'run_id'     => $first->run_id,
            'source'     => $first->source,
            'ref_id'     => $lines->max('ref_id'),
            'ref_label'  => $lines->firstWhere('ref_label', '!=', '')->ref_label ?? $first->ref_label,
            'started_at' => $lines->min('created_at'),
            'ended_at'   => $lines->max('created_at'),
            'total_ms'   => $lines->sum('duration_ms'),
            'max_step'   => $lines->max('step'),
            'nb_lines'   => $lines->count(),
            'has_error'  => $lines->contains(fn ($l) => $l->level === 'error') ? 1 : 0,
            'done'       => $lines->contains(fn ($l) => str_contains((string) $l->message, '✓')) ? 1 : 0,
        ];
    }

    private function decorateRun(object $r): array
    {
        $status = $r->has_error ? 'failed' : ($r->done ? 'completed' : 'running');
        $started = $r->started_at instanceof Carbon ? $r->started_at : Carbon::parse($r->started_at);
        $ended   = $r->ended_at instanceof Carbon ? $r->ended_at : Carbon::parse($r->ended_at);

        return [
            'run_id'    => $r->run_id,
            'source'    => $r->source,
            'source_label' => $r->source === 'page' ? '📄 Page' : '🖼️ Produit',
            'ref_id'    => $r->ref_id,
            'ref_label' => $r->ref_label ?: '—',
            'status'    => $status,
            'badge'     => $status === 'completed' ? 'badge-active' : ($status === 'failed' ? 'badge-annulee' : 'badge-generating'),
            'status_label' => $status === 'completed' ? 'Terminé' : ($status === 'failed' ? 'Échec' : 'En cours'),
            'max_step'  => (int) $r->max_step,
            'lines'     => (int) $r->nb_lines,
            'duration'  => $this->humanDuration((int) $r->total_ms),
            'started'   => $started->format('d/m H:i:s'),
            'started_full' => $started->format('Y-m-d H:i:s'),
        ];
    }

    private function humanDuration(int $ms): string
    {
        if ($ms < 1000) {
            return $ms . ' ms';
        }
        $s = $ms / 1000;
        return $s < 60 ? round($s, 1) . ' s' : floor($s / 60) . 'm ' . round($s % 60) . 's';
    }

    /** Lit et parse les dernières entrées de storage/logs/laravel.log. */
    private function readSystemLog(int $max, string $search): array
    {
        $path = storage_path('logs/laravel.log');
        if (! is_file($path)) {
            return [];
        }

        // Lecture des derniers ~400 Ko pour éviter de charger un fichier énorme.
        $size = filesize($path);
        $chunk = 400_000;
        $fh = fopen($path, 'rb');
        if ($size > $chunk) {
            fseek($fh, -$chunk, SEEK_END);
            fgets($fh); // ligne partielle ignorée
        }
        $content = stream_get_contents($fh);
        fclose($fh);

        $entries = [];
        // Chaque entrée commence par [YYYY-MM-DD HH:MM:SS].
        $parts = preg_split('/(?=^\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\])/m', (string) $content, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($parts as $block) {
            if (! preg_match('/^\[(.*?)\]\s+(\w+)\.(\w+):\s*(.*)/s', trim($block), $m)) {
                continue;
            }
            $level = strtolower($m[3]);
            $message = trim($m[4]);
            $first = strtok($message, "\n");
            if ($search !== '' && stripos($block, $search) === false) {
                continue;
            }
            $entries[] = [
                'time'    => $m[1],
                'channel' => $m[2],
                'level'   => $level,
                'message' => mb_substr($first, 0, 400),
                'detail'  => mb_strlen($message) > 400 || str_contains($message, "\n")
                    ? mb_substr($message, 0, 4000) : '',
                'class'   => in_array($level, ['error', 'critical', 'emergency', 'alert'], true) ? 'err'
                    : ($level === 'warning' ? 'warn' : 'info'),
            ];
        }

        return array_slice(array_reverse($entries), 0, $max);
    }
}
