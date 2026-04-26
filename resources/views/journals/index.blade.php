<x-layouts.app title="Journal">
    <div class="page-head">
        <div>
            <p class="page-kicker">Journal</p>
            <h1 class="page-title serif">Catatan progress</h1>
            <p class="page-subtitle">Jurnal tiap pertemuan, lengkap dengan status bantuan.</p>
        </div>
        <a href="{{ route('journals.create') }}" class="btn">Isi Jurnal</a>
    </div>

    <section class="card">
        <div class="list-divide">
            @forelse ($journals as $journal)
                <a href="{{ route('journals.show', $journal['id']) }}" class="entry-list-item">
                    <div class="row-top">
                        <div>
                            <span class="badge accent">{{ $groups->get($journal['group_id'])['name'] ?? $journal['group_id'] }} &middot; Pertemuan {{ $journal['meeting_no'] }}</span>
                            <h2 style="margin: 9px 0 0; font-size: 15px; font-weight: 650;">{{ $journal['progress_today'] }}</h2>
                            <p class="row-subtitle">{{ $journal['journal_date'] ?? '' }}</p>
                        </div>
                        @if ($journal['help_request'] ?? false)
                            <span class="badge err">Butuh bantuan</span>
                        @endif
                    </div>
                </a>
            @empty
                <p style="padding: 18px; color: var(--muted);">Belum ada jurnal.</p>
            @endforelse
        </div>
    </section>
</x-layouts.app>
