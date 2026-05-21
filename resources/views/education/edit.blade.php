<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Education Record') }} - {{ $employee->full_name }}
            </h2>
            <a href="{{ route('employees.education.index', $employee) }}">
                <x-secondary-button>
                    {{ __('Back to Education') }}
                </x-secondary-button>
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
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

            <form method="POST" action="{{ route('employees.education.update', [$employee, $education]) }}" enctype="multipart/form-data">
                @csrf
                @method('PATCH')

                <!-- Basic Information -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-6">Basic Information</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Education Level -->
                            <div class="md:col-span-2">
                                <label for="education_level" class="block text-sm font-medium text-gray-700">Education Level *</label>
                                <select name="education_level" id="education_level" required 
                                        onchange="toggleDegreeField()"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select Education Level</option>
                                    @foreach(\App\Models\EmployeeEducation::EDUCATION_LEVELS as $key => $label)
                                        <option value="{{ $key }}" {{ old('education_level', $education->education_level) == $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- School Name -->
                            <div class="md:col-span-2">
                                <label for="school_name" class="block text-sm font-medium text-gray-700">School Name *</label>
                                <input type="text" name="school_name" id="school_name" value="{{ old('school_name', $education->school_name) }}" required 
                                       placeholder="e.g. University of the Philippines"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Basic Course/Program -->
                            <div>
                                <label for="course" class="block text-sm font-medium text-gray-700">Basic Course/Program</label>
                                <input type="text" name="course" id="course" value="{{ old('course', $education->course) }}" 
                                       placeholder="e.g. Elementary Education, High School"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Degree/Course (for higher education) -->
                            <div id="degree_field" class="hidden">
                                <label for="degree_course" class="block text-sm font-medium text-gray-700">Degree/Course *</label>
                                <input type="text" name="degree_course" id="degree_course" value="{{ old('degree_course', $education->degree_course) }}" 
                                       placeholder="e.g. BACHELOR OF SCIENCE IN COMPUTER SCIENCE"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <p class="text-xs text-gray-500 mt-1">Enter the full degree name in CAPITAL LETTERS as it appears on official documents</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Period and Graduation -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-6">Period of Attendance & Graduation</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Period From -->
                            <div>
                                <label for="period_from" class="block text-sm font-medium text-gray-700">From (Year)</label>
                                <input type="number" name="period_from" id="period_from" value="{{ old('period_from', $education->period_from) }}" 
                                       min="1900" max="{{ date('Y') + 10 }}"
                                       placeholder="e.g. 2018"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Period To -->
                            <div>
                                <label for="period_to" class="block text-sm font-medium text-gray-700">To (Year)</label>
                                <input type="number" name="period_to" id="period_to" value="{{ old('period_to', $education->period_to) }}" 
                                       min="1900" max="{{ date('Y') + 10 }}"
                                       placeholder="e.g. 2022"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <p class="text-xs text-gray-500 mt-1">Leave blank if currently attending</p>
                            </div>

                            <!-- Year Graduated -->
                            <div>
                                <label for="year_graduated_pds" class="block text-sm font-medium text-gray-700">Year Graduated</label>
                                <input type="number" name="year_graduated_pds" id="year_graduated_pds" value="{{ old('year_graduated_pds', $education->year_graduated_pds) }}" 
                                       min="1900" max="{{ date('Y') + 10 }}"
                                       placeholder="e.g. 2022"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <p class="text-xs text-gray-500 mt-1">Leave blank if not yet graduated</p>
                            </div>

                            <!-- Highest Level/Units Earned -->
                            <div class="md:col-span-3">
                                <label for="highest_level_units_earned" class="block text-sm font-medium text-gray-700">Highest Level/Units Earned</label>
                                <input type="text" name="highest_level_units_earned" id="highest_level_units_earned" value="{{ old('highest_level_units_earned', $education->highest_level_units_earned) }}" 
                                       placeholder="e.g. Graduate, 150 units, 4th Year"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <p class="text-xs text-gray-500 mt-1">Specify highest level reached or number of units completed</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Honors and Recognition -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-6">Honors, Scholarships & Recognition</h3>
                        
                        <div>
                            <label for="scholarship_honors_received" class="block text-sm font-medium text-gray-700">Scholarship/Academic Honors Received</label>
                            <textarea name="scholarship_honors_received" id="scholarship_honors_received" rows="4"
                                      placeholder="e.g. Magna Cum Laude, Dean's List, Academic Scholarship, etc."
                                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">{{ old('scholarship_honors_received', $education->scholarship_honors_received) }}</textarea>
                            <p class="text-xs text-gray-500 mt-1">List all academic honors, scholarships, awards, and recognitions received</p>
                        </div>
                    </div>
                </div>

                <!-- Document Attachment -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-6">Document Attachment</h3>
                        
                        @if($education->attachment_id)
                            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <svg class="h-5 w-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                        </svg>
                                        <span class="text-sm font-medium text-green-800">Document attached</span>
                                    </div>
                                    <a href="{{ route('employees.education.download', [$employee, $education]) }}" 
                                       class="text-green-600 hover:text-green-800 text-sm">
                                        Download Current Document
                                    </a>
                                </div>
                            </div>
                        @endif
                        
                        <div>
                            <label for="attachment" class="block text-sm font-medium text-gray-700">
                                {{ $education->attachment_id ? 'Replace Document' : 'Education Document' }}
                            </label>
                            <input type="file" name="attachment" id="attachment" 
                                   accept=".pdf,.jpg,.jpeg,.png"
                                   class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            <p class="text-xs text-gray-500 mt-1">
                                {{ $education->attachment_id ? 'Upload a new file to replace the current document' : 'Upload diploma, transcript, certificate, or other education credential' }} (PDF, JPG, PNG - Max 5MB)
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end space-x-4">
                    <a href="{{ route('employees.education.index', $employee) }}">
                        <x-secondary-button>
                            {{ __('Cancel') }}
                        </x-secondary-button>
                    </a>
                    <x-primary-button>
                        {{ __('Update Education Record') }}
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>

    <!-- JavaScript for Dynamic Fields -->
    <script>
        function toggleDegreeField() {
            const levelSelect = document.getElementById('education_level');
            const degreeField = document.getElementById('degree_field');
            const degreeInput = document.getElementById('degree_course');
            
            const levelsRequiringDegree = ['College', 'Graduate Studies', 'Vocational/Trade'];
            
            if (levelSelect && degreeField && degreeInput) {
                if (levelsRequiringDegree.includes(levelSelect.value)) {
                    degreeField.classList.remove('hidden');
                    degreeInput.setAttribute('required', 'required');
                } else {
                    degreeField.classList.add('hidden');
                    degreeInput.removeAttribute('required');
                }
            }
        }

        // Expose function globally for inline onchange attribute
        window.toggleDegreeField = toggleDegreeField;

        // Initialize immediately or on page load
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', toggleDegreeField);
        } else {
            toggleDegreeField();
        }

        // Validate year ranges
        document.getElementById('period_to').addEventListener('change', function() {
            const fromYear = parseInt(document.getElementById('period_from').value);
            const toYear = parseInt(this.value);
            
            if (fromYear && toYear && toYear < fromYear) {
                alert('End year cannot be earlier than start year.');
                this.value = '';
            }
        });

        document.getElementById('year_graduated_pds').addEventListener('change', function() {
            const fromYear = parseInt(document.getElementById('period_from').value);
            const toYear = parseInt(document.getElementById('period_to').value);
            const gradYear = parseInt(this.value);
            
            if (fromYear && gradYear && gradYear < fromYear) {
                alert('Graduation year cannot be earlier than start year.');
                this.value = '';
            }
            
            if (toYear && gradYear && gradYear > toYear) {
                alert('Graduation year cannot be later than end year.');
                this.value = '';
            }
        });
    </script>
</x-app-layout>