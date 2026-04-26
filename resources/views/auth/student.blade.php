<x-layouts.app title="Jurnal - Masuk Siswa">
    <div class="login-shell">
        <div class="login-nav">
            <a href="{{ route('login') }}" class="sidebar-brand" style="padding: 0; text-decoration: none; color: inherit;">
                <div class="brand-mark"><img src="https://static.wixstatic.com/media/07639e_83549958900b44ad9fea05d99e380dd5~mv2.png/v1/fill/w_559,h_512,al_c/07639e_83549958900b44ad9fea05d99e380dd5~mv2.png" alt="Logo"></div>
                <div>
                    <div class="brand-name">Jurnal</div>
                    <div class="brand-sub">STEM &middot; Sekolah Palembang Harapan</div>
                </div>
            </a>
            <a href="{{ route('login') }}" class="badge" style="text-decoration: none;">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 12H5"></path><path d="m11 18-6-6 6-6"></path>
                </svg>
                Kembali
            </a>
        </div>

        <div class="login-stage" style="max-width: 560px; margin: 0 auto;">
            <div class="role-head">
                <h1 class="serif">Masuk sebagai <em>Siswa.</em></h1>
                <p>Pilih kelompokmu, lalu pilih nama.</p>
            </div>

            <div class="card card-pad">
                @if (count($groups) === 0)
                    <p style="color: var(--muted); margin: 0;">Belum ada kelompok yang didaftarkan oleh guru. Silakan minta guru untuk mendaftarkan kelompok terlebih dahulu.</p>
                @else
                    <div class="field">
                        <label for="group-select">Kelompok</label>
                        <select class="input" id="group-select">
                            <option value="">— Pilih kelompok —</option>
                            @foreach ($groups as $group)
                                <option value="{{ $group['id'] }}">Kelompok {{ $group['number'] ?? '' }} &middot; {{ $group['name'] ?? '' }}</option>
                            @endforeach
                        </select>
                    </div>

                    <form action="{{ route('login.student') }}" method="POST" id="student-form" style="display: none;">
                        @csrf
                        <input type="hidden" name="student_id" id="student_id">
                        <div class="field">
                            <label>Nama</label>
                            <div id="student-list" style="display: flex; flex-direction: column; gap: 8px;"></div>
                            <p id="empty-students" style="color: var(--muted); font-size: 13px; margin-top: 8px; display: none;">
                                Belum ada nama di kelompok ini. Minta guru mendaftarkan namamu dulu.
                            </p>
                        </div>
                    </form>
                @endif
            </div>

            <div style="margin-top: 40px; text-align: center; color: var(--muted); font-size: 13px;">
                &copy; 2026 Jurnal &middot; Sekolah Palembang Harapan
            </div>
        </div>
    </div>

    <script>
        const studentsByGroup = @json($studentsByGroup ?? []);

        const groupSelect = document.getElementById('group-select');
        if (groupSelect) {
            groupSelect.addEventListener('change', (e) => {
                const groupId = e.target.value;
                const form = document.getElementById('student-form');
                const list = document.getElementById('student-list');
                const empty = document.getElementById('empty-students');

                if (!groupId) {
                    form.style.display = 'none';
                    return;
                }

                form.style.display = '';
                list.innerHTML = '';
                const students = studentsByGroup[groupId] || [];

                if (students.length === 0) {
                    empty.style.display = '';
                    return;
                }

                empty.style.display = 'none';
                students.forEach((student) => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'btn btn-ghost';
                    btn.style.cssText = 'width: 100%; justify-content: flex-start; padding: 14px 16px;';
                    btn.textContent = student.name;
                    btn.addEventListener('click', () => {
                        document.getElementById('student_id').value = student.id;
                        document.getElementById('student-form').submit();
                    });
                    list.appendChild(btn);
                });
            });
        }
    </script>
</x-layouts.app>
