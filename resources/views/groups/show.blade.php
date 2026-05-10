<x-layouts.app title="Profil Kelompok">
    <div class="page-head">
        <div>
            <p class="page-kicker">Kelompok</p>
            <h1 class="page-title serif">{{ $group['name'] ?? $group['id'] }}</h1>
            <p class="page-subtitle">Profil progres, jurnal, dokumentasi, dan feedback kelompok.</p>
        </div>
        @if (current_user()->isTeacher())
            <a href="{{ route('groups.index') }}" class="btn secondary">Kelola Kelompok</a>
        @endif
    </div>

    <section class="stat-grid">
        <div class="card stat-card">
            <div class="stat-value">{{ $members->count() }}</div>
            <div class="stat-label">Anggota</div>
        </div>
        <div class="card stat-card">
            <div class="stat-value">{{ $progress }}%</div>
            <div class="stat-label">Progress target</div>
        </div>
        <div class="card stat-card">
            <div class="stat-value">{{ $journals->count() }}</div>
            <div class="stat-label">Jurnal</div>
        </div>
        <div class="card stat-card">
            <div class="stat-value">{{ $documentations->count() }}</div>
            <div class="stat-label">Dokumentasi</div>
        </div>
        <div class="card stat-card">
            <div class="stat-value">{{ $feedbacks->where('revision_status', '!=', 'selesai')->count() }}</div>
            <div class="stat-label">Revisi terbuka</div>
        </div>
        <div class="card stat-card">
            <div class="stat-value">{{ $journals->where('help_request', true)->count() }}</div>
            <div class="stat-label">Help request</div>
        </div>
    </section>

    <section class="dashboard-grid">
        <div class="section-grid">
            <div class="card">
                <div class="card-head">
                    <h2 class="card-title">Progress Target</h2>
                </div>
                <div class="group-row">
                    <div class="target-progress-head">
                        <span>{{ $checkedTargets }} dari {{ $targets->count() }} target selesai penuh</span>
                        <span class="mono">{{ $progress }}%</span>
                    </div>
                    <div class="progress-line"><span style="width: {{ $progress }}%"></span></div>
                </div>
                <div class="list-divide">
                    @forelse ($targetProgress as $item)
                        <div class="group-row">
                            <div class="row-top">
                                <div>
                                    <h3 class="row-title">{{ $item['target']['title'] ?? 'Target belum berjudul' }}</h3>
                                    <p class="row-subtitle">{{ $item['checked'] }} dari {{ $item['total'] }} checklist selesai</p>
                                </div>
                                <span class="badge {{ $item['complete'] ? 'ok' : 'warn' }}">{{ $item['complete'] ? 'selesai' : 'proses' }}</span>
                            </div>
                        </div>
                    @empty
                        <p style="padding: 18px; color: var(--muted);">Belum ada target aktif.</p>
                    @endforelse
                </div>
            </div>

            <div class="card">
                <div class="card-head">
                    <h2 class="card-title">Jurnal Terbaru</h2>
                </div>
                <div class="list-divide">
                    @forelse ($journals->take(6) as $journal)
                        <a class="entry-list-item" href="{{ route('journals.show', $journal['id']) }}">
                            <span class="badge accent">Pertemuan {{ $journal['meeting_no'] ?? '-' }}</span>
                            @if ($journal['help_request'] ?? false)
                                <span class="badge err" style="margin-left: 6px;">Butuh bantuan</span>
                            @endif
                            <strong style="display: block; margin-top: 8px;">{{ $journal['journal_date'] ?? '-' }}</strong>
                            <span class="row-subtitle" style="display: block;">{{ $journal['progress_today'] ?? 'Catatan belum lengkap' }}</span>
                        </a>
                    @empty
                        <p style="padding: 18px; color: var(--muted);">Belum ada jurnal.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <aside class="section-grid">
            <div class="card">
                <div class="card-head">
                    <h2 class="card-title">Anggota</h2>
                </div>
                <div class="list-divide">
                    @forelse ($members as $member)
                        <div class="group-row">
                            <h3 class="row-title">{{ $member['name'] ?? '-' }}</h3>
                            <p class="row-subtitle">{{ $member['id'] ?? '' }}</p>
                        </div>
                    @empty
                        <p style="padding: 18px; color: var(--muted);">Belum ada anggota.</p>
                    @endforelse
                </div>
            </div>

            <div class="card">
                <div class="card-head">
                    <h2 class="card-title">Feedback Terbaru</h2>
                </div>
                <div class="list-divide">
                    @forelse ($feedbacks->take(5) as $feedback)
                        <div class="group-row">
                            <span class="badge warn">{{ $feedback['priority'] ?? 'sedang' }}</span>
                            <p style="margin: 8px 0 0;">{{ $feedback['comment'] ?? '-' }}</p>
                            <p class="row-subtitle">Status: {{ $feedback['revision_status'] ?? 'baru' }}</p>
                        </div>
                    @empty
                        <p style="padding: 18px; color: var(--muted);">Belum ada feedback.</p>
                    @endforelse
                </div>
            </div>
        </aside>
    </section>
</x-layouts.app>
