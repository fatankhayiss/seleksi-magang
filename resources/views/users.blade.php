<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - Imported Data</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-900">
    <div class="max-w-5xl mx-auto py-10 px-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-semibold mb-6">Imported Users</h1>
            <a href="/import" class="text-sm text-indigo-600 hover:text-indigo-700 underline">Kembali ke Import</a>
        </div>

        <div class="bg-white shadow rounded overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Address</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($users as $u)
                        <tr>
                            <td class="px-4 py-2 text-sm">{{ $u->name }}</td>
                            <td class="px-4 py-2 text-sm">{{ $u->email }}</td>
                            <td class="px-4 py-2 text-sm">{{ $u->address }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-6 text-center text-sm text-gray-500">Belum ada data.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $users->links() }}
        </div>
    </div>
</body>
</html>
