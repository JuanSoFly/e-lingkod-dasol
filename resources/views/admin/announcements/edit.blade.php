@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">Edit Announcement</h2>
                    <a href="{{ route('admin.announcements.index') }}" class="text-gray-600 hover:text-gray-900">
                        &larr; Back to List
                    </a>
                </div>

                <form action="{{ route('admin.announcements.update', $announcement) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="mb-4">
                        <label for="title" class="block text-gray-700 text-sm font-bold mb-2">Title</label>
                        <input type="text" name="title" id="title" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" value="{{ old('title', $announcement->title) }}" required>
                        @error('title') <p class="text-red-500 text-xs italic">{{ $message }}</p> @enderror
                    </div>

                    <div class="mb-4">
                        <label for="message" class="block text-gray-700 text-sm font-bold mb-2">Message</label>
                        <textarea name="message" id="message" rows="4" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" required>{{ old('message', $announcement->message) }}</textarea>
                        @error('message') <p class="text-red-500 text-xs italic">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="type" class="block text-gray-700 text-sm font-bold mb-2">Type</label>
                            <select name="type" id="type" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                <option value="info" {{ old('type', $announcement->type) == 'info' ? 'selected' : '' }}>Info</option>
                                <option value="warning" {{ old('type', $announcement->type) == 'warning' ? 'selected' : '' }}>Warning</option>
                                <option value="danger" {{ old('type', $announcement->type) == 'danger' ? 'selected' : '' }}>Danger (Red)</option>
                                <option value="success" {{ old('type', $announcement->type) == 'success' ? 'selected' : '' }}>Success (Green)</option>
                            </select>
                            @error('type') <p class="text-red-500 text-xs italic">{{ $message }}</p> @enderror
                        </div>
                        
                        <div class="flex items-center mt-6">
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="is_important" class="form-checkbox h-5 w-5 text-indigo-600" style="border-radius: 4px;" value="1" {{ old('is_important', $announcement->is_important) ? 'checked' : '' }}>
                                <span class="ml-2 text-gray-700 font-bold">Mark as Important?</span>
                            </label>
                            @error('is_important') <p class="text-red-500 text-xs italic">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div>
                            <label for="starts_at" class="block text-gray-700 text-sm font-bold mb-2">Start Date (Optional)</label>
                            <input type="date" name="starts_at" id="starts_at" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" value="{{ old('starts_at', $announcement->starts_at?->format('Y-m-d')) }}">
                        </div>
                        <div>
                            <label for="ends_at" class="block text-gray-700 text-sm font-bold mb-2">End Date (Optional)</label>
                            <input type="date" name="ends_at" id="ends_at" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" value="{{ old('ends_at', $announcement->ends_at?->format('Y-m-d')) }}">
                        </div>
                    </div>

                    <div class="flex items-center justify-end">
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                            Update Announcement
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
