<x-layouts.app title="Detail Jurnal">
    <div class="dashboard-grid">
        <article class="card form-card">
            <div class="row-top">
                <div>
                    <span class="badge accent">{{ ($group['name'] ?? null) ?: ($journal['group_id'] ?? '-') }}</span>
                    <h1 class="page-title serif" style="margin-top: 10px;">Jurnal Pertemuan {{ $journal['meeting_no'] ?? '-' }}</h1>
                    <p class="row-subtitle" style="margin-top: 6px;">Tanggal pertemuan: {{ $journal['journal_date'] ?? '-' }}</p>
                </div>
                <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                    @if ($journal['help_request'] ?? false)
                        <span class="badge err">Butuh bantuan</span>
                    @endif
                    <a href="{{ route('journals.edit', $journal['id']) }}" class="btn secondary" style="padding: 8px 12px;">Edit Jurnal</a>
                </div>
            </div>

            <dl class="form-grid" style="margin-top: 22px;">
                @foreach ([
                    'target_vs_realization' => 'Target vs realisasi',
                    'progress_today' => 'Progress pertemuan ini',
                    'data_result' => 'Data atau hasil',
                    'problem' => 'Kendala',
                    'solution_next_step' => 'Solusi dan next step',
                    'insight' => 'Insight',
                ] as $field => $label)
                    <div>
                        <dt class="card-title">{{ $label }}</dt>
                        <dd style="margin: 5px 0 0; color: var(--ink-2); white-space: pre-line;">{{ $journal[$field] ?? '-' }}</dd>
                    </div>
                @endforeach
            </dl>

            <section style="margin-top: 22px; padding-top: 18px; border-top: 1px solid var(--border);">
                <h2 class="card-title">Checklist target</h2>
                <div class="form-grid" style="gap: 8px; margin-top: 12px;">
                    @forelse (($journal['target_checklist'] ?? []) as $targetId => $checked)
                        @php $target = $targets->get($targetId); @endphp
                        <div class="check-card">
                            <span class="check-box {{ $checked ? 'done' : '' }}">{!! $checked ? '&#10003;' : '' !!}</span>
                            <span style="display: block; width: 100%;">
                                @if (is_array($target))
                                    <span style="display: flex; justify-content: space-between; gap: 10px; align-items: flex-start; flex-wrap: wrap;">
                                        <span>
                                            @if (($target['mode'] ?? 'pertemuan') === 'mingguan')
                                                <span class="badge accent">Minggu {{ $target['week_no'] ?? '-' }}</span>
                                                @if (! empty($target['week_start']) && ! empty($target['week_end']))
                                                    <span class="badge" style="margin-left: 6px;">{{ \Carbon\Carbon::parse($target['week_start'])->format('d M') }} &ndash; {{ \Carbon\Carbon::parse($target['week_end'])->format('d M Y') }}</span>
                                                @endif
                                            @else
                                                <span class="badge accent">Pertemuan {{ $target['meeting_no'] ?? '-' }}</span>
                                                @if (! empty($target['target_date']))
                                                    <span class="badge" style="margin-left: 6px;">{{ \Carbon\Carbon::parse($target['target_date'])->format('d M Y') }}</span>
                                                @endif
                                            @endif
                                        </span>
                                        <span class="badge {{ ($target['status'] ?? 'active') === 'active' ? 'ok' : '' }}">{{ $target['status'] ?? 'active' }}</span>
                                    </span>
                                    <strong style="display: block; margin-top: 10px;">{{ $target['title'] ?? $targetId }}</strong>
                                    @if (! empty($target['description']))
                                        <span class="muted" style="display: block; margin-top: 4px;">{{ $target['description'] }}</span>
                                    @endif
                                    @if (! empty($target['checklist_items']))
                                        <span class="form-grid" style="display: grid; gap: 8px; margin-top: 12px;">
                                            @foreach ($target['checklist_items'] as $item)
                                                <span style="display: flex; gap: 8px; align-items: flex-start;">
                                                    <span class="check-box" style="margin-top: 1px;"></span>
                                                    <span>{{ $item }}</span>
                                                </span>
                                            @endforeach
                                        </span>
                                    @endif
                                @else
                                    <strong>{{ $targetId }}</strong>
                                @endif
                            </span>
                        </div>
                    @empty
                        <p class="muted">Tidak ada target yang dicentang.</p>
                    @endforelse
                </div>
            </section>
        </article>

        <aside class="section-grid">
            <section class="card">
                <div class="card-head">
                    <h2 class="card-title">Dokumentasi</h2>
                </div>
                @php
                    $images = $documentations->where('file_type', 'foto')->values();
                    $others = $documentations->where('file_type', '!=', 'foto')->values();
                @endphp

                @if ($images->isNotEmpty())
                    <div class="doc-slider" data-slider>
                        <div class="doc-slider-track" data-slider-track>
                            @foreach ($images as $img)
                                @php $imageUrl = route('documentations.show', $img['id']); @endphp
                                <div class="doc-slide">
                                    <a href="{{ $imageUrl }}" target="_blank">
                                        <img src="{{ $imageUrl }}" alt="{{ $img['file_name'] ?? 'Dokumentasi' }}" loading="lazy">
                                    </a>
                                </div>
                            @endforeach
                        </div>
                        @if ($images->count() > 1)
                            <button type="button" class="doc-slider-btn prev" data-slider-prev aria-label="Sebelumnya">&lsaquo;</button>
                            <button type="button" class="doc-slider-btn next" data-slider-next aria-label="Selanjutnya">&rsaquo;</button>
                            <div class="doc-slider-dots" data-slider-dots>
                                @foreach ($images as $i => $img)
                                    <button type="button" data-slider-dot="{{ $i }}" class="{{ $i === 0 ? 'active' : '' }}"></button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                @if ($others->isNotEmpty() || $images->isEmpty())
                    <div class="list-divide">
                        @forelse ($others as $documentation)
                            @php $documentationUrl = route('documentations.show', $documentation['id']); @endphp
                            <a href="{{ $documentationUrl }}" target="_blank" class="entry-list-item">
                                <strong>{{ $documentation['file_name'] ?? 'Dokumentasi' }}</strong>
                                <span class="row-subtitle" style="display: block;">{{ $documentation['file_type'] ?? 'file' }}</span>
                            </a>
                        @empty
                            @if ($images->isEmpty())
                                <p style="padding: 18px; color: var(--muted);">Belum ada dokumentasi.</p>
                            @endif
                        @endforelse
                    </div>
                @endif
            </section>

            <section class="card">
                <div class="card-head">
                    <h2 class="card-title">Feedback Guru</h2>
                </div>
                <div class="list-divide">
                    @forelse ($feedbacks as $feedback)
                        <div class="group-row">
                            <div style="display: flex; justify-content: space-between; gap: 10px;">
                                <span class="badge violet">{{ $feedback['feedback_type'] ?? 'Feedback' }}</span>
                                <span class="badge warn">{{ $feedback['priority'] ?? 'sedang' }}</span>
                            </div>
                            <p style="margin: 12px 0 0; color: var(--ink-2);">{{ $feedback['comment'] ?? 'Komentar belum tersedia' }}</p>
                            <p class="row-subtitle">Status revisi: {{ $feedback['revision_status'] ?? 'baru' }}</p>
                        </div>
                    @empty
                        <p style="padding: 18px; color: var(--muted);">Belum ada feedback.</p>
                    @endforelse
                </div>

                @if (current_user()->isTeacher())
                    <form action="{{ route('feedback.store') }}" method="POST" class="form-card" style="border-top: 1px solid var(--border);">
                        @csrf
                        <input type="hidden" name="journal_id" value="{{ $journal['id'] }}">
                        <div class="field">
                            <label for="comment">Feedback</label>
                            <textarea class="input" id="comment" name="comment" rows="4" required placeholder="Tulis feedback...">{{ old('comment') }}</textarea>
                        </div>
                        <div class="grid-3">
                            <select name="feedback_type" required class="input">
                                <option value="data testing">Data testing</option>
                                <option value="desain">Desain</option>
                                <option value="coding">Coding</option>
                                <option value="presentasi">Presentasi</option>
                            </select>
                            <select name="priority" required class="input">
                                <option value="sedang">Sedang</option>
                                <option value="rendah">Rendah</option>
                                <option value="tinggi">Tinggi</option>
                            </select>
                            <select name="revision_status" required class="input">
                                <option value="baru">Baru</option>
                                <option value="revisi">Revisi</option>
                                <option value="selesai">Selesai</option>
                            </select>
                        </div>
                        <button type="submit" class="btn" style="width: 100%; margin-top: 12px;">Kirim Feedback</button>
                    </form>
                @endif
            </section>
        </aside>
    </div>

    <script>
        document.querySelectorAll('[data-slider]').forEach((slider) => {
            const track = slider.querySelector('[data-slider-track]');
            const slides = track.children;
            const dots = slider.querySelectorAll('[data-slider-dot]');
            let index = 0;

            const goTo = (i) => {
                index = (i + slides.length) % slides.length;
                track.style.transform = `translateX(-${index * 100}%)`;
                dots.forEach((d, di) => d.classList.toggle('active', di === index));
            };

            slider.querySelector('[data-slider-prev]')?.addEventListener('click', () => goTo(index - 1));
            slider.querySelector('[data-slider-next]')?.addEventListener('click', () => goTo(index + 1));
            dots.forEach((d, i) => d.addEventListener('click', () => goTo(i)));
        });
    </script>
</x-layouts.app>
