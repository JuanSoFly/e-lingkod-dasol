<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Create New Office
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Office Information</h3>
                            <p class="mt-1 text-sm text-gray-600">Create a new municipal office for OPCR management</p>
                        </div>
                        <a href="{{ route('opcr.offices.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Cancel
                        </a>
                    </div>
                </div>

                <form method="POST" action="{{ route('opcr.offices.store') }}">
                    @csrf
                    <div class="p-6 space-y-6">
                        <!-- Basic Information -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="col-span-2 md:col-span-1">
                                <x-input-label for="name" value="Office Name *" />
                                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" value="{{ old('name') }}" placeholder="e.g., Accounting Office" required />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>

                            <div class="col-span-2 md:col-span-1">
                                <x-input-label for="code" value="Office Code" />
                                <x-text-input id="code" name="code" type="text" class="mt-1 block w-full" value="{{ old('code') }}" placeholder="e.g., ACCTO (optional - will auto-generate if empty)" />
                                <p class="mt-1 text-sm text-gray-500">Leave empty to auto-generate based on parent office</p>
                                <x-input-error :messages="$errors->get('code')" class="mt-2" />
                            </div>

                            <div class="col-span-2">
                                <x-input-label for="description" value="Description" />
                                <textarea id="description" name="description" rows="3" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" placeholder="Brief description of office functions and responsibilities">{{ old('description') }}</textarea>
                                <x-input-error :messages="$errors->get('description')" class="mt-2" />
                            </div>

                            <div class="col-span-2 md:col-span-1">
                                <x-input-label for="parent_id" value="Parent Office" />
                                <select id="parent_id" name="parent_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="">Select Parent Office (Optional)</option>
                                    @foreach($parentOffices as $office)
                                        <option value="{{ $office->id }}" {{ old('parent_id') == $office->id ? 'selected' : '' }}>
                                            {{ str_repeat('──', $office->level - 1) }} {{ $office->name }} ({{ $office->code }})
                                        </option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-sm text-gray-500">Select if this is a sub-office</p>
                                <x-input-error :messages="$errors->get('parent_id')" class="mt-2" />
                            </div>

                            <div class="col-span-2 md:col-span-1">
                                <x-input-label for="head_title" value="Head Title" />
                                <x-text-input id="head_title" name="head_title" type="text" class="mt-1 block w-full" value="{{ old('head_title') }}" placeholder="e.g., Municipal Accountant" />
                                <x-input-error :messages="$errors->get('head_title')" class="mt-2" />
                            </div>
                        </div>

                        <!-- Contact Information -->
                        <div class="border-t border-gray-200 pt-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Contact Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="col-span-2 md:col-span-1">
                                    <x-input-label for="contact_number" value="Contact Number" />
                                    <x-text-input id="contact_number" name="contact_number" type="tel" class="mt-1 block w-full" value="{{ old('contact_number') }}" placeholder="e.g., (047) 123-4567" />
                                    <x-input-error :messages="$errors->get('contact_number')" class="mt-2" />
                                </div>

                                <div class="col-span-2 md:col-span-1">
                                    <x-input-label for="email" value="Email Address" />
                                    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" value="{{ old('email') }}" placeholder="e.g., office@dasol.gov.ph" />
                                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                                </div>

                                <div class="col-span-2">
                                    <x-input-label for="location" value="Office Location" />
                                    <x-text-input id="location" name="location" type="text" class="mt-1 block w-full" value="{{ old('location') }}" placeholder="e.g., Municipal Hall Building, 2nd Floor" />
                                    <x-input-error :messages="$errors->get('location')" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="border-t border-gray-200 pt-6">
                            <div class="flex justify-end space-x-4">
                                <a href="{{ route('opcr.offices.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    Cancel
                                </a>
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition ease-in-out duration-150">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    Create Office
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>