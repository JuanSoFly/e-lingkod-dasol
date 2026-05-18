<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Add Performance Period') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('performance-periods.store') }}">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div><x-input-label for="year" value="Year" /><x-text-input id="year" name="year" type="number" class="mt-1 block w-full" :value="old('year', date('Y'))" required /></div>
                        <div><x-input-label for="semester" value="Semester / Cycle" /><x-text-input id="semester" name="semester" type="text" class="mt-1 block w-full" :value="old('semester')" placeholder="e.g., 1st Semester" required /></div>
                        <div class="md:col-span-2"><x-input-label for="name" value="Period Name (Optional)" /><x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" placeholder="Leave blank for default format (e.g. 2026 - 1st Semester)" /></div>
                        <div><x-input-label for="start_date" value="Start Date" /><x-text-input id="start_date" name="start_date" type="date" class="mt-1 block w-full" :value="old('start_date')" required /></div>
                        <div><x-input-label for="end_date" value="End Date" /><x-text-input id="end_date" name="end_date" type="date" class="mt-1 block w-full" :value="old('end_date')" required /></div>
                        <div>
                            <x-input-label for="status" value="Status" />
                            <select id="status" name="status" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="active">Active</option>
                                <option value="inactive" selected>Inactive</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex items-center justify-end mt-4"><x-primary-button>Save Period</x-primary-button></div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>