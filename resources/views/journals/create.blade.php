<x-layouts.app title="Isi Jurnal">
    <div class="page-head">
        <div>
            <p class="page-kicker">Journal</p>
            <h1 class="page-title serif">Isi jurnal harian</h1>
            <p class="page-subtitle">Catat target, realisasi, data, kendala, dan dokumentasi.</p>
        </div>
    </div>

    @php
        $hasOldChecklist = old('_target_checklist_seen') !== null;
        $studentGroupId = current_user()->group_id;
    @endphp

    <form action="{{ route('journals.store') }}" method="POST" enctype="multipart/form-data" class="card form-card form-grid" style="max-width: 920px;">
        @csrf
        <input type="hidden" name="_target_checklist_seen" value="1">
        <div class="grid-3">
            @if (current_user()->isTeacher())
                <div class="field">
                    <label for="group_id">Kelompok</label>
                    <select class="input" id="group_id" name="group_id" required>
                        <option value="">Pilih kelompok</option>
                        @foreach ($groups as $group)
                            <option value="{{ $group['id'] }}" @selected(old('group_id') === $group['id'])>{{ $group['name'] ?? $group['id'] }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="field">
                <label for="meeting_no">Pertemuan ke</label>
                <input class="input" id="meeting_no" name="meeting_no" type="number" min="1" value="{{ old('meeting_no', 1) }}" required>
            </div>
            <div class="field">
                <label for="journal_date">Tanggal</label>
                <input class="input" id="journal_date" name="journal_date" type="date" value="{{ old('journal_date', now()->toDateString()) }}" required>
            </div>
        </div>

        <section>
            <h2 class="card-title">Daftar target</h2>
            <p class="page-subtitle">Item yang sudah pernah dicentang di jurnal kelompok akan otomatis tercentang sebagai lanjutan, tanpa mengubah jurnal lama.</p>
            <div class="form-grid" style="gap: 10px; margin-top: 12px;">
                @forelse ($targets as $target)
                    <div class="check-card" style="align-items: flex-start;">
                        <span style="display: block; width: 100%;">
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
                            <strong style="display: block; margin-top: 10px;">{{ $target['title'] ?? 'Target belum berjudul' }}</strong>
                            @if (! empty($target['description']))
                                <span class="muted" style="display: block; margin-top: 4px;">{{ $target['description'] }}</span>
                            @endif
                            @if (! empty($target['checklist_items']))
                                <span class="form-grid" style="display: grid; gap: 8px; margin-top: 12px;">
                                    @foreach ($target['checklist_items'] as $item)
                                        @php
                                            $historicalGroups = collect($historicalChecklistByGroup)
                                                ->filter(fn ($history) => data_get($history, $target['id']) === true || data_get($history, $target['id'].'.items.'.$loop->index))
                                                ->keys()
                                                ->values();
                                            $isHistoricallyChecked = $studentGroupId && $historicalGroups->contains($studentGroupId);
                                            $isChecked = $hasOldChecklist
                                                ? (bool) old("target_checklist.{$target['id']}.items.{$loop->index}")
                                                : $isHistoricallyChecked;
                                        @endphp
                                        <label style="display: flex; gap: 8px; align-items: flex-start; cursor: pointer;">
                                            <input
                                                type="checkbox"
                                                name="target_checklist[{{ $target['id'] }}][items][{{ $loop->index }}]"
                                                value="{{ $item }}"
                                                data-historical-groups='@json($historicalGroups)'
                                                @checked($isChecked)
                                                style="margin-top: 2px;"
                                            >
                                            <span>{{ $item }}</span>
                                        </label>
                                    @endforeach
                                </span>
                            @else
                                @php
                                    $historicalGroups = collect($historicalChecklistByGroup)
                                        ->filter(fn ($history) => data_get($history, $target['id']) === true)
                                        ->keys()
                                        ->values();
                                    $isHistoricallyChecked = $studentGroupId && $historicalGroups->contains($studentGroupId);
                                    $isChecked = $hasOldChecklist
                                        ? (bool) old("target_checklist.{$target['id']}.completed")
                                        : $isHistoricallyChecked;
                                @endphp
                                <label style="display: flex; gap: 8px; align-items: flex-start; cursor: pointer; margin-top: 12px;">
                                    <input
                                        type="checkbox"
                                        name="target_checklist[{{ $target['id'] }}][completed]"
                                        value="1"
                                        data-historical-groups='@json($historicalGroups)'
                                        @checked($isChecked)
                                        style="margin-top: 2px;"
                                    >
                                    <span>Target ini selesai.</span>
                                </label>
                            @endif
                        </span>
                    </div>
                @empty
                    <p class="check-card muted">Belum ada target aktif.</p>
                @endforelse
            </div>
        </section>

        <section>
            <h2 class="card-title">Kontribusi anggota</h2>
            <p class="page-subtitle">Tuliskan pekerjaan tiap siswa dan upload foto/screenshot bukti. Maksimal 5 MB per gambar.</p>
            <div class="form-grid" style="gap: 10px; margin-top: 12px;">
                @foreach ($studentsByGroup as $groupId => $students)
                    @foreach ($students as $student)
                        @php
                            $isVisible = current_user()->isStudent() && current_user()->group_id === $groupId;
                            $oldContribution = old("member_contributions.{$student['id']}.contribution");
                        @endphp
                        <div class="check-card contribution-card {{ $isVisible ? '' : (current_user()->isTeacher() ? '' : 'hidden') }}" data-contribution-group="{{ $groupId }}" style="{{ current_user()->isTeacher() ? 'display:none;' : '' }}">
                            <span style="display: block; width: 100%;">
                                <strong>{{ $student['name'] ?? 'Siswa' }}</strong>
                                <input type="hidden" name="member_contributions[{{ $student['id'] }}][student_id]" value="{{ $student['id'] }}">
                                <input type="hidden" name="member_contributions[{{ $student['id'] }}][student_name]" value="{{ $student['name'] ?? 'Siswa' }}">
                                <div class="field" style="margin-top: 10px;">
                                    <label for="contribution_{{ $student['id'] }}">Kontribusi</label>
                                    <textarea class="input" id="contribution_{{ $student['id'] }}" name="member_contributions[{{ $student['id'] }}][contribution]" rows="2" placeholder="Contoh: memasang sensor, menulis laporan, dokumentasi testing">{{ $oldContribution }}</textarea>
                                </div>
                                <div class="field" style="margin-bottom: 0;">
                                    <label for="contribution_image_{{ $student['id'] }}">Foto / screenshot bukti</label>
                                    <input class="input file-input" id="contribution_image_{{ $student['id'] }}" name="contribution_images[{{ $student['id'] }}]" type="file" accept="image/jpeg,image/png,image/webp">
                                </div>
                            </span>
                        </div>
                    @endforeach
                @endforeach
                <p class="check-card muted" data-contribution-empty style="{{ current_user()->isTeacher() ? '' : 'display:none;' }}">Pilih kelompok dulu untuk menampilkan daftar anggota.</p>
            </div>
        </section>

        @foreach ([
            'target_vs_realization' => ['Target vs realisasi', 'Apa target hari ini dan apa yang selesai?'],
            'progress_today' => ['Progress hari ini', 'Tuliskan progress kelompok hari ini.'],
            'data_result' => ['Data atau hasil', 'Contoh: sensor aktif pada tinggi air 8 cm.'],
            'problem' => ['Kendala', 'Masalah teknis, desain, data, atau kolaborasi.'],
            'solution_next_step' => ['Solusi dan next step', 'Langkah perbaikan berikutnya.'],
            'insight' => ['Insight', 'Pelajaran atau temuan penting hari ini.'],
        ] as $name => [$label, $placeholder])
            <div class="field">
                <label for="{{ $name }}">{{ $label }}</label>
                <textarea class="input" id="{{ $name }}" name="{{ $name }}" rows="3" @if ($name === 'progress_today') required @endif placeholder="{{ $placeholder }}">{{ old($name) }}</textarea>
            </div>
        @endforeach

        <div class="grid-2">
            <label class="check-card" style="align-items: center;">
                <input type="checkbox" name="help_request" value="1" @checked(old('help_request'))>
                <span>Kelompok butuh bantuan guru</span>
            </label>
            <div class="field" style="margin-bottom: 0;">
                <label for="documentations">Upload dokumentasi</label>
                <input class="input file-input" id="documentations" name="documentations[]" type="file" multiple>
                <p class="row-subtitle">Maksimal 6 file, 20 MB per file. Foto, video, PDF, atau TXT.</p>
            </div>
        </div>

        <div class="form-actions">
            <a href="{{ route('journals.index') }}" class="btn secondary">Batal</a>
            <button type="submit" class="btn">Simpan Jurnal</button>
        </div>
    </form>

    <script>
        (() => {
            const groupSelect = document.getElementById('group_id');
            const cards = document.querySelectorAll('[data-contribution-group]');
            const emptyState = document.querySelector('[data-contribution-empty]');
            const historicalCheckboxes = document.querySelectorAll('[data-historical-groups]');
            const hasOldChecklist = @json($hasOldChecklist);
            const fallbackGroup = @json($studentGroupId);

            if (cards.length === 0 && historicalCheckboxes.length === 0) {
                return;
            }

            const syncContributionCards = () => {
                const selectedGroup = groupSelect ? groupSelect.value : fallbackGroup;
                let visible = 0;
                cards.forEach((card) => {
                    const show = card.dataset.contributionGroup === selectedGroup;
                    card.style.display = show ? '' : 'none';
                    if (show) visible += 1;
                });

                if (emptyState) {
                    emptyState.style.display = visible === 0 ? '' : 'none';
                }
            };

            const syncHistoricalChecklist = () => {
                if (hasOldChecklist) {
                    return;
                }

                const selectedGroup = groupSelect ? groupSelect.value : fallbackGroup;
                historicalCheckboxes.forEach((checkbox) => {
                    let groups = [];
                    try {
                        groups = JSON.parse(checkbox.dataset.historicalGroups || '[]');
                    } catch (error) {
                        groups = [];
                    }
                    checkbox.checked = selectedGroup ? groups.includes(selectedGroup) : false;
                });
            };

            groupSelect?.addEventListener('change', () => {
                syncContributionCards();
                syncHistoricalChecklist();
            });
            syncContributionCards();
            syncHistoricalChecklist();
        })();
    </script>
</x-layouts.app>
