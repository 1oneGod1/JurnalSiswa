<x-layouts.app title="Targets">
    <div class="page-head">
        <div>
            <p class="page-kicker">Targets</p>
            <h1 class="page-title serif">Target pertemuan</h1>
            <p class="page-subtitle">Guru membuat target, siswa melihatnya sebagai checklist jurnal.</p>
        </div>
    </div>

    <div class="split-grid">
        @if (auth()->user()->isTeacher())
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
                <h2 class="card-title">Daftar target</h2>
            </div>
            <div class="list-divide">
                @forelse ($targets as $target)
                    <article class="group-row">
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
                            <span class="badge {{ ($target['status'] ?? 'active') === 'active' ? 'ok' : '' }}">{{ $target['status'] ?? 'active' }}</span>
                        </div>

                        @if (! empty($target['checklist_items']))
                            <div class="form-grid" style="gap: 8px; margin-top: 16px;">
                                @foreach ($target['checklist_items'] as $item)
                                    <div class="check-card">
                                        <span class="check-box"></span>
                                        <span>{{ $item }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if (auth()->user()->isTeacher())
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
    </div>

    <script>
        function toggleTargetMode(mode) {
            document.getElementById('mode-pertemuan').style.display = mode === 'pertemuan' ? '' : 'none';
            document.getElementById('mode-mingguan').style.display = mode === 'mingguan' ? '' : 'none';
        }
    </script>
</x-layouts.app>
