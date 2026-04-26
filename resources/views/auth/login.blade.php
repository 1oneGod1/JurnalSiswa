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
                <button class="role-card" type="button" data-role-card data-email="siswa@example.com">
                    <div class="rc-icon student">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="8" r="3.5"></circle>
                            <path d="M4.5 20c1.2-3.5 4.1-5.5 7.5-5.5s6.3 2 7.5 5.5"></path>
                        </svg>
                    </div>
                    <h3>Masuk sebagai Siswa</h3>
                    <p class="desc">Isi jurnal per pertemuan, checklist target, upload bukti, dan baca feedback guru.</p>
                    <ul>
                        <li><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 4 4 10-10"></path></svg>Isi jurnal per pertemuan</li>
                        <li><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 4 4 10-10"></path></svg>Upload foto dan video evidence</li>
                        <li><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 4 4 10-10"></path></svg>Lihat target dan revisi</li>
                    </ul>
                    <div class="rc-foot">
                        <span class="rc-cta">Lanjutkan <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="m13 6 6 6-6 6"></path></svg></span>
                        <span class="rc-tag">Akun siswa</span>
                    </div>
                </button>

                <button class="role-card" type="button" data-role-card data-email="guru@example.com">
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
                        <li><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 4 4 10-10"></path></svg>Target pertemuan dan checklist</li>
                        <li><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 4 4 10-10"></path></svg>Feedback prioritas</li>
                    </ul>
                    <div class="rc-foot">
                        <span class="rc-cta">Masuk <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="m13 6 6 6-6 6"></path></svg></span>
                        <span class="rc-tag">Akun guru</span>
                    </div>
                </button>
            </div>

            <div class="teacher-login-panel card card-pad" id="login-panel">
                <h2 class="serif" style="margin: 0 0 6px; font-size: 30px; letter-spacing: -.4px;">Masuk ke <em>Jurnal.</em></h2>
                <p class="page-subtitle" id="login-copy">Pilih peran di atas, lalu gunakan password akun sekolah.</p>
                <form action="{{ route('login.store') }}" method="POST" style="margin-top: 20px;">
                    @csrf
                    <div class="field">
                        <label for="email">Email sekolah</label>
                        <input class="input" id="email" name="email" type="email" value="{{ old('email', 'siswa@example.com') }}" required autofocus>
                    </div>
                    <div class="field">
                        <label for="password">Kata sandi</label>
                        <input class="input" id="password" name="password" type="password" value="password" required>
                    </div>
                    <label style="display: flex; align-items: center; gap: 8px; color: var(--muted); font-size: 13px; margin-bottom: 16px;">
                        <input type="checkbox" name="remember" value="1">
                        Ingat saya
                    </label>
                    <button class="btn" type="submit" style="width: 100%;">
                        Masuk ke dashboard
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="m13 6 6 6-6 6"></path></svg>
                    </button>
                </form>
            </div>

            <div style="margin-top: 40px; text-align: center; color: var(--muted); font-size: 13px;">
                &copy; 2026 Jurnal &middot; SMAN 8 &middot; Akun demo: guru@example.com / siswa@example.com, password: password
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('[data-role-card]').forEach((card) => {
            card.addEventListener('click', () => {
                document.querySelectorAll('[data-role-card]').forEach((item) => item.classList.remove('active'));
                card.classList.add('active');
                document.getElementById('email').value = card.dataset.email;
                document.getElementById('password').value = 'password';
                document.getElementById('login-copy').textContent = card.dataset.email.includes('guru')
                    ? 'Gunakan akun guru untuk memantau semua kelompok.'
                    : 'Gunakan akun siswa untuk mengisi jurnal kelompok.';
                document.getElementById('login-panel').scrollIntoView({ behavior: 'smooth', block: 'center' });
            });
        });
    </script>
</x-layouts.app>
