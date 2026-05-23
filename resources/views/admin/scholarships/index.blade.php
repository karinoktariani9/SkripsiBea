<x-app-layout>
    <x-slot name="header">
        <div class="mb-4">
            <h2 class="text-2xl font-bold text-gray-900 leading-tight">
                {{ __('Scholarship Management') }}
            </h2>
            <p class="text-gray-500 text-sm mt-1">Kelola daftar beasiswa dan data pendukung</p>
        </div>
    </x-slot>

    <div class="py-6 bg-[#f8fafc] min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            @if(session('success'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded-r-lg" role="alert">
                    <p class="text-sm font-medium">{{ session('success') }}</p>
                </div>
            @endif

            <!-- Table Header & Actions -->
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="flex flex-col">
                    <h3 class="text-lg font-bold text-gray-900">Daftar Beasiswa</h3>
                    <p class="text-xs text-gray-500">Showing {{ $scholarships->firstItem() }} to {{ $scholarships->lastItem() }} of {{ $scholarships->total() }} results</p>
                </div>
                
                <div class="flex space-x-3">
                    <a href="{{ route('admin.scholarships.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold uppercase tracking-widest rounded-lg transition-colors shadow-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Add Scholarship
                    </a>
                    
                    <form action="{{ route('admin.scholarships.sync_embeddings') }}" method="POST">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold uppercase tracking-widest rounded-lg transition-colors shadow-sm">
                            🤖 Sync Embeddings
                        </button>
                    </form>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-4 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Title</th>
                                <th scope="col" class="px-4 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider whitespace-nowrap w-40">Country</th>
                                <th scope="col" class="px-4 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider whitespace-nowrap w-32">Degree</th>
                                <th scope="col" class="px-4 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider whitespace-nowrap w-32">Deadline</th>
                                <th scope="col" class="px-4 py-4 text-center text-xs font-bold text-gray-700 uppercase tracking-wider whitespace-nowrap w-32">Vector Status</th>
                                <th scope="col" class="px-4 py-4 text-right text-xs font-bold text-gray-700 uppercase tracking-wider w-24">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse($scholarships as $scholarship)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-4 py-4">
                                        <div class="text-sm font-semibold text-gray-900">{{ $scholarship->nama_beasiswa ?? $scholarship->name }}</div>
                                        <div class="text-xs text-gray-500 mt-1 truncate max-w-md">{{ Str::limit($scholarship->deskripsi ?? $scholarship->description, 70) }}</div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 font-medium">{{ $scholarship->negara ?? $scholarship->country }}</div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 inline-flex text-[10px] leading-4 font-semibold rounded-md bg-purple-100 text-purple-700">
                                            {{ $scholarship->jenjang ?? $scholarship->level }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                                        {{ $scholarship->deadline }}
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-center">
                                        @if($scholarship->embedding)
                                            <span class="px-2.5 py-1 inline-flex text-[10px] leading-4 font-medium rounded-full bg-green-100 text-green-700">
                                                Synced
                                            </span>
                                        @else
                                            <span class="px-2.5 py-1 inline-flex text-[10px] leading-4 font-medium rounded-full bg-yellow-100 text-yellow-700">
                                                Pending
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end items-center space-x-2">
                                            <a href="{{ route('admin.scholarships.edit', $scholarship->id) }}" class="text-blue-500 hover:text-blue-700 p-1" title="Edit">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                            </a>
                                            <form action="{{ route('admin.scholarships.destroy', $scholarship->id) }}" method="POST" onsubmit="return confirm('Hapus data ini?');" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-500 hover:text-red-700 p-1" title="Delete">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                                        Belum ada data beasiswa di database.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination Links -->
            <div class="mt-4">
                {{ $scholarships->appends(request()->query())->links() }}
            </div>

        </div>
    </div>
</x-app-layout>
