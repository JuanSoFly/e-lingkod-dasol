<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Audit Trail Export</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #1f2937; }
        .header { text-align: center; margin-bottom: 16px; }
        .meta { margin-bottom: 12px; }
        .meta span { display: inline-block; margin-right: 12px; font-size: 11px; color: #4b5563; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 6px; text-align: left; }
        th { background-color: #f3f4f6; font-weight: 600; }
        tbody tr:nth-child(even) { background-color: #f9fafb; }
    </style>
</head>
<body>
<div class="header">
    <h2>Audit Trail Export</h2>
    <p>Municipality of Dasol HRIS</p>
</div>
<div class="meta">
    <span>Generated: {{ $generatedAt->format('F d, Y h:i A') }}</span>
    <span>Total Records: {{ $logs->count() }}</span>
    @if(!empty($filters))
        <span>Filters Applied:</span>
        @foreach($filters as $label => $value)
            @if(!empty($value))
                <span>{{ ucwords(str_replace('_', ' ', $label)) }}: {{ $value }}</span>
            @endif
        @endforeach
    @endif
</div>
<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>User</th>
            <th>Action</th>
            <th>Action Type</th>
            <th>Subject Type</th>
            <th>Subject ID</th>
            <th>Description</th>
            <th>IP</th>
            <th>User Agent</th>
            @if($includeOldValues)
                <th>Old Values</th>
            @endif
            @if($includeNewValues)
                <th>New Values</th>
            @endif
        </tr>
    </thead>
    <tbody>
        @forelse($logs as $log)
            <tr>
                <td>{{ optional($log->created_at)->format('Y-m-d H:i:s') }}</td>
                <td>{{ optional($log->causer)->name ?? 'System' }}</td>
                <td>{{ $log->properties['action'] ?? $log->description }}</td>
                <td>{{ $log->properties['action_type'] ?? '-' }}</td>
                <td>{{ $log->subject_type ?? '-' }}</td>
                <td>{{ $log->subject_id ?? '-' }}</td>
                <td>{{ $log->description }}</td>
                <td>{{ $log->properties['user_ip'] ?? '' }}</td>
                <td>{{ $log->properties['user_agent'] ?? '' }}</td>
                @if($includeOldValues)
                    <td>{{ json_encode($log->properties['old_values'] ?? [], JSON_UNESCAPED_UNICODE) }}</td>
                @endif
                @if($includeNewValues)
                    <td>{{ json_encode($log->properties['new_values'] ?? [], JSON_UNESCAPED_UNICODE) }}</td>
                @endif
            </tr>
        @empty
            <tr>
                <td colspan="{{ 9 + ($includeOldValues ? 1 : 0) + ($includeNewValues ? 1 : 0) }}" style="text-align:center;">No audit activities found for the selected filters.</td>
            </tr>
        @endforelse
    </tbody>
</table>
</body>
</html>
