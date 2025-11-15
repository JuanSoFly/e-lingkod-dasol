<?php

namespace App\Exports;

use App\Exports\Traits\WithExcelFormatting;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class AuditTrailExport implements FromCollection, WithHeadings, WithMapping, WithColumnWidths, WithStyles
{
    use WithExcelFormatting;

    private Collection $auditLogs;
    private bool $includeOldValues;
    private bool $includeNewValues;
    private array $filters;

    public function __construct(
        Collection $auditLogs,
        bool $includeOldValues = false,
        bool $includeNewValues = false,
        array $filters = []
    ) {
        $this->auditLogs = $auditLogs;
        $this->includeOldValues = $includeOldValues;
        $this->includeNewValues = $includeNewValues;
        $this->filters = $filters;
        $this->defaultColumnWidth = 22;
        $this->headerBackgroundColor = '1F4E78';
        $this->bodyFontSize = 10;
    }

    public function collection(): Collection
    {
        return $this->auditLogs;
    }

    public function headings(): array
    {
        $headings = [
            'Timestamp',
            'User',
            'Action',
            'Action Category',
            'Subject Type',
            'Subject ID',
            'Description',
            'IP Address',
            'User Agent',
        ];

        if ($this->includeOldValues) {
            $headings[] = 'Previous Values';
        }

        if ($this->includeNewValues) {
            $headings[] = 'New Values';
        }

        return $headings;
    }

    public function map($log): array
    {
        $row = [
            optional($log->created_at)->timezone(config('app.timezone'))->format('Y-m-d H:i:s'),
            optional($log->causer)->name ?? 'System',
            $log->properties['action'] ?? $log->description,
            Str::headline($log->properties['action_type'] ?? 'System'),
            class_basename($log->subject_type) ?: '-',
            $log->subject_id ?? '-',
            $log->description,
            $log->properties['user_ip'] ?? 'N/A',
            $log->properties['user_agent'] ?? 'N/A',
        ];

        if ($this->includeOldValues) {
            $row[] = $this->formatValuesForSheet($log->properties['old_values'] ?? []);
        }

        if ($this->includeNewValues) {
            $row[] = $this->formatValuesForSheet($log->properties['new_values'] ?? []);
        }

        return $row;
    }

    protected function getColumnFormats(): array
    {
        $formats = [
            'A' => 'yyyy-mm-dd hh:mm:ss',
            'B' => NumberFormat::FORMAT_TEXT,
            'C' => NumberFormat::FORMAT_TEXT,
            'D' => NumberFormat::FORMAT_TEXT,
            'E' => NumberFormat::FORMAT_TEXT,
            'F' => NumberFormat::FORMAT_TEXT,
            'G' => NumberFormat::FORMAT_TEXT,
            'H' => NumberFormat::FORMAT_TEXT,
            'I' => NumberFormat::FORMAT_TEXT,
        ];

        if ($this->includeOldValues) {
            $formats['J'] = NumberFormat::FORMAT_TEXT;
        }

        if ($this->includeNewValues) {
            $columnIndex = $this->includeOldValues ? 'K' : 'J';
            $formats[$columnIndex] = NumberFormat::FORMAT_TEXT;
        }

        return $formats;
    }

    public function columnWidths(): array
    {
        $widths = [
            'A' => 22,
            'B' => 24,
            'C' => 24,
            'D' => 20,
            'E' => 26,
            'F' => 14,
            'G' => 45,
            'H' => 16,
            'I' => 45,
        ];

        if ($this->includeOldValues) {
            $widths['J'] = 45;
        }

        if ($this->includeNewValues) {
            $widths[$this->includeOldValues ? 'K' : 'J'] = 45;
        }

        return $widths;
    }

    protected function getColumnCount(): int
    {
        $count = 9;

        if ($this->includeOldValues) {
            $count++;
        }

        if ($this->includeNewValues) {
            $count++;
        }

        return $count;
    }

    public function title(): string
    {
        $rangeLabel = $this->buildRangeLabel();
        return trim('Audit Trail Activity Logs ' . $rangeLabel);
    }

    private function buildRangeLabel(): string
    {
        $from = $this->filters['date_from'] ?? null;
        $to = $this->filters['date_to'] ?? null;

        if (!$from && !$to) {
            return '';
        }

        $fromLabel = $from ? date('M d, Y', strtotime($from)) : 'Start';
        $toLabel = $to ? date('M d, Y', strtotime($to)) : 'Today';

        return "({$fromLabel} - {$toLabel})";
    }

    private function formatValuesForSheet($values): string
    {
        if (empty($values)) {
            return '';
        }

        $flat = $this->flattenValues((array) $values);

        return collect($flat)
            ->map(fn ($value, $key) => sprintf('%s: %s', Str::headline($key), $this->stringifyValue($value)))
            ->implode(PHP_EOL);
    }

    private function flattenValues(array $values, string $prefix = ''): array
    {
        $result = [];

        foreach ($values as $key => $value) {
            $fullKey = $prefix === '' ? $key : $prefix . '.' . $key;

            if (is_array($value)) {
                $result += $this->flattenValues($value, $fullKey);
            } else {
                $result[$fullKey] = $value;
            }
        }

        return $result;
    }

    private function stringifyValue($value): string
    {
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return (string) $value;
    }
}
