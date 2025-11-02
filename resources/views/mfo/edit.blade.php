<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit Major Final Output
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Update MFO Details</h3>
                            <p class="mt-1 text-sm text-gray-600">Adjust metadata, hierarchy placement, or deactivate this output as needed.</p>
                        </div>
                        <a href="{{ route('opcr.mfos.show', $mfo) }}" class="inline-flex items-center px-4 py-2 bg-gray-100 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Back to Details
                        </a>
                    </div>
                </div>

                <form method="POST" action="{{ route('opcr.mfos.update', $mfo) }}">
                    @csrf
                    @method('PATCH')
                    <div class="p-6 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="office_id" value="Office *" />
                                <select id="office_id" name="office_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                    @foreach($offices as $office)
                                        <option value="{{ $office->id }}" {{ old('office_id', $mfo->office_id) == $office->id ? 'selected' : '' }}>
                                            {{ $office->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('office_id')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="code" value="MFO Code *" />
                                <x-text-input id="code" name="code" type="text" class="mt-1 block w-full" value="{{ old('code', $mfo->code) }}" maxlength="20" required />
                                <x-input-error :messages="$errors->get('code')" class="mt-2" />
                            </div>

                            <div class="md:col-span-2">
                                <x-input-label for="title" value="MFO Title *" />
                                <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" value="{{ old('title', $mfo->title) }}" maxlength="255" required />
                                <x-input-error :messages="$errors->get('title')" class="mt-2" />
                            </div>

                            <div class="md:col-span-2">
                                <x-input-label for="description" value="Description" />
                                <textarea id="description" name="description" rows="4" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('description', $mfo->description) }}</textarea>
                                <x-input-error :messages="$errors->get('description')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="level" value="Hierarchy Level *" />
                                <select id="level" name="level" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                    @for($i = 1; $i <= 5; $i++)
                                        <option value="{{ $i }}" {{ old('level', $mfo->level) == $i ? 'selected' : '' }}>Level {{ $i }}</option>
                                    @endfor
                                </select>
                                <x-input-error :messages="$errors->get('level')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="parent_id" value="Parent MFO" />
                                <select id="parent_id" name="parent_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="">No Parent (Top Level)</option>
                                    @foreach($parentMFOs as $parent)
                                        <option value="{{ $parent->id }}" {{ old('parent_id', $mfo->parent_id) == $parent->id ? 'selected' : '' }}>
                                            {{ $parent->code }} — {{ $parent->title }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-sm text-gray-500">Only MFOs outside this branch are available to prevent circular references.</p>
                                <x-input-error :messages="$errors->get('parent_id')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="is_active" value="Status" />
                                <label class="inline-flex items-center mt-2">
                                    <input id="is_active" name="is_active" type="checkbox" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50" {{ old('is_active', $mfo->is_active) ? 'checked' : '' }}>
                                    <span class="ml-2 text-sm text-gray-600">Active</span>
                                </label>
                                <x-input-error :messages="$errors->get('is_active')" class="mt-2" />
                            </div>
                        </div>

                        <div class="bg-gray-50 px-6 py-4 flex items-center justify-end space-x-3 rounded-b-lg">
                            <a href="{{ route('opcr.mfos.show', $mfo) }}" class="inline-flex items-center px-4 py-2 bg-gray-100 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Cancel
                            </a>
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Update MFO
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
