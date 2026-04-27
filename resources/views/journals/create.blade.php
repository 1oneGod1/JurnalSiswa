<x-layouts.app title="Isi Jurnal">
    <div class="page-head">
        <div>
            <p class="page-kicker">Journal</p>
            <h1 class="page-title serif">Isi jurnal pertemuan</h1>
            <p class="page-subtitle">Catat target, realisasi, data, kendala, dan dokumentasi.</p>
        </div>
    </div>

    <form action="{{ route('journals.store') }}" method="POST" enctype="multipart/form-data" class="card form-card form-grid" style="max-width: 920px;">
        @csrf
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
            <h2 class="card-title">Checklist target</h2>
            <div class="form-grid" style="gap: 10px; margin-top: 12px;">
                @forelse ($targets as $target)
                    <label class="check-card">
                        <input type="checkbox" name="target_checklist[{{ $target['id'] }}]" value="1" @checked(old("target_checklist.{$target['id']}")) style="margin-top: 2px;">
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
</x-layouts.app>
