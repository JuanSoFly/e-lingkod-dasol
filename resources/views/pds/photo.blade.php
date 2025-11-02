<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Employee Photo') }} - {{ $employee->full_name }}
            </h2>
            <a href="{{ route('employee-portal.my-201-file') }}">
                <x-secondary-button>
                    {{ __('Back to PDS Dashboard') }}
                </x-secondary-button>
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Success Message -->
            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Validation Errors -->
            @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Photo Upload Section -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-6">Identification Photo (3.5 cm × 4.5 cm)</h3>

                        <form method="POST" action="{{ route('pds.upload-photo', $employee) }}" enctype="multipart/form-data">
                            @csrf

                            <!-- Current Photo Display -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Current Photo</label>
                                <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center">
                                    @if($employee->activePhoto && $employee->activePhoto->photo_path)
                                        <div class="mb-4">
                                            <img src="{{ $employee->activePhoto->photo_url }}" alt="Current Photo"
                                                 class="mx-auto h-48 w-auto object-cover rounded-lg shadow-md">
                                        </div>
                                        <p class="text-sm text-gray-600 mb-2">
                                            Uploaded: {{ $employee->activePhoto->created_at->format('M d, Y') }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            Size: {{ $employee->activePhoto->photo_size_kb }} KB
                                        </p>
                                    @else
                                        <div class="py-8">
                                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            <p class="mt-2 text-sm text-gray-600">No photo uploaded yet</p>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Photo Upload -->
                            <div class="mb-6">
                                <label for="photo" class="block text-sm font-medium text-gray-700 mb-2">
                                    Upload New Photo *
                                </label>
                                <input type="file" name="photo" id="photo" accept="image/jpeg,image/jpg" required
                                       class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                <p class="mt-1 text-xs text-gray-500">
                                    Accepted format: JPG/JPEG. Maximum size: 2MB. Recommended dimensions: 3.5 cm × 4.5 cm (300 DPI).
                                </p>
                            </div>

                            <div class="flex justify-end">
                                <x-primary-button>
                                    {{ __('Upload Photo') }}
                                </x-primary-button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Thumbmark Upload Section -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-6">Right Thumbmark (2.5 cm × 2.5 cm)</h3>

                        <form method="POST" action="{{ route('pds.upload-thumbmark', $employee) }}" enctype="multipart/form-data">
                            @csrf

                            <!-- Current Thumbmark Display -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Current Thumbmark</label>
                                <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center">
                                    @if($employee->activePhoto && $employee->activePhoto->thumbmark_path)
                                        <div class="mb-4">
                                            <img src="{{ $employee->activePhoto->thumbmark_url }}" alt="Current Thumbmark"
                                                 class="mx-auto h-32 w-auto object-cover rounded-lg shadow-md">
                                        </div>
                                        <p class="text-sm text-gray-600 mb-2">
                                            Uploaded: {{ $employee->activePhoto->created_at->format('M d, Y') }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            Size: {{ $employee->activePhoto->thumbmark_size_kb }} KB
                                        </p>
                                    @else
                                        <div class="py-8">
                                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11.5V14m0-2.5v-6a1.5 1.5 0 113 0m-3 6a1.5 1.5 0 00-3 0v2a7.5 7.5 0 0015 0v-5a1.5 1.5 0 00-3 0m-6-3V11m0-5.5v-1a1.5 1.5 0 013 0v1m0 0V11m0-5.5a1.5 1.5 0 013 0v3m0 0V11" />
                                            </svg>
                                            <p class="mt-2 text-sm text-gray-600">No thumbmark uploaded yet</p>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Thumbmark Upload -->
                            <div class="mb-6">
                                <label for="thumbmark" class="block text-sm font-medium text-gray-700 mb-2">
                                    Upload New Thumbmark *
                                </label>
                                <input type="file" name="thumbmark" id="thumbmark" accept="image/jpeg,image/jpg" required
                                       class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                                <p class="mt-1 text-xs text-gray-500">
                                    Accepted format: JPG/JPEG. Maximum size: 1MB. Recommended dimensions: 2.5 cm × 2.5 cm (300 DPI).
                                </p>
                            </div>

                            <div class="flex justify-end">
                                <x-primary-button>
                                    {{ __('Upload Thumbmark') }}
                                </x-primary-button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Guidelines Section -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mt-8">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">CSC Form No. 212 Photo Requirements</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <p class="font-medium text-blue-800 mb-2">Identification Photo Specifications:</p>
                                    <ul class="list-disc list-inside space-y-1 text-xs">
                                        <li><strong>Size:</strong> 3.5 cm × 4.5 cm (1.38" × 1.77")</li>
                                        <li><strong>Format:</strong> JPEG/JPG only</li>
                                        <li><strong>Quality:</strong> 300 DPI or higher</li>
                                        <li><strong>Maximum File Size:</strong> 2MB</li>
                                        <li><strong>Background:</strong> White or light-colored</li>
                                        <li><strong>Appearance:</strong> Professional attire, plain background</li>
                                        <li><strong>Name Tag:</strong> Handwritten name tag visible across chest</li>
                                    </ul>
                                </div>

                                <div>
                                    <p class="font-medium text-blue-800 mb-2">Thumbmark Specifications:</p>
                                    <ul class="list-disc list-inside space-y-1 text-xs">
                                        <li><strong>Size:</strong> 2.5 cm × 2.5 cm (1" × 1")</li>
                                        <li><strong>Format:</strong> JPEG/JPG only</li>
                                        <li><strong>Quality:</strong> Clear and readable</li>
                                        <li><strong>Maximum File Size:</strong> 1MB</li>
                                        <li><strong>Color:</strong> Blue or black ink preferred</li>
                                        <li><strong>Clarity:</strong> Distinct ridges visible</li>
                                        <li><strong>Position:</strong> Right thumb only</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="mt-4 p-3 bg-yellow-100 rounded-md">
                                <p class="text-xs font-medium text-yellow-800">Important Notes:</p>
                                <ul class="text-xs text-yellow-700 mt-1 space-y-1">
                                    <li>• Photos must be recent (taken within the last 6 months)</li>
                                    <li>• Both photo and thumbmark are required for CSC Form No. 212 completion</li>
                                    <li>• Ensure both images are clear and meet specifications</li>
                                    <li>• HR office may request retake if requirements are not met</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
