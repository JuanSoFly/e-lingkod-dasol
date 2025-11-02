<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Family Background') }} - {{ $employee->full_name }}
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

            <form method="POST" action="{{ route('pds.update-family-background', $employee) }}">
                @csrf

                <!-- Spouse Information -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-6">Spouse Information</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <!-- Spouse Surname -->
                            <div>
                                <label for="spouse_surname" class="block text-sm font-medium text-gray-700">Surname</label>
                                <input type="text" name="spouse_surname" id="spouse_surname" 
                                       value="{{ old('spouse_surname', $familyBackground?->spouse_surname) }}" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Spouse First Name -->
                            <div>
                                <label for="spouse_first_name" class="block text-sm font-medium text-gray-700">First Name</label>
                                <input type="text" name="spouse_first_name" id="spouse_first_name" 
                                       value="{{ old('spouse_first_name', $familyBackground?->spouse_first_name) }}" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Spouse Middle Name -->
                            <div>
                                <label for="spouse_middle_name" class="block text-sm font-medium text-gray-700">Middle Name</label>
                                <input type="text" name="spouse_middle_name" id="spouse_middle_name" 
                                       value="{{ old('spouse_middle_name', $familyBackground?->spouse_middle_name) }}" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Spouse Occupation -->
                            <div>
                                <label for="spouse_occupation" class="block text-sm font-medium text-gray-700">Occupation</label>
                                <input type="text" name="spouse_occupation" id="spouse_occupation" 
                                       value="{{ old('spouse_occupation', $familyBackground?->spouse_occupation) }}" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Spouse Employer -->
                            <div>
                                <label for="spouse_employer" class="block text-sm font-medium text-gray-700">Employer/Business Name</label>
                                <input type="text" name="spouse_employer" id="spouse_employer" 
                                       value="{{ old('spouse_employer', $familyBackground?->spouse_employer) }}" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Spouse Telephone -->
                            <div>
                                <label for="spouse_telephone_no" class="block text-sm font-medium text-gray-700">Telephone Number</label>
                                <input type="text" name="spouse_telephone_no" id="spouse_telephone_no" 
                                       value="{{ old('spouse_telephone_no', $familyBackground?->spouse_telephone_no) }}" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        <!-- Spouse Business Address -->
                        <div class="mt-6">
                            <label for="spouse_business_address" class="block text-sm font-medium text-gray-700">Business Address</label>
                            <textarea name="spouse_business_address" id="spouse_business_address" rows="3" 
                                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">{{ old('spouse_business_address', $familyBackground?->spouse_business_address) }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Father Information -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-6">Father's Information</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Father Surname -->
                            <div>
                                <label for="father_surname" class="block text-sm font-medium text-gray-700">Surname</label>
                                <input type="text" name="father_surname" id="father_surname" 
                                       value="{{ old('father_surname', $familyBackground?->father_surname) }}" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Father First Name -->
                            <div>
                                <label for="father_first_name" class="block text-sm font-medium text-gray-700">First Name</label>
                                <input type="text" name="father_first_name" id="father_first_name" 
                                       value="{{ old('father_first_name', $familyBackground?->father_first_name) }}" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Father Middle Name -->
                            <div>
                                <label for="father_middle_name" class="block text-sm font-medium text-gray-700">Middle Name</label>
                                <input type="text" name="father_middle_name" id="father_middle_name" 
                                       value="{{ old('father_middle_name', $familyBackground?->father_middle_name) }}" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mother Information -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-6">Mother's Information</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Mother Maiden Name -->
                            <div>
                                <label for="mother_maiden_name" class="block text-sm font-medium text-gray-700">Maiden Name</label>
                                <input type="text" name="mother_maiden_name" id="mother_maiden_name" 
                                       value="{{ old('mother_maiden_name', $familyBackground?->mother_maiden_name) }}" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Mother Surname -->
                            <div>
                                <label for="mother_surname" class="block text-sm font-medium text-gray-700">Surname</label>
                                <input type="text" name="mother_surname" id="mother_surname" 
                                       value="{{ old('mother_surname', $familyBackground?->mother_surname) }}" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Mother First Name -->
                            <div>
                                <label for="mother_first_name" class="block text-sm font-medium text-gray-700">First Name</label>
                                <input type="text" name="mother_first_name" id="mother_first_name" 
                                       value="{{ old('mother_first_name', $familyBackground?->mother_first_name) }}" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <!-- Mother Middle Name -->
                            <div>
                                <label for="mother_middle_name" class="block text-sm font-medium text-gray-700">Middle Name</label>
                                <input type="text" name="mother_middle_name" id="mother_middle_name" 
                                       value="{{ old('mother_middle_name', $familyBackground?->mother_middle_name) }}" 
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Children Information -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-medium text-gray-900">Children Information</h3>
                            <button type="button" id="add-child" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                                Add Child
                            </button>
                        </div>
                        
                        <div id="children-container">
                            @if($children && $children->count() > 0)
                                @foreach($children as $index => $child)
                                    <div class="child-entry border border-gray-200 rounded-lg p-4 mb-4">
                                        <div class="flex justify-between items-center mb-4">
                                            <h4 class="text-md font-medium text-gray-800">Child {{ $index + 1 }}</h4>
                                            <button type="button" class="remove-child text-red-600 hover:text-red-800 text-sm font-medium">Remove</button>
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Full Name *</label>
                                                <input type="text" name="children[{{ $index }}][full_name]" 
                                                       value="{{ old('children.'.$index.'.full_name', $child->full_name) }}" required
                                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Date of Birth *</label>
                                                <input type="date" name="children[{{ $index }}][date_of_birth]" 
                                                       value="{{ old('children.'.$index.'.date_of_birth', $child->date_of_birth?->format('Y-m-d')) }}" required
                                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="text-center text-gray-500 py-8">
                                    <p>No children added yet. Click "Add Child" to add a child.</p>
                                </div>
                            @endif
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
                        {{ __('Update Family Background') }}
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>

    <!-- JavaScript for Dynamic Children Management -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let childIndex = {{ $children ? $children->count() : 0 }};
            const container = document.getElementById('children-container');
            const addButton = document.getElementById('add-child');

            function createChildEntry(index) {
                return `
                    <div class="child-entry border border-gray-200 rounded-lg p-4 mb-4">
                        <div class="flex justify-between items-center mb-4">
                            <h4 class="text-md font-medium text-gray-800">Child ${index + 1}</h4>
                            <button type="button" class="remove-child text-red-600 hover:text-red-800 text-sm font-medium">Remove</button>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Full Name *</label>
                                <input type="text" name="children[${index}][full_name]" required
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Date of Birth *</label>
                                <input type="date" name="children[${index}][date_of_birth]" required
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                    </div>
                `;
            }

            function updateChildNumbers() {
                const entries = container.querySelectorAll('.child-entry');
                entries.forEach((entry, index) => {
                    entry.querySelector('h4').textContent = `Child ${index + 1}`;
                    
                    // Update input names
                    const nameInput = entry.querySelector('input[name*="[full_name]"]');
                    const dateInput = entry.querySelector('input[name*="[date_of_birth]"]');
                    
                    if (nameInput) nameInput.name = `children[${index}][full_name]`;
                    if (dateInput) dateInput.name = `children[${index}][date_of_birth]`;
                });
            }

            function removeEmptyMessage() {
                const emptyMessage = container.querySelector('.text-center.text-gray-500');
                if (emptyMessage) {
                    emptyMessage.remove();
                }
            }

            addButton.addEventListener('click', function() {
                removeEmptyMessage();
                
                const newEntry = document.createElement('div');
                newEntry.innerHTML = createChildEntry(childIndex);
                container.appendChild(newEntry.firstElementChild);
                
                childIndex++;
                updateChildNumbers();
            });

            container.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-child')) {
                    e.target.closest('.child-entry').remove();
                    updateChildNumbers();
                    
                    // If no children left, show empty message
                    if (container.children.length === 0) {
                        container.innerHTML = `
                            <div class="text-center text-gray-500 py-8">
                                <p>No children added yet. Click "Add Child" to add a child.</p>
                            </div>
                        `;
                    }
                }
            });
        });
    </script>
</x-app-layout>
