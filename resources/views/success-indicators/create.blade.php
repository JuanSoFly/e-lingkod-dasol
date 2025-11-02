<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Create Success Indicator
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">Indicator Details</h3>
                        <p class="mt-1 text-sm text-gray-600">Align targets with quantitative, efficiency, and timeliness measures.</p>
                    </div>
                    <a href="{{ route('opcr.success-indicators.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">Cancel</a>
                </div>

                <form method="POST" action="{{ route('opcr.success-indicators.store') }}">
                    @csrf
                    <div class="p-6 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="mfo_id" value="Major Final Output *" />
                                <select id="mfo_id" name="mfo_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                    <option value="">Select MFO</option>
                                    @foreach($mfos as $mfo)
                                        <option value="{{ $mfo->id }}" {{ (old('mfo_id', $selectedMFO)) == $mfo->id ? 'selected' : '' }}>
                                            {{ $mfo->code }} &mdash; {{ Str::limit($mfo->title, 70) }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('mfo_id')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="code" value="Indicator Code *" />
                                <x-text-input id="code" name="code" type="text" class="mt-1 block w-full" value="{{ old('code', $indicatorCode) }}" maxlength="30" required />
                                <p class="mt-1 text-sm text-gray-500">Codes follow the SI-### convention. Adjust only if necessary.</p>
                                <x-input-error :messages="$errors->get('code')" class="mt-2" />
                            </div>

                            <div class="md:col-span-2">
                                <x-input-label for="title" value="Indicator Title *" />
                                <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" value="{{ old('title') }}" maxlength="255" placeholder="e.g., Client feedback response rate" required />
                                <x-input-error :messages="$errors->get('title')" class="mt-2" />
                            </div>

                            <div class="md:col-span-2">
                                <x-input-label for="description" value="Description" />
                                <textarea id="description" name="description" rows="4" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" placeholder="Detail the indicator scope and measurement logic">{{ old('description') }}</textarea>
                                <x-input-error :messages="$errors->get('description')" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="target_quantity" value="Target Quantity" />
                                <input id="target_quantity" name="target_quantity" type="number" step="0.01" min="0" value="{{ old('target_quantity') }}" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" placeholder="e.g., 120" />
                                <x-input-error :messages="$errors->get('target_quantity')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="measurement_unit" value="Measurement Unit" />
                                <x-text-input id="measurement_unit" name="measurement_unit" type="text" class="mt-1 block w-full" value="{{ old('measurement_unit') }}" maxlength="50" placeholder="e.g., Applications processed" />
                                <x-input-error :messages="$errors->get('measurement_unit')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="target_efficiency" value="Target Efficiency" />
                                <x-text-input id="target_efficiency" name="target_efficiency" type="text" class="mt-1 block w-full" value="{{ old('target_efficiency') }}" maxlength="100" placeholder="e.g., 95% SLA compliance" />
                                <x-input-error :messages="$errors->get('target_efficiency')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="target_timeliness" value="Target Timeliness" />
                                <x-text-input id="target_timeliness" name="target_timeliness" type="text" class="mt-1 block w-full" value="{{ old('target_timeliness') }}" maxlength="100" placeholder="e.g., Within 2 working days" />
                                <x-input-error :messages="$errors->get('target_timeliness')" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="is_active" value="Status" />
                            <label class="inline-flex items-center mt-2">
                                <input id="is_active" name="is_active" type="checkbox" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50" {{ old('is_active', true) ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-600">Active</span>
                            </label>
                            <x-input-error :messages="$errors->get('is_active')" class="mt-2" />
                        </div>

                        <div class="bg-gray-50 px-6 py-4 flex items-center justify-end space-x-3 rounded-b-lg">
                            <a href="{{ route('opcr.success-indicators.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">Cancel</a>
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">Save Indicator</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
