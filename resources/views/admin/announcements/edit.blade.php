<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Announcement') }}
        </h2>
    </x-slot>

    <div class="space-y-6">
        <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <div>
                     <h3 class="text-lg font-medium text-gray-900">Edit Announcement</h3>
                     <p class="text-sm text-gray-500 mt-1">Update the details of the announcement.</p>
                </div>
                <a href="{{ route('admin.announcements.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                    &larr; Back to List
                </a>
            </div>
            <div class="p-6">
                <form action="{{ route('admin.announcements.update', $announcement) }}" method="POST" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <!-- Title -->
                    <div>
                        <x-input-label for="title" :value="__('Title')" />
                        <x-text-input id="title" class="block mt-1 w-full" type="text" name="title" :value="old('title', $announcement->title)" required autofocus />
                        <x-input-error :messages="$errors->get('title')" class="mt-2" />
                    </div>

                    <!-- Message -->
                    <div>
                        <x-input-label for="message" :value="__('Message')" />
                        <textarea id="message" name="message" rows="4" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>{{ old('message', $announcement->message) }}</textarea>
                        <x-input-error :messages="$errors->get('message')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Type -->
                        <div>
                            <x-input-label for="type" :value="__('Type')" />
                            <select id="type" name="type" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="info" {{ old('type', $announcement->type) == 'info' ? 'selected' : '' }}>Info</option>
                                <option value="warning" {{ old('type', $announcement->type) == 'warning' ? 'selected' : '' }}>Warning</option>
                                <option value="danger" {{ old('type', $announcement->type) == 'danger' ? 'selected' : '' }}>Danger (Red)</option>
                                <option value="success" {{ old('type', $announcement->type) == 'success' ? 'selected' : '' }}>Success (Green)</option>
                            </select>
                            <x-input-error :messages="$errors->get('type')" class="mt-2" />
                        </div>

                         <!-- Importance -->
                        <div class="flex items-center md:pt-8">
                             <label for="is_important" class="inline-flex items-center">
                                <input id="is_important" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="is_important" value="1" {{ old('is_important', $announcement->is_important) ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-600">{{ __('Mark as Important?') }}</span>
                            </label>
                            <x-input-error :messages="$errors->get('is_important')" class="mt-2" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Start Date -->
                        <div>
                            <x-input-label for="starts_at" :value="__('Start Date (Optional)')" />
                            <x-text-input id="starts_at" class="block mt-1 w-full" type="date" name="starts_at" :value="old('starts_at', $announcement->starts_at?->format('Y-m-d'))" />
                            <x-input-error :messages="$errors->get('starts_at')" class="mt-2" />
                        </div>

                        <!-- End Date -->
                        <div>
                            <x-input-label for="ends_at" :value="__('End Date (Optional)')" />
                            <x-text-input id="ends_at" class="block mt-1 w-full" type="date" name="ends_at" :value="old('ends_at', $announcement->ends_at?->format('Y-m-d'))" />
                            <x-input-error :messages="$errors->get('ends_at')" class="mt-2" />
                        </div>
                    </div>

                    <div class="flex items-center justify-end pt-6 border-t border-gray-200">
                        <a href="{{ route('admin.announcements.index') }}" class="mr-4 text-sm text-gray-600 hover:text-gray-900">
                             {{ __('Cancel') }}
                        </a>
                        <x-primary-button>
                            {{ __('Update Announcement') }}
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
