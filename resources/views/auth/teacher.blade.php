<x-layouts.app title="Jurnal - Masuk Guru">
    <div class="login-shell">
        <div class="login-nav">
            <a href="{{ route('login') }}" class="sidebar-brand" style="padding: 0; text-decoration: none; color: inherit;">
                <div class="brand-mark">J</div>
                <div>
                    <div class="brand-name">Jurnal</div>
                    <div class="brand-sub">STEM &middot; SMAN 8</div>
                </div>
            </a>
            <a href="{{ route('login') }}" class="badge" style="text-decoration: none;">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M19 12H5"></path><path d="m11 18-6-6 6-6"></path>
                </svg>
                Kembali
            </a>
        </div>

        <div class="login-stage" style="max-width: 480px; margin: 0 auto;">
            <div class="role-head">
                <h1 class="serif">Masuk sebagai <em>Guru.</em></h1>
                <p>Gunakan akun guru untuk memantau semua kelompok.</p>
            </div>

            <div class="card card-pad">
                <form action="{{ route('login.store') }}" method="POST">
                    @csrf
                    <div class="field">
                        <label for="email">Email sekolah</label>
                        <input class="input" id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>
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
</x-layouts.app>
