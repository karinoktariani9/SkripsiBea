<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Beasiswa') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form action="{{ route('admin.scholarships.update', $scholarship->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-4">
                            <label for="nama_beasiswa" class="block text-sm font-medium text-gray-700">Judul Beasiswa</label>
                            <input type="text" name="nama_beasiswa" id="nama_beasiswa" value="{{ $scholarship->nama_beasiswa ?? $scholarship->name }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                        </div>

                        <div class="mb-4">
                            <label for="negara" class="block text-sm font-medium text-gray-700">Negara</label>
                            <input type="text" name="negara" id="negara" value="{{ $scholarship->negara ?? $scholarship->country }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>

                        <div class="mb-4">
                            <label for="jenjang" class="block text-sm font-medium text-gray-700">Jenjang</label>
                            <select name="jenjang" id="jenjang" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="S1" {{ ($scholarship->jenjang ?? $scholarship->level) == 'S1' ? 'selected' : '' }}>S1 / Sarjana</option>
                                <option value="S2" {{ ($scholarship->jenjang ?? $scholarship->level) == 'S2' ? 'selected' : '' }}>S2 / Magister</option>
                                <option value="S3" {{ ($scholarship->jenjang ?? $scholarship->level) == 'S3' ? 'selected' : '' }}>S3 / Doktoral</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="deskripsi" class="block text-sm font-medium text-gray-700">Deskripsi Lengkap (Teks ini yang akan dijadikan Vector AI)</label>
                            <textarea name="deskripsi" id="deskripsi" rows="5" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ $scholarship->deskripsi ?? $scholarship->description }}</textarea>
                            <p class="mt-1 text-sm text-gray-500">Catatan: Mengubah deskripsi akan mereset status Vector menjadi Pending (harus di-sync ulang).</p>
                        </div>

                        <div class="flex justify-end space-x-3">
                            <a href="{{ route('admin.scholarships.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-800 uppercase tracking-widest hover:bg-gray-300">
                                Batal
                            </a>
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                                Update Data
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
