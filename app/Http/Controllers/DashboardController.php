<?php

namespace App\Http\Controllers;

use App\Services\FirebaseService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(FirebaseService $firebase): View
    {
        $user = current_user();
        $groups = collect($firebase->all('groups'));
        $targets = collect($firebase->all('targets'))->where('status', 'active');
        $journals = collect($firebase->all('journals'));
        $feedbacks = collect($firebase->all('feedbacks'));
        $documentations = collect($firebase->all('documentations'));
        $readiness = collect($firebase->all('readiness'))->keyBy('group_id');

        if ($user->isStudent()) {
            $groups = $groups->where('id', $user->group_id)->values();
            $journals = $journals->where('group_id', $user->group_id)->values();
            $feedbacks = $feedbacks->where('group_id', $user->group_id)->values();
            $documentations = $documentations->where('group_id', $user->group_id)->values();
        }

        $targetIds = $targets->pluck('id')->all();
        $groupSummaries = $groups->map(function (array $group) use ($journals, $feedbacks, $documentations, $readiness, $targetIds): array {
            $groupJournals = $journals->where('group_id', $group['id']);
            $latestJournal = $groupJournals->sortByDesc(fn ($journal) => $this->journalSortKey($journal))->first();
            $checkedTargets = $groupJournals
                ->flatMap(fn ($journal) => collect($journal['target_checklist'] ?? [])->filter()->keys())
                ->unique()
                ->intersect($targetIds)
                ->count();

            $targetTotal = max(count($targetIds), 1);
            $readinessScore = (int) ($readiness->get($group['id'])['score'] ?? 0);

            return [
                'group' => $group,
                'latest_journal' => $latestJournal,
                'progress' => (int) round(($checkedTargets / $targetTotal) * 100),
                'help_requests' => $groupJournals->where('help_request', true)->count(),
                'documentation_count' => $documentations->where('group_id', $group['id'])->count(),
                'open_feedback_count' => $feedbacks
                    ->where('group_id', $group['id'])
                    ->where('revision_status', '!=', 'selesai')
                    ->count(),
                'readiness_score' => $readinessScore,
            ];
        })->values();

        $stats = [
            'groups' => $groups->count(),
            'targets' => $targets->count(),
            'journals' => $journals->count(),
            'help_requests' => $journals->where('help_request', true)->count(),
            'average_progress' => (int) round($groupSummaries->avg('progress') ?: 0),
            'average_readiness' => (int) round($groupSummaries->avg('readiness_score') ?: 0),
        ];

        return view('dashboard.index', [
            'stats' => $stats,
            'groups' => $groupSummaries,
            'latestJournals' => $journals->sortByDesc(fn ($journal) => $this->journalSortKey($journal))->take(5),
            'helpRequests' => $journals->where('help_request', true)->sortByDesc(fn ($journal) => $this->journalSortKey($journal))->take(5),
            'targets' => $targets->sortByDesc('meeting_no')->take(4),
        ]);
    }

    private function journalSortKey(array $journal): string
    {
        return sprintf(
            '%03d-%s-%s',
            (int) ($journal['meeting_no'] ?? 0),
            $journal['journal_date'] ?? '',
            $journal['created_at'] ?? ''
        );
    }
}
