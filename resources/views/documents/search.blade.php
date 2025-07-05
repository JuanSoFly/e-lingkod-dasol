<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Document Search') }}
        </h2>
    </x-slot>

    <div class="space-y-6">
        <!-- Search Form -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-gray-200">
                <div class="p-6">
                    <form id="documentSearchForm" class="space-y-6">
                        @csrf
                        
                        <!-- Main Search Bar -->
                        <div>
                            <label for="search_query" class="block text-sm font-medium text-gray-700">
                                Search Documents
                            </label>
                            <div class="mt-1 relative">
                                <input type="text" id="search_query" name="search_query" 
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Enter keywords, employee name, document type, or content...">
                                <button type="submit" 
                                    class="absolute inset-y-0 right-0 px-4 py-2 bg-gray-800 text-white rounded-r-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Advanced Filters -->
                        <div class="border-t pt-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Advanced Filters</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <!-- Document Type Filter -->
                                <div>
                                    <label for="document_type" class="block text-sm font-medium text-gray-700">
                                        Document Type
                                    </label>
                                    <select id="document_type" name="document_type"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">All Types</option>
                                        <option value="appointment">Appointment</option>
                                        <option value="performance_rating">Performance Rating</option>
                                        <option value="training_certificate">Training Certificate</option>
                                        <option value="leave_form">Leave Form</option>
                                        <option value="service_record">Service Record</option>
                                        <option value="disciplinary_action">Disciplinary Action</option>
                                        <option value="commendation">Commendation</option>
                                        <option value="medical_certificate">Medical Certificate</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>

                                <!-- Department Filter -->
                                <div>
                                    <label for="department" class="block text-sm font-medium text-gray-700">
                                        Department
                                    </label>
                                    <select id="department" name="department"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">All Departments</option>
                                        <option value="Human Resources">Human Resources</option>
                                        <option value="Finance">Finance</option>
                                        <option value="Information Technology">Information Technology</option>
                                        <option value="Administration">Administration</option>
                                        <option value="Public Affairs">Public Affairs</option>
                                        <option value="Legal Affairs">Legal Affairs</option>
                                        <option value="Engineering">Engineering</option>
                                        <option value="Health Services">Health Services</option>
                                    </select>
                                </div>

                                <!-- Date Range Filter -->
                                <div>
                                    <label for="date_range" class="block text-sm font-medium text-gray-700">
                                        Date Range
                                    </label>
                                    <select id="date_range" name="date_range"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">Any Time</option>
                                        <option value="today">Today</option>
                                        <option value="week">This Week</option>
                                        <option value="month">This Month</option>
                                        <option value="quarter">This Quarter</option>
                                        <option value="year">This Year</option>
                                        <option value="custom">Custom Range</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Custom Date Range (Hidden by default) -->
                            <div id="customDateRange" class="hidden mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="start_date" class="block text-sm font-medium text-gray-700">
                                        Start Date
                                    </label>
                                    <input type="date" id="start_date" name="start_date"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label for="end_date" class="block text-sm font-medium text-gray-700">
                                        End Date
                                    </label>
                                    <input type="date" id="end_date" name="end_date"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                            </div>
                        </div>

                        <!-- Search Options -->
                        <div class="border-t pt-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Search Options</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-3">
                                    <div class="flex items-center">
                                        <input id="include_content" name="include_content" type="checkbox" checked
                                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                        <label for="include_content" class="ml-2 block text-sm text-gray-900">
                                            Search document content (OCR)
                                        </label>
                                    </div>
                                    <div class="flex items-center">
                                        <input id="exact_match" name="exact_match" type="checkbox"
                                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                        <label for="exact_match" class="ml-2 block text-sm text-gray-900">
                                            Exact phrase match
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="space-y-3">
                                    <div class="flex items-center">
                                        <input id="case_sensitive" name="case_sensitive" type="checkbox"
                                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                        <label for="case_sensitive" class="ml-2 block text-sm text-gray-900">
                                            Case sensitive
                                        </label>
                                    </div>
                                    <div class="flex items-center">
                                        <input id="include_archived" name="include_archived" type="checkbox"
                                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                        <label for="include_archived" class="ml-2 block text-sm text-gray-900">
                                            Include archived documents
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Search Results -->
            <div id="searchResults" class="hidden">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Search Results</h3>
                            <div class="text-sm text-gray-500">
                                <span id="resultCount">0 documents found</span>
                            </div>
                        </div>
                        
                        <!-- Loading Spinner -->
                        <div id="loadingSpinner" class="hidden text-center py-8">
                            <div class="inline-flex items-center px-4 py-2 font-semibold leading-6 text-sm shadow rounded-md text-white bg-indigo-500 hover:bg-indigo-400 transition ease-in-out duration-150 cursor-not-allowed">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Searching documents...
                            </div>
                        </div>
                        
                        <!-- Results Container -->
                        <div id="resultsContainer" class="space-y-4">
                            <!-- Results will be populated here -->
                        </div>
                        
                        <!-- No Results Message -->
                        <div id="noResults" class="hidden text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No documents found</h3>
                            <p class="mt-1 text-sm text-gray-500">Try adjusting your search criteria or filters.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search Tips -->
            <div class="mt-6 bg-blue-50 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="font-medium text-lg text-blue-900 mb-4">Search Tips</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-blue-700">
                        <div>
                            <h4 class="font-medium text-blue-800">Search Techniques</h4>
                            <ul class="mt-2 space-y-1">
                                <li>• Use quotes for exact phrases: "performance rating"</li>
                                <li>• Use wildcards: performance* finds performance, performer, etc.</li>
                                <li>• Use boolean operators: AND, OR, NOT</li>
                                <li>• Search by employee ID: EMP-001</li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="font-medium text-blue-800">Search Scope</h4>
                            <ul class="mt-2 space-y-1">
                                <li>• Document titles and descriptions</li>
                                <li>• OCR-extracted content from scanned documents</li>
                                <li>• Employee names and departments</li>
                                <li>• Document metadata and tags</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript for Enhanced Functionality -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle date range selection
            const dateRangeSelect = document.getElementById('date_range');
            const customDateRange = document.getElementById('customDateRange');
            
            dateRangeSelect.addEventListener('change', function() {
                if (this.value === 'custom') {
                    customDateRange.classList.remove('hidden');
                } else {
                    customDateRange.classList.add('hidden');
                }
            });

            // Handle form submission
            const searchForm = document.getElementById('documentSearchForm');
            const searchResults = document.getElementById('searchResults');
            const loadingSpinner = document.getElementById('loadingSpinner');
            const resultsContainer = document.getElementById('resultsContainer');
            const noResults = document.getElementById('noResults');
            const resultCount = document.getElementById('resultCount');

            searchForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Show results section and loading spinner
                searchResults.classList.remove('hidden');
                loadingSpinner.classList.remove('hidden');
                resultsContainer.innerHTML = '';
                noResults.classList.add('hidden');
                
                // Simulate search API call (replace with actual API endpoint)
                setTimeout(function() {
                    loadingSpinner.classList.add('hidden');
                    
                    // Mock search results (replace with actual API response)
                    const mockResults = [
                        {
                            id: 1,
                            title: "Performance Rating - Q4 2024",
                            employee: "Juan Dela Cruz",
                            type: "Performance Rating",
                            department: "Human Resources",
                            date: "2024-12-15",
                            excerpt: "Outstanding performance rating for Q4 2024 with commendable achievements...",
                            relevance: 95
                        },
                        {
                            id: 2,
                            title: "Training Certificate - Leadership Development",
                            employee: "Maria Santos",
                            type: "Training Certificate",
                            department: "Administration",
                            date: "2024-11-20",
                            excerpt: "Certificate of completion for Leadership Development Program...",
                            relevance: 88
                        }
                    ];
                    
                    if (mockResults.length > 0) {
                        resultCount.textContent = `${mockResults.length} documents found`;
                        
                        mockResults.forEach(result => {
                            const resultElement = createResultElement(result);
                            resultsContainer.appendChild(resultElement);
                        });
                    } else {
                        noResults.classList.remove('hidden');
                        resultCount.textContent = '0 documents found';
                    }
                }, 1500); // Simulate API delay
            });

            function createResultElement(result) {
                const div = document.createElement('div');
                div.className = 'border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow';
                
                div.innerHTML = `
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <h4 class="text-lg font-medium text-gray-900 hover:text-indigo-600 cursor-pointer">
                                ${result.title}
                            </h4>
                            <div class="mt-1 flex items-center text-sm text-gray-500">
                                <span>${result.employee}</span>
                                <span class="mx-2">•</span>
                                <span>${result.department}</span>
                                <span class="mx-2">•</span>
                                <span>${result.date}</span>
                                <span class="mx-2">•</span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    ${result.type}
                                </span>
                            </div>
                            <p class="mt-2 text-sm text-gray-600">${result.excerpt}</p>
                        </div>
                        <div class="ml-4 flex items-center space-x-2">
                            <span class="text-xs text-gray-400">${result.relevance}% match</span>
                            <button class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                                View
                            </button>
                            <button class="text-gray-400 hover:text-gray-600 text-sm">
                                Download
                            </button>
                        </div>
                    </div>
                `;
                
                return div;
            }
        });
    </script>
</x-app-layout>