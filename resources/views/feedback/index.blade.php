<x-layouts.app title="Feedback">
    <div class="page-head">
        <div>
            <p class="page-kicker">Feedback</p>
            <h1 class="page-title serif">Catatan revisi</h1>
            <p class="page-subtitle">Komentar guru, prioritas, dan status revisi setiap jurnal.</p>
        </div>
    </div>

    <section class="card">
        <div class="list-divide">
            @forelse ($feedbacks as $feedback)
                @php($journal = $journals->get($feedback['journal_id']))
                <a href="{{ $journal ? route('journals.show', $feedback['journal_id']) : '#' }}" class="entry-list-item">
                    <div class="row-top">
                        <div>
                            <span class="badge violet">{{ $groups->get($feedback['group_id'])['name'] ?? $feedback['group_id'] }} &middot; {{ $feedback['feedback_type'] }}</span>
                            <p style="margin: 10px 0 0; color: var(--ink-2);">{{ $feedback['comment'] }}</p>
                            @if ($journal)
                                <p class="row-subtitle">Jurnal pertemuan {{ $journal['meeting_no'] }} &middot; {{ $journal['journal_date'] }}</p>
                            @endif
                        </div>
                        <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                            <span class="badge warn">{{ $feedback['priority'] }}</span>
                            <span class="badge">{{ $feedback['revision_status'] }}</span>
                        </div>
                    </div>
                </a>
            @empty
                <p style="padding: 18px; color: var(--muted);">Belum ada feedback.</p>
            @endforelse
        </div>
    </section>
</x-layouts.app>
