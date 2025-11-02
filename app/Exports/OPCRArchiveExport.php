<?php

namespace App\Exports;

use App\Exports\Traits\WithExcelFormatting;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class OPCRArchiveExport implements FromCollection, WithHeadings, WithMapping, WithColumnWidths, WithStyles
{
    use WithExcelFormatting;
    protected $workflows;

    /**
     * Create a new export instance.
     *
     * @param \Illuminate\Support\Collection $workflows
     */
    public function __construct($workflows)
    {
        $this->workflows = $workflows;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        // Flatten the workflow-target relationships into a collection of export rows
        $exportData = collect();

        foreach ($this->workflows as $workflow) {
            $workflow->load(['office', 'period', 'committedBy.employee', 'targets.mfo', 'targets.successIndicator', 'targets.ratings']);

            if ($workflow->targets->count() > 0) {
                // Export workflows with targets
                foreach ($workflow->targets as $target) {
                    $rating = $target->ratings->first();
                    $exportData->push([
                        'workflow' => $workflow,
                        'target' => $target,
                        'rating' => $rating,
                        'has_targets' => true,
                    ]);
                }
            } else {
                // Export workflows without targets
                $exportData->push([
                    'workflow' => $workflow,
                    'target' => null,
                    'rating' => null,
                    'has_targets' => false,
                ]);
            }
        }

        return $exportData;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'OPCR Title',
            'Office',
            'Performance Period',
            'Department Head',
            'MFO Code',
            'MFO Description',
            'Success Indicator',
            'Target Quantity',
            'Accomplished Quantity',
            'Target Efficiency',
            'Accomplished Efficiency',
            'Target Timeliness',
            'Accomplished Timeliness',
            'QET Rating',
            'Adjectival Rating',
            'Final Status',
            'Date Approved',
            'Created At',
        ];
    }

    /**
     * @param mixed $row
     * @return array
     */
    public function map($row): array
    {
        $workflow = $row['workflow'];
        $target = $row['target'];
        $rating = $row['rating'];
        $hasTargets = $row['has_targets'];

        if ($hasTargets && $target) {
            // Row with target data
            return [
                $workflow->title,
                $workflow->office->name ?? 'Unknown Office',
                $workflow->period->name ?? 'Unknown Period',
                $workflow->committedBy->employee ?
                    $workflow->committedBy->employee->first_name . ' ' . $workflow->committedBy->employee->last_name : 'Unknown',
                $target->mfo->code ?? '',
                $target->mfo->description ?? '',
                $target->successIndicator->description ?? '',
                $target->target_quantity ?? '',
                $rating?->accomplished_quantity ?? '',
                $target->target_efficiency ?? '',
                $rating?->accomplished_efficiency ?? '',
                $target->target_timeliness ?? '',
                $rating?->accomplished_timeliness ?? '',
                $rating?->final_rating ?? '',
                $rating?->adjectival_rating ?? '',
                ucfirst($workflow->workflow_state),
                $workflow->approved_at?->format('Y-m-d H:i:s') ?? '',
                $workflow->created_at->format('Y-m-d H:i:s'),
            ];
        } else {
            // Row without target data (workflow only)
            return [
                $workflow->title,
                $workflow->office->name ?? 'Unknown Office',
                $workflow->period->name ?? 'Unknown Period',
                $workflow->committedBy->employee ?
                    $workflow->committedBy->employee->first_name . ' ' . $workflow->committedBy->employee->last_name : 'Unknown',
                'No Performance Targets Available',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                ucfirst($workflow->workflow_state),
                $workflow->approved_at?->format('Y-m-d H:i:s') ?? '',
                $workflow->created_at->format('Y-m-d H:i:s'),
            ];
        }
    }

    /**
     * Override header background color to maintain lavender theme
     */
    protected function getHeaderBackgroundColor(): string
    {
        return 'E6E6FA'; // Lavender color from original implementation
    }

    /**
     * Custom column formatting for OPCR data
     */
    protected function getColumnFormats(): array
    {
        return [
            'H' => NumberFormat::FORMAT_NUMBER,        // Target Quantity
            'I' => NumberFormat::FORMAT_NUMBER,        // Accomplished Quantity
            'N' => NumberFormat::FORMAT_NUMBER_00,     // QET Rating
            'Q' => 'YYYY-MM-DD HH:MM:SS',             // Date Approved
            'R' => 'YYYY-MM-DD HH:MM:SS',             // Created At
        ];
    }

    /**
     * Custom column widths for OPCR data
     */
    public function columnWidths(): array
    {
        return [
            'A' => 25, // OPCR Title
            'B' => 20, // Office
            'C' => 20, // Performance Period - increased from 15
            'D' => 25, // Department Head
            'E' => 18, // MFO Code - increased from 12
            'F' => 25, // MFO Description - increased from 20
            'G' => 35, // Success Indicator - increased from 30
            'H' => 15, // Target Quantity - increased from 12
            'I' => 18, // Accomplished Quantity - increased from 15
            'J' => 18, // Target Efficiency - increased from 12
            'K' => 20, // Accomplished Efficiency - increased from 15
            'L' => 18, // Target Timeliness - increased from 12
            'M' => 20, // Accomplished Timeliness - increased from 15
            'N' => 15, // QET Rating - increased from 10
            'O' => 18, // Adjectival Rating - increased from 15
            'P' => 15, // Final Status - increased from 12
            'Q' => 20, // Date Approved - increased from 18
            'R' => 20, // Created At - increased from 18
        ];
    }

    /**
     * Get the number of columns for this export
     */
    protected function getColumnCount(): int
    {
        return 18; // A to R columns
    }

    /**
     * Custom title for the export
     */
    public function title(): string
    {
        return 'OPCR Archive Report';
    }
}