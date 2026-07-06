<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp Agent Admin · Klinik Bustari</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center bg-gray-50 p-4">
    <form method="POST" action="{{ route('login.submit') }}" class="w-full max-w-sm bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4">
        @csrf
        <h1 class="text-xl font-semibold text-gray-900">WhatsApp Agent Admin</h1>
        <p class="text-sm text-gray-500">Masukkan password admin.</p>
        <input
            type="password"
            name="password"
            placeholder="Admin password"
            required autofocus
            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
        >
        @error('password')
            <p class="text-xs text-red-600">{{ $message }}</p>
        @enderror
        <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg py-2">
            Masuk
        </button>
    </form>
</body>
</html>
