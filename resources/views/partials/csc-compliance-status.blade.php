@php
$completenessCheck = $completenessCheck ?? [];
$isCSCCompliant = $completenessCheck['is_csc_compliant'] ?? false;
$completenessPercentage = $completenessCheck['completeness_percentage'] ?? 0;
$missingFields = $completenessCheck['missing_required_fields'] ?? [];
$warnings = $completenessCheck['warnings'] ?? [];
$employee = $employee ?? null;
@endphp

@once
    @vite('resources/js/pages/csc-compliance-status.js')
@endonce

<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-800">
            CSC Form No. 212 Status
        </h3>
        <div class="flex items-center space-x-2">
            @if($isCSCCompliant)
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    Fully Compliant
                </span>
            @else
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $completenessPercentage >= 70 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800' }}">
                    @if($completenessPercentage >= 70)
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        Partially Complete
                    @else
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                        Needs Attention
                    @endif
                    {{ $completenessPercentage }}% Complete
                </span>
            @endif
        </div>
    </div>

    <!-- Progress Bar -->
    <div class="mb-6">
        <div class="flex items-center justify-between text-sm text-gray-600 mb-2">
            <span>Overall Completion</span>
            <span class="font-medium">{{ $completenessPercentage }}%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-3">
            <div class="bg-gradient-to-r {{ $isCSCCompliant ? 'from-green-500 to-green-600' : ($completenessPercentage >= 70 ? 'from-yellow-500 to-yellow-600' : 'from-red-500 to-red-600') }} h-3 rounded-full transition-all duration-500 ease-out"
                 style="width: {{ $completenessPercentage }}%"></div>
        </div>
    </div>

    <!-- Status Details -->
    <div class="space-y-4">
        @if(!$isCSCCompliant && !empty($missingFields))
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <h4 class="text-sm font-semibold text-red-800 mb-2">Missing Required CSC Fields:</h4>
                <ul class="text-sm text-red-700 space-y-1">
                    @foreach($missingFields as $field)
                        <li class="flex items-start">
                            <svg class="w-4 h-4 mr-2 mt-0.5 text-red-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                            {{ formatCSCFieldName($field) }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(!empty($warnings))
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <h4 class="text-sm font-semibold text-yellow-800 mb-2">CSC Compliance Notes:</h4>
                <ul class="text-sm text-yellow-700 space-y-1">
                    @foreach($warnings as $warning)
                        <li class="flex items-start">
                            <svg class="w-4 h-4 mr-2 mt-0.5 text-yellow-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                            {{ $warning }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($isCSCCompliant)
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="flex items-start">
                    <svg class="w-5 h-5 mr-3 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <h4 class="text-sm font-semibold text-green-800">CSC Form No. 212 Fully Compliant</h4>
                        <p class="text-sm text-green-700 mt-1">
                            Your Personal Data Sheet meets all Civil Service Commission requirements. All required fields are complete and properly formatted.
                        </p>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Action Buttons -->
    <div class="mt-6 flex flex-wrap gap-3">
        @if($employee)
            <a href="{{ route('pds.questionnaire', $employee) }}"
               class="inline-flex items-center justify-center btn-responsive btn-touch border border-gray-300 rounded-md shadow-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                Edit CSC Fields
            </a>

            <button onclick="validateAndGeneratePDF('{{ $employee->id }}', event)"
                    class="inline-flex items-center justify-center btn-responsive btn-touch border border-transparent rounded-md shadow-sm font-medium text-white {{ $isCSCCompliant ? 'bg-blue-600 hover:bg-blue-700' : 'bg-gray-400 cursor-not-allowed' }}
                           focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    {{ !$isCSCCompliant ? 'disabled' : '' }}>
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Generate PDS PDF
            </button>

            <button onclick="refreshCSCStatus('{{ $employee->id }}', event)"
                    class="inline-flex items-center justify-center btn-responsive btn-touch border border-gray-300 rounded-md shadow-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Refresh Status
            </button>
        @endif
    </div>

    <!-- CSC Requirements Info -->
    <div class="mt-6 pt-6 border-t border-gray-200">
        <details class="group">
            <summary class="flex items-center justify-between cursor-pointer text-sm font-medium text-gray-700 hover:text-gray-900">
                <span>CSC Form No. 212 Requirements</span>
                <svg class="w-4 h-4 group-open:rotate-180 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </summary>
            <div class="mt-3 text-sm text-gray-600 space-y-2">
                <p>The Civil Service Commission Form No. 212 is the official Personal Data Sheet required for government employees.</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-3">
                    <div>
                        <h5 class="font-semibold text-gray-700 mb-2">Required Fields (34-41):</h5>
                        <ul class="space-y-1 text-xs">
                            <li>• Field 34: Relationship to appointing authority</li>
                            <li>• Field 35: Administrative/criminal charges</li>
                            <li>• Field 36: Election candidacy details</li>
                            <li>• Field 37: Resignation to campaign</li>
                            <li>• Field 38: Immigrant status</li>
                            <li>• Field 39: Government ID information</li>
                            <li>• Field 41: Special group memberships</li>
                        </ul>
                    </div>
                    <div>
                        <h5 class="font-semibold text-gray-700 mb-2">Format Requirements:</h5>
                        <ul class="space-y-1 text-xs">
                            <li>• Dates: mm/dd/yyyy format</li>
                            <li>• Salary Grade: 00-0 format</li>
                            <li>• Phone: Philippine format validation</li>
                            <li>• Government IDs: Proper formatting</li>
                            <li>• References: Exactly 3 required</li>
                        </ul>
                    </div>
                </div>
            </div>
        </details>
    </div>
</div>

@php
function formatCSCFieldName($field) {
    $fieldNames = [
        'field_34_relationship' => 'Relationship to appointing authority (Field 34)',
        'field_35_charges' => 'Administrative/criminal charges details (Field 35)',
        'field_36_candidate' => 'Election candidacy details (Field 36)',
        'field_37_resignation' => 'Resignation to campaign details (Field 37)',
        'field_38_immigrant' => 'Immigrant status details (Field 38)',
        'field_39_yes_no' => 'Immigrant status answer (Field 39)',
        'field_41_indigenous_member' => 'Indigenous group membership (Field 41)',
        'field_41_pwd_member' => 'PWD membership (Field 41)',
        'field_41_solo_parent_member' => 'Solo parent membership (Field 41)',
        'questionnaire_complete' => 'CSC questionnaire (Fields 34-41)',
        'references_complete' => 'Character references (exactly 3 required)',
        'personal_info_complete' => 'Personal information',
        'family_background_complete' => 'Family background',
        'eligibility_complete' => 'Civil service eligibility',
        'work_experience_complete' => 'Work experience',
        'voluntary_work_complete' => 'Voluntary work',
        'training_complete' => 'Training and development',
        'other_info_complete' => 'Other information'
    ];

    return $fieldNames[$field] ?? ucfirst(str_replace('_', ' ', $field));
}
@endphp
