<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Performance Target') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('performance-targets.update', $performanceTarget) }}">
                    @csrf
                    @method('PUT')

                    <div class="space-y-4">
                        <div>
                            <x-input-label for="objective" value="Objective" />
                            <textarea id="objective" name="objective" rows="3" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('objective', $performanceTarget->objective) }}</textarea>
                            <x-input-error :messages="$errors->get('objective')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="target" value="Target" />
                            <textarea id="target" name="target" rows="3" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('target', $performanceTarget->target) }}</textarea>
                            <x-input-error :messages="$errors->get('target')" class="mt-2" />
                        </div>
                         <div>
                            <x-input-label for="success_indicator" value="Success Indicator" />
                            <textarea id="success_indicator" name="success_indicator" rows="3" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('success_indicator', $performanceTarget->success_indicator) }}</textarea>
                             <x-input-error :messages="$errors->get('success_indicator')" class="mt-2" />
                        </div>
                         <div>
                            <x-input-label for="weight" value="Weight (%)" />
                            <x-text-input id="weight" name="weight" type="number" class="mt-1 block w-full" :value="old('weight', $performanceTarget->weight)" required />
                             <x-input-error :messages="$errors->get('weight')" class="mt-2" />
                        </div>
                    </div>

                    <div class="flex items-center justify-end mt-4">
                        <x-primary-button>Update Target</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>