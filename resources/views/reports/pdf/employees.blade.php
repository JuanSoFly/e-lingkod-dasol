<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Employee Masterlist</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 4px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 16px; }
        .header p { margin: 0; font-size: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Employee Masterlist</h1>
        <p>e-Lingkod Dasol HRIS - Generated on: {{ now()->format('F d, Y') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Emp. No.</th>
                <th>Last Name</th>
                <th>First Name</th>
                <th>Position</th>
                <th>Department</th>
                <th>Status</th>
                <th>Date Hired</th>
                <th>Email</th>
            </tr>
        </thead>
        <tbody>
            @forelse($employees as $employee)
                <tr>
                    <td>{{ $employee->employee_number }}</td>
                    <td>{{ $employee->last_name }}</td>
                    <td>{{ $employee->first_name }}</td>
                    <td>{{ $employee->position }}</td>
                    <td>{{ $employee->department }}</td>
                    <td>{{ $employee->employment_status }}</td>
                    <td>{{ $employee->date_hired?->format('Y-m-d') ?? 'Not provided' }}</td>
                    <td>{{ $employee->email }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align: center;">No employees found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>