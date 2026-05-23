<x-app-layout>
    <x-slot name="header">
        <div class="mb-4">
            <h2 class="text-2xl font-bold text-gray-900 leading-tight">
                Manage Scholarships
            </h2>
            <p class="text-gray-500 text-sm mt-1">Kelola data beasiswa dan dataset untuk chatbot</p>
        </div>
    </x-slot>

    <div class="py-6 bg-[#f8fafc] min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Top Section: Upload & Stats -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Upload Dataset Card -->
                <div class="col-span-1 lg:col-span-2 space-y-4">
                    <form action="{{ route('admin.scholarships.upload') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
                        @csrf
                        <div class="bg-white rounded-2xl border border-gray-200 p-8 flex flex-col items-center justify-center text-center border-dashed">
                            <div class="w-12 h-12 mb-4 text-gray-400">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                            </div>
                            <h3 class="text-gray-900 font-semibold mb-1">Upload dataset (JSON / CSV)</h3>
                            <p class="text-sm text-gray-400 mb-4">Pilih file untuk menambah atau memperbarui data beasiswa</p>
                            
                            <label class="bg-[#2563eb] hover:bg-blue-700 text-white px-6 py-2.5 rounded-lg text-sm font-medium inline-flex items-center transition-colors cursor-pointer">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                                Browse Files
                                <input type="file" name="dataset_file" accept=".csv,.json" class="hidden" onchange="document.getElementById('uploadForm').submit()">
                            </label>
                            <p class="text-xs text-gray-400 mt-4">Supported: JSON, CSV</p>
                        </div>
                    </form>

                    <!-- Sync Buttons -->
                    <div class="grid grid-cols-2 gap-4">
                        <form action="{{ route('admin.scholarships.sync_embeddings') }}" method="POST" class="w-full">
                            @csrf
                            <button type="submit" class="w-full bg-white border border-gray-200 rounded-xl py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 flex items-center justify-center transition-colors">
                                <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path></svg>
                                Sync Embeddings
                            </button>
                        </form>
                        <a href="{{ route('dashboard') }}" class="w-full bg-white border border-gray-200 rounded-xl py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 flex items-center justify-center transition-colors">
                            <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                            Refresh Data
                        </a>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="col-span-1 lg:col-span-1 flex flex-col gap-4">
                    <div class="bg-[#2563eb] rounded-xl p-6 text-white h-full flex flex-col justify-center">
                        <p class="text-blue-100 text-sm font-medium mb-2">Total Data</p>
                        <p class="text-4xl font-bold">{{ $scholarships->total() }}</p>
                    </div>
                    
                    <div class="bg-white rounded-xl border border-gray-200 p-5 flex items-center">
                        <div class="mr-4 text-gray-400">
                           <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 font-medium">Last Updated</p>
                            <p class="text-gray-900 font-medium text-sm mt-0.5">
                                {{ $lastUpdated ? \Carbon\Carbon::parse($lastUpdated)->format('d F Y') : 'No updates yet' }}
                            </p>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl border border-gray-200 p-5 flex items-center">
                        <div class="mr-4 text-gray-400">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4M4 12c0 2.21 3.582 4 8 4s8-1.79 8-4"></path></svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 font-medium">Source Type</p>
                            <p class="text-gray-900 font-medium text-sm mt-0.5">Supabase</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Bar -->
            <form action="{{ route('dashboard') }}" method="GET" class="bg-white border border-gray-200 rounded-xl p-3 flex flex-col md:flex-row gap-3">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by title or country..." class="pl-10 w-full border-0 focus:ring-0 text-sm text-gray-900 placeholder-gray-400 py-2 bg-transparent">
                </div>
                
                <div class="h-10 w-px bg-gray-200 hidden md:block"></div>
                
                <select name="destination" onchange="this.form.submit()" class="border-0 focus:ring-0 text-sm text-gray-700 py-2 bg-transparent flex-1 appearance-none bg-no-repeat bg-right pr-8" style="background-image: url('data:image/svg+xml;utf8,<svg width=\"20\" height=\"20\" viewBox=\"0 0 24 24\" fill=\"none\" xmlns=\"http://www.w3.org/2000/svg\"><path d=\"M6 9L12 15L18 9\" stroke=\"%239CA3AF\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"/></svg>'); bg-position: right 0.5rem center; background-size: 1.25rem;">
                    <option value="">All Destinations</option>
                    <option value="domestic" {{ request('destination') == 'domestic' ? 'selected' : '' }}>Domestic (Dalam Negeri)</option>
                    <option value="international" {{ request('destination') == 'international' ? 'selected' : '' }}>International (Luar Negeri)</option>
                </select>

                <div class="h-10 w-px bg-gray-200 hidden md:block"></div>

                <select name="degree" onchange="this.form.submit()" class="border-0 focus:ring-0 text-sm text-gray-700 py-2 bg-transparent flex-1 appearance-none bg-no-repeat bg-right pr-8" style="background-image: url('data:image/svg+xml;utf8,<svg width=\"20\" height=\"20\" viewBox=\"0 0 24 24\" fill=\"none\" xmlns=\"http://www.w3.org/2000/svg\"><path d=\"M6 9L12 15L18 9\" stroke=\"%239CA3AF\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"/></svg>'); bg-position: right 0.5rem center; background-size: 1.25rem;">
                    <option value="">All Degrees</option>
                    <option value="S1" {{ request('degree') == 'S1' ? 'selected' : '' }}>S1 / Bachelor</option>
                    <option value="S2" {{ request('degree') == 'S2' ? 'selected' : '' }}>S2 / Master</option>
                    <option value="S3" {{ request('degree') == 'S3' ? 'selected' : '' }}>S3 / PhD</option>
                </select>

                <div class="h-10 w-px bg-gray-200 hidden md:block"></div>

                <select name="status" onchange="this.form.submit()" class="border-0 focus:ring-0 text-sm text-gray-700 py-2 bg-transparent flex-1 appearance-none bg-no-repeat bg-right pr-8" style="background-image: url('data:image/svg+xml;utf8,<svg width=\"20\" height=\"20\" viewBox=\"0 0 24 24\" fill=\"none\" xmlns=\"http://www.w3.org/2000/svg\"><path d=\"M6 9L12 15L18 9\" stroke=\"%239CA3AF\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"/></svg>'); bg-position: right 0.5rem center; background-size: 1.25rem;">
                    <option value="">All Status</option>
                    <option value="embedded" {{ request('status') == 'embedded' ? 'selected' : '' }}>Embedded</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                </select>
                <button type="submit" class="hidden"></button>
            </form>

            <!-- Table Header & Actions -->
            <div class="flex justify-between items-center pt-2">
                <p class="text-sm text-gray-500">Showing {{ $scholarships->firstItem() }} to {{ $scholarships->lastItem() }} of {{ $scholarships->total() }} results</p>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-4 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Title</th>
                                    <th scope="col" class="px-4 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider whitespace-nowrap w-40">Country</th>
                                    <th scope="col" class="px-4 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider whitespace-nowrap w-32">Degree</th>
                                    <th scope="col" class="px-4 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider whitespace-nowrap w-28">Deadline</th>
                                    <th scope="col" class="px-4 py-4 text-center text-xs font-bold text-gray-700 uppercase tracking-wider whitespace-nowrap w-24">Status</th>
                                    <th scope="col" class="px-4 py-4 text-right text-xs font-bold text-gray-700 uppercase tracking-wider w-24">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @forelse($scholarships as $s)
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-4 py-4">
                                        <div class="text-sm font-semibold text-gray-900">{{ $s->nama_beasiswa ?? $s->name }}</div>
                                        <div class="text-xs text-gray-500 mt-1 truncate max-w-md">{{ Str::limit($s->deskripsi ?? $s->description, 70) }}</div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 font-medium">{{ $s->negara ?? $s->country }}</div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 inline-flex text-[10px] leading-4 font-semibold rounded-md bg-purple-100 text-purple-700">
                                            {{ $s->jenjang ?? $s->level }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                                        {{ $s->deadline }}
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-center">
                                        <span class="px-2.5 py-1 inline-flex text-[10px] leading-4 font-medium rounded-full {{ $s->embedding ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                            {{ $s->embedding ? 'Embedded' : 'Pending' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end items-center space-x-2">
                                            <a href="{{ route('admin.scholarships.edit', $s->id) }}" class="text-blue-500 hover:text-blue-700 p-1" title="Edit">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                            </a>
                                            <form action="{{ route('admin.scholarships.destroy', $s->id) }}" method="POST" onsubmit="return confirm('Hapus beasiswa ini?');" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-500 hover:text-red-700 p-1" title="Delete">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
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
