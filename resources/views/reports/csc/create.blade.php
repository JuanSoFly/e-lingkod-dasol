<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Generate CSC Report') }}
            </h2>
            <a href="{{ route('csc-reports.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                Back to Reports
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('csc-reports.store') }}" id="reportGenerationForm">
                        @csrf
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Report Type -->
                            <div class="md:col-span-2">
                                <label for="report_type" class="block text-sm font-medium text-gray-700 mb-2">
                                    Report Type <span class="text-red-500">*</span>
                                </label>
                                <select name="report_type" id="report_type" required 
                                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select Report Type</option>
                                    <option value="accession">Monthly Accession Report</option>
                                    <option value="separation">Monthly Separation Report</option>
                                    <option value="dibar">Monthly DIBAR Report</option>
                                    <option value="harassment">Monthly Sexual Harassment Cases Report</option>
                                    <option value="ighr">Annual IGHR Report</option>
                                </select>
                                @error('report_type')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Year -->
                            <div>
                                <label for="report_year" class="block text-sm font-medium text-gray-700 mb-2">
                                    Year <span class="text-red-500">*</span>
                                </label>
                                <select name="report_year" id="report_year" required 
                                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select Year</option>
                                    @for($i = 2020; $i <= date('Y'); $i++)
                                        <option value="{{ $i }}" {{ $i == date('Y') ? 'selected' : '' }}>{{ $i }}</option>
                                    @endfor
                                </select>
                                @error('report_year')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Month (for monthly reports) -->
                            <div id="monthField">
                                <label for="report_month" class="block text-sm font-medium text-gray-700 mb-2">
                                    Month <span class="text-red-500">*</span>
                                </label>
                                <select name="report_month" id="report_month" 
                                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select Month</option>
                                    @for($i = 1; $i <= 12; $i++)
                                        <option value="{{ $i }}" {{ $i == date('n') ? 'selected' : '' }}>
                                            {{ \Carbon\Carbon::create(null, $i, 1)->format('F') }}
                                        </option>
                                    @endfor
                                </select>
                                @error('report_month')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Department Filter -->
                            <div>
                                <label for="department" class="block text-sm font-medium text-gray-700 mb-2">
                                    Department (Optional)
                                </label>
                                <select name="department" id="department" 
                                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">All Departments</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept }}">{{ $dept }}</option>
                                    @endforeach
                                </select>
                                @error('department')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- File Format -->
                            <div>
                                <label for="file_format" class="block text-sm font-medium text-gray-700 mb-2">
                                    File Format <span class="text-red-500">*</span>
                                </label>
                                <select name="file_format" id="file_format" required 
                                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    <option value="both" selected>Both PDF and Excel</option>
                                    <option value="pdf">PDF Only</option>
                                    <option value="excel">Excel Only</option>
                                </select>
                                @error('file_format')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Additional Filters -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Additional Filters (Optional)
                                </label>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label for="employment_status" class="block text-xs text-gray-600 mb-1">Employment Status</label>
                                        <select name="filters[employment_status]" id="employment_status" 
                                                class="block w-full border-gray-300 rounded-md shadow-sm text-sm">
                                            <option value="">All Statuses</option>
                                            <option value="permanent">Permanent</option>
                                            <option value="temporary">Temporary</option>
                                            <option value="contractual">Contractual</option>
                                            <option value="casual">Casual</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="salary_grade_min" class="block text-xs text-gray-600 mb-1">Min Salary Grade</label>
                                        <input type="number" name="filters[salary_grade_min]" id="salary_grade_min" min="1" max="33"
                                               class="block w-full border-gray-300 rounded-md shadow-sm text-sm">
                                    </div>
                                    <div>
                                        <label for="salary_grade_max" class="block text-xs text-gray-600 mb-1">Max Salary Grade</label>
                                        <input type="number" name="filters[salary_grade_max]" id="salary_grade_max" min="1" max="33"
                                               class="block w-full border-gray-300 rounded-md shadow-sm text-sm">
                                    </div>
                                </div>
                            </div>

                            <!-- Description -->
                            <div class="md:col-span-2">
                                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                                    Description (Optional)
                                </label>
                                <textarea name="description" id="description" rows="3" 
                                          class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                          placeholder="Add any additional notes or context for this report..."></textarea>
                                @error('description')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Report Type Descriptions -->
                        <div class="mt-8 p-4 bg-blue-50 rounded-lg" id="reportDescription">
                            <h4 class="font-medium text-blue-900 mb-2">Report Information</h4>
                            <div id="descriptionContent" class="text-sm text-blue-800">
                                Select a report type to see its description and requirements.
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="mt-8 flex justify-end space-x-4">
                            <button type="button" onclick="window.history.back()" 
                                    class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                                Cancel
                            </button>
                            <button type="submit" id="generateButton" 
                                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                <span id="buttonText">Generate Report</span>
                                <span id="loadingSpinner" class="hidden">
                                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Generating...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const reportTypeSelect = document.getElementById('report_type');
            const monthField = document.getElementById('monthField');
            const reportMonthSelect = document.getElementById('report_month');
            const descriptionContent = document.getElementById('descriptionContent');
            const form = document.getElementById('reportGenerationForm');
            const generateButton = document.getElementById('generateButton');
            const buttonText = document.getElementById('buttonText');
            const loadingSpinner = document.getElementById('loadingSpinner');

            const reportDescriptions = {
                'accession': {
                    title: 'Monthly Accession Report',
                    description: 'Tracks new hires, appointments, and transfers-in for the selected month. This report is required by CSC and includes detailed information about each new employee including position, salary grade, and appointment type.',
                    monthly: true
                },
                'separation': {
                    title: 'Monthly Separation Report',
                    description: 'Tracks resignations, retirements, terminations, and transfers-out for the selected month. Includes separation type, effective date, and length of service for each departing employee.',
                    monthly: true
                },
                'dibar': {
                    title: 'Monthly DIBAR Report',
                    description: 'Reports employees "Dropped from the Rolls" (AWOL for 30+ days) for the selected month. This is a critical compliance report that must be submitted to CSC for administrative action.',
                    monthly: true
                },
                'harassment': {
                    title: 'Monthly Sexual Harassment Cases Report',
                    description: 'Confidential report tracking sexual harassment cases, investigations, and resolutions for the selected month. Includes case status, investigation timeline, and outcomes while maintaining confidentiality.',
                    monthly: true
                },
                'ighr': {
                    title: 'Annual IGHR Report',
                    description: 'Comprehensive Inventory of Government Human Resources for the entire year. Includes complete workforce demographics, employment statistics, performance metrics, and strategic HR analysis.',
                    monthly: false
                }
            };

            function updateReportTypeDisplay() {
                const selectedType = reportTypeSelect.value;
                
                if (selectedType && reportDescriptions[selectedType]) {
                    const info = reportDescriptions[selectedType];
                    descriptionContent.innerHTML = `
                        <strong>${info.title}</strong><br>
                        ${info.description}
                    `;
                    
                    // Show/hide month field based on report type
                    if (info.monthly) {
                        monthField.style.display = 'block';
                        reportMonthSelect.required = true;
                    } else {
                        monthField.style.display = 'none';
                        reportMonthSelect.required = false;
                        reportMonthSelect.value = '';
                    }
                } else {
                    descriptionContent.textContent = 'Select a report type to see its description and requirements.';
                    monthField.style.display = 'block';
                    reportMonthSelect.required = false;
                }
            }

            // Update display when report type changes
            reportTypeSelect.addEventListener('change', updateReportTypeDisplay);
            
            // Initial update
            updateReportTypeDisplay();

            // Handle form submission
            form.addEventListener('submit', function(e) {
                generateButton.disabled = true;
                buttonText.classList.add('hidden');
                loadingSpinner.classList.remove('hidden');
                
                // Re-enable button after 10 seconds as fallback
                setTimeout(function() {
                    generateButton.disabled = false;
                    buttonText.classList.remove('hidden');
                    loadingSpinner.classList.add('hidden');
                }, 10000);
            });
        });
    </script>
</x-app-layout>