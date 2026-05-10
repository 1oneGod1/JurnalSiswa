@props(['title' => 'Jurnal Projek STEM'])

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Jurnal Projek STEM' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @php
        $uiCss = file_get_contents(resource_path('css/app.css'));
        $uiCss = substr($uiCss, strpos($uiCss, ':root') ?: 0);
    @endphp
    <style>{!! $uiCss !!}</style>
</head>
<body>
    @php $u = current_user(); @endphp
    @if ($u)
        @php
            $items = [
                ['route' => 'dashboard', 'match' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'M3 11 12 4l9 7 M5 10v9a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1v-9'],
                ['route' => 'targets.index', 'match' => 'targets.*', 'label' => 'Targets', 'icon' => 'M12 4a8 8 0 1 0 0 16 8 8 0 0 0 0-16 M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8 M12 12h.01'],
                ['route' => 'calendar.index', 'match' => 'calendar.*', 'label' => 'Kalender', 'icon' => 'M7 3v3 M17 3v3 M4 8h16 M5 5h14a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z M8 12h.01 M12 12h.01 M16 12h.01 M8 16h.01 M12 16h.01'],
                ['route' => 'journals.index', 'match' => 'journals.*', 'label' => 'Journal', 'icon' => 'M5 4h10a3 3 0 0 1 3 3v13H8a3 3 0 0 1-3-3V4Z M5 17a3 3 0 0 1 3-3h10'],
                ['route' => 'feedback.index', 'match' => 'feedback.*', 'label' => 'Feedback', 'icon' => 'M4 5h16v11H9l-5 4V5Z'],
            ];

            if ($u->isTeacher()) {
                $items[] = ['route' => 'groups.index', 'match' => 'groups.*', 'label' => 'Kelompok', 'icon' => 'M9 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6 M3 19c.7-3 3-4.5 6-4.5s5.3 1.5 6 4.5 M17 9a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5 M15 14c2.5.2 4.5 1.8 5.5 4.5'];
            }
        @endphp
        <div class="app-shell">
            <aside class="sidebar">
                <a class="sidebar-brand" href="{{ route('dashboard') }}">
                    <div class="brand-mark"><img src="https://static.wixstatic.com/media/07639e_83549958900b44ad9fea05d99e380dd5~mv2.png/v1/fill/w_559,h_512,al_c/07639e_83549958900b44ad9fea05d99e380dd5~mv2.png" alt="Logo"></div>
                    <div>
                        <div class="brand-name">Jurnal</div>
                        <div class="brand-sub">STEM &middot; Sekolah Palembang Harapan</div>
                    </div>
                </a>
                <nav class="sidebar-nav">
                    @foreach ($items as $item)
                        <a class="nav-item {{ request()->routeIs($item['match']) ? 'active' : '' }}" href="{{ route($item['route']) }}">
                            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                @foreach (explode(' M', $item['icon']) as $path)
                                    <path d="{{ str_starts_with($path, 'M') ? $path : 'M'.$path }}"></path>
                                @endforeach
                            </svg>
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>
                <div class="sidebar-footer">
                    <div class="user-chip">
                        <div class="row-title">{{ $u->name }}</div>
                        <div class="row-subtitle">{{ ucfirst($u->role) }} @if($u->group_id) &middot; {{ $u->group_id }} @endif</div>
                        <form action="{{ route('logout') }}" method="POST" style="margin-top: 10px;">
                            @csrf
                            <button class="btn secondary" type="submit" style="width: 100%; padding: 8px 10px;">Keluar</button>
                        </form>
                    </div>
                </div>
            </aside>

            <div class="main-pane">
                <header class="topbar">
                    <div>
                        <div class="topbar-title">{{ $title ?? 'Jurnal Projek STEM' }}</div>
                    </div>
                    <span class="badge ok"><span style="width: 6px; height: 6px; border-radius: 999px; background: var(--ok);"></span>Semester 2 &middot; 2025/26</span>
                </header>
                <main class="content">
                    @if (session('status'))
                        <div class="notice">{{ session('status') }}</div>
                    @endif

                    @if ($errors->any())
                        <div class="notice err">
                            <p style="margin: 0 0 6px; font-weight: 700;">Periksa lagi input berikut:</p>
                            <ul style="margin: 0; padding-left: 18px;">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{ $slot }}
                </main>
            </div>
        </div>
    @else
        @if (session('status') || $errors->any())
            <div style="position: fixed; left: 20px; right: 20px; top: 20px; z-index: 30;">
                @if (session('status'))
                    <div class="notice">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="notice err">
                        <p style="margin: 0 0 6px; font-weight: 700;">Periksa lagi input berikut:</p>
                        <ul style="margin: 0; padding-left: 18px;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @endif
        {{ $slot }}
    @endif
</body>
</html>
