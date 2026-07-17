<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login &mdash; {{ config('earsip.title', 'e-Arsip') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                }
            }
        }
    </script>
</head>

<body
    class="bg-slate-900 min-h-screen flex items-center justify-center p-4 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] relative">

    <div class="absolute inset-0 bg-gradient-to-br from-indigo-900/80 via-slate-900/90 to-purple-900/80"></div>

    <div class="relative w-full max-w-md">
        <!-- Logo Header -->
        <div class="text-center mb-8">
            <div
                class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 shadow-xl shadow-indigo-500/30 mb-4">
                <i class="fa fa-archive text-white text-4xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-white tracking-tight">{{ config('earsip.title', 'e-Arsip') }}</h1>
            <p class="text-slate-400 mt-2">{{ config('earsip.description', 'Sistem Arsip Elektronik') }}</p>
        </div>

        <!-- Login Card -->
        <div class="bg-white/10 backdrop-blur-xl border border-white/20 rounded-3xl p-8 shadow-2xl">
            @if (session('error'))
                <div
                    class="mb-6 p-4 rounded-xl bg-rose-500/20 border border-rose-500/50 text-rose-200 text-sm flex items-center gap-3">
                    <i class="fa fa-exclamation-circle text-rose-400 text-lg"></i>
                    {{ session('error') }}
                </div>
            @endif

            <form method="POST" action="{{ plugin_route('login.submit') }}" class="space-y-5">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1.5 ml-1">Username / Email</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fa fa-user text-slate-400"></i>
                        </div>
                        <input type="text" name="username" value="{{ old('username') }}" required autofocus
                            class="block w-full pl-11 pr-4 py-3 bg-slate-800/50 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all sm:text-sm"
                            placeholder="Masukkan username atau email">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1.5 ml-1">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fa fa-lock text-slate-400"></i>
                        </div>
                        <input type="password" name="password" required
                            class="block w-full pl-11 pr-4 py-3 bg-slate-800/50 border border-slate-700 rounded-xl text-white placeholder-slate-500 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all sm:text-sm"
                            placeholder="••••••••">
                    </div>
                </div>

                <button type="submit"
                    class="w-full flex justify-center py-3 px-4 border border-transparent rounded-xl shadow-lg shadow-indigo-600/30 text-sm font-semibold text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 focus:ring-offset-slate-900 transition-all transform hover:scale-[1.02] active:scale-[0.98]">
                    Masuk ke Sistem
                </button>
            </form>

            <div class="mt-8 text-center text-xs text-slate-400">
                &copy; {{ date('Y') }} {{ config('earsip.title', 'e-Arsip') }}. All rights reserved.
            </div>
        </div>
    </div>
</body>

</html>