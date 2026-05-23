<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-4">
            <a href="{{ route('users.index') }}" class="text-gray-400 hover:text-indigo-600 transition-colors p-2 bg-white rounded-lg shadow-sm border border-gray-100 hover:border-indigo-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <div>
                <h2 class="font-semibold text-2xl text-gray-800 tracking-tight">
                    {{ __('Edit User') }} <span class="text-gray-400 font-normal">/ {{ $user->name }}</span>
                </h2>
            </div>
        </div>
    </x-slot>

    <div class="py-12 bg-gray-50/50 min-h-[calc(100vh-65px)]">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <!-- Header Decoration -->
                <div class="h-32 bg-gradient-to-r from-indigo-500 to-blue-600 relative overflow-hidden">
                    <div class="absolute inset-0 bg-white/10 [mask-image:linear-gradient(to_bottom,white,transparent)]"></div>
                </div>
                
                <div class="px-8 pb-8 relative">
                    <!-- Avatar Offset -->
                    <div class="-mt-12 mb-6 flex justify-between items-end">
                        <div class="h-24 w-24 rounded-2xl bg-white p-1.5 shadow-lg border border-gray-100">
                            <div class="h-full w-full rounded-xl bg-gradient-to-br from-indigo-100 to-blue-200 flex items-center justify-center text-4xl text-indigo-700 font-bold shadow-inner">
                                {{ substr($user->name, 0, 1) }}
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('users.update', $user) }}" class="space-y-8">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pb-8 border-b border-gray-100">
                            <!-- Name -->
                            <div>
                                <x-input-label for="name" :value="__('Name')" class="text-gray-500 font-medium mb-2" />
                                <div class="px-4 py-3 bg-gray-50 rounded-xl border border-gray-100 text-gray-900 font-medium">
                                    {{ $user->name }}
                                </div>
                                <p class="text-xs text-gray-400 mt-2">Names cannot be changed here.</p>
                            </div>

                            <!-- Email -->
                            <div>
                                <x-input-label for="email" :value="__('Email Adddress')" class="text-gray-500 font-medium mb-2" />
                                <div class="px-4 py-3 bg-gray-50 rounded-xl border border-gray-100 text-gray-900 font-medium">
                                    {{ $user->email }}
                                </div>
                            </div>
                        </div>

                        <!-- Roles -->
                        <div>
                            <x-input-label :value="__('Assign Roles')" class="text-lg font-semibold text-gray-900 mb-4" />
                            @error('roles')
                                <p class="text-sm text-red-600 mb-2">{{ $message }}</p>
                            @enderror
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                @foreach ($roles as $role)
                                    <label class="relative flex cursor-pointer items-start gap-4 rounded-xl border border-gray-200 bg-white p-5 transition-colors hover:bg-gray-50 has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50/50">
                                        <div class="flex items-center mt-0.5">
                                            <input type="checkbox" name="roles[]" value="{{ $role->name }}" 
                                                class="size-5 rounded border-gray-300 text-indigo-600 transition-all focus:ring-indigo-600 focus:ring-offset-2"
                                                @checked(in_array($role->name, $user->roles->pluck('name')->toArray()))>
                                        </div>

                                        <div>
                                            <strong class="font-medium text-gray-900 border-b border-transparent">
                                                {{ ucfirst($role->name) }} Role
                                            </strong>
                                            
                                            <p class="mt-1 text-sm text-gray-500">
                                                @if($role->name === 'admin')
                                                    Full access to all system features and user management.
                                                @else
                                                    Standard access to browse and save scholarships.
                                                @endif
                                            </p>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-4 pt-4 border-t border-gray-100 mt-8">
                            <a href="{{ route('users.index') }}" class="px-6 py-2.5 rounded-xl font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors">
                                Cancel
                            </a>
                            <button type="submit" class="px-6 py-2.5 rounded-xl font-medium text-white shadow-lg bg-indigo-600 hover:bg-indigo-700 focus:ring-offset-2 focus:ring-indigo-600 transition-all">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
