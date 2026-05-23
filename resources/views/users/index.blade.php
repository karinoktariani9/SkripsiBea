<x-app-layout>
    <x-slot name="header">
        <div class="mb-4">
            <h2 class="text-2xl font-bold text-gray-900 leading-tight">
                Manage Users
            </h2>
            <p class="text-gray-500 text-sm mt-1">Kelola data pengguna, peran (roles), dan izin akses</p>
        </div>
    </x-slot>

    <div class="py-6 bg-[#f8fafc] min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            @if (session('success'))
            <div x-data="{ show: true }" x-show="show" class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl relative shadow-sm flex items-center justify-between" role="alert">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span class="block sm:inline font-medium text-sm">{{ session('success') }}</span>
                </div>
                <button @click="show = false" class="text-emerald-500 hover:text-emerald-700 focus:outline-none">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            @endif

            <!-- Top Section: Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Total Users -->
                <div class="bg-[#2563eb] rounded-xl p-6 text-white flex flex-col justify-center">
                    <p class="text-blue-100 text-sm font-medium mb-2">Total Users</p>
                    <p class="text-4xl font-bold">{{ \App\Models\User::count() }}</p>
                </div>
                
                <!-- Admins -->
                <div class="bg-white rounded-xl border border-gray-200 p-6 flex flex-col justify-center">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium mb-2">Administrators</p>
                            <p class="text-3xl font-bold text-gray-900">{{ \App\Models\User::role('admin')->count() }}</p>
                        </div>
                        <div class="text-purple-500 bg-purple-50 rounded-full p-3">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                        </div>
                    </div>
                </div>

                <!-- Regular Users -->
                <div class="bg-white rounded-xl border border-gray-200 p-6 flex flex-col justify-center">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium mb-2">Regular Users</p>
                            <p class="text-3xl font-bold text-gray-900">{{ \App\Models\User::role('user')->count() }}</p>
                        </div>
                        <div class="text-blue-500 bg-blue-50 rounded-full p-3">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="bg-white border border-gray-200 rounded-xl p-3 flex flex-col md:flex-row gap-3">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <input type="text" placeholder="Search by name or email..." class="pl-10 w-full border-0 focus:ring-0 text-sm text-gray-900 placeholder-gray-400 py-2 bg-transparent">
                </div>
                
                <div class="h-10 w-px bg-gray-200 hidden md:block"></div>
                
                <select class="border-0 focus:ring-0 text-sm text-gray-700 py-2 bg-transparent flex-1 appearance-none bg-no-repeat bg-right pr-8 lg:max-w-[200px]" style="background-image: url('data:image/svg+xml;utf8,<svg width=\"20\" height=\"20\" viewBox=\"0 0 24 24\" fill=\"none\" xmlns=\"http://www.w3.org/2000/svg\"><path d=\"M6 9L12 15L18 9\" stroke=\"%239CA3AF\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"/></svg>'); bg-position: right 0.5rem center; background-size: 1.25rem;">
                    <option>All Roles</option>
                    <option>Admin</option>
                    <option>User</option>
                </select>

                <div class="h-10 w-px bg-gray-200 hidden md:block"></div>

                <select class="border-0 focus:ring-0 text-sm text-gray-700 py-2 bg-transparent flex-1 appearance-none bg-no-repeat bg-right pr-8 lg:max-w-[200px]" style="background-image: url('data:image/svg+xml;utf8,<svg width=\"20\" height=\"20\" viewBox=\"0 0 24 24\" fill=\"none\" xmlns=\"http://www.w3.org/2000/svg\"><path d=\"M6 9L12 15L18 9\" stroke=\"%239CA3AF\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"/></svg>'); bg-position: right 0.5rem center; background-size: 1.25rem;">
                    <option>All Status</option>
                    <option>Active</option>
                </select>
            </div>

            <!-- Table Header & Actions -->
            <div class="flex justify-between items-center pt-2">
                <p class="text-sm text-gray-500">Showing {{ $users->count() }} of {{ $users->total() }} users</p>
                <a href="{{ route('users.create') }}" class="bg-[#2563eb] hover:bg-blue-700 text-white px-5 py-2 rounded-lg text-sm font-medium flex items-center transition-colors shadow-sm">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Add User
                </a>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr class="bg-white">
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Name / Contact</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Role</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Joined Date</th>
                                <th scope="col" class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach ($users as $user)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-700 font-semibold border border-blue-100">
                                                {{ substr($user->name, 0, 1) }}
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-semibold text-gray-900">{{ $user->name }}</div>
                                            <div class="text-xs text-gray-500 mt-1">{{ $user->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex gap-1.5 flex-wrap">
                                        @forelse ($user->roles as $role)
                                            <span class="px-2 py-1 inline-flex text-xs leading-4 font-semibold rounded-md 
                                                {{ $role->name === 'admin' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                                                {{ ucfirst($role->name) }}
                                            </span>
                                        @empty
                                            <span class="px-2 py-1 inline-flex text-xs leading-4 font-semibold rounded-md bg-gray-100 text-gray-600">
                                                No Role
                                            </span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2.5 py-1 inline-flex text-xs leading-4 font-medium rounded-full bg-green-100 text-green-700">Active</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                                    {{ $user->created_at->format('d/m/Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                    <a href="{{ route('users.edit', $user) }}" class="inline-block text-blue-500 hover:text-blue-700 p-1 mx-1.5 focus:outline-none" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </a>
                                    <form action="{{ route('users.destroy', $user) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:text-red-700 p-1 ml-1.5 focus:outline-none" title="Delete">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="px-6 border-t border-gray-100 bg-gray-50/30">
                    {{ $users->links() }}
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
