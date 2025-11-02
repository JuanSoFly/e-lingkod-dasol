<?php

namespace App\Exports;

use App\Exports\Traits\WithExcelFormatting;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class OfficePerformanceExport implements FromCollection, WithHeadings, WithTitle, WithColumnWidths, WithStyles
{
    use WithExcelFormatting;
    protected $data;
    protected $periodName;

    public function __construct(array $data, string $periodName = 'Unknown Period')
    {
        $this->data = $data;
        $this->periodName = $periodName;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        // Convert associative array to collection for export
        $exportData = collect($this->data)->map(function ($office) {
            return [
                'Office Name' => $office['name'],
                'Average Rating' => number_format($office['avg_rating'], 2),
                'Completion Rate (%)' => $office['completion_rate'],
                'QET Score' => number_format($office['qet_score'], 2),
                'Status' => $office['status'],
                'Total Workflows' => $office['total_workflows'],
                'Completed Workflows' => $office['completed_workflows']
            ];
        });

        return $exportData;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'Office Name',
            'Average Rating',
            'Completion Rate (%)',
            'QET Score',
            'Status',
            'Total Workflows',
            'Completed Workflows'
        ];
    }

    /**
     * Custom column formatting for office performance data
     */
    protected function getColumnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Average Rating
            'C' => NumberFormat::FORMAT_PERCENTAGE_00,           // Completion Rate
            'D' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // QET Score
            'F' => NumberFormat::FORMAT_NUMBER,                  // Total Workflows
            'G' => NumberFormat::FORMAT_NUMBER,                  // Completed Workflows
        ];
    }

    /**
     * Custom column widths for office performance data
     */
    public function columnWidths(): array
    {
        return [
            'A' => 30, // Office Name
            'B' => 15, // Average Rating
            'C' => 18, // Completion Rate
            'D' => 15, // QET Score - increased from 12 for better readability
            'E' => 15, // Status
            'F' => 18, // Total Workflows
            'G' => 22, // Completed Workflows
        ];
    }

    /**
     * Get the number of columns for this export
     */
    protected function getColumnCount(): int
    {
        return 7; // A to G columns
    }

    /**
     * Custom title for the export
     */
    public function title(): string
    {
        return "Office Performance Comparison - {$this->periodName}";
    }
}
