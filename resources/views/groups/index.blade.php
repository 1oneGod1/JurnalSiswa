<x-layouts.app title="Kelompok">
    <header class="page-head">
        <div>
            <h1 class="page-title">Kelompok</h1>
            <p class="page-subtitle">Kelola kelompok dan daftarkan nama siswa.</p>
        </div>
    </header>

    @if (session('status'))
        <div class="badge ok" style="margin-bottom: 16px;">{{ session('status') }}</div>
    @endif

    <section class="card card-pad" style="margin-bottom: 24px;">
        <h2 class="serif" style="margin: 0 0 12px; font-size: 22px;">Tambah kelompok</h2>
        <form action="{{ route('groups.store') }}" method="POST" style="display: grid; grid-template-columns: 120px 1fr auto; gap: 12px; align-items: end;">
            @csrf
            <div class="field" style="margin: 0;">
                <label for="number">Nomor</label>
                <input class="input" id="number" name="number" type="number" min="1" max="99" required>
            </div>
            <div class="field" style="margin: 0;">
                <label for="name">Nama / topik kelompok</label>
                <input class="input" id="name" name="name" type="text" maxlength="120" required>
            </div>
            <button class="btn" type="submit">Tambah</button>
        </form>
        @error('number')<p style="color: var(--danger); font-size: 13px; margin-top: 8px;">{{ $message }}</p>@enderror
        @error('name')<p style="color: var(--danger); font-size: 13px; margin-top: 8px;">{{ $message }}</p>@enderror
    </section>

    @if ($groups->isEmpty())
        <div class="card card-pad" style="text-align: center; color: var(--muted);">
            Belum ada kelompok. Tambahkan kelompok pertama di atas.
        </div>
    @else
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 16px;">
            @foreach ($groups as $group)
                @php $members = $studentsByGroup->get($group['id'], collect()); @endphp
                <div class="card card-pad">
                    <div style="display: flex; justify-content: space-between; align-items: start; gap: 12px;">
                        <div>
                            <div class="row-subtitle">Kelompok {{ $group['number'] ?? '' }}</div>
                            <h3 class="serif" style="margin: 4px 0 0; font-size: 20px;">{{ $group['name'] ?? '' }}</h3>
                        </div>
                        <form action="{{ route('groups.destroy', $group['id']) }}" method="POST" onsubmit="return confirm('Hapus kelompok ini?');">
                            @csrf @method('DELETE')
                            <button class="btn btn-ghost" type="submit" style="padding: 6px 10px; font-size: 12px;">Hapus</button>
                        </form>
                    </div>

                    <div style="margin-top: 16px;">
                        <div class="row-subtitle" style="margin-bottom: 8px;">Anggota ({{ $members->count() }})</div>
                        @if ($members->isEmpty())
                            <p style="color: var(--muted); font-size: 13px; margin: 0 0 12px;">Belum ada anggota.</p>
                        @else
                            <ul style="list-style: none; padding: 0; margin: 0 0 12px; display: flex; flex-direction: column; gap: 6px;">
                                @foreach ($members as $member)
                                    <li style="display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; background: var(--surface-2, #f5f5f4); border-radius: 8px;">
                                        <span>{{ $member->name }}</span>
                                        <form action="{{ route('groups.students.destroy', $member->id) }}" method="POST" onsubmit="return confirm('Hapus siswa ini?');">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-ghost" type="submit" style="padding: 4px 8px; font-size: 11px;">Hapus</button>
                                        </form>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        <form action="{{ route('groups.students.store', $group['id']) }}" method="POST" style="display: flex; gap: 8px;">
                            @csrf
                            <input class="input" name="name" type="text" placeholder="Nama siswa baru" maxlength="120" required style="flex: 1;">
                            <button class="btn" type="submit" style="padding: 10px 14px;">Daftarkan</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-layouts.app>
