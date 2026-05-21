<x-app-layout>
    <div class="space-y-6">
        @if(session('success'))
            <div class="p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    {{ session('success') }}
                </div>
            </div>
        @endif

        <!-- Page Header -->
        <div class="bg-white p-4 sm:p-6 rounded-lg border border-gray-200 shadow-sm text-center">
            <div class="flex flex-col items-center justify-center space-y-4">
                <div>
                    <h3 class="text-xl font-bold text-gray-900">All Announcements</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        {{ $announcements->total() }} total announcements
                    </p>
                </div>
                @if($announcements->total() > 0)
                <div>
                    <a href="{{ route('admin.announcements.create') }}" class="pds-edit-link" data-title="Create Announcement">
                        <x-primary-button>
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Create Announcement
                        </x-primary-button>
                    </a>
                </div>
                @endif
            </div>
        </div>

        <!-- Search and Filter Section -->
        <div class="bg-white p-4 sm:p-6 rounded-lg border border-gray-200 shadow-sm">
            <form method="GET" action="{{ route('admin.announcements.index') }}" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Search Bar -->
                    <div class="lg:col-span-2 relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input type="text"
                               name="search"
                               value="{{ request('search') }}"
                               placeholder="Search by title or message..."
                               class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>

                    <!-- Type Filter -->
                    <div>
                        <select name="type" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="">All Types</option>
                            <option value="info" {{ request('type') == 'info' ? 'selected' : '' }}>Info</option>
                            <option value="success" {{ request('type') == 'success' ? 'selected' : '' }}>Success</option>
                            <option value="warning" {{ request('type') == 'warning' ? 'selected' : '' }}>Warning</option>
                            <option value="danger" {{ request('type') == 'danger' ? 'selected' : '' }}>Danger</option>
                        </select>
                    </div>

                     <!-- Action Buttons -->
                     <div class="flex flex-col sm:flex-row gap-2">
                        <button type="submit" class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 w-full sm:w-auto">
                            Search
                        </button>
                        @if(request()->hasAny(['search', 'type']))
                        <a href="{{ route('admin.announcements.index') }}" class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 w-full sm:w-auto">
                            Clear
                        </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        <!-- Mobile Card View -->
        <div class="lg:hidden space-y-4">
            @forelse($announcements as $announcement)
                <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4">
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex items-center gap-2">
                             @if($announcement->is_important)
                                <i class="fas fa-star text-amber-500" title="Important"></i>
                            @endif
                            <h3 class="text-sm font-semibold text-gray-900 announcement-title">{{ $announcement->title }}</h3>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                            @if($announcement->type == 'info') bg-blue-100 text-blue-800
                            @elseif($announcement->type == 'warning') bg-amber-100 text-amber-800
                            @elseif($announcement->type == 'danger') bg-red-100 text-red-800
                            @elseif($announcement->type == 'success') bg-green-100 text-green-800
                            @endif">
                            {{ ucfirst($announcement->type) }}
                        </span>
                    </div>

                    <p class="text-sm text-gray-600 mb-3 announcement-message">{{ Str::limit($announcement->message, 100) }}</p>

                    <div class="grid grid-cols-2 gap-2 text-xs text-gray-500 mb-4">
                        <div>
                            <span class="block text-gray-400">Start Date</span>
                            {{ $announcement->starts_at ? $announcement->starts_at->format('M d, Y') : 'Immediately' }}
                        </div>
                        <div>
                            <span class="block text-gray-400">End Date</span>
                            {{ $announcement->ends_at ? $announcement->ends_at->format('M d, Y') : 'No Expiry' }}
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                        @php
                            $isActive = true;
                            if ($announcement->starts_at && $announcement->starts_at->isFuture()) $isActive = false;
                            if ($announcement->ends_at && $announcement->ends_at->isPast()) $isActive = false;
                        @endphp
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $isActive ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                            {{ $isActive ? 'Active' : 'Inactive' }}
                        </span>

                        <div class="flex space-x-2">
                            <a href="{{ route('admin.announcements.edit', $announcement) }}" class="pds-edit-link p-1 text-indigo-600 hover:text-indigo-900 bg-indigo-50 rounded" data-title="Edit Announcement">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </a>
                            <form action="{{ route('admin.announcements.destroy', $announcement) }}" method="POST" onsubmit="return confirm('Are you sure?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1 text-red-600 hover:text-red-900 bg-red-50 rounded">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                 <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-8 text-center">
                    <svg class="w-12 h-12 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-500 mb-2">No announcements found</h3>
                    <p class="text-sm text-gray-400 mb-4">
                        @if(request()->hasAny(['search', 'type']))
                            Try adjusting your search criteria or <a href="{{ route('admin.announcements.index') }}" class="text-blue-600 hover:text-blue-800">clear all filters</a>.
                        @else
                            Get started by creating your first announcement.
                        @endif
                    </p>
                    <a href="{{ route('admin.announcements.create') }}" class="pds-edit-link" data-title="Create Announcement">
                        <x-primary-button>
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Create Announcement
                        </x-primary-button>
                    </a>
                </div>
            @endforelse
        </div>

        <!-- Desktop Table View -->
        <div class="hidden lg:block bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto lg:overflow-visible">
                <table class="min-w-full divide-y divide-gray-200 lg:table-fixed lg:w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/3">Title / Message</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">Duration</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">Status</th>
                            <th class="relative px-6 py-3 w-1/6"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($announcements as $announcement)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-medium text-gray-900 flex items-center gap-2 announcement-title">
                                            @if($announcement->is_important)
                                                <i class="fas fa-star text-amber-500" title="Important"></i>
                                            @endif
                                            {{ $announcement->title }}
                                        </span>
                                        <span class="text-xs text-gray-500 truncate max-w-xs announcement-message">{{ Str::limit($announcement->message, 80) }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @if($announcement->type == 'info') bg-blue-100 text-blue-800
                                        @elseif($announcement->type == 'warning') bg-amber-100 text-amber-800
                                        @elseif($announcement->type == 'danger') bg-red-100 text-red-800
                                        @elseif($announcement->type == 'success') bg-green-100 text-green-800
                                        @endif">
                                        {{ ucfirst($announcement->type) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <div class="flex flex-col">
                                        <span>{{ $announcement->starts_at ? $announcement->starts_at->format('M d, Y') : 'Immediately' }}</span>
                                        <span class="text-xs text-gray-400">to {{ $announcement->ends_at ? $announcement->ends_at->format('M d, Y') : 'Forever' }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $isActive = true;
                                        if ($announcement->starts_at && $announcement->starts_at->isFuture()) $isActive = false;
                                        if ($announcement->ends_at && $announcement->ends_at->isPast()) $isActive = false;
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $isActive ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                        {{ $isActive ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end space-x-2">
                                        <a href="{{ route('admin.announcements.edit', $announcement) }}" class="pds-edit-link text-indigo-600 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 px-3 py-1 rounded transition-colors" data-title="Edit Announcement">
                                            Edit
                                        </a>
                                        <form action="{{ route('admin.announcements.destroy', $announcement) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this announcement?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900 bg-red-50 hover:bg-red-100 px-3 py-1 rounded transition-colors">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <svg class="w-12 h-12 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path>
                                    </svg>
                                    <h3 class="text-lg font-medium text-gray-500 mb-2">No announcements found</h3>
                                    <p class="text-sm text-gray-400 mb-4">Get started by creating your first announcement.</p>
                                    <a href="{{ route('admin.announcements.create') }}" class="pds-edit-link" data-title="Create Announcement">
                                        <x-primary-button>
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                            </svg>
                                            Create Announcement
                                        </x-primary-button>
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        @if($announcements->hasPages())
        <div class="bg-white px-4 py-3 border border-gray-200 rounded-lg">
            {{ $announcements->links() }}
        </div>
        @endif
    </div>

    <!-- Enhanced Search Functionality (Optional, but good for design alignment) -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-submit on type filter change
            const typeFilter = document.querySelector('select[name="type"]');
            if (typeFilter) {
                typeFilter.addEventListener('change', function() {
                    this.closest('form').submit();
                });
            }

            // Highlight search terms
             function highlightSearchTerms() {
                const searchTerm = new URLSearchParams(window.location.search).get('search');
                if (!searchTerm) return;

                const terms = searchTerm.split(' ').filter(term => term.length > 1);
                const targets = document.querySelectorAll('.announcement-title, .announcement-message');

                terms.forEach(term => {
                    targets.forEach(element => {
                        const regex = new RegExp(`(${term})`, 'gi');
                        // Use a safer approach to not break HTML inside if any
                        if(element.children.length === 0 || (element.children.length === 1 && element.querySelector('i'))) {
                             // Simple text replacement for leaf nodes or just text + icon
                            // Be careful not to replace inside HTML tags
                             const html = element.innerHTML;
                             // This is a naive implementation, but sufficient for simple text
                             // element.innerHTML = html.replace(regex, '<mark class="bg-yellow-200 px-0.5 rounded">$1</mark>');
                        }
                    });
                });
            }
            // highlightSearchTerms(); // Can enable if robust highlighting is needed
        });
    </script>
</x-app-layout>
