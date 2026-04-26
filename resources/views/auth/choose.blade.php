<x-layouts.app title="Jurnal - Pilih Peran">
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
                <a class="role-card" href="{{ route('login.siswa') }}" style="text-decoration: none; color: inherit;">
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
                </a>

                <a class="role-card" href="{{ route('login.guru') }}" style="text-decoration: none; color: inherit;">
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
                </a>
            </div>

            <div style="margin-top: 40px; text-align: center; color: var(--muted); font-size: 13px;">
                &copy; 2026 Jurnal &middot; SMAN 8
            </div>
        </div>
    </div>
</x-layouts.app>
