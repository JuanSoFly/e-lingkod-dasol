<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>OPCR - {{ $title ?? 'OPCR Report' }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            font-size: 16px;
            margin: 0;
        }
        .header h2 {
            font-size: 14px;
            margin: 5px 0;
        }
        .info-section {
            margin-bottom: 20px;
        }
        .info-row {
            display: flex;
            margin-bottom: 5px;
        }
        .info-label {
            font-weight: bold;
            width: 120px;
        }
        .mfo-section {
            margin-bottom: 20px;
        }
        .mfo-title {
            font-weight: bold;
            font-size: 13px;
            margin-bottom: 5px;
        }
        .si-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .si-table th,
        .si-table td {
            border: 1px solid #ccc;
            padding: 5px;
            text-align: left;
        }
        .si-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .summary-section {
            margin-top: 30px;
            border-top: 2px solid #000;
            padding-top: 15px;
        }
        .signature-section {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }
        .signature-box {
            width: 45%;
            text-align: center;
        }
        .signature-line {
            border-bottom: 1px solid #000;
            margin: 30px 0 5px 0;
        }
        .footer {
            margin-top: 30px;
            font-size: 10px;
            color: #666;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Republic of the Philippines</h1>
        <h2>Province of Pangasinan</h2>
        <h2>Municipality of Dasol</h2>
        <h2>OFFICE PERFORMANCE COMMITMENT AND REVIEW</h2>
    </div>

    <div class="info-section">
        <div class="info-row">
            <div class="info-label">Office:</div>
            <div>{{ isset($workflow['office']) ? $workflow['office']['name'] : 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Performance Period:</div>
            <div>{{ isset($workflow['period']) ? $workflow['period']['name'] : 'N/A' }} {{ isset($workflow['period']['start_date']) && isset($workflow['period']['end_date']) ? '(' . $workflow['period']['start_date']->format('M Y') . ' to ' . $workflow['period']['end_date']->format('M Y') . ')' : '' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Title:</div>
            <div>{{ $title ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Status:</div>
            <div>{{ isset($workflow['workflow_state']) ? ucfirst(str_replace('_', ' ', $workflow['workflow_state'])) : 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Overall Rating:</div>
            <div>{{ isset($workflow['overall_rating']) ? $workflow['overall_rating'] : 'Not Rated' }}</div>
        </div>
    </div>

    <div class="mfo-section">
        <h3>Major Final Outputs and Success Indicators</h3>

        @if($mfos->count() > 0)
            @foreach($mfos as $mfoData)
                <div class="mfo-title">
                    {{ $mfoData['mfo']['code'] ?? 'N/A' }} - {{ $mfoData['mfo']['description'] ?? 'N/A' }}
                </div>

                @if($mfoData['success_indicators']->count() > 0)
                    <table class="si-table">
                        <thead>
                            <tr>
                                <th width="5%">#</th>
                                <th width="45%">Success Indicator</th>
                                <th width="15%">Target</th>
                                <th width="15%">Actual</th>
                                <th width="10%">Rating</th>
                                <th width="10%">Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($mfoData['success_indicators'] as $index => $siData)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $siData['si']['description'] ?? 'N/A' }}</td>
                                    <td>{{ $siData['si']['target_quality'] ?? 'N/A' }}</td>
                                    <td>{{ $siData['si']['accomplished_quality'] ?? 'N/A' }}</td>
                                    <td>{{ $siData['si']['average_rating'] ?? 'N/A' }}</td>
                                    <td>{{ $siData['is_target_met'] ? 'Met' : 'Not Met' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p>No success indicators found for this MFO.</p>
                @endif
            @endforeach
        @else
            <p>No Major Final Outputs found for this workflow.</p>
        @endif
    </div>

    @if(isset($summary))
    <div class="summary-section">
        <h3>Performance Summary</h3>
        <div class="info-row">
            <div class="info-label">Total MFOs:</div>
            <div>{{ $summary['total_mfos'] }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Total Success Indicators:</div>
            <div>{{ $summary['total_success_indicators'] }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Rated Indicators:</div>
            <div>{{ $summary['rated_success_indicators'] }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Completion Rate:</div>
            <div>{{ $summary['rating_completion_percentage'] }}%</div>
        </div>
        <div class="info-row">
            <div class="info-label">Average Rating:</div>
            <div>{{ $summary['average_rating'] ?? 'Not Rated' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Targets Met:</div>
            <div>{{ $summary['targets_met_count'] }} out of {{ $summary['total_success_indicators'] }} ({{ $summary['targets_met_percentage'] }}%)</div>
        </div>
    </div>
    @endif

    <div class="signature-section">
        <div class="signature-box">
            <div>Prepared by:</div>
            <div class="signature-line"></div>
            <div>{{ isset($workflow['committedBy']) ? $workflow['committedBy']['name'] : 'N/A' }}</div>
            <div>{{ isset($workflow['office']) ? $workflow['office']['name'] : 'N/A' }}</div>
        </div>

        <div class="signature-box">
            <div>Approved by:</div>
            <div class="signature-line"></div>
            <div>{{ isset($workflow['approvedBy']) ? $workflow['approvedBy']['name'] : 'N/A' }}</div>
            <div>Municipality of Dasol</div>
        </div>
    </div>

    <div class="footer">
        <p>Generated on {{ isset($generated_at) ? $generated_at->format('F d, Y g:i A') : now()->format('F d, Y g:i A') }} by {{ isset($generated_by['name']) ? $generated_by['name'] : 'System' }}</p>
        <p>This is a system-generated document. Page 1 of 1.</p>
    </div>
</body>
</html>