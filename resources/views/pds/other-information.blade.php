<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Other Information') }} - {{ $employee->full_name }}
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

            <!-- Special Skills Section -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-medium text-gray-900">Special Skills & Hobbies</h3>
                        <button type="button" onclick="toggleForm('special_skills')" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                            Add Skill
                        </button>
                    </div>

                    <!-- Add Form for Special Skills -->
                    <div id="form-special_skills" class="hidden mb-6 p-4 bg-gray-50 rounded-lg">
                        <form method="POST" action="{{ route('pds.store-other-information', $employee) }}">
                            @csrf
                            <input type="hidden" name="information_type" value="special_skills">
                            
                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <label for="special_skills_description" class="block text-sm font-medium text-gray-700">Special Skill or Hobby *</label>
                                    <input type="text" name="description" id="special_skills_description" required
                                           placeholder="e.g. Computer Programming, Photography, Musical Instruments"
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>

                            <div class="mt-4 flex justify-end space-x-2">
                                <button type="button" onclick="toggleForm('special_skills')" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">
                                    Cancel
                                </button>
                                <x-primary-button>
                                    {{ __('Add Skill') }}
                                </x-primary-button>
                            </div>
                        </form>
                    </div>

                    <!-- Special Skills List -->
                    @if($specialSkills && $specialSkills->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($specialSkills as $skill)
                                <div class="flex justify-between items-center p-3 bg-blue-50 rounded-lg">
                                    <span class="text-sm text-gray-800">{{ $skill->description }}</span>
                                    <form method="POST" action="{{ route('pds.destroy-other-information', [$employee, $skill]) }}" 
                                          onsubmit="return confirm('Are you sure you want to delete this skill?')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 text-xs">
                                            ×
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center text-gray-500 py-4">
                            <p class="text-sm">No special skills added yet.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Non-Academic Distinctions Section -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-medium text-gray-900">Non-Academic Distinctions & Recognition</h3>
                        <button type="button" onclick="toggleForm('distinctions')" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                            Add Recognition
                        </button>
                    </div>

                    <!-- Add Form for Distinctions -->
                    <div id="form-distinctions" class="hidden mb-6 p-4 bg-gray-50 rounded-lg">
                        <form method="POST" action="{{ route('pds.store-other-information', $employee) }}">
                            @csrf
                            <input type="hidden" name="information_type" value="distinctions">
                            
                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <label for="distinctions_description" class="block text-sm font-medium text-gray-700">Recognition or Award *</label>
                                    <input type="text" name="description" id="distinctions_description" required
                                           placeholder="e.g. Employee of the Year 2023, Outstanding Service Award"
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>

                            <div class="mt-4 flex justify-end space-x-2">
                                <button type="button" onclick="toggleForm('distinctions')" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">
                                    Cancel
                                </button>
                                <x-primary-button>
                                    {{ __('Add Recognition') }}
                                </x-primary-button>
                            </div>
                        </form>
                    </div>

                    <!-- Distinctions List -->
                    @if($distinctions && $distinctions->count() > 0)
                        <div class="space-y-3">
                            @foreach($distinctions as $distinction)
                                <div class="flex justify-between items-center p-4 bg-green-50 rounded-lg">
                                    <div class="flex items-center">
                                        <svg class="h-5 w-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                        <span class="text-sm text-gray-800">{{ $distinction->description }}</span>
                                    </div>
                                    <form method="POST" action="{{ route('pds.destroy-other-information', [$employee, $distinction]) }}" 
                                          onsubmit="return confirm('Are you sure you want to delete this recognition?')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center text-gray-500 py-4">
                            <p class="text-sm">No recognitions added yet.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Membership in Organizations Section -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-medium text-gray-900">Membership in Association/Organization</h3>
                        <button type="button" onclick="toggleForm('memberships')" class="inline-flex items-center px-4 py-2 bg-purple-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-700">
                            Add Membership
                        </button>
                    </div>

                    <!-- Add Form for Memberships -->
                    <div id="form-memberships" class="hidden mb-6 p-4 bg-gray-50 rounded-lg">
                        <form method="POST" action="{{ route('pds.store-other-information', $employee) }}">
                            @csrf
                            <input type="hidden" name="information_type" value="memberships">
                            
                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <label for="memberships_description" class="block text-sm font-medium text-gray-700">Organization/Association *</label>
                                    <input type="text" name="description" id="memberships_description" required
                                           placeholder="e.g. Philippine Institute of Civil Engineers, Rotary Club"
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>

                            <div class="mt-4 flex justify-end space-x-2">
                                <button type="button" onclick="toggleForm('memberships')" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">
                                    Cancel
                                </button>
                                <x-primary-button>
                                    {{ __('Add Membership') }}
                                </x-primary-button>
                            </div>
                        </form>
                    </div>

                    <!-- Memberships List -->
                    @if($memberships && $memberships->count() > 0)
                        <div class="space-y-3">
                            @foreach($memberships as $membership)
                                <div class="flex justify-between items-center p-4 bg-purple-50 rounded-lg">
                                    <div class="flex items-center">
                                        <svg class="h-5 w-5 text-purple-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                                        </svg>
                                        <span class="text-sm text-gray-800">{{ $membership->description }}</span>
                                    </div>
                                    <form method="POST" action="{{ route('pds.destroy-other-information', [$employee, $membership]) }}" 
                                          onsubmit="return confirm('Are you sure you want to delete this membership?')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center text-gray-500 py-4">
                            <p class="text-sm">No memberships added yet.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Summary Section -->
            @if(($specialSkills && $specialSkills->count() > 0) || ($distinctions && $distinctions->count() > 0) || ($memberships && $memberships->count() > 0))
                <div class="bg-gray-50 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Summary</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-blue-600">{{ $specialSkills ? $specialSkills->count() : 0 }}</div>
                                <div class="text-sm text-gray-600">Special Skills</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-green-600">{{ $distinctions ? $distinctions->count() : 0 }}</div>
                                <div class="text-sm text-gray-600">Recognitions</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-purple-600">{{ $memberships ? $memberships->count() : 0 }}</div>
                                <div class="text-sm text-gray-600">Memberships</div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Information Panel -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mt-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">Guidelines</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <p class="font-medium">Special Skills:</p>
                                    <ul class="list-disc list-inside mt-1 text-xs">
                                        <li>Technical skills</li>
                                        <li>Creative abilities</li>
                                        <li>Hobbies and interests</li>
                                        <li>Language proficiency</li>
                                    </ul>
                                </div>
                                <div>
                                    <p class="font-medium">Distinctions:</p>
                                    <ul class="list-disc list-inside mt-1 text-xs">
                                        <li>Awards and honors</li>
                                        <li>Service recognitions</li>
                                        <li>Achievement certificates</li>
                                        <li>Performance awards</li>
                                    </ul>
                                </div>
                                <div>
                                    <p class="font-medium">Memberships:</p>
                                    <ul class="list-disc list-inside mt-1 text-xs">
                                        <li>Professional associations</li>
                                        <li>Civic organizations</li>
                                        <li>Alumni associations</li>
                                        <li>Social clubs</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript for Form Toggles -->
    <script>
        function toggleForm(type) {
            const form = document.getElementById('form-' + type);
            if (form.classList.contains('hidden')) {
                // Hide all other forms first
                document.querySelectorAll('[id^="form-"]').forEach(f => f.classList.add('hidden'));
                // Show this form
                form.classList.remove('hidden');
                // Focus on the input
                form.querySelector('input[name="description"]').focus();
            } else {
                form.classList.add('hidden');
            }
        }
    </script>
</x-app-layout>
