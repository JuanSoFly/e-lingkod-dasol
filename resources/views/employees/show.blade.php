<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Employee 201 File') }}
            </h2>
            @can('employee.edit', $employee)
            <a href="{{ route('employees.edit', $employee->id) }}">
                <x-primary-button>
                    {{ __('Edit Employee') }}
                </x-primary-button>
            </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Personal Info -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Personal Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div><dt class="text-sm font-medium text-gray-500">Full Name</dt><dd class="mt-1 text-sm text-gray-900">{{ $employee->first_name }} {{ $employee->middle_name }} {{ $employee->last_name }}</dd></div>
                        <div><dt class="text-sm font-medium text-gray-500">Email Address</dt><dd class="mt-1 text-sm text-gray-900">{{ $employee->email }}</dd></div>
                        <div><dt class="text-sm font-medium text-gray-500">Contact Number</dt><dd class="mt-1 text-sm text-gray-900">{{ $employee->contact_number }}</dd></div>
                        <div><dt class="text-sm font-medium text-gray-500">Address</dt><dd class="mt-1 text-sm text-gray-900">{{ $employee->address }}</dd></div>
                        <div><dt class="text-sm font-medium text-gray-500">Birth Date</dt><dd class="mt-1 text-sm text-gray-900">{{ $employee->birth_date?->format('F d, Y') ?? 'Not provided' }}</dd></div>
                        <div><dt class="text-sm font-medium text-gray-500">Gender</dt><dd class="mt-1 text-sm text-gray-900">{{ $employee->gender }}</dd></div>
                        <div><dt class="text-sm font-medium text-gray-500">Civil Status</dt><dd class="mt-1 text-sm text-gray-900">{{ $employee->civil_status }}</dd></div>
                    </div>
                </div>
            </div>

            <!-- Employment Details -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Employment Details</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div><dt class="text-sm font-medium text-gray-500">Employee Number</dt><dd class="mt-1 text-sm text-gray-900">{{ $employee->employee_number }}</dd></div>
                        <div><dt class="text-sm font-medium text-gray-500">Position</dt><dd class="mt-1 text-sm text-gray-900">{{ $employee->position }}</dd></div>
                        <div><dt class="text-sm font-medium text-gray-500">Department</dt><dd class="mt-1 text-sm text-gray-900">{{ $employee->department }}</dd></div>
                        <div><dt class="text-sm font-medium text-gray-500">Employment Status</dt><dd class="mt-1 text-sm text-gray-900">{{ $employee->employment_status }}</dd></div>
                        <div><dt class="text-sm font-medium text-gray-500">Date Hired</dt><dd class="mt-1 text-sm text-gray-900">{{ $employee->date_hired?->format('F d, Y') ?? 'Not provided' }}</dd></div>
                        <div><dt class="text-sm font-medium text-gray-500">Salary Grade</dt><dd class="mt-1 text-sm text-gray-900">{{ $employee->salary_grade }}</dd></div>
                        <div><dt class="text-sm font-medium text-gray-500">Step Increment</dt><dd class="mt-1 text-sm text-gray-900">{{ $employee->step_increment }}</dd></div>
                    </div>
                </div>
            </div>
            
            <!-- Employee Documents -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Employee Documents</h3>

                    @can('create', [App\Models\EmployeeDocument::class, $employee])
                    <!-- Document Upload Form -->
                    <form action="{{ route('employees.documents.store', $employee) }}" method="POST" enctype="multipart/form-data" class="mb-6 pb-6 border-b">
                        @csrf
                        <div class="flex flex-col md:flex-row md:items-end md:space-x-4 space-y-4 md:space-y-0">
                            <div class="flex-1">
                                <x-input-label for="document_type" :value="__('Document Type')" />
                                <select id="document_type" name="document_type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                    <option value="">Select Document Type</option>
                                    <option value="id_card" {{ old('document_type') == 'id_card' ? 'selected' : '' }}>ID Card</option>
                                    <option value="passport" {{ old('document_type') == 'passport' ? 'selected' : '' }}>Passport</option>
                                    <option value="birth_certificate" {{ old('document_type') == 'birth_certificate' ? 'selected' : '' }}>Birth Certificate</option>
                                    <option value="diploma" {{ old('document_type') == 'diploma' ? 'selected' : '' }}>Diploma</option>
                                    <option value="license" {{ old('document_type') == 'license' ? 'selected' : '' }}>Professional License</option>
                                    <option value="contract" {{ old('document_type') == 'contract' ? 'selected' : '' }}>Employment Contract</option>
                                    <option value="other" {{ old('document_type') == 'other' ? 'selected' : '' }}>Other Document</option>
                                </select>
                                <x-input-error :messages="$errors->get('document_type')" class="mt-2" />
                            </div>
                            <div class="flex-1">
                                <x-input-label for="document" :value="__('File (PDF, JPG, PNG)')" />
                                <input id="document" name="document" type="file" class="mt-1 block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-gray-200 file:text-gray-700 hover:file:bg-gray-300" required>
                                <x-input-error :messages="$errors->get('document')" class="mt-2" />
                            </div>
                            <div>
                                <x-primary-button>{{ __('Upload') }}</x-primary-button>
                            </div>
                        </div>
                    </form>
                    @endcan

                    <!-- Documents List -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                             <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($employee->documents()->latest()->get() as $document)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $document->document_type }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $document->uploaded_at?->format('M d, Y') ?? 'Date unknown' }} by {{ $document->uploader?->name ?? 'Unknown' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            @can('view', $document)
                                            <a href="{{ route('documents.show', $document) }}" class="text-indigo-600 hover:text-indigo-900">View/Download</a>
                                            @endcan
                                            @can('delete', $document)
                                            <form action="{{ route('documents.destroy', $document) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this document?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="ml-4 text-red-600 hover:text-red-900">Delete</button>
                                            </form>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">No documents uploaded yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="mt-6">
                <a href="{{ route('employees.index') }}">
                    <x-secondary-button>
                        {{ __('Back to Employee List') }}
                    </x-secondary-button>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>