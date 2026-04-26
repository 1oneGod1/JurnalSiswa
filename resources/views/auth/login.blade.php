<x-layouts.app title="Jurnal - Masuk">
    <div class="login-shell">
        <div class="login-nav">
            <div class="sidebar-brand" style="padding: 0;">
                <div class="brand-mark">J</div>
                <div>
                    <div class="brand-name">Jurnal</div>
                    <div class="brand-sub">STEM &middot; SMAN 8</div>
                </div>
            </div>
            <span class="badge ok"><span style="width: 6px; height: 6px; border-radius: 999px; background: var(--ok);"></span>Semester 2 &middot; 2025/26</span>
        </div>

        <div class="login-stage">
            <div class="role-head">
                <span class="badge" style="margin: 0 auto;">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 12h14"></path><path d="m13 6 6 6-6 6"></path>
                    </svg>
                    Pilih peran untuk mulai
                </span>
                <h1 class="serif">Tiap pertemuan, <em>tercatat.</em></h1>
                <p>Ruang kerja bersama untuk kelompok STEM dan guru pembimbing, dari target mingguan sampai kesiapan Expo Day.</p>
            </div>

            <div class="role-cards">
                <button class="role-card active" type="button" data-show="student-panel">
                    <div class="rc-icon student">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="8" r="3.5"></circle>
                            <path d="M4.5 20c1.2-3.5 4.1-5.5 7.5-5.5s6.3 2 7.5 5.5"></path>
                        </svg>
                    </div>
                    <h3>Masuk sebagai Siswa</h3>
                    <p class="desc">Pilih kelompok dan nama. Tanpa password.</p>
                    <ul>
                        <li><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 4 4 10-10"></path></svg>Pilih kelompok 1-12</li>
                        <li><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 4 4 10-10"></path></svg>Pilih nama dari daftar</li>
                        <li><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 4 4 10-10"></path></svg>Langsung isi jurnal</li>
                    </ul>
                    <div class="rc-foot">
                        <span class="rc-cta">Lanjutkan <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="m13 6 6 6-6 6"></path></svg></span>
                        <span class="rc-tag">Tanpa email</span>
                    </div>
                </button>

                <button class="role-card" type="button" data-show="teacher-panel">
                    <div class="rc-icon teacher">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="9" cy="9" r="3"></circle>
                            <path d="M3 19c.9-3 3.3-4.5 6-4.5s5.1 1.5 6 4.5"></path>
                            <circle cx="17" cy="7" r="2.5"></circle>
                            <path d="M15.5 14c2.5.2 4.5 1.8 5.5 4.5"></path>
                        </svg>
                    </div>
                    <h3>Masuk sebagai Guru</h3>
                    <p class="desc">Pantau seluruh kelompok, tetapkan target mingguan, dan beri feedback terstruktur.</p>
                    <ul>
                        <li><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 4 4 10-10"></path></svg>Dashboard progres semua kelompok</li>
                        <li><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 4 4 10-10"></path></svg>Kelola kelompok &amp; siswa</li>
                        <li><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 4 4 10-10"></path></svg>Beri feedback prioritas</li>
                    </ul>
                    <div class="rc-foot">
                        <span class="rc-cta">Masuk <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="m13 6 6 6-6 6"></path></svg></span>
                        <span class="rc-tag">Akun guru</span>
                    </div>
                </button>
            </div>

            {{-- Student Panel --}}
            <div class="teacher-login-panel card card-pad" id="student-panel">
                <h2 class="serif" style="margin: 0 0 6px; font-size: 30px; letter-spacing: -.4px;">Masuk sebagai <em>Siswa.</em></h2>
                <p class="page-subtitle">Pilih kelompok lalu pilih nama kamu.</p>

                @if (count($groups) === 0)
                    <p style="margin-top: 20px; color: var(--muted);">Belum ada kelompok yang didaftarkan oleh guru. Silakan minta guru untuk mendaftarkan kelompok terlebih dahulu.</p>
                @else
                    <div class="field" style="margin-top: 20px;">
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

            {{-- Teacher Panel --}}
            <div class="teacher-login-panel card card-pad" id="teacher-panel" style="display: none;">
                <h2 class="serif" style="margin: 0 0 6px; font-size: 30px; letter-spacing: -.4px;">Masuk ke <em>Jurnal.</em></h2>
                <p class="page-subtitle">Gunakan akun guru untuk memantau semua kelompok.</p>
                <form action="{{ route('login.store') }}" method="POST" style="margin-top: 20px;">
                    @csrf
                    <div class="field">
                        <label for="email">Email sekolah</label>
                        <input class="input" id="email" name="email" type="email" value="{{ old('email', 'guru@example.com') }}" required>
                    </div>
                    <div class="field">
                        <label for="password">Kata sandi</label>
                        <input class="input" id="password" name="password" type="password" required>
                    </div>
                    <label style="display: flex; align-items: center; gap: 8px; color: var(--muted); font-size: 13px; margin-bottom: 16px;">
                        <input type="checkbox" name="remember" value="1">
                        Ingat saya
                    </label>
                    <button class="btn" type="submit" style="width: 100%;">
                        Masuk ke dashboard
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="m13 6 6 6-6 6"></path></svg>
                    </button>
                    @error('email')
                        <p style="color: var(--danger); font-size: 13px; margin-top: 12px;">{{ $message }}</p>
                    @enderror
                </form>
            </div>

            <div style="margin-top: 40px; text-align: center; color: var(--muted); font-size: 13px;">
                &copy; 2026 Jurnal &middot; SMAN 8
            </div>
        </div>
    </div>

    <script>
        const studentsByGroup = @json($studentsByGroup ?? []);

        document.querySelectorAll('[data-show]').forEach((card) => {
            card.addEventListener('click', () => {
                document.querySelectorAll('[data-show]').forEach((item) => item.classList.remove('active'));
                card.classList.add('active');
                document.getElementById('student-panel').style.display = card.dataset.show === 'student-panel' ? '' : 'none';
                document.getElementById('teacher-panel').style.display = card.dataset.show === 'teacher-panel' ? '' : 'none';
            });
        });

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
