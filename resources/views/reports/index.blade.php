<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Reports & Analytics') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Employee Reports -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="font-semibold text-lg text-gray-800">Employee Reports</h3>
                        <p class="mt-2 text-sm text-gray-600">Generate reports related to employee data.</p>
                        <div class="mt-4 border-t pt-4 space-y-2">
                            <div class="flex justify-between items-center">
                                <p class="text-sm">Employee Masterlist</p>
                                <div class="flex space-x-2">
                                    @can('reports.export')
                                    <a href="{{ route('reports.employees.excel') }}" class="text-sm text-green-600 hover:text-green-800 font-semibold">Excel</a>
                                    <a href="{{ route('reports.employees.pdf') }}" class="text-sm text-red-600 hover:text-red-800 font-semibold">PDF</a>
                                    @endcan
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Leave Reports -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="font-semibold text-lg text-gray-800">Leave Reports</h3>
                        <p class="mt-2 text-sm text-gray-600">Generate reports on leave applications and balances.</p>
                         <div class="mt-4 border-t pt-4 space-y-2">
                             <div class="flex justify-between items-center">
                                <p class="text-sm">Leave Balance Summary</p>
                                <div class="flex space-x-2">
                                    @can('reports.export')
                                    <a href="{{ route('reports.leave-balances.excel') }}" class="text-sm text-green-600 hover:text-green-800 font-semibold">Excel</a>
                                    @endcan
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                 <!-- Performance Reports -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="font-semibold text-lg text-gray-800">Performance Reports</h3>
                        <p class="mt-2 text-sm text-gray-600">Generate reports on IPCR ratings and reviews.</p>
                         <div class="mt-4 border-t pt-4 space-y-2">
                            <div class="flex justify-between items-center">
                                <p class="text-sm">Performance Review Summary</p>
                                <div class="flex space-x-2">
                                     @can('reports.export')
                                    <a href="{{ route('reports.performance-summary.excel') }}" class="text-sm text-green-600 hover:text-green-800 font-semibold">Excel</a>
                                     @endcan
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>