<x-layouts.app title="Edit Jurnal">
    <div class="page-head">
        <div>
            <p class="page-kicker">Journal</p>
            <h1 class="page-title serif">Edit jurnal</h1>
            <p class="page-subtitle">Perbarui catatan progress dan tambahkan dokumentasi jika diperlukan.</p>
        </div>
    </div>

    @php
        $checkedTargets = old('target_checklist', $journal['target_checklist'] ?? []);
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
            <h2 class="card-title">Checklist target</h2>
            <div class="form-grid" style="gap: 10px; margin-top: 12px;">
                @forelse ($targets as $target)
                    <label class="check-card">
                        <input type="checkbox" name="target_checklist[{{ $target['id'] }}]" value="1" @checked((bool) data_get($checkedTargets, $target['id'])) style="margin-top: 2px;">
                        <span>
                            <strong>
                                {{ ($target['mode'] ?? 'pertemuan') === 'mingguan' ? 'Minggu' : 'Pertemuan' }}
                                {{ ($target['mode'] ?? 'pertemuan') === 'mingguan' ? ($target['week_no'] ?? '-') : ($target['meeting_no'] ?? '-') }}
                                &middot; {{ $target['title'] ?? 'Target belum berjudul' }}
                            </strong>
                            @if (! empty($target['description']))
                                <span class="muted" style="display: block; margin-top: 2px;">{{ $target['description'] }}</span>
                            @endif
                        </span>
                    </label>
                @empty
                    <p class="check-card muted">Belum ada target aktif.</p>
                @endforelse
            </div>
        </section>

        @foreach ([
            'target_vs_realization' => ['Target vs realisasi', 'Apa target hari ini dan apa yang benar-benar selesai?'],
            'progress_today' => ['Progress hari ini', 'Tuliskan pekerjaan yang dilakukan kelompok.'],
            'data_result' => ['Data atau hasil', 'Contoh: sensor aktif pada tinggi air 8 cm.'],
            'problem' => ['Kendala', 'Masalah teknis, desain, data, atau kolaborasi.'],
            'solution_next_step' => ['Solusi dan next step', 'Langkah perbaikan berikutnya.'],
            'insight' => ['Insight', 'Pelajaran atau temuan penting dari proses hari ini.'],
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
</x-layouts.app>
