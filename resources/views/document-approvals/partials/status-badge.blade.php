<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
    @if($status === 'approved') bg-green-100 text-green-800 
    @elseif($status === 'rejected') bg-red-100 text-red-800 
    @elseif($status === 'under_review') bg-blue-100 text-blue-800 
    @elseif($status === 'submitted') bg-yellow-100 text-yellow-800 
    @else bg-gray-100 text-gray-800 @endif">
    @if($status === 'under_review')
        Under Review
    @else
        {{ ucfirst($status) }}
    @endif
</span>