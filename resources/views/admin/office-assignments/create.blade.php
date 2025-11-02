@extends('layouts.app')

@section('title', 'Create Office Assignment')

@section('content')
<div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex items-center mb-6">
                <a href="{{ route('admin.office-assignments.index') }}" class="text-blue-600 hover:text-blue-800 mr-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </a>
                <h1 class="text-2xl font-semibold text-gray-900">Create Office Assignment</h1>
            </div>

            <form method="POST" action="{{ route('admin.office-assignments.store') }}" class="space-y-6">
                @csrf

                <!-- User Selection -->
                <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="user_id" class="block text-sm font-medium text-gray-700">
                            User <span class="text-red-500">*</span>
                        </label>
                        <select id="user_id" name="user_id" required class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                            <option value="">Select a user</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->email }} @if($user->employee) - {{ $user->employee->full_name }} @endif
                                </option>
                            @endforeach
                        </select>
                        @error('user_id')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Office Selection -->
                    <div>
                        <label for="office_id" class="block text-sm font-medium text-gray-700">
                            Office <span class="text-red-500">*</span>
                        </label>
                        <select id="office_id" name="office_id" required class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                            <option value="">Select an office</option>
                            @foreach($offices as $office)
                                <option value="{{ $office->id }}" {{ old('office_id') == $office->id ? 'selected' : '' }}>
                                    {{ $office->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('office_id')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Role Selection -->
                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700">
                            Role <span class="text-red-500">*</span>
                        </label>
                        <select id="role" name="role" required class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                            <option value="">Select a role</option>
                            @foreach($roles as $key => $description)
                                <option value="{{ $key }}" {{ old('role') == $key ? 'selected' : '' }}>
                                    {{ $description }}
                                </option>
                            @endforeach
                        </select>
                        @error('role')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Active Status -->
                <div class="flex items-center">
                    <input id="is_active" name="is_active" type="checkbox" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="is_active" class="ml-2 block text-sm text-gray-900">
                        Active assignment
                    </label>
                </div>

                <!-- Notes -->
                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700">
                        Notes <span class="text-gray-500">(optional)</span>
                    </label>
                    <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Any additional notes about this assignment...">{{ old('notes') }}</textarea>
                    @error('notes')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Role Information -->
                <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                    <h3 class="text-sm font-medium text-blue-900 mb-2">Role Responsibilities</h3>
                    <div class="text-sm text-blue-800 space-y-1">
                        <p><strong>Department Head:</strong> Manages OPCR creation, updates, and submissions for the office</p>
                        <p><strong>Assessor (PMT):</strong> Evaluates OPCR performance and provides ratings</p>
                        <p><strong>Final Approver (Mayor):</strong> Gives final approval and signs off on OPCR</p>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex justify-end space-x-3 pt-6 border-t">
                    <a href="{{ route('admin.office-assignments.index') }}" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Cancel
                    </a>
                    <button type="submit" class="bg-blue-600 py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Create Assignment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection