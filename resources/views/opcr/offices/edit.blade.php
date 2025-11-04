<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit Office: {{ $office->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Edit Office Information</h3>
                            <p class="mt-1 text-sm text-gray-600">Update office details for OPCR management</p>
                        </div>
                        <a href="{{ route('opcr.offices.show', $office) }}" class="inline-flex items-center px-4 py-2 bg-gray-100 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17H9l2v6a2H6l-7l-4l8-2l8.586l-.707-5.293-5.293-5.293-5.293-5.707H4l-6a2 6 7l4 4s-1 2 2 3-4 6h8l4-6z"></path>
                            </svg>
                            Back to Office
                        </a>
                    </div>
                </div>

                <form method="POST" action="{{ route('opcr.offices.update', $office) }}">
                    @csrf
                    @method('PATCH')
                    <div class="p-6 space-y-6">
                        <!-- Basic Information -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="col-span-2 md:col-span-1">
                                <x-input-label for="name" value="Office Name *" />
                                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" value="{{ old('name') ?? $office->name }}" placeholder="e.g., Accounting Office" required />
                                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                            </div>

                            <div class="col-span-2 md:col-span-1">
                                <x-input-label for="code" value="Office Code" />
                                <x-text-input id="code" name="code" type="text" class="mt-1 block w-full" value="{{ old('code') ?? $office->code }}" placeholder="e.g., ACCTO" />
                                <p class="mt-1 text-sm text-gray-500">Current office code: {{ $office->code }}</p>
                                <x-input-error :messages="$errors->get('code')" class="mt-2" />
                            </div>

                            <div class="col-span-2">
                                <x-input-label for="description" value="Description" />
                                <textarea id="description" name="description" rows="3" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" placeholder="Brief description of office functions and responsibilities">{{ old('description') ?? $office->description }}</textarea>
                                <x-input-error :messages="$errors->get('description')" class="mt-2" />
                            </div>

                            <div class="col-span-2 md:col-span-1">
                                <x-input-label for="parent_id" value="Parent Office" />
                                <select id="parent_id" name="parent_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="">Select Parent Office (Optional)</option>
                                    @foreach($parentOffices as $parentOffice)
                                        <option value="{{ $parentOffice->id }}" {{ old('parent_id') == $parentOffice->id ? 'selected' : '' }}>
                                            {{ str_repeat('──', $parentOffice->level - 1) }} {{ $parentOffice->name }} ({{ $parentOffice->code }})
                                        </option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-sm text-gray-500">Current: {{ $office->parent ? $office->parent->name : 'None' }}</p>
                                <x-input-error :messages="$errors->get('parent_id')" class="mt-2" />
                            </div>

                            <div class="col-span-2 md:col-span-1">
                                <x-input-label for="head_title" value="Head Title" />
                                <x-text-input id="head_title" name="head_title" type="text" class="mt-1 block w-full" value="{{ old('head_title') ?? $office->head_title }}" placeholder="e.g., Municipal Accountant" />
                                <x-input-error :messages="$errors->get('head_title')" class="mt-2" />
                            </div>

                            <div class="col-span-2">
                                <x-input-label for="level" value="Office Level" />
                                <input type="number" id="level" name="level" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" value="{{ old('level') ?? $office->level }}" min="1" />
                                <x-input-error :messages="$errors->get('level')" class="mt-2" />
                            </div>
                        </div>

                        <!-- Contact Information -->
                        <div class="border-t border-gray-200 pt-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Contact Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="col-span-2 md:col-span-1">
                                    <x-input-label for="contact_number" value="Contact Number" />
                                    <x-text-input id="contact_number" name="contact_number" type="tel" class="mt-1 block w-full" value="{{ old('contact_number') ?? $office->contact_number }}" placeholder="e.g., (047) 123-4567" />
                                    <x-input-error :messages="$errors->get('contact_number')" class="mt-2" />
                                </div>

                                <div class="col-span-2 md:col-span-1">
                                    <x-input-label for="email" value="Email Address" />
                                    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" value="{{ old('email') ?? $office->email }}" placeholder="e.g., office@dasol.gov.ph" />
                                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                                </div>

                                <div class="col-span-2">
                                    <x-input-label for="location" value="Office Location" />
                                    <x-text-input id="location" name="location" type="text" class="mt-1 block w-full" value="{{ old('location') ?? $office->location }}" placeholder="e.g., Municipal Hall Building, 2nd Floor" />
                                    <x-input-error :messages="$errors->get('location')" class="mt-2" />
                                </div>
                            </div>
                        </div>

  
                        <!-- Form Actions -->
                        <div class="border-t border-gray-200 pt-6">
                            <div class="flex justify-end space-x-4">
                                <a href="{{ route('opcr.offices.show', $office) }}" class="inline-flex items-center px-4 py-2 bg-gray-100 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    Cancel
                                </a>
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition ease-in-out duration-150">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4h6v1a1 11 11.89 2 5 2 1 1 3 4s1m-2 7-11 11.89 2-13 12-4 8-1 8-1 9v1.65 9s2m-2 15-4 15-4v1.65 9-1 10s2m-2 18-6.65 11.67 16.34 10-16 34l3 11-2 17 8 17-32v-3.2.88 8.82a6.96 16.96a6.04 18.24-7.03 24-20 34l3 11.5 24-32 26.58 31.4l3 11.67 32 48.8a6.02 32.98 36.27 34l3 12.12 38.04 40.8a8.02 42.08 44.58l3 14.04 46.72 48.6a8.12 50.96 56l3 16.02 56.42 60.8a8.22 62.1l4 18.06 64.66l3 20.82 68.36 72.5l4 23.38 76.24 80.8a8.34 82.92 88l4 26.4 89.18 92.98l4 27.58 96.48l4 31.38 99.42l4 32.06 102.44l4 35.74 106.8a8.42 108.98l4 38.08 112.56l4 41.66 116.64l4 44.42 121.72l4 45.26 124.8l4 46.84 128.96l4 49.02"></path>
                                    </svg>
                                    Update Office
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
