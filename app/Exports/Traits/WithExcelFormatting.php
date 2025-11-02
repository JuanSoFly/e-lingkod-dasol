<?php

namespace App\Exports\Traits;

use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithProperties;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

trait WithExcelFormatting
{
    /**
     * Default formatting configuration.
     * Override these properties in your export class if needed.
     */
    protected $defaultColumnWidth = 30;
    protected $headerBackgroundColor = '2F5597'; // Professional blue
    protected $headerTextColor = 'FFFFFF';
    protected $headerFontSize = 12;
    protected $bodyFontSize = 11;
    protected $enableAutoFilter = true;
    protected $enableFrozenPanes = true;
    protected $enableTextWrapping = true;
    protected $printOrientation = PageSetup::ORIENTATION_LANDSCAPE;
    protected $paperSize = PageSetup::PAPERSIZE_A4;
    protected $margins = [0.7, 0.7, 0.75, 0.75]; // top, right, bottom, left

    /**
     * Define which columns should use specific formatting.
     * Override in your export class for custom column formatting.
     */
    protected function getColumnFormats(): array
    {
        return [
            // Override in export class for specific date/number formats
            // 'A' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            // 'B' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }

    /**
     * Define column widths.
     * Override in your export class for custom column widths.
     */
    public function columnWidths(): array
    {
        $columns = [];
        $columnCount = $this->getColumnCount();

        for ($i = 0; $i < $columnCount; $i++) {
            $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $columns[$columnLetter] = $this->defaultColumnWidth;
        }

        return $columns;
    }

    /**
     * Apply styles to the worksheet.
     */
    public function styles(Worksheet $sheet): array
    {
        $styles = [];

        // Header row styling (first row)
        $styles[1] = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => $this->headerTextColor],
                'size' => $this->headerFontSize,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => $this->headerBackgroundColor],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => $this->enableTextWrapping,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];

        // Body styling (all rows except header)
        $bodyStyle = [
            'font' => [
                'size' => $this->bodyFontSize,
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => $this->enableTextWrapping,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D0D0D0'],
                ],
            ],
        ];

        // Apply body styling to all rows
        $highestRow = $sheet->getHighestDataRow();
        $highestColumn = $sheet->getHighestDataColumn();
        $sheet->getStyle('A2:' . $highestColumn . $highestRow)->applyFromArray($bodyStyle);

        return $styles;
    }

    /**
     * Register events for post-processing.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Set print settings
                $this->setPrintSettings($sheet);

                // Enable auto-filter
                if ($this->enableAutoFilter) {
                    $this->enableAutoFilter($sheet);
                }

                // Enable frozen panes
                if ($this->enableFrozenPanes) {
                    $this->setFrozenPanes($sheet);
                }

                // Auto-size row heights for text wrapping
                if ($this->enableTextWrapping) {
                    $this->adjustRowHeights($sheet);
                }
            },
        ];
    }

    /**
     * Set print settings for the worksheet.
     */
    protected function setPrintSettings(Worksheet $sheet): void
    {
        $pageSetup = $sheet->getPageSetup();
        $pageSetup->setOrientation($this->printOrientation);
        $pageSetup->setPaperSize($this->paperSize);
        $pageSetup->setFitToWidth(1);
        $pageSetup->setFitToHeight(0);

        $pageMargins = $sheet->getPageMargins();
        $pageMargins->setTop($this->margins[0]);
        $pageMargins->setRight($this->margins[1]);
        $pageMargins->setBottom($this->margins[2]);
        $pageMargins->setLeft($this->margins[3]);
    }

    /**
     * Enable auto-filter for the data range.
     */
    protected function enableAutoFilter(Worksheet $sheet): void
    {
        $highestRow = $sheet->getHighestDataRow();
        $highestColumn = $sheet->getHighestDataColumn();
        $sheet->setAutoFilter('A1:' . $highestColumn . $highestRow);
    }

    /**
     * Set frozen panes to freeze header row.
     */
    protected function setFrozenPanes(Worksheet $sheet): void
    {
        $sheet->freezePane('A2');
    }

    /**
     * Adjust row heights for better text wrapping display.
     */
    protected function adjustRowHeights(Worksheet $sheet): void
    {
        $highestRow = $sheet->getHighestDataRow();

        for ($row = 1; $row <= $highestRow; $row++) {
            $sheet->getRowDimension($row)->setRowHeight(-1); // Auto height
        }
    }

    /**
     * Get the number of columns in the export.
     * This should be implemented in the export class.
     */
    protected function getColumnCount(): int
    {
        // Default implementation - should be overridden in export class
        return 10;
    }

    /**
     * Set document properties.
     */
    public function properties(): array
    {
        return [
            'title' => $this->title() ?? 'Export Report',
            'description' => 'Generated from E-Lingkod Dasol HRIS',
            'subject' => 'HRIS Export',
            'keywords' => 'hris, export, report',
            'category' => 'Reports',
            'manager' => 'E-Lingkod Dasol HRIS',
            'company' => 'Municipality of Dasol, Pangasinan',
        ];
    }

    /**
     * Get the document title.
     * Override in your export class.
     */
    public function title(): string
    {
        return 'Export Report';
    }

    /**
     * Apply custom column formatting.
     */
    public function columnFormats(): array
    {
        return $this->getColumnFormats();
    }

    /**
     * Custom formatting options for specific exports.
     * Override these methods in your export class for customization.
     */

    protected function getHeaderBackgroundColor(): string
    {
        return $this->headerBackgroundColor;
    }

    protected function getHeaderTextColor(): string
    {
        return $this->headerTextColor;
    }

    protected function getDefaultColumnWidth(): int
    {
        return $this->defaultColumnWidth;
    }

    protected function shouldEnableAutoFilter(): bool
    {
        return $this->enableAutoFilter;
    }

    protected function shouldEnableFrozenPanes(): bool
    {
        return $this->enableFrozenPanes;
    }

    protected function shouldEnableTextWrapping(): bool
    {
        return $this->enableTextWrapping;
    }
}