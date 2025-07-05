<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Edit CSC Report
                </h2>
                <p class="text-sm text-gray-600 mt-1">{{ $report->report_number }}</p>
            </div>
            <a href="{{ route('csc-reports.show', $report) }}" 
               class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                Cancel
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('csc-reports.update', $report) }}">
                        @csrf
                        @method('PUT')

                        <!-- Title -->
                        <div class="mb-6">
                            <label for="title" class="block text-sm font-medium text-gray-700">Report Title</label>
                            <input type="text" name="title" id="title" 
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" 
                                   value="{{ old('title', $report->title) }}" required>
                            @error('title')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Description -->
                        <div class="mb-6">
                            <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea name="description" id="description" rows="4"
                                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">{{ old('description', $report->description) }}</textarea>
                            @error('description')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Submission Notes -->
                        <div class="mb-6">
                            <label for="submission_notes" class="block text-sm font-medium text-gray-700">Submission Notes</label>
                            <textarea name="submission_notes" id="submission_notes" rows="3"
                                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">{{ old('submission_notes', $report->submission_notes) }}</textarea>
                            @error('submission_notes')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Report Details (Read-only) -->
                        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                            <h4 class="text-sm font-medium text-gray-700 mb-3">Report Details (Read-only)</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                <div>
                                    <span class="font-medium">Type:</span>
                                    <span class="ml-2 capitalize">{{ str_replace('_', ' ', $report->report_type) }}</span>
                                </div>
                                <div>
                                    <span class="font-medium">Period:</span>
                                    <span class="ml-2">
                                        @if($report->report_month)
                                            {{ \Carbon\Carbon::create($report->report_year, $report->report_month, 1)->format('F Y') }}
                                        @else
                                            {{ $report->report_year }}
                                        @endif
                                    </span>
                                </div>
                                <div>
                                    <span class="font-medium">Status:</span>
                                    <span class="ml-2 capitalize">{{ $report->status }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end">
                            <button type="submit" 
                                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Update Report
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>