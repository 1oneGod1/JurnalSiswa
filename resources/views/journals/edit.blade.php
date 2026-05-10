<x-layouts.app title="Edit Jurnal">
    <div class="page-head">
        <div>
            <p class="page-kicker">Journal</p>
            <h1 class="page-title serif">Edit jurnal</h1>
            <p class="page-subtitle">Perbarui catatan dan dokumentasi jika diperlukan.</p>
        </div>
    </div>

    @php
        $checkedTargets = old('target_checklist', $journal['target_checklist'] ?? []);
        $memberContributions = old('member_contributions', $journal['member_contributions'] ?? []);
    @endphp

    <form action="{{ route('journals.update', $journal['id']) }}" method="POST" enctype="multipart/form-data" class="card form-card form-grid" style="max-width: 920px;">
        @csrf
        @method('PUT')

        <div class="grid-3">
            @if (current_user()->isTeacher())
                <div class="field">
                    <label for="group_id">Kelompok</label>
                    <select class="input" id="group_id" name="group_id" required>
                        <option value="">Pilih kelompok</option>
                        @foreach ($groups as $group)
                            <option value="{{ $group['id'] }}" @selected(old('group_id', $journal['group_id'] ?? '') === $group['id'])>{{ $group['name'] ?? $group['id'] }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="field">
                <label for="meeting_no">Pertemuan ke</label>
                <input class="input" id="meeting_no" name="meeting_no" type="number" min="1" value="{{ old('meeting_no', $journal['meeting_no'] ?? 1) }}" required>
            </div>
            <div class="field">
                <label for="journal_date">Tanggal</label>
                <input class="input" id="journal_date" name="journal_date" type="date" value="{{ old('journal_date', $journal['journal_date'] ?? now()->toDateString()) }}" required>
            </div>
        </div>

        <section>
            <h2 class="card-title">Daftar target</h2>
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
                                        @php $itemChecked = data_get($checkedTargets, $target['id']) === true || data_get($checkedTargets, $target['id'].'.items.'.$loop->index); @endphp
                                        <label style="display: flex; gap: 8px; align-items: flex-start; cursor: pointer;">
                                            <input type="checkbox" name="target_checklist[{{ $target['id'] }}][items][{{ $loop->index }}]" value="{{ $item }}" @checked($itemChecked) style="margin-top: 2px;">
                                            <span>{{ $item }}</span>
                                        </label>
                                    @endforeach
                                </span>
                            @else
                                @php $targetChecked = data_get($checkedTargets, $target['id']) === true || data_get($checkedTargets, $target['id'].'.completed'); @endphp
                                <label style="display: flex; gap: 8px; align-items: flex-start; cursor: pointer; margin-top: 12px;">
                                    <input type="checkbox" name="target_checklist[{{ $target['id'] }}][completed]" value="1" @checked($targetChecked) style="margin-top: 2px;">
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
            <p class="page-subtitle">Perbarui pekerjaan tiap siswa dan upload foto/screenshot bukti baru jika diperlukan. Maksimal 5 MB per gambar.</p>
            <div class="form-grid" style="gap: 10px; margin-top: 12px;">
                @foreach ($studentsByGroup as $groupId => $students)
                    @foreach ($students as $student)
                        @php
                            $selectedGroup = old('group_id', $journal['group_id'] ?? current_user()->group_id);
                            $isVisible = $selectedGroup === $groupId;
                            $contribution = data_get($memberContributions, $student['id'], []);
                        @endphp
                        <div class="check-card contribution-card" data-contribution-group="{{ $groupId }}" style="{{ $isVisible ? '' : 'display:none;' }}">
                            <span style="display: block; width: 100%;">
                                <strong>{{ $student['name'] ?? 'Siswa' }}</strong>
                                <input type="hidden" name="member_contributions[{{ $student['id'] }}][student_id]" value="{{ $student['id'] }}">
                                <input type="hidden" name="member_contributions[{{ $student['id'] }}][student_name]" value="{{ $student['name'] ?? 'Siswa' }}">
                                <input type="hidden" name="member_contributions[{{ $student['id'] }}][photo_documentation_id]" value="{{ $contribution['photo_documentation_id'] ?? '' }}">
                                <div class="field" style="margin-top: 10px;">
                                    <label for="contribution_{{ $student['id'] }}">Kontribusi</label>
                                    <textarea class="input" id="contribution_{{ $student['id'] }}" name="member_contributions[{{ $student['id'] }}][contribution]" rows="2" placeholder="Contoh: memasang sensor, menulis laporan, dokumentasi testing">{{ $contribution['contribution'] ?? '' }}</textarea>
                                </div>
                                <div class="field" style="margin-bottom: 0;">
                                    <label for="contribution_image_{{ $student['id'] }}">Foto / screenshot bukti</label>
                                    <input class="input file-input" id="contribution_image_{{ $student['id'] }}" name="contribution_images[{{ $student['id'] }}]" type="file" accept="image/jpeg,image/png,image/webp">
                                    @if (! empty($contribution['photo_documentation_id']))
                                        <a class="soft-link" href="{{ route('documentations.show', $contribution['photo_documentation_id']) }}" target="_blank">Lihat bukti tersimpan</a>
                                    @endif
                                </div>
                            </span>
                        </div>
                    @endforeach
                @endforeach
                <p class="check-card muted" data-contribution-empty>Pilih kelompok dulu untuk menampilkan daftar anggota.</p>
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
                <textarea class="input" id="{{ $name }}" name="{{ $name }}" rows="3" @if ($name === 'progress_today') required @endif placeholder="{{ $placeholder }}">{{ old($name, $journal[$name] ?? '') }}</textarea>
            </div>
        @endforeach

        <div class="grid-2">
            <label class="check-card" style="align-items: center;">
                <input type="checkbox" name="help_request" value="1" @checked(old('help_request', $journal['help_request'] ?? false))>
                <span>Kelompok butuh bantuan guru</span>
            </label>
            <div class="field" style="margin-bottom: 0;">
                <label for="documentations">Tambah dokumentasi</label>
                <input class="input file-input" id="documentations" name="documentations[]" type="file" multiple>
                <p class="row-subtitle">File lama tetap tersimpan. Tambahkan maksimal 6 file baru, 20 MB per file.</p>
            </div>
        </div>

        @if ($documentations->isNotEmpty())
            <section>
                <h2 class="card-title">Dokumentasi tersimpan</h2>
                <div class="form-grid" style="gap: 8px; margin-top: 12px;">
                    @foreach ($documentations as $documentation)
                        <a href="{{ route('documentations.show', $documentation['id']) }}" target="_blank" class="check-card">
                            <span class="check-box done">&#10003;</span>
                            <span>{{ $documentation['file_name'] ?? 'Dokumentasi' }}</span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        <div class="form-actions">
            <a href="{{ route('journals.show', $journal['id']) }}" class="btn secondary">Batal</a>
            <button type="submit" class="btn">Simpan Perubahan</button>
        </div>
    </form>

    <script>
        (() => {
            const groupSelect = document.getElementById('group_id');
            const cards = document.querySelectorAll('[data-contribution-group]');
            const emptyState = document.querySelector('[data-contribution-empty]');
            const fallbackGroup = '{{ old('group_id', $journal['group_id'] ?? current_user()->group_id) }}';

            if (cards.length === 0) {
                return;
            }

            const syncContributionCards = () => {
                const groupId = groupSelect ? groupSelect.value : fallbackGroup;
                let visible = 0;
                cards.forEach((card) => {
                    const show = card.dataset.contributionGroup === groupId;
                    card.style.display = show ? '' : 'none';
                    if (show) visible += 1;
                });

                if (emptyState) {
                    emptyState.style.display = visible === 0 ? '' : 'none';
                }
            };

            groupSelect?.addEventListener('change', syncContributionCards);
            syncContributionCards();
        })();
    </script>
</x-layouts.app>
