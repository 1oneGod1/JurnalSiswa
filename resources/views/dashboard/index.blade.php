<x-layouts.app title="Dashboard">
    <div class="page-head">
        <div>
            <p class="page-kicker">Dashboard</p>
            <h1 class="page-title serif">Ringkasan progress</h1>
            <p class="page-subtitle">Pantau kelompok, jurnal terakhir, help request, dan kesiapan expo.</p>
        </div>
        <a href="{{ route('journals.create') }}" class="btn">
            Jurnal Baru
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="m13 6 6 6-6 6"></path></svg>
        </a>
    </div>

    <section class="stat-grid">
        @foreach ([
            ['label' => 'Kelompok', 'value' => $stats['groups'], 'tone' => 'accent', 'icon' => 'M4 7h16 M4 12h16 M4 17h16'],
            ['label' => 'Target aktif', 'value' => $stats['targets'], 'tone' => 'violet', 'icon' => 'M12 4a8 8 0 1 0 0 16 8 8 0 0 0 0-16 M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8'],
            ['label' => 'Jurnal', 'value' => $stats['journals'], 'tone' => '', 'icon' => 'M5 4h10a3 3 0 0 1 3 3v13H8a3 3 0 0 1-3-3V4Z M5 17a3 3 0 0 1 3-3h10'],
            ['label' => 'Help request', 'value' => $stats['help_requests'], 'tone' => 'warn', 'icon' => 'M12 9v4 M12 17h.01 M10.3 4.9 2.8 18a1.5 1.5 0 0 0 1.3 2.2h15.8a1.5 1.5 0 0 0 1.3-2.2L13.7 4.9a1.5 1.5 0 0 0-2.6 0Z'],
            ['label' => 'Progress rata-rata', 'value' => $stats['average_progress'].'%', 'tone' => 'ok', 'icon' => 'M4 19V5 M4 19h16 M8 15l3-3 3 2 5-6'],
            ['label' => 'Expo readiness', 'value' => $stats['average_readiness'].'%', 'tone' => 'accent', 'icon' => 'M12 3v5 M12 16v5 M3 12h5 M16 12h5 M6 6l2 2 M16 16l2 2 M6 18l2-2 M16 8l2-2'],
        ] as $stat)
            <div class="card stat-card">
                <div class="stat-icon {{ $stat['tone'] }}">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                        @foreach (explode(' M', $stat['icon']) as $path)
                            <path d="{{ str_starts_with($path, 'M') ? $path : 'M'.$path }}"></path>
                        @endforeach
                    </svg>
                </div>
                <div class="stat-value">{{ $stat['value'] }}</div>
                <div class="stat-label">{{ $stat['label'] }}</div>
            </div>
        @endforeach
    </section>

    <section class="dashboard-grid">
        <div class="card">
            <div class="card-head">
                <div>
                    <h2 class="card-title">Progress Kelompok</h2>
                    <p class="page-subtitle">Ringkasan target, dokumentasi, dan kebutuhan bantuan.</p>
                </div>
            </div>
            <div class="list-divide">
                @forelse ($groups as $summary)
                    <div class="group-row">
                        <div class="row-top">
                            <div>
                                <h3 class="row-title">{{ $summary['group']['name'] ?? $summary['group']['id'] }}</h3>
                                <p class="row-subtitle">{{ $summary['group']['project_title'] ?? 'Judul projek belum diisi' }}</p>
                            </div>
                            <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                @if ($summary['help_requests'] > 0)
                                    <span class="badge err">Butuh bantuan</span>
                                @endif
                                @if ($summary['open_feedback_count'] > 0)
                                    <span class="badge warn">Ada revisi</span>
                                @else
                                    <span class="badge ok">Stabil</span>
                                @endif
                            </div>
                        </div>

                        <div class="metric-grid">
                            <div>
                                <div class="metric-label">Progress</div>
                                <div style="display: flex; align-items: center; gap: 10px; margin-top: 8px;">
                                    <div class="progress-line" style="flex: 1;"><span style="width: {{ $summary['progress'] }}%"></span></div>
                                    <span class="mono" style="color: var(--muted); font-size: 11.5px;">{{ $summary['progress'] }}%</span>
                                </div>
                            </div>
                            <div>
                                <div class="metric-label">Dokumentasi</div>
                                <div class="metric-value">{{ $summary['documentation_count'] }} file</div>
                            </div>
                            <div>
                                <div class="metric-label">Expo readiness</div>
                                <div class="metric-value">{{ $summary['readiness_score'] }}%</div>
                            </div>
                        </div>

                        @if ($summary['latest_journal'])
                            <a href="{{ route('journals.show', $summary['latest_journal']['id']) }}" class="soft-link">
                                Jurnal terakhir: {{ $summary['latest_journal']['progress_today'] }}
                            </a>
                        @endif
                    </div>
                @empty
                    <p style="padding: 18px; color: var(--muted);">Belum ada kelompok.</p>
                @endforelse
            </div>
        </div>

        <div class="section-grid">
            <div class="card">
                <div class="card-head">
                    <h2 class="card-title">Target Aktif</h2>
                </div>
                <div class="list-divide">
                    @forelse ($targets as $target)
                        <div style="padding: 14px 16px;">
                            <span class="badge accent">Pertemuan {{ $target['meeting_no'] ?? '-' }}</span>
                            <p style="margin: 8px 0 0; font-weight: 650;">{{ $target['title'] }}</p>
                        </div>
                    @empty
                        <p style="padding: 16px; color: var(--muted);">Belum ada target aktif.</p>
                    @endforelse
                </div>
            </div>

            <div class="card">
                <div class="card-head">
                    <h2 class="card-title">Help Request</h2>
                </div>
                <div class="list-divide">
                    @forelse ($helpRequests as $journal)
                        <a href="{{ route('journals.show', $journal['id']) }}" style="display: block; padding: 14px 16px; text-decoration: none;">
                            <span class="badge warn">Pertemuan {{ $journal['meeting_no'] }}</span>
                            <p style="margin: 8px 0 0; color: var(--ink-2); font-size: 13px;">{{ $journal['problem'] ?: $journal['progress_today'] }}</p>
                        </a>
                    @empty
                        <p style="padding: 16px; color: var(--muted);">Tidak ada help request.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>
