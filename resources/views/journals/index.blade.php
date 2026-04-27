<x-layouts.app title="Journal">
    <div class="page-head">
        <div>
            <p class="page-kicker">Journal</p>
            <h1 class="page-title serif">Catatan progress</h1>
            <p class="page-subtitle">Jurnal harian lengkap dengan status bantuan.</p>
        </div>
        <a href="{{ route('journals.create') }}" class="btn">Isi Jurnal</a>
    </div>

    <section class="card">
        <div class="list-divide">
            @forelse ($journals as $journal)
                <div class="entry-list-item" style="display: flex; align-items: flex-start; gap: 12px;">
                    <a href="{{ route('journals.show', $journal['id']) }}" style="flex: 1; min-width: 0; color: inherit; text-decoration: none;">
                        <div class="row-top">
                            <div>
                                <span class="badge accent">{{ $groups->get($journal['group_id'] ?? null)['name'] ?? ($journal['group_id'] ?? '-') }} &middot; Pertemuan {{ $journal['meeting_no'] ?? '-' }}</span>
                                <h2 style="margin: 9px 0 0; font-size: 15px; font-weight: 650;">Jurnal {{ $journal['journal_date'] ?? '-' }}</h2>
                                <p class="row-subtitle">Pertemuan {{ $journal['meeting_no'] ?? '-' }}</p>
                                <p style="margin: 8px 0 0; color: var(--ink-2); font-size: 13px;">{{ $journal['progress_today'] ?? 'Catatan belum lengkap' }}</p>
                            </div>
                            @if ($journal['help_request'] ?? false)
                                <span class="badge err">Butuh bantuan</span>
                            @endif
                        </div>
                    </a>
                    <form action="{{ route('journals.destroy', $journal['id']) }}" method="POST" onsubmit="return confirm('Hapus jurnal ini? Semua dokumentasi & feedback ikut terhapus.');" style="margin: 0; flex: none;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-ghost" style="padding: 6px 10px; font-size: 12px; color: var(--err);">Hapus</button>
                    </form>
                </div>
            @empty
                <p style="padding: 18px; color: var(--muted);">Belum ada jurnal.</p>
            @endforelse
        </div>
    </section>
</x-layouts.app>
