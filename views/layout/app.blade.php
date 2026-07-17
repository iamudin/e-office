<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard' }} — {{ config('earsip.title', 'e-Arsip') }}</title>

    {{-- Tailwind CSS CDN --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
                    colors: {
                        brand: { 50: '#eef2ff', 100: '#e0e7ff', 200: '#c7d2fe', 300: '#a5b4fc', 400: '#818cf8', 500: '#6366f1', 600: '#4f46e5', 700: '#4338ca', 800: '#3730a3', 900: '#312e81' }
                    }
                }
            }
        }
    </script>

    {{-- Fonts & Icons --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

    {{-- jQuery (needed for DataTables) --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

    @stack('styles')

    <style>
        [x-cloak] {
            display: none !important;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* DataTable overrides */
        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            outline: none;
            transition: all 0.15s;
        }

        .dataTables_wrapper .dataTables_filter input:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, .15);
        }

        .dataTables_wrapper .dataTables_info {
            font-size: 0.8rem;
            color: #64748b;
            padding-top: 1rem;
        }

        .dataTables_wrapper .dataTables_paginate {
            padding-top: 1rem;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0.35rem 0.75rem !important;
            margin: 0 2px;
            border-radius: 0.5rem !important;
            border: 1px solid #e2e8f0 !important;
            font-size: 0.8rem;
            color: #475569 !important;
            background: #fff !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #4f46e5 !important;
            color: #fff !important;
            border-color: #4f46e5 !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #eef2ff !important;
            color: #4f46e5 !important;
            border-color: #c7d2fe !important;
        }

        table.dataTable {
            border-collapse: collapse !important;
        }

        table.dataTable thead th {
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            border-bottom: 2px solid #e2e8f0;
            padding: 0.75rem 1rem !important;
        }

        table.dataTable tbody td {
            padding: 0.75rem 1rem !important;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.85rem;
            vertical-align: middle;
        }

        table.dataTable tbody tr:hover {
            background: #f8fafc !important;
        }

        .dataTables_processing {
            background: rgba(255, 255, 255, 0.9) !important;
            border: none !important;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body class="font-sans bg-gray-50 text-gray-800 antialiased">

    {{-- ═══════════ SIDEBAR ═══════════ --}}
    <aside id="sidebar"
        class="fixed inset-y-0 left-0 z-50 w-64 bg-gradient-to-b from-slate-900 via-slate-900 to-slate-800 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out shadow-2xl">

        {{-- Logo --}}
        <div class="flex items-center gap-3 h-16 px-5 border-b border-white/10">
            <div
                class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/20">
                <i class="fa fa-archive text-white text-sm"></i>
            </div>
            <div>
                <h1 class="text-white font-bold text-base tracking-tight leading-tight">
                    {{ config('earsip.title', 'E-Office') }}</h1>
                <p class="text-slate-500 text-[10px] font-medium uppercase tracking-wider">E-Office System</p>
            </div>
        </div>

        {{-- Navigation --}}
        <nav class="mt-6 px-3 space-y-1">
            @php
                $currentPath = request()->path();
                $sidebarMenus = [
                    ['name' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'fa-dashboard', 'match' => 'dashboard'],
                    ['name' => 'Arsip Surat', 'route' => 'earsip.surat-masuk.index', 'icon' => 'fa-envelope', 'match' => 'surat-masuk'],
                    ['name' => 'Briefing', 'route' => 'earsip.briefing.index', 'icon' => 'fa-bullhorn', 'match' => 'briefing'],
                ];
                if (earsip_user() && earsip_user()->isAdmin()) {
                    $sidebarMenus[] = ['name' => 'Akses User', 'route' => 'earsip.pejabat.index', 'icon' => 'fa-users', 'match' => 'pejabat'];
                    $sidebarMenus[] = ['name' => 'Pengaturan', 'route' => 'earsip.pengaturan.index', 'icon' => 'fa-cog', 'match' => 'pengaturan'];
                }
            @endphp
            @foreach($sidebarMenus as $menu)
                    @php $isActive = str_contains($currentPath, $menu['match']); @endphp
                    <a href="{{ plugin_route($menu['route']) }}" class="group flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition-all duration-200
                       {{ $isActive
                ? 'bg-gradient-to-r from-indigo-600/90 to-indigo-500/90 text-white shadow-lg shadow-indigo-600/20'
                : 'text-slate-400 hover:text-white hover:bg-white/[.07]' }}">
                        <i
                            class="fa {{ $menu['icon'] }} w-5 text-center {{ $isActive ? 'text-white' : 'text-slate-500 group-hover:text-indigo-400' }} transition-colors"></i>
                        <span>{{ $menu['name'] }}</span>
                        @if($isActive)
                            <span class="ml-auto w-1.5 h-1.5 rounded-full bg-white"></span>
                        @endif
                    </a>
            @endforeach
        </nav>

        {{-- Sidebar footer --}}
        <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-white/10">
            <div class="flex items-center gap-3 px-2">
                <div
                    class="w-8 h-8 rounded-lg bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white text-xs font-bold shadow">
                    {{ strtoupper(substr(earsip_user()->name ?? 'U', 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-white truncate">{{ earsip_user()->name ?? '-' }}</p>
                    <p class="text-[11px] text-slate-500 truncate">
                        {{ earsip_user()?->pejabat?->alias_jabatan ?? earsip_user()->level }}</p>
                </div>
                <form method="POST" action="{{ plugin_route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="w-7 h-7 rounded-lg flex items-center justify-center text-slate-500 hover:text-rose-400 hover:bg-white/10 transition-all"
                        title="Keluar">
                        <i class="fa fa-sign-out text-sm"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- ═══════════ MAIN CONTENT ═══════════ --}}
    <div class="lg:ml-64 min-h-screen flex flex-col">

        {{-- Top bar --}}
        <header
            class="sticky top-0 z-40 h-14 bg-white/80 backdrop-blur-xl border-b border-gray-100 flex items-center justify-between px-4 lg:px-6">
            {{-- Mobile menu --}}
            <button id="sidebarToggle"
                class="lg:hidden w-9 h-9 rounded-lg flex items-center justify-center text-gray-500 hover:bg-gray-100 hover:text-gray-900 transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

            {{-- Breadcrumb --}}
            <div class="hidden lg:flex items-center gap-2 text-sm">
                <a href="{{ plugin_route('dashboard') }}"
                    class="text-gray-400 hover:text-indigo-600 transition-colors"><i class="fa fa-home"></i></a>
                <span class="text-gray-300">/</span>
                <span class="text-gray-600 font-medium">{{ $title ?? 'Dashboard' }}</span>
            </div>

            <div class="flex-1"></div>

            {{-- Right actions --}}
            <div class="flex items-center gap-3">
                <span class="hidden sm:inline text-xs text-gray-400">{{ now()->translatedFormat('l, d F Y') }}</span>
            </div>
        </header>

        {{-- Page content --}}
        <main class="flex-1 p-4 lg:p-6">
            {{-- Flash messages --}}
            @if(session('success'))
                <div
                    class="mb-5 flex items-center gap-3 p-4 rounded-xl bg-emerald-50 border border-emerald-200/60 text-emerald-700 animate-[fadeIn_0.3s_ease]">
                    <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center flex-shrink-0"><i
                            class="fa fa-check text-emerald-600"></i></div>
                    <span class="text-sm">{{ session('success') }}</span>
                </div>
            @endif
            @if(session('danger') || session('warning') || session('error'))
                <div
                    class="mb-5 flex items-center gap-3 p-4 rounded-xl bg-rose-50 border border-rose-200/60 text-rose-700 animate-[fadeIn_0.3s_ease]">
                    <div class="w-8 h-8 rounded-lg bg-rose-100 flex items-center justify-center flex-shrink-0"><i
                            class="fa fa-exclamation text-rose-600"></i></div>
                    <span class="text-sm">{{ session('danger') ?? session('warning') ?? session('error') }}</span>
                </div>
            @endif
            @if($errors->any())
                <div
                    class="mb-5 p-4 rounded-xl bg-rose-50 border border-rose-200/60 text-rose-700 animate-[fadeIn_0.3s_ease]">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-8 h-8 rounded-lg bg-rose-100 flex items-center justify-center flex-shrink-0"><i
                                class="fa fa-exclamation-triangle text-rose-600"></i></div>
                        <span class="text-sm font-semibold">Terdapat kesalahan:</span>
                    </div>
                    <ul class="list-disc pl-12 text-sm space-y-1">
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    {{-- Mobile overlay --}}
    <div id="sidebarOverlay"
        class="fixed inset-0 z-40 bg-black/40 backdrop-blur-sm hidden lg:hidden transition-opacity"></div>

    <script>
        // Mobile sidebar
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        document.getElementById('sidebarToggle')?.addEventListener('click', () => {
            sidebar.classList.toggle('-translate-x-full');
            overlay.classList.toggle('hidden');
        });
        overlay?.addEventListener('click', () => {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        });
    </script>

    @stack('scripts')
</body>

</html>