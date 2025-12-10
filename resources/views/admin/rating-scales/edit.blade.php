<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit Rating Scale
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <form action="{{ route('admin.rating-scales.update', $ratingScale) }}" method="POST" x-data="ratingScaleForm()">
                    @csrf
                    @method('PUT')
                    <div class="p-6 space-y-6">
                        <!-- Basic Information -->
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="md:col-span-2">
                                    <x-input-label for="name" :value="__('Scale Name')" />
                                    <x-text-input wire:model="name" id="name" name="name" type="text" value="{{ old('name', $ratingScale->name) }}" class="mt-1 block w-full" required autofocus />
                                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                                </div>

                                <div class="md:col-span-2">
                                    <x-input-label for="description" :value="__('Description (Optional)')" />
                                    <textarea id="description" name="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ old('description', $ratingScale->description) }}</textarea>
                                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                                </div>

                                <div>
                                    <label class="flex items-center">
                                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $ratingScale->is_active) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <span class="ml-2 text-sm text-gray-700">Active</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- QET Weights Configuration -->
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">QET Weight Configuration</h3>
                            <div class="bg-blue-50 border border-blue-200 rounded-md p-4 mb-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-blue-800">
                                            QET weights must sum to 100% (1.0). These weights determine how each component contributes to the final rating.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <x-input-label for="qet_weights_quality" :value="__('Quality Weight (%)')" />
                                    <div class="mt-1 relative rounded-md shadow-sm">
                                        <input type="number"
                                               id="qet_weights_quality"
                                               name="qet_weights[quality]"
                                               min="0"
                                               max="1"
                                               step="0.01"
                                               value="{{ old('qet_weights.quality', $ratingScale->getQETWeights()['quality']) }}"
                                               x-model="qetWeights.quality"
                                               @input="calculateTotal()"
                                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm pr-12"
                                               required>
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                            <span class="text-gray-500 sm:text-sm" x-text="Math.round(qetWeights.quality * 100) + '%'"></span>
                                        </div>
                                    </div>
                                    <x-input-error :messages="$errors->get('qet_weights.quality')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="qet_weights_efficiency" :value="__('Efficiency Weight (%)')" />
                                    <div class="mt-1 relative rounded-md shadow-sm">
                                        <input type="number"
                                               id="qet_weights_efficiency"
                                               name="qet_weights[efficiency]"
                                               min="0"
                                               max="1"
                                               step="0.01"
                                               value="{{ old('qet_weights.efficiency', $ratingScale->getQETWeights()['efficiency']) }}"
                                               x-model="qetWeights.efficiency"
                                               @input="calculateTotal()"
                                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm pr-12"
                                               required>
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                            <span class="text-gray-500 sm:text-sm" x-text="Math.round(qetWeights.efficiency * 100) + '%'"></span>
                                        </div>
                                    </div>
                                    <x-input-error :messages="$errors->get('qet_weights.efficiency')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="qet_weights_timeliness" :value="__('Timeliness Weight (%)')" />
                                    <div class="mt-1 relative rounded-md shadow-sm">
                                        <input type="number"
                                               id="qet_weights_timeliness"
                                               name="qet_weights[timeliness]"
                                               min="0"
                                               max="1"
                                               step="0.01"
                                               value="{{ old('qet_weights.timeliness', $ratingScale->getQETWeights()['timeliness']) }}"
                                               x-model="qetWeights.timeliness"
                                               @input="calculateTotal()"
                                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm pr-12"
                                               required>
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                            <span class="text-gray-500 sm:text-sm" x-text="Math.round(qetWeights.timeliness * 100) + '%'"></span>
                                        </div>
                                    </div>
                                    <x-input-error :messages="$errors->get('qet_weights.timeliness')" class="mt-2" />
                                </div>
                            </div>

                            <!-- Total Weight Display -->
                            <div class="mt-4 p-3 rounded-md" :class="totalWeight === 100 ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5" :class="totalWeight === 100 ? 'text-green-400' : 'text-red-400'" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium" :class="totalWeight === 100 ? 'text-green-800' : 'text-red-800'">
                                            Total Weight: <span x-text="totalWeight + '%'"></span>
                                        </p>
                                        <p class="text-sm" :class="totalWeight === 100 ? 'text-green-700' : 'text-red-700'">
                                            <span x-text="totalWeight === 100 ? 'Weights are correctly configured.' : 'Weights must sum to 100%. Current total: ' + totalWeight + '%'"></span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Rating Values -->
                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900">Rating Values</h3>
                                <button type="button" @click="addRatingValue()" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    Add Rating Value
                                </button>
                            </div>

                            <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4 mb-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-yellow-800">
                                            Rating value ranges must not overlap and should cover the full 0-100% spectrum.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Rating Values Container -->
                            <div class="space-y-4" id="rating-values-container">
                                <template x-for="(value, index) in ratingValues" :key="index">
                                    <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                                        <div class="flex items-center justify-between mb-4">
                                            <h4 class="text-sm font-medium text-gray-900">Rating Value #<span x-text="index + 1"></span></h4>
                                            <button type="button" @click="removeRatingValue(index)" class="text-red-600 hover:text-red-900">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Rating Value</label>
                                                <input type="number"
                                                       :name="`rating_values[${index}][rating_value]`"
                                                       x-model="value.rating_value"
                                                       min="1"
                                                       max="5"
                                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                                       required>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Rating Label</label>
                                                <input type="text"
                                                       :name="`rating_values[${index}][rating_label]`"
                                                       x-model="value.rating_label"
                                                       maxlength="50"
                                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                                       required>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Min Percentage (%)</label>
                                                <input type="number"
                                                       :name="`rating_values[${index}][min_percentage]`"
                                                       x-model="value.min_percentage"
                                                       min="0"
                                                       max="100"
                                                       step="0.01"
                                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                                       required>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Max Percentage (%)</label>
                                                <input type="number"
                                                       :name="`rating_values[${index}][max_percentage]`"
                                                       x-model="value.max_percentage"
                                                       min="0"
                                                       max="100"
                                                       step="0.01"
                                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                                       required>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Color Code</label>
                                                <div class="mt-1 flex items-center space-x-2">
                                                    <input type="color"
                                                           :name="`rating_values[${index}][color_code]`"
                                                           x-model="value.color_code"
                                                           class="h-8 w-16 rounded border-gray-300">
                                                    <input type="text"
                                                           :name="`rating_values[${index}][color_code]`"
                                                           x-model="value.color_code"
                                                           pattern="^#[0-9A-Fa-f]{6}$"
                                                           class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                                           required>
                                                </div>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Display Order</label>
                                                <input type="number"
                                                       :name="`rating_values[${index}][display_order]`"
                                                       x-model="value.display_order"
                                                       min="0"
                                                       max="10"
                                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                                       required>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            @if($errors->has('rating_values'))
                                <div class="mt-2 text-sm text-red-600">
                                    {{ $errors->first('rating_values') }}
                                </div>
                            @endif
                        </div>

                        <!-- Form Actions -->
                        <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
                            <a href="{{ route('admin.rating-scales.show', $ratingScale) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Cancel
                            </a>
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Update Rating Scale
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function ratingScaleForm() {
            return {
                qetWeights: {
                    quality: {{ $ratingScale->getQETWeights()['quality'] }},
                    efficiency: {{ $ratingScale->getQETWeights()['efficiency'] }},
                    timeliness: {{ $ratingScale->getQETWeights()['timeliness'] }}
                },
                totalWeight: 100,
                ratingValues: @json($ratingScale->ratingValues()->ordered()->get()->map(function($value) {
                    return [
                        'rating_value' => $value->rating_value,
                        'rating_label' => $value->rating_label,
                        'min_percentage' => $value->min_percentage,
                        'max_percentage' => $value->max_percentage,
                        'color_code' => $value->color_code,
                        'display_order' => $value->display_order,
                    ];
                })->toArray()),

                init() {
                    this.calculateTotal();
                },

                calculateTotal() {
                    this.totalWeight = Math.round(
                        (this.qetWeights.quality + this.qetWeights.efficiency + this.qetWeights.timeliness) * 100
                    );
                },

                addRatingValue() {
                    this.ratingValues.push({
                        rating_value: '',
                        rating_label: '',
                        min_percentage: '',
                        max_percentage: '',
                        color_code: '#6b7280',
                        display_order: this.ratingValues.length + 1
                    });
                },

                removeRatingValue(index) {
                    if (this.ratingValues.length > 2) {
                        this.ratingValues.splice(index, 1);
                    } else {
                        alert('You must have at least 2 rating values.');
                    }
                }
            }
        }
    </script>
</x-app-layout>
