<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit Performance Period
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <form action="{{ route('admin.performance-periods.update', $period) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="p-6 space-y-6">
                        <!-- Basic Information -->
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <x-input-label for="year" :value="__('Year')" />
                                    <select id="year" name="year" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                                        @for($y = date('Y'); $y <= date('Y') + 2; $y++)
                                            <option value="{{ $y }}" {{ old('year', $period->year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                                        @endfor
                                    </select>
                                    <x-input-error :messages="$errors->get('year')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="semester" :value="__('Semester')" />
                                    <select id="semester" name="semester" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                                        <option value="first" {{ old('semester', $period->semester) == 'first' ? 'selected' : '' }}>First Semester</option>
                                        <option value="second" {{ old('semester', $period->semester) == 'second' ? 'selected' : '' }}>Second Semester</option>
                                        <option value="annual" {{ old('semester', $period->semester) == 'annual' ? 'selected' : '' }}>Annual</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('semester')" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <!-- Date Configuration -->
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Date Configuration</h3>
                            <div class="bg-blue-50 border border-blue-200 rounded-md p-4 mb-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-blue-700">
                                            Configure the evaluation period dates for this performance period.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <x-input-label for="start_date" :value="__('Start Date')" />
                                    <input type="date" id="start_date" name="start_date" value="{{ old('start_date', $period->start_date->format('Y-m-d')) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                                    <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="end_date" :value="__('End Date')" />
                                    <input type="date" id="end_date" name="end_date" value="{{ old('end_date', $period->end_date->format('Y-m-d')) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                                    <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <!-- Additional Settings -->
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Additional Settings</h3>
                            <div class="space-y-4">
                                <div>
                                    <x-input-label for="description" :value="__('Description')" />
                                    <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Optional description for this performance period">{{ old('description', $period->description) }}</textarea>
                                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                                </div>

                                <div class="flex items-center">
                                    <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $period->is_active) ? 'checked' : '' }} class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                    <label for="is_active" class="ml-2 block text-sm text-gray-900">
                                        Mark as active period
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
                            <a href="{{ route('admin.performance-periods.show', $period) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Cancel
                            </a>
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Update Period
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>