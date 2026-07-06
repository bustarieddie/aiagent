<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login · Klinik Bustari WhatsApp Agent</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-50 to-emerald-50 p-4">
    <div class="w-full max-w-sm bg-white rounded-2xl shadow-xl border border-gray-100 p-6 space-y-4">
        <div class="text-center">
            <div class="inline-block bg-emerald-100 rounded-full p-3 mb-2">
                <span class="text-3xl">💬</span>
            </div>
            <h1 class="text-xl font-semibold text-gray-900">Klinik Bustari</h1>
            <p class="text-sm text-gray-500 mt-0.5">WhatsApp AI Agent Admin</p>
        </div>

        @if (($stage ?? 'email') === 'email')
            <form method="POST" action="{{ route('login.request') }}" class="space-y-4">
                @csrf
                <label class="block text-sm">
                    <span class="text-gray-700">Email admin</span>
                    <input
                        type="email" name="email" value="{{ old('email') }}"
                        placeholder="admin@klinikbustari.com"
                        required autofocus
                        class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
                    >
                </label>
                @error('email')
                    <p class="text-xs text-red-600">{{ $message }}</p>
                @enderror
                <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg py-2">
                    Hantar Kod OTP
                </button>
                <p class="text-[11px] text-center text-gray-400">
                    Kod 6-digit akan dihantar ke email anda.
                </p>
            </form>
        @else
            <form method="POST" action="{{ route('login.verify.submit') }}" class="space-y-4">
                @csrf
                <p class="text-sm text-gray-600 text-center">
                    Kod OTP dihantar ke<br>
                    <strong class="text-gray-900">{{ $email }}</strong>
                </p>
                <label class="block text-sm">
                    <span class="text-gray-700">6-digit code</span>
                    <input
                        type="text" name="code" maxlength="6" pattern="\d{6}" inputmode="numeric"
                        placeholder="000000"
                        required autofocus
                        class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-3 text-2xl text-center font-mono tracking-widest focus:outline-none focus:ring-2 focus:ring-emerald-500"
                    >
                </label>
                @error('code')
                    <p class="text-xs text-red-600 text-center">{{ $message }}</p>
                @enderror
                <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg py-2">
                    Verify & Login
                </button>
                <a href="{{ route('login') }}" class="block text-center text-xs text-gray-500 hover:text-gray-700">
                    ← Guna email lain
                </a>
            </form>
        @endif
    </div>
</body>
</html>
