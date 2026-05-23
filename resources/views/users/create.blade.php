<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center space-x-4">
            <a href="{{ route('users.index') }}" class="text-gray-400 hover:text-[#2563eb] transition-colors p-2 bg-white rounded-lg shadow-sm border border-gray-100 hover:border-blue-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <div>
                <h2 class="font-bold text-2xl text-gray-900 tracking-tight">
                    {{ __('Create User') }}
                </h2>
            </div>
        </div>
    </x-slot>

    <div class="py-12 bg-[#f8fafc] min-h-[calc(100vh-65px)]">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <!-- Header Decoration -->
                <div class="h-16 bg-[#2563eb] relative overflow-hidden">
                    <div class="absolute inset-0 bg-white/10 [mask-image:linear-gradient(to_bottom,white,transparent)]"></div>
                </div>
                
                <div class="px-8 pb-8 pt-8 relative">
                    <form method="POST" action="{{ route('users.store') }}" class="space-y-6">
                        @csrf

                        <div class="grid grid-cols-1 gap-6 pb-6 border-b border-gray-100">
                            <!-- Name -->
                            <div>
                                <x-input-label for="name" :value="__('Name')" class="text-gray-700 font-medium mb-1.5" />
                                <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus class="w-full px-4 py-2.5 bg-white rounded-xl border border-gray-200 text-gray-900 focus:border-[#2563eb] focus:ring-[#2563eb] shadow-sm transition-colors" placeholder="e.g. John Doe">
                                @error('name')
                                    <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Email -->
                            <div>
                                <x-input-label for="email" :value="__('Email Address')" class="text-gray-700 font-medium mb-1.5" />
                                <input id="email" type="email" name="email" value="{{ old('email') }}" required class="w-full px-4 py-2.5 bg-white rounded-xl border border-gray-200 text-gray-900 focus:border-[#2563eb] focus:ring-[#2563eb] shadow-sm transition-colors" placeholder="e.g. john@example.com">
                                @error('email')
                                    <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Password -->
                            <div>
                                <x-input-label for="password" :value="__('Password')" class="text-gray-700 font-medium mb-1.5" />
                                <input id="password" type="password" name="password" required class="w-full px-4 py-2.5 bg-white rounded-xl border border-gray-200 text-gray-900 focus:border-[#2563eb] focus:ring-[#2563eb] shadow-sm transition-colors" placeholder="********">
                                @error('password')
                                    <p class="text-sm text-red-600 mt-2">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Roles -->
                        <div>
                            <x-input-label :value="__('Assign Roles')" class="text-lg font-semibold text-gray-900 mb-4 mt-2" />
                            @error('roles')
                                <p class="text-sm text-red-600 mb-2">{{ $message }}</p>
                            @enderror
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                @foreach ($roles as $role)
                                    <label class="relative flex cursor-pointer items-start gap-4 rounded-xl border border-gray-200 bg-white p-5 transition-colors hover:bg-gray-50 has-[:checked]:border-[#2563eb] has-[:checked]:bg-blue-50/50">
                                        <div class="flex items-center mt-0.5">
                                            <input type="checkbox" name="roles[]" value="{{ $role->name }}" 
                                                class="size-5 rounded border-gray-300 text-[#2563eb] transition-all focus:ring-[#2563eb] focus:ring-offset-2">
                                        </div>

                                        <div>
                                            <strong class="font-medium text-gray-900 border-b border-transparent">
                                                {{ ucfirst($role->name) }} Role
                                            </strong>
                                            
                                            <p class="mt-1 text-sm text-gray-500">
                                                @if($role->name === 'admin')
                                                    Full access to all system features.
                                                @else
                                                    Standard access to browse.
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
                            <button type="submit" class="px-6 py-2.5 rounded-xl font-medium text-white shadow-md bg-[#2563eb] hover:bg-blue-700 focus:ring-offset-2 focus:ring-[#2563eb] transition-all">
                                Create User
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
