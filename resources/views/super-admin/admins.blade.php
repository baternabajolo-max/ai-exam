<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Faculty - LEMU Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="h-full bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <div class="w-64 bg-gray-800 text-white flex flex-col justify-between p-6">
            <div class="space-y-6">
                <div class="text-2xl font-black tracking-wider text-gray-200">LEMU Admin</div>
                <nav class="space-y-2">
                    <a href="{{ route('super-admin.dashboard') }}" class="block px-4 py-2 rounded-lg hover:bg-gray-700 transition">Dashboard & Analytics</a>
                    <a href="#" class="block px-4 py-2 rounded-lg bg-gray-700 font-semibold">Faculty Management</a>
                </nav>
            </div>
            <div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full py-2 bg-red-600 hover:bg-red-700 rounded-lg text-sm font-bold transition">Sign Out</button>
                </form>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-y-auto">
            <header class="bg-white dark:bg-gray-800 shadow px-8 py-4 flex justify-between items-center">
                <h1 class="text-xl font-bold">Faculty Accounts Overview</h1>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</span>
            </header>

            <main class="p-8 space-y-8 max-w-7xl w-full mx-auto">
                <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-xl space-y-4">
                    <div class="flex justify-between items-center">
                        <h2 class="text-lg font-bold">All Registered Faculty</h2>
                        <a href="{{ route('super-admin.dashboard') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700 transition">Back to Dashboard</a>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>