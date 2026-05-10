<x-layouts.app title="Kalender">
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
            <p class="page-kicker">Kalender</p>
            <h1 class="page-title serif">Kalender target</h1>
            <p class="page-subtitle">Lihat semua deadline, jadwal penting, dan target pribadi dalam satu halaman yang lebih lega.</p>
        </div>
        <a class="btn secondary" href="{{ route('targets.index') }}">Kembali ke Targets</a>
    </div>

    <div class="calendar-page-grid">
        <section class="card calendar-main">
            <div class="card-head">
                <div>
                    <h2 class="card-title">Kalender target</h2>
                    <p class="page-subtitle">Klik tanggal untuk melihat detail jadwal.</p>
                </div>
            </div>

            <div class="calendar-wrap">
                <div class="calendar-toolbar">
                    <button class="icon-btn" type="button" data-calendar-prev aria-label="Bulan sebelumnya">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"></path></svg>
                    </button>
                    <div>
                        <h3 id="calendar-title" class="calendar-title">Kalender</h3>
                        <p class="calendar-subtitle">Target dan jadwal penting muncul sebagai penanda tanggal.</p>
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
                    <textarea class="input" id="schedule_note" name="note" rows="4" placeholder="Detail singkat atau target yang ingin dicapai"></textarea>
                </div>
                <button type="submit" class="btn" style="width: 100%;">Tambahkan ke Kalender</button>
            </form>
        </section>
    </div>

    <script>
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
                    ? selectedEvents.map((event) => {
                        const progress = Number(event.progress || 0);
                        const statusClass = {
                            'selesai': 'ok',
                            'terlambat': 'err',
                            'proses': 'warn',
                            'belum mulai': 'violet',
                        }[event.status] || 'violet';

                        return `
                            <article class="group-row calendar-event-row">
                                <div class="row-top">
                                    <div>
                                        <span class="badge ${event.type === 'target' ? 'accent' : 'warn'}">${escapeHtml(event.label || event.type)}</span>
                                        ${event.endDate && event.endDate !== event.date ? `<span class="badge" style="margin-left: 6px;">${formatDate(event.date, { day: 'numeric', month: 'short' })} - ${formatDate(event.endDate)}</span>` : ''}
                                        <h3 style="margin: 10px 0 0; font-size: 18px; font-weight: 650;">${escapeHtml(event.title)}</h3>
                                        ${event.description ? `<p class="page-subtitle">${escapeHtml(event.description)}</p>` : ''}
                                        ${event.note ? `<p class="page-subtitle">${escapeHtml(event.note)}</p>` : ''}
                                        ${event.scopeLabel ? `<p class="row-subtitle">${escapeHtml(event.scopeLabel)}</p>` : ''}
                                    </div>
                                    <div class="calendar-event-actions">
                                        ${event.status ? `<span class="badge ${statusClass}">${escapeHtml(event.status)}</span>` : ''}
                                        ${event.type !== 'target' && event.canDelete ? `
                                            <form action="${escapeHtml(event.deleteUrl)}" method="POST" style="margin: 0;">
                                                <input type="hidden" name="_token" value="${escapeHtml(csrfToken)}">
                                                <input type="hidden" name="_method" value="DELETE">
                                                <button type="submit" class="danger-link">Hapus</button>
                                            </form>
                                        ` : ''}
                                    </div>
                                </div>
                                ${event.type === 'target' ? `
                                    <div class="target-checklist-form">
                                        <div class="target-progress-head">
                                            <span>${progress}% selesai</span>
                                            <span class="mono">${progress}%</span>
                                        </div>
                                        <div class="progress-line"><span style="width: ${progress}%"></span></div>
                                    </div>
                                ` : ''}
                                ${event.items?.length ? `
                                    <div class="form-grid calendar-checklist" style="gap: 8px; margin-top: 12px;">
                                        ${event.items.map((item) => `
                                            <div class="check-card target-check-card">
                                                <span class="check-box"></span>
                                                <span>${escapeHtml(item)}</span>
                                            </div>
                                        `).join('')}
                                    </div>
                                ` : ''}
                            </article>
                        `;
                    }).join('')
                    : '<p class="empty-state">Gunakan form di kanan untuk menambahkan jadwal penting atau target pribadi.</p>';
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
