<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Personal Information') }} - {{ $employee->full_name }}
            </h2>
            <a href="{{ route('pds.dashboard', $employee) }}">
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

            <form method="POST" action="{{ route('pds.update-personal-information', $employee) }}">
                @csrf
                @method('PATCH')

                <!-- Basic Information Section -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-6">Basic Information</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <!-- First Name -->
                            <div>
                                <label for="first_name" class="block text-sm font-medium text-gray-700">First Name *</label>
                                <input type="text" name="first_name" id="first_name" value="{{ old('first_name', $employee->first_name) }}" required 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Middle Name -->
                            <div>
                                <label for="middle_name" class="block text-sm font-medium text-gray-700">Middle Name</label>
                                <input type="text" name="middle_name" id="middle_name" value="{{ old('middle_name', $employee->middle_name) }}" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Last Name -->
                            <div>
                                <label for="last_name" class="block text-sm font-medium text-gray-700">Last Name *</label>
                                <input type="text" name="last_name" id="last_name" value="{{ old('last_name', $employee->last_name) }}" required 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Name Extension -->
                            <div>
                                <label for="name_extension" class="block text-sm font-medium text-gray-700">Name Extension</label>
                                <input type="text" name="name_extension" id="name_extension" value="{{ old('name_extension', $employee->name_extension) }}" 
                                       placeholder="Jr., Sr., III, etc." class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Birth Date -->
                            <div>
                                <label for="birth_date" class="block text-sm font-medium text-gray-700">Birth Date *</label>
                                <input type="date" name="birth_date" id="birth_date" value="{{ old('birth_date', $employee->birth_date?->format('Y-m-d')) }}" required 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Place of Birth -->
                            <div>
                                <label for="place_of_birth" class="block text-sm font-medium text-gray-700">Place of Birth</label>
                                <input type="text" name="place_of_birth" id="place_of_birth" value="{{ old('place_of_birth', $employee->place_of_birth) }}" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Gender -->
                            <div>
                                <label for="gender" class="block text-sm font-medium text-gray-700">Gender *</label>
                                <select name="gender" id="gender" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select Gender</option>
                                    <option value="Male" {{ old('gender', $employee->gender) == 'Male' ? 'selected' : '' }}>Male</option>
                                    <option value="Female" {{ old('gender', $employee->gender) == 'Female' ? 'selected' : '' }}>Female</option>
                                </select>
                            </div>

                            <!-- Civil Status -->
                            <div>
                                <label for="civil_status" class="block text-sm font-medium text-gray-700">Civil Status *</label>
                                <select name="civil_status" id="civil_status" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select Civil Status</option>
                                    <option value="Single" {{ old('civil_status', $employee->civil_status) == 'Single' ? 'selected' : '' }}>Single</option>
                                    <option value="Married" {{ old('civil_status', $employee->civil_status) == 'Married' ? 'selected' : '' }}>Married</option>
                                    <option value="Widowed" {{ old('civil_status', $employee->civil_status) == 'Widowed' ? 'selected' : '' }}>Widowed</option>
                                    <option value="Separated" {{ old('civil_status', $employee->civil_status) == 'Separated' ? 'selected' : '' }}>Separated</option>
                                    <option value="Other" {{ old('civil_status', $employee->civil_status) == 'Other' ? 'selected' : '' }}>Other</option>
                                </select>
                            </div>

                            <!-- Civil Status Other Details -->
                            <div>
                                <label for="civil_status_other_details" class="block text-sm font-medium text-gray-700">Other Details (if applicable)</label>
                                <input type="text" name="civil_status_other_details" id="civil_status_other_details" 
                                       value="{{ old('civil_status_other_details', $employee->civil_status_other_details) }}" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Citizenship Information -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-6">Citizenship Information</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Citizenship -->
                            <div>
                                <label for="citizenship" class="block text-sm font-medium text-gray-700">Citizenship *</label>
                                <select name="citizenship" id="citizenship" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select Citizenship</option>
                                    <option value="Filipino" {{ old('citizenship', $employee->citizenship) == 'Filipino' ? 'selected' : '' }}>Filipino</option>
                                    <option value="Dual Citizenship" {{ old('citizenship', $employee->citizenship) == 'Dual Citizenship' ? 'selected' : '' }}>Dual Citizenship</option>
                                </select>
                            </div>

                            <!-- Dual Citizenship Type -->
                            <div>
                                <label for="dual_citizenship_type" class="block text-sm font-medium text-gray-700">Dual Citizenship Type</label>
                                <select name="dual_citizenship_type" id="dual_citizenship_type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select Type</option>
                                    <option value="By Birth" {{ old('dual_citizenship_type', $employee->dual_citizenship_type) == 'By Birth' ? 'selected' : '' }}>By Birth</option>
                                    <option value="By Naturalization" {{ old('dual_citizenship_type', $employee->dual_citizenship_type) == 'By Naturalization' ? 'selected' : '' }}>By Naturalization</option>
                                </select>
                            </div>

                            <!-- Dual Citizenship Country -->
                            <div>
                                <label for="dual_citizenship_country" class="block text-sm font-medium text-gray-700">Country (if dual citizenship)</label>
                                <input type="text" name="dual_citizenship_country" id="dual_citizenship_country" 
                                       value="{{ old('dual_citizenship_country', $employee->dual_citizenship_country) }}" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Physical Characteristics -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-6">Physical Characteristics</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Height -->
                            <div>
                                <label for="height" class="block text-sm font-medium text-gray-700">Height</label>
                                <input type="text" name="height" id="height" value="{{ old('height', $employee->height) }}" 
                                       placeholder="e.g. 5'6&quot;" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Weight -->
                            <div>
                                <label for="weight" class="block text-sm font-medium text-gray-700">Weight</label>
                                <input type="text" name="weight" id="weight" value="{{ old('weight', $employee->weight) }}" 
                                       placeholder="e.g. 65 kg" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Blood Type -->
                            <div>
                                <label for="blood_type" class="block text-sm font-medium text-gray-700">Blood Type</label>
                                <select name="blood_type" id="blood_type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select Blood Type</option>
                                    @foreach(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $bloodType)
                                        <option value="{{ $bloodType }}" {{ old('blood_type', $employee->blood_type) == $bloodType ? 'selected' : '' }}>{{ $bloodType }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Government IDs -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-6">Government Identification Numbers</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <!-- GSIS Number -->
                            <div>
                                <label for="gsis_number" class="block text-sm font-medium text-gray-700">GSIS Number</label>
                                <input type="text" name="gsis_number" id="gsis_number" value="{{ old('gsis_number', $employee->gsis_number) }}" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Pag-IBIG Number -->
                            <div>
                                <label for="pagibig_number" class="block text-sm font-medium text-gray-700">Pag-IBIG Number</label>
                                <input type="text" name="pagibig_number" id="pagibig_number" value="{{ old('pagibig_number', $employee->pagibig_number) }}" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- PhilHealth Number -->
                            <div>
                                <label for="philhealth_number" class="block text-sm font-medium text-gray-700">PhilHealth Number</label>
                                <input type="text" name="philhealth_number" id="philhealth_number" value="{{ old('philhealth_number', $employee->philhealth_number) }}" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- SSS Number -->
                            <div>
                                <label for="sss_number" class="block text-sm font-medium text-gray-700">SSS Number</label>
                                <input type="text" name="sss_number" id="sss_number" value="{{ old('sss_number', $employee->sss_number) }}" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- TIN Number -->
                            <div>
                                <label for="tin_number" class="block text-sm font-medium text-gray-700">TIN Number</label>
                                <input type="text" name="tin_number" id="tin_number" value="{{ old('tin_number', $employee->tin_number) }}" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Agency Employee Number -->
                            <div>
                                <label for="agency_employee_no" class="block text-sm font-medium text-gray-700">Agency Employee Number</label>
                                <input type="text" name="agency_employee_no" id="agency_employee_no" value="{{ old('agency_employee_no', $employee->agency_employee_no) }}" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Residential Address -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-6">Residential Address</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <!-- House/Block/Lot No. -->
                            <div>
                                <label for="res_house_block_lot_no" class="block text-sm font-medium text-gray-700">House/Block/Lot No.</label>
                                <input type="text" name="res_house_block_lot_no" id="res_house_block_lot_no" 
                                       value="{{ old('res_house_block_lot_no', $employee->res_house_block_lot_no) }}" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Street -->
                            <div>
                                <label for="res_street" class="block text-sm font-medium text-gray-700">Street</label>
                                <input type="text" name="res_street" id="res_street" value="{{ old('res_street', $employee->res_street) }}" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Subdivision/Village -->
                            <div>
                                <label for="res_subdivision_village" class="block text-sm font-medium text-gray-700">Subdivision/Village</label>
                                <input type="text" name="res_subdivision_village" id="res_subdivision_village" 
                                       value="{{ old('res_subdivision_village', $employee->res_subdivision_village) }}" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Barangay -->
                            <div>
                                <label for="res_barangay" class="block text-sm font-medium text-gray-700">Barangay</label>
                                <input type="text" name="res_barangay" id="res_barangay" value="{{ old('res_barangay', $employee->res_barangay) }}" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- City/Municipality -->
                            <div>
                                <label for="res_city_municipality" class="block text-sm font-medium text-gray-700">City/Municipality</label>
                                <input type="text" name="res_city_municipality" id="res_city_municipality" 
                                       value="{{ old('res_city_municipality', $employee->res_city_municipality) }}" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Province -->
                            <div>
                                <label for="res_province" class="block text-sm font-medium text-gray-700">Province</label>
                                <input type="text" name="res_province" id="res_province" value="{{ old('res_province', $employee->res_province) }}" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- ZIP Code -->
                            <div>
                                <label for="res_zip_code" class="block text-sm font-medium text-gray-700">ZIP Code</label>
                                <input type="text" name="res_zip_code" id="res_zip_code" value="{{ old('res_zip_code', $employee->res_zip_code) }}" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Permanent Address -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-6">Permanent Address</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <!-- House/Block/Lot No. -->
                            <div>
                                <label for="perm_house_block_lot_no" class="block text-sm font-medium text-gray-700">House/Block/Lot No.</label>
                                <input type="text" name="perm_house_block_lot_no" id="perm_house_block_lot_no" 
                                       value="{{ old('perm_house_block_lot_no', $employee->perm_house_block_lot_no) }}" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Street -->
                            <div>
                                <label for="perm_street" class="block text-sm font-medium text-gray-700">Street</label>
                                <input type="text" name="perm_street" id="perm_street" value="{{ old('perm_street', $employee->perm_street) }}" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Subdivision/Village -->
                            <div>
                                <label for="perm_subdivision_village" class="block text-sm font-medium text-gray-700">Subdivision/Village</label>
                                <input type="text" name="perm_subdivision_village" id="perm_subdivision_village" 
                                       value="{{ old('perm_subdivision_village', $employee->perm_subdivision_village) }}" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Barangay -->
                            <div>
                                <label for="perm_barangay" class="block text-sm font-medium text-gray-700">Barangay</label>
                                <input type="text" name="perm_barangay" id="perm_barangay" value="{{ old('perm_barangay', $employee->perm_barangay) }}" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- City/Municipality -->
                            <div>
                                <label for="perm_city_municipality" class="block text-sm font-medium text-gray-700">City/Municipality</label>
                                <input type="text" name="perm_city_municipality" id="perm_city_municipality" 
                                       value="{{ old('perm_city_municipality', $employee->perm_city_municipality) }}" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Province -->
                            <div>
                                <label for="perm_province" class="block text-sm font-medium text-gray-700">Province</label>
                                <input type="text" name="perm_province" id="perm_province" value="{{ old('perm_province', $employee->perm_province) }}" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- ZIP Code -->
                            <div>
                                <label for="perm_zip_code" class="block text-sm font-medium text-gray-700">ZIP Code</label>
                                <input type="text" name="perm_zip_code" id="perm_zip_code" value="{{ old('perm_zip_code', $employee->perm_zip_code) }}" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-6">Contact Information</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Telephone Number -->
                            <div>
                                <label for="telephone_no" class="block text-sm font-medium text-gray-700">Telephone Number</label>
                                <input type="text" name="telephone_no" id="telephone_no" value="{{ old('telephone_no', $employee->telephone_no) }}" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Mobile Number -->
                            <div>
                                <label for="mobile_no" class="block text-sm font-medium text-gray-700">Mobile Number</label>
                                <input type="text" name="mobile_no" id="mobile_no" value="{{ old('mobile_no', $employee->mobile_no) }}" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Email Address -->
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700">Email Address *</label>
                                <input type="email" name="email" id="email" value="{{ old('email', $employee->email) }}" required 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end space-x-4">
                    <a href="{{ route('pds.dashboard', $employee) }}">
                        <x-secondary-button>
                            {{ __('Cancel') }}
                        </x-secondary-button>
                    </a>
                    <x-primary-button>
                        {{ __('Update Personal Information') }}
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
