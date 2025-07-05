<?php

namespace App\Exports;

use App\Models\PerformanceReview;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PerformanceSummaryExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        // For now, let's pull all reviews. This could be filtered by period later.
        return PerformanceReview::with(['employee', 'period', 'reviewer'])->get();
    }

    public function headings(): array
    {
        return [
            'Employee Name',
            'Performance Period',
            'Overall Rating',
            'Strengths',
            'Areas for Improvement',
            'Recommendations',
            'Reviewed By',
            'Review Date',
        ];
    }

    public function map($review): array
    {
        return [
            $review->employee->first_name . ' ' . $review->employee->last_name,
            $review->period->year . ' - ' . $review->period->semester,
            $review->overall_rating,
            $review->strengths,
            $review->areas_for_improvement,
            $review->recommendations,
            $review->reviewer->name,
            $review->review_date->format('Y-m-d'),
        ];
    }
}