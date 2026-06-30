<!doctype html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ระบบรายงานผลการสอบ SciGrad')</title>
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <script src="https://cdn.jsdelivr.net/npm/lucide@0.263.0/dist/umd/lucide.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Noto Sans Thai', sans-serif; }
        .canva-header { background: linear-gradient(135deg, #8B4513 0%, #A0522D 50%, #C4725C 100%); }
        header .canva-text { color: #FFFBF7; }
        .form-section { background: #fdf6f0; border: 1px solid #e8cdb5; }
        .menu-card { background: #FFFBF7; border: 1px solid #E8C4B8; transition: all .2s; }
        .menu-card:hover { border-color: #C4725C; box-shadow: 0 4px 14px rgba(139,69,19,.12); transform: translateY(-1px); }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-dept { background: #e0d4c8; color: #6b3a1f; }
        .status-approved { background: #d1fae5; color: #065f46; }
        .status-rejected { background: #fee2e2; color: #991b1b; }
        @media print { .no-print { display: none !important; } }
    </style>
    @stack('styles')
</head>
<body class="min-h-screen bg-[#FAF0E6]">
    <header class="canva-header shadow-md no-print">
        <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between gap-4">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 min-w-0">
                <div class="w-10 h-10 bg-white/90 rounded-full flex items-center justify-center shrink-0">
                    <i data-lucide="graduation-cap" class="w-6 h-6 text-amber-900"></i>
                </div>
                <div class="min-w-0">
                    <h1 class="canva-text text-lg font-bold truncate">ระบบรายงานผลการสอบ SciGrad</h1>
                    <p class="canva-text text-sm opacity-90 truncate">คณะวิทยาศาสตร์ มหาวิทยาลัยขอนแก่น</p>
                </div>
            </a>
            <div class="flex items-center gap-2 shrink-0">
                @php $currentRole = session('scigrade_role', 'instructor'); @endphp
                <span class="hidden sm:inline text-xs text-white/80 px-2 py-1 rounded bg-white/15">
                    {{ match($currentRole) { 'dept_admin' => 'Admin สาขา', 'faculty_admin' => 'Admin กลาง', default => 'อาจารย์' } }}
                </span>
                <span class="text-sm text-white/90 hidden md:inline">{{ session('staff_display_name', auth()->user()->name) }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded px-3 py-1.5 text-sm bg-white/90 text-amber-900 font-medium hover:bg-white">ออกจากระบบ</button>
                </form>
            </div>
        </div>
    </header>

    @hasSection('subnav')
        <nav class="no-print bg-white border-b border-amber-200">
            <div class="max-w-7xl mx-auto px-4 py-2 flex items-center gap-4 text-sm">
                <a href="{{ route('dashboard') }}" class="text-[#8B4513] hover:underline flex items-center gap-1">
                    <i data-lucide="home" class="w-4 h-4"></i> หน้าหลัก
                </a>
                @yield('subnav')
            </div>
        </nav>
    @endif

    <main class="max-w-7xl mx-auto px-4 py-6">
        @if (session('status'))
            <div class="mb-4 rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm no-print">{{ session('status') }}</div>
        @endif
        @yield('content')
    </main>

    @stack('scripts')
    <script>lucide.createIcons();</script>
</body>
</html>
