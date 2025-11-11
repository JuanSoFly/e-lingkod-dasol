<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Employee 201 File') }}
            </h2>
            <div class="flex flex-wrap gap-3 lg:justify-end">
                @can('export', $employee)
                <a href="{{ route('pds.export.single', $employee->id) }}"
                   class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-900 focus:outline-none focus:border-green-900 focus:ring focus:ring-green-300 disabled:opacity-25 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    {{ __('Download Excel') }}
                </a>
                @endcan
                @can('employee.edit', $employee)
                <a href="{{ route('pds.dashboard', $employee->id) }}">
                    <x-primary-button>
                        {{ __('Manage PDS') }}
                    </x-primary-button>
                </a>
                <a href="{{ route('employees.edit', $employee->id) }}">
                    <x-secondary-button>
                        {{ __('Edit Basic Info') }}
                    </x-secondary-button>
                </a>
                @endcan
            </div>
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
                        <div><dt class="text-sm font-medium text-gray-500">Sex</dt><dd class="mt-1 text-sm text-gray-900">{{ $employee->gender }}</dd></div>
                        <div><dt class="text-sm font-medium text-gray-500">Civil Status</dt><dd class="mt-1 text-sm text-gray-900">{{ $employee->civil_status }}</dd></div>
                        <div><dt class="text-sm font-medium text-gray-500">Record Created</dt><dd class="mt-1 text-sm text-gray-900">{{ $employee->created_at_formatted }}</dd></div>
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
                        <div><dt class="text-sm font-medium text-gray-500">Department</dt><dd class="mt-1 text-sm text-gray-900">{{ $employee->office?->name ?? $employee->department }}</dd></div>
                        <div><dt class="text-sm font-medium text-gray-500">Employment Status</dt><dd class="mt-1 text-sm text-gray-900">{{ $employee->employment_status }}</dd></div>
                        <div><dt class="text-sm font-medium text-gray-500">Date Hired</dt><dd class="mt-1 text-sm text-gray-900">{{ $employee->date_hired?->format('F d, Y') ?? 'Not provided' }}</dd></div>
                        <div><dt class="text-sm font-medium text-gray-500">Salary Grade</dt><dd class="mt-1 text-sm text-gray-900">{{ $employee->salary_grade }}</dd></div>
                        <div><dt class="text-sm font-medium text-gray-500">Step Increment</dt><dd class="mt-1 text-sm text-gray-900">{{ $employee->step_increment }}</dd></div>
                        <div><dt class="text-sm font-medium text-gray-500">Basic Salary</dt><dd class="mt-1 text-sm text-gray-900">₱{{ number_format($employee->basic_salary, 2) }}</dd></div>
                    </div>
                </div>
            </div>

            <!-- Educational Background -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg" id="education">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Educational Background</h3>
                        @can('employee.edit', $employee)
                        <a href="{{ route('employees.education.index', $employee) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            Manage Education →
                        </a>
                        @endcan
                    </div>

                    @if($employee->education->count() > 0)
                        <div class="space-y-4">
                            @foreach($employee->education->groupBy('education_level') as $level => $levelEducations)
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <h4 class="font-medium text-gray-900 mb-3 flex items-center">
                                        @switch($level)
                                            @case('Elementary')
                                                <svg class="h-4 w-4 text-blue-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3z"/>
                                                </svg>
                                                @break
                                            @case('Secondary')
                                                <svg class="h-4 w-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                                                </svg>
                                                @break
                                            @case('College')
                                                <svg class="h-4 w-4 text-purple-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                    <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3z"/>
                                                </svg>
                                                @break
                                            @default
                                                <svg class="h-4 w-4 text-gray-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
                                                </svg>
                                        @endswitch
                                        {{ $level }}
                                    </h4>

                                    <div class="space-y-3">
                                        @foreach($levelEducations->take(3) as $education)
                                            <div class="text-sm">
                                                <div class="font-medium text-gray-900">{{ $education->school_name }}</div>
                                                @if($education->degree_course)
                                                    <div class="text-gray-600">{{ $education->degree_course }}</div>
                                                @elseif($education->course)
                                                    <div class="text-gray-600">{{ $education->course }}</div>
                                                @endif
                                                @if($education->graduation_year)
                                                    <div class="text-gray-500">Graduated: {{ $education->graduation_year }}</div>
                                                @elseif($education->duration !== 'Not specified')
                                                    <div class="text-gray-500">Period: {{ $education->duration }}</div>
                                                @endif
                                                @if($education->all_honors)
                                                    <div class="text-green-600 text-xs">{{ Str::limit($education->all_honors, 50) }}</div>
                                                @endif
                                            </div>
                                        @endforeach

                                        @if($levelEducations->count() > 3)
                                            <div class="text-xs text-gray-500">
                                                + {{ $levelEducations->count() - 3 }} more record(s)
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-4 text-center">
                            @can('employee.edit', $employee)
                            <a href="{{ route('employees.education.index', $employee) }}" class="text-blue-600 hover:text-blue-800 text-sm">
                                View All Education Records ({{ $employee->education->count() }})
                            </a>
                            @endcan
                        </div>
                    @else
                        <div class="text-center text-gray-500 py-6">
                            <svg class="mx-auto h-8 w-8 text-gray-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                            <p class="text-sm">No education records found.</p>
                            @can('employee.edit', $employee)
                            <a href="{{ route('employees.education.create', $employee) }}" class="mt-2 inline-flex items-center text-blue-600 hover:text-blue-800 text-sm">
                                Add Education Record
                            </a>
                            @endcan
                        </div>
                    @endif
                </div>
            </div>

            <!-- OPCR Information -->
            @can('opcr.view')
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">OPCR Information</h3>
                        @can('opcr.manage')
                        <a href="{{ route('opcr.workflows.create') }}?employee_id={{ $employee->id }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            Create OPCR Workflow →
                        </a>
                        @endcan
                    </div>

                    @if($employee->user && $employee->user->officeAssignments->count() > 0)
                        <div class="space-y-4">
                            @foreach($employee->user->officeAssignments as $assignment)
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">Office</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ $assignment->office->name }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">OPCR Role</dt>
                                            <dd class="mt-1">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                    @switch($assignment->role)
                                                        @case('Department Head')
                                                            bg-blue-100 text-blue-800
                                                            @break
                                                        @case('Assessor')
                                                            bg-green-100 text-green-800
                                                            @break
                                                        @case('Final Approver')
                                                            bg-purple-100 text-purple-800
                                                            @break
                                                        @default
                                                            bg-gray-100 text-gray-800
                                                    @endswitch
                                                ">
                                                    {{ $assignment->role }}
                                                </span>
                                            </dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">Assigned Since</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ $assignment->created_at->format('F d, Y') }}</dd>
                                        </div>
                                    </div>

                                    <!-- Show current workflow status for this office -->
                                    @if($currentWorkflow = \App\Models\OPCRWorkflow::where('office_id', $assignment->office_id)
                                        ->whereHas('committedBy', function($q) use ($employee) {
                                            $q->where('employee_id', $employee->id);
                                        })
                                        ->with('period')
                                        ->first())
                                    <div class="mt-4 pt-4 border-t border-gray-200">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500">Current Period</dt>
                                                <dd class="mt-1 text-sm text-gray-900">{{ $currentWorkflow->period->name }}</dd>
                                            </div>
                                            <div>
                                                <dt class="text-sm font-medium text-gray-500">Workflow Status</dt>
                                                <dd class="mt-1">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                        @switch($currentWorkflow->workflow_state)
                                                            @case('draft')
                                                                bg-gray-100 text-gray-800
                                                                @break
                                                            @case('committed')
                                                                bg-blue-100 text-blue-800
                                                                @break
                                                            @case('in_progress')
                                                                bg-yellow-100 text-yellow-800
                                                                @break
                                                            @case('evaluation')
                                                                bg-orange-100 text-orange-800
                                                                @break
                                                            @case('final_approval')
                                                                bg-purple-100 text-purple-800
                                                                @break
                                                            @case('approved')
                                                                bg-green-100 text-green-800
                                                                @break
                                                            @default
                                                                bg-red-100 text-red-800
                                                        @endswitch
                                                    ">
                                                        {{ ucfirst(str_replace('_', ' ', $currentWorkflow->workflow_state)) }}
                                                    </span>
                                                </dd>
                                            </div>
                                        </div>
                                        @if($currentWorkflow->updated_at)
                                        <div class="mt-2">
                                            <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ $currentWorkflow->updated_at->format('F d, Y g:i A') }}</dd>
                                        </div>
                                        @endif
                                    </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <p class="text-sm text-gray-500">No OPCR office assignments found for this employee.</p>
                            @can('opcr.manage')
                            <a href="{{ route('admin.office-assignments.create') }}?user_id={{ $employee->user->id ?? '' }}"
                               class="mt-2 inline-flex items-center text-blue-600 hover:text-blue-800 text-sm font-medium">
                                Assign Office Role →
                            </a>
                            @endcan
                        </div>
                    @endif
                </div>
            </div>
            @endcan

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
                                            <form action="{{ route('documents.destroy', $document) }}" method="POST" class="inline-block" data-confirm="Are you sure you want to delete this document?">
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

            <!-- OPCR Information Section -->
            @if(auth()->user()->can('opcr.view') && !empty($opcrData))
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        OPCR Performance Information
                    </h3>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Department Head Status -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="font-medium text-gray-900 mb-3">Department Head Status</h4>
                            <div class="space-y-2">
                                <div class="flex items-center">
                                    <span class="text-sm font-medium text-gray-500 w-24">Is Dept. Head:</span>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $opcrData['is_department_head'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                        {{ $opcrData['is_department_head'] ? 'Yes' : 'No' }}
                                    </span>
                                </div>
                                @if($opcrData['is_department_head'] && $opcrData['managed_offices']->isNotEmpty())
                                    <div class="flex items-center">
                                        <span class="text-sm font-medium text-gray-500 w-24">Manages:</span>
                                        <span class="text-sm text-gray-900">{{ $opcrData['managed_offices']->implode(', ') }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Office Assignments -->
                        @if($opcrData['office_assignments']->isNotEmpty())
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="font-medium text-gray-900 mb-3">Office Assignments</h4>
                            <div class="space-y-2">
                                @foreach($opcrData['office_assignments'] as $assignment)
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <span class="text-sm font-medium text-gray-900">{{ $assignment->office->name }}</span>
                                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ $assignment->role }}
                                            </span>
                                        </div>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $assignment->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                            {{ $assignment->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Recent OPCR Workflows -->
                    @if($opcrData['opcr_workflows']->isNotEmpty() || $opcrData['committed_workflows']->isNotEmpty())
                        <div class="mt-6">
                            <h4 class="font-medium text-gray-900 mb-3">Recent OPCR Workflows</h4>
                            <div class="space-y-3">
                                @foreach($opcrData['committed_workflows']->take(3) as $workflow)
                                    <div class="border border-gray-200 rounded-lg p-3">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <div class="font-medium text-gray-900">{{ $workflow->title }}</div>
                                                <div class="text-sm text-gray-500">
                                                    {{ $workflow->office->name }} • {{ $workflow->period->name }}
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    {{ ucfirst($workflow->workflow_state) }}
                                                </span>
                                                <div class="text-xs text-gray-500 mt-1">
                                                    {{ $workflow->updated_at->format('M d, Y') }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach

                                @if($opcrData['committed_workflows']->count() > 3)
                                    <div class="text-center">
                                        <a href="{{ route('opcr.archive.index', ['committed_by' => $employee->user_id]) }}"
                                           class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                            View All OPCR Workflows →
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Quick Actions -->
                    <div class="mt-6 flex flex-wrap gap-3">
                        @can('opcr.view')
                            <a href="{{ route('opcr.dashboard') }}"
                               class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:border-indigo-900 focus:ring focus:ring-indigo-300 transition ease-in-out duration-150">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                                OPCR Dashboard
                            </a>
                        @endcan

                        @if($opcrData['is_department_head'])
                            @can('opcr.manage')
                                <a href="{{ route('opcr.workflows.create') }}"
                                   class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:outline-none focus:border-green-900 focus:ring focus:ring-green-300 transition ease-in-out duration-150">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Create OPCR
                                </a>
                            @endcan
                        @endif
                    </div>
                </div>
            </div>
            @endif

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
