<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSelfRatingRequest;
use App\Http\Requests\StoreSupervisorRatingRequest;
use App\Models\PerformanceRating;
use App\Models\PerformanceTarget;
use Illuminate\Http\Request;

class PerformanceRatingController extends Controller
{
    public function storeSelfRating(StoreSelfRatingRequest $request, PerformanceTarget $target)
    {
        $this->authorize('rate', $target);

        PerformanceRating::updateOrCreate(
            ['target_id' => $target->id],
            ['self_rating' => $request->validated('self_rating')]
        );

        return back()->with('success', 'Self-rating submitted successfully.');
    }

    public function storeSupervisorRating(StoreSupervisorRatingRequest $request, PerformanceTarget $target)
    {
        $this->authorize('evaluate', $target);

        // Calculate final rating (simple average for now)
        $selfRating = $target->rating->self_rating;
        $supervisorRating = $request->validated('supervisor_rating');
        $finalRating = round(($selfRating + $supervisorRating) / 2);

        PerformanceRating::updateOrCreate(
            ['target_id' => $target->id],
            [
                'supervisor_rating' => $supervisorRating,
                'final_rating' => $finalRating
            ]
        );

        return back()->with('success', 'Supervisor rating submitted successfully.');
    }
}