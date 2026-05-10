<x-layouts.app title="Targets">
    @php
        $targetEvents = $targets
            ->filter(fn ($target) => ($target['status'] ?? 'active') === 'active')
            ->map(function ($target) {
                $mode = $target['mode'] ?? 'pertemuan';
                $startDate = $mode === 'mingguan'
                    ? ($target['week_start'] ?? null)
                    : ($target['target_date'] ?? null);
                $endDate = $mode === 'mingguan'
                    ? ($target['week_end'] ?? $startDate)
                    : $startDate;

                if (empty($startDate)) {
                    return null;
                }

                return [
                    'id' => $target['id'] ?? md5(($target['title'] ?? '').$startDate),
                    'title' => $target['title'] ?? 'Target belum berjudul',
                    'date' => $startDate,
                    'endDate' => $endDate,
                    'type' => 'target',
                    'label' => $mode === 'mingguan'
                        ? 'Minggu '.($target['week_no'] ?? '-')
                        : 'Pertemuan '.($target['meeting_no'] ?? '-'),
                    'description' => $target['description'] ?? null,
                    'items' => $target['checklist_items'] ?? [],
                    'status' => $target['computed_status'] ?? ($target['status'] ?? 'active'),
                    'progress' => $target['progress_percent'] ?? 0,
                ];
            })
            ->filter()
            ->values();

        $scheduleEvents = $schedules
            ->map(fn ($schedule) => [
                'id' => $schedule['id'],
                'title' => $schedule['title'] ?? 'Jadwal penting',
                'date' => $schedule['date'] ?? null,
                'endDate' => $schedule['date'] ?? null,
                'type' => $schedule['type'] ?? 'penting',
                'label' => ($schedule['type'] ?? 'penting') === 'target-pribadi' ? 'Target pribadi' : 'Jadwal penting',
                'note' => $schedule['note'] ?? null,
                'deleteUrl' => route('targets.schedules.destroy', $schedule['id']),
                'canDelete' => (bool) ($schedule['can_delete'] ?? false),
                'scopeLabel' => $schedule['scope_label'] ?? 'Pribadi',
            ])
            ->filter(fn ($schedule) => ! empty($schedule['date']))
            ->values();
    @endphp

    <div class="page-head">
        <div>
            <p class="page-kicker">Targets</p>
            <h1 class="page-title serif">Target pertemuan</h1>
            <p class="page-subtitle">Guru membuat target, siswa melihatnya sebagai checklist jurnal dan jadwal kerja.</p>
        </div>
    </div>

    <div class="target-grid {{ current_user()->isTeacher() ? 'target-grid-teacher' : '' }}">
        @if (current_user()->isTeacher())
            <section class="card form-card">
                <h2 class="card-title">Buat target</h2>
                <form action="{{ route('targets.store') }}" method="POST" class="form-grid" style="margin-top: 18px;">
                    @csrf
                    <div class="field">
                        <label for="title">Judul target</label>
                        <input class="input" id="title" name="title" value="{{ old('title') }}" required>
                    </div>
                    <div class="field">
                        <label for="mode">Tipe target</label>
                        <select class="input" id="mode" name="mode" required onchange="toggleTargetMode(this.value)">
                            <option value="pertemuan" {{ old('mode', 'pertemuan') === 'pertemuan' ? 'selected' : '' }}>Per Pertemuan</option>
                            <option value="mingguan" {{ old('mode') === 'mingguan' ? 'selected' : '' }}>Mingguan</option>
                        </select>
                    </div>
                    <div class="grid-2" id="mode-pertemuan" style="{{ old('mode', 'pertemuan') === 'mingguan' ? 'display:none;' : '' }}">
                        <div class="field">
                            <label for="meeting_no">Pertemuan ke</label>
                            <input class="input" id="meeting_no" name="meeting_no" type="number" min="1" value="{{ old('meeting_no', 1) }}">
                        </div>
                        <div class="field">
                            <label for="target_date">Tanggal target</label>
                            <input class="input" id="target_date" name="target_date" type="date" value="{{ old('target_date') }}">
                        </div>
                    </div>
                    <div id="mode-mingguan" style="{{ old('mode') === 'mingguan' ? '' : 'display:none;' }}">
                        <div class="field">
                            <label for="week_no">Minggu ke</label>
                            <input class="input" id="week_no" name="week_no" type="number" min="1" max="52" value="{{ old('week_no', 1) }}">
                        </div>
                        <div class="grid-2">
                            <div class="field">
                                <label for="week_start">Mulai</label>
                                <input class="input" id="week_start" name="week_start" type="date" value="{{ old('week_start') }}">
                            </div>
                            <div class="field">
                                <label for="week_end">Selesai</label>
                                <input class="input" id="week_end" name="week_end" type="date" value="{{ old('week_end') }}">
                            </div>
                        </div>
                    </div>
                    <div class="field">
                        <label for="description">Deskripsi</label>
                        <textarea class="input" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                    </div>
                    <div class="field">
                        <label for="checklist_items">Checklist siswa</label>
                        <textarea class="input" id="checklist_items" name="checklist_items" rows="5" placeholder="Satu item per baris">{{ old('checklist_items') }}</textarea>
                    </div>
                    <button type="submit" class="btn">Simpan Target</button>
                </form>
            </section>
        @endif

        <section class="card">
            <div class="card-head">
                <div>
                    <h2 class="card-title">Daftar target</h2>
                    <p class="page-subtitle">{{ $targets->count() }} dari {{ $targetCounts['all'] ?? $targets->count() }} target ditampilkan.</p>
                </div>
            </div>
            <form action="{{ route('targets.index') }}" method="GET" class="target-filter-bar">
                <div class="field">
                    <label for="status">Status</label>
                    <select class="input" id="status" name="status">
                        @foreach (['all' => 'Semua', 'belum mulai' => 'Belum mulai', 'proses' => 'Proses', 'selesai' => 'Selesai', 'terlambat' => 'Terlambat'] as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['status'] ?? 'all') === $value)>{{ $label }} @if(isset($targetCounts[$value])) ({{ $targetCounts[$value] }}) @endif</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="mode_filter">Tipe</label>
                    <select class="input" id="mode_filter" name="mode">
                        <option value="all" @selected(($filters['mode'] ?? 'all') === 'all')>Semua tipe</option>
                        <option value="pertemuan" @selected(($filters['mode'] ?? 'all') === 'pertemuan')>Pertemuan</option>
                        <option value="mingguan" @selected(($filters['mode'] ?? 'all') === 'mingguan')>Mingguan</option>
                    </select>
                </div>
                <div class="field">
                    <label for="q">Cari</label>
                    <input class="input" id="q" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Judul atau deskripsi">
                </div>
                <div class="target-filter-actions">
                    <button class="btn secondary" type="submit">Terapkan</button>
                    <a class="btn ghost" href="{{ route('targets.index') }}">Reset</a>
                </div>
            </form>
            <div class="list-divide">
                @forelse ($targets as $target)
                    <article class="group-row">
                        @php
                            $computedStatus = $target['computed_status'] ?? ($target['status'] ?? 'active');
                            $statusClass = match ($computedStatus) {
                                'selesai' => 'ok',
                                'terlambat' => 'err',
                                'proses' => 'warn',
                                'diarsipkan' => '',
                                default => 'violet',
                            };
                        @endphp
                        <div class="row-top">
                            <div>
                                @if (($target['mode'] ?? 'pertemuan') === 'mingguan')
                                    <span class="badge accent">Minggu {{ $target['week_no'] ?? '-' }}</span>
                                    @if (! empty($target['week_start']) && ! empty($target['week_end']))
                                        <span class="badge" style="margin-left: 6px;">{{ \Carbon\Carbon::parse($target['week_start'])->format('d M') }} – {{ \Carbon\Carbon::parse($target['week_end'])->format('d M Y') }}</span>
                                    @endif
                                @else
                                    <span class="badge accent">Pertemuan {{ $target['meeting_no'] ?? '-' }}</span>
                                    @if (! empty($target['target_date']))
                                        <span class="badge" style="margin-left: 6px;">{{ \Carbon\Carbon::parse($target['target_date'])->format('d M Y') }}</span>
                                    @endif
                                @endif
                                <h3 style="margin: 10px 0 0; font-size: 18px; font-weight: 650;">{{ $target['title'] ?? 'Target belum berjudul' }}</h3>
                                @if (! empty($target['description']))
                                    <p class="page-subtitle">{{ $target['description'] }}</p>
                                @endif
                            </div>
                            <span class="badge {{ $statusClass }}">{{ $computedStatus }}</span>
                        </div>

                        @if (! empty($target['checklist_items']))
                            <form action="{{ route('targets.checklist', $target['id']) }}" method="POST" class="target-checklist-form">
                                @csrf
                                <div class="target-progress-head">
                                    <span>{{ $target['completed_count'] ?? 0 }} dari {{ $target['total_count'] ?? count($target['checklist_items']) }} selesai</span>
                                    <span class="mono">{{ $target['progress_percent'] ?? 0 }}%</span>
                                </div>
                                <div class="progress-line"><span style="width: {{ $target['progress_percent'] ?? 0 }}%"></span></div>
                                <div class="form-grid" style="gap: 8px; margin-top: 12px;">
                                    @foreach ($target['checklist_items'] as $index => $item)
                                        @php $isChecked = in_array((string) $index, $target['checked_items'] ?? [], true); @endphp
                                        <label class="check-card target-check-card {{ $isChecked ? 'is-checked' : '' }}">
                                            <input
                                                type="checkbox"
                                                name="checklist[]"
                                                value="{{ $index }}"
                                                {{ $isChecked ? 'checked' : '' }}
                                                {{ current_user()->isTeacher() ? 'disabled' : '' }}
                                            >
                                            <span class="check-box {{ $isChecked ? 'done' : '' }}">
                                                @if ($isChecked)
                                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 4 4L19 6"></path></svg>
                                                @endif
                                            </span>
                                            <span>{{ $item }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                @if (current_user()->isStudent())
                                    <button type="submit" class="btn secondary target-save-btn">Simpan Checklist</button>
                                @endif
                            </form>
                        @endif

                        @if (current_user()->isTeacher())
                            <div style="margin-top: 14px; display: flex; gap: 12px; align-items: center;">
                                @if (($target['status'] ?? 'active') === 'active')
                                    <form action="{{ route('targets.archive', $target['id']) }}" method="POST" style="margin: 0;">
                                        @csrf
                                        <button class="danger-link" type="submit">Arsipkan</button>
                                    </form>
                                @endif
                                <form action="{{ route('targets.destroy', $target['id']) }}" method="POST" style="margin: 0;" onsubmit="return confirm('Hapus permanen target ini? Aksi ini tidak bisa dibatalkan.');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="danger-link" type="submit" style="color: var(--err);">Hapus permanen</button>
                                </form>
                            </div>
                        @endif
                    </article>
                @empty
                    <p style="padding: 18px; color: var(--muted);">Belum ada target pertemuan.</p>
                @endforelse
            </div>
        </section>

        <aside class="calendar-panel">
            <section class="card">
                <div class="card-head">
                    <div>
                        <h2 class="card-title">Kalender target</h2>
                        <p class="page-subtitle">Lihat tanggal penting, deadline, dan target pribadi.</p>
                    </div>
                </div>

                <div class="calendar-wrap">
                    <div class="calendar-toolbar">
                        <button class="icon-btn" type="button" data-calendar-prev aria-label="Bulan sebelumnya">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"></path></svg>
                        </button>
                        <div>
                            <h3 id="calendar-title" class="calendar-title">Kalender</h3>
                            <p class="calendar-subtitle">Klik tanggal untuk melihat detail.</p>
                        </div>
                        <button class="icon-btn" type="button" data-calendar-next aria-label="Bulan berikutnya">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"></path></svg>
                        </button>
                    </div>

                    <div class="calendar-weekdays" aria-hidden="true">
                        <span>Min</span>
                        <span>Sen</span>
                        <span>Sel</span>
                        <span>Rab</span>
                        <span>Kam</span>
                        <span>Jum</span>
                        <span>Sab</span>
                    </div>
                    <div id="target-calendar" class="calendar-grid" aria-label="Kalender target siswa"></div>

                    <div class="schedule-detail">
                        <div class="row-top">
                            <div>
                                <h3 id="selected-date-title" class="row-title">Detail jadwal</h3>
                                <p id="selected-date-subtitle" class="row-subtitle">Pilih tanggal pada kalender.</p>
                            </div>
                        </div>
                        <div id="selected-events" class="schedule-list"></div>
                    </div>
                </div>
            </section>

            <section class="card">
                <div class="card-head">
                    <div>
                        <h2 class="card-title">Tambah jadwal penting</h2>
                        <p class="page-subtitle">{{ current_user()->isTeacher() ? 'Jadwal dari guru tampil di seluruh akun.' : 'Jadwal siswa tersimpan sebagai catatan pribadi.' }}</p>
                    </div>
                </div>
                <form id="schedule-form" class="calendar-wrap" action="{{ route('targets.schedules.store') }}" method="POST">
                    @csrf
                    <div class="field">
                        <label for="schedule_title">Judul jadwal</label>
                        <input class="input" id="schedule_title" name="title" placeholder="Contoh: Kumpul laporan akhir" required>
                    </div>
                    <div class="grid-2">
                        <div class="field">
                            <label for="schedule_date">Tanggal</label>
                            <input class="input" id="schedule_date" name="date" type="date" required>
                        </div>
                        <div class="field">
                            <label for="schedule_type">Jenis</label>
                            <select class="input" id="schedule_type" name="type">
                                <option value="penting">Jadwal penting</option>
                                <option value="target-pribadi">Target pribadi</option>
                            </select>
                        </div>
                    </div>
                    <div class="field">
                        <label for="schedule_note">Catatan</label>
                        <textarea class="input" id="schedule_note" name="note" rows="3" placeholder="Detail singkat atau target yang ingin dicapai"></textarea>
                    </div>
                    <button type="submit" class="btn" style="width: 100%;">Tambahkan ke Kalender</button>
                </form>
            </section>
        </aside>
    </div>

    <script>
        function toggleTargetMode(mode) {
            document.getElementById('mode-pertemuan').style.display = mode === 'pertemuan' ? '' : 'none';
            document.getElementById('mode-mingguan').style.display = mode === 'mingguan' ? '' : 'none';
        }

        (() => {
            const targetEvents = @json($targetEvents);
            const scheduleEvents = @json($scheduleEvents);
            const csrfToken = '{{ csrf_token() }}';
            const calendarEl = document.getElementById('target-calendar');
            const titleEl = document.getElementById('calendar-title');
            const selectedTitleEl = document.getElementById('selected-date-title');
            const selectedSubtitleEl = document.getElementById('selected-date-subtitle');
            const selectedEventsEl = document.getElementById('selected-events');
            const scheduleForm = document.getElementById('schedule-form');
            const scheduleDate = document.getElementById('schedule_date');

            if (! calendarEl || ! titleEl || ! scheduleForm) {
                return;
            }

            const firstEventDate = [...targetEvents, ...scheduleEvents].find((event) => event.date)?.date;
            const today = new Date();
            let visibleMonth = firstEventDate ? new Date(`${firstEventDate}T00:00:00`) : new Date(today.getFullYear(), today.getMonth(), 1);
            let selectedDate = firstEventDate || toDateKey(today);

            scheduleDate.value = selectedDate;

            function toDateKey(date) {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');

                return `${year}-${month}-${day}`;
            }

            function escapeHtml(value) {
                return String(value || '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');
            }

            function formatDate(dateKey, options = { day: 'numeric', month: 'long', year: 'numeric' }) {
                return new Intl.DateTimeFormat('id-ID', options).format(new Date(`${dateKey}T00:00:00`));
            }

            function allEvents() {
                return [...targetEvents, ...scheduleEvents];
            }

            function eventsForDate(dateKey) {
                return allEvents().filter((event) => {
                    const endDate = event.endDate || event.date;

                    return dateKey >= event.date && dateKey <= endDate;
                });
            }

            function renderCalendar() {
                const year = visibleMonth.getFullYear();
                const month = visibleMonth.getMonth();
                const monthStart = new Date(year, month, 1);
                const firstCell = new Date(year, month, 1 - monthStart.getDay());

                titleEl.textContent = new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric' }).format(monthStart);
                calendarEl.innerHTML = '';

                for (let index = 0; index < 42; index += 1) {
                    const cellDate = new Date(firstCell);
                    cellDate.setDate(firstCell.getDate() + index);
                    const dateKey = toDateKey(cellDate);
                    const cellEvents = eventsForDate(dateKey);
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = [
                        'calendar-day',
                        cellDate.getMonth() !== month ? 'is-muted' : '',
                        dateKey === toDateKey(today) ? 'is-today' : '',
                        dateKey === selectedDate ? 'is-selected' : '',
                        cellEvents.length ? 'has-event' : '',
                    ].filter(Boolean).join(' ');
                    button.setAttribute('aria-label', `${formatDate(dateKey)} - ${cellEvents.length} jadwal`);
                    button.innerHTML = `
                        <span>${cellDate.getDate()}</span>
                        ${cellEvents.length ? `<small>${cellEvents.length}</small>` : ''}
                    `;
                    button.addEventListener('click', () => {
                        selectedDate = dateKey;
                        scheduleDate.value = selectedDate;
                        renderCalendar();
                        renderSelectedEvents();
                    });
                    calendarEl.appendChild(button);
                }
            }

            function renderSelectedEvents() {
                const selectedEvents = eventsForDate(selectedDate);
                selectedTitleEl.textContent = formatDate(selectedDate);
                selectedSubtitleEl.textContent = selectedEvents.length
                    ? `${selectedEvents.length} jadwal pada tanggal ini`
                    : 'Belum ada jadwal pada tanggal ini.';

                selectedEventsEl.innerHTML = selectedEvents.length
                    ? selectedEvents.map((event) => `
                        <article class="schedule-item ${event.type === 'target' ? 'is-target' : ''}">
                            <div class="schedule-item-head">
                                <span class="badge ${event.type === 'target' ? 'accent' : 'warn'}">${escapeHtml(event.label || event.type)}</span>
                                ${event.type !== 'target' && event.canDelete ? `
                                    <form action="${escapeHtml(event.deleteUrl)}" method="POST" style="margin: 0;">
                                        <input type="hidden" name="_token" value="${escapeHtml(csrfToken)}">
                                        <input type="hidden" name="_method" value="DELETE">
                                        <button type="submit" class="danger-link">Hapus</button>
                                    </form>
                                ` : ''}
                            </div>
                            <h4>${escapeHtml(event.title)}</h4>
                            ${event.scopeLabel ? `<p>${escapeHtml(event.scopeLabel)}</p>` : ''}
                            ${event.status ? `<p>Status: ${escapeHtml(event.status)}${event.progress ? ` (${event.progress}%)` : ''}</p>` : ''}
                            ${event.endDate && event.endDate !== event.date ? `<p>${formatDate(event.date, { day: 'numeric', month: 'short' })} - ${formatDate(event.endDate)}</p>` : ''}
                            ${event.description ? `<p>${escapeHtml(event.description)}</p>` : ''}
                            ${event.note ? `<p>${escapeHtml(event.note)}</p>` : ''}
                            ${event.items?.length ? `<ul>${event.items.map((item) => `<li>${escapeHtml(item)}</li>`).join('')}</ul>` : ''}
                        </article>
                    `).join('')
                    : '<p class="empty-state">Gunakan form di bawah untuk menambahkan jadwal penting atau target pribadi.</p>';
            }

            document.querySelector('[data-calendar-prev]').addEventListener('click', () => {
                visibleMonth = new Date(visibleMonth.getFullYear(), visibleMonth.getMonth() - 1, 1);
                renderCalendar();
            });

            document.querySelector('[data-calendar-next]').addEventListener('click', () => {
                visibleMonth = new Date(visibleMonth.getFullYear(), visibleMonth.getMonth() + 1, 1);
                renderCalendar();
            });

            renderCalendar();
            renderSelectedEvents();
        })();
    </script>
</x-layouts.app>
