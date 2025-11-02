<?php

namespace App\Http\Controllers;

use App\Models\RatingScale;
use App\Models\RatingScaleValue;
use App\Models\OfficeRatingScale;
use App\Models\Office;
use App\Services\AuditTrailService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RatingScaleController extends Controller
{
    private AuditTrailService $auditTrailService;

    public function __construct(AuditTrailService $auditTrailService)
    {
        $this->middleware(['auth', 'permission:opcr.manage']);
        $this->auditTrailService = $auditTrailService;
    }

    /**
     * Display listing of rating scales.
     */
    public function index(Request $request): View
    {
        $query = RatingScale::with(['ratingValues', 'offices'])
            ->withCount(['offices' => function ($query) {
                $query->where('office_rating_scales.is_active', true);
            }]);

        // Filter by active status
        if ($request->boolean('active_only', true)) {
            $query->where('is_active', true);
        }

        // Search by name
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $ratingScales = $query->orderBy('is_default', 'desc')
                             ->orderBy('name')
                             ->paginate(15)
                             ->withQueryString();

        return view('admin.rating-scales.index', compact('ratingScales'));
    }

    /**
     * Show the form for creating a new rating scale.
     */
    public function create(): View
    {
        return view('admin.rating-scales.create');
    }

    /**
     * Store a newly created rating scale.
     */
    public function store(Request $request): RedirectResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:100|unique:rating_scales,name',
                'description' => 'nullable|string|max:255',
                'is_active' => 'boolean',
                'is_default' => 'boolean',
                'qet_weights' => 'required|array',
                'qet_weights.quantity' => 'required|numeric|min:0|max:1',
                'qet_weights.efficiency' => 'required|numeric|min:0|max:1',
                'qet_weights.timeliness' => 'required|numeric|min:0|max:1',
                'rating_values' => 'required|array|min:2',
                'rating_values.*.rating_value' => 'required|integer|min:1|max:5',
                'rating_values.*.rating_label' => 'required|string|max:50',
                'rating_values.*.min_percentage' => 'required|numeric|min:0|max:100',
                'rating_values.*.max_percentage' => 'required|numeric|min:0|max:100',
                'rating_values.*.color_code' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
                'rating_values.*.display_order' => 'required|integer|min:0|max:10',
            ]);

            // Validate QET weights sum to 1.0
            $weightSum = array_sum($validated['qet_weights']);
            if (abs($weightSum - 1.0) > 0.01) {
                throw ValidationException::withMessages([
                    'qet_weights' => 'QET weights must sum to 100% (1.0). Current sum: ' . ($weightSum * 100) . '%'
                ]);
            }

            // Validate rating values don't overlap
            $this->validateRatingValueRanges($validated['rating_values']);

            DB::beginTransaction();

            // If setting as default, unset other defaults
            if ($validated['is_default'] ?? false) {
                RatingScale::where('is_default', true)->update(['is_default' => false]);
            }

            $ratingScale = RatingScale::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
                'is_default' => $validated['is_default'] ?? false,
                'scale_configuration' => [
                    'min_rating' => 1,
                    'max_rating' => 5,
                    'type' => 'custom_5_point'
                ],
                'qet_weights' => $validated['qet_weights'],
                'created_by' => Auth::user()->name,
                'updated_by' => Auth::user()->name,
            ]);

            // Create rating scale values
            foreach ($validated['rating_values'] as $value) {
                RatingScaleValue::create([
                    'rating_scale_id' => $ratingScale->id,
                    'rating_value' => $value['rating_value'],
                    'rating_label' => $value['rating_label'],
                    'min_percentage' => $value['min_percentage'],
                    'max_percentage' => $value['max_percentage'],
                    'color_code' => $value['color_code'],
                    'display_order' => $value['display_order'],
                ]);
            }

            DB::commit();

            // Log audit trail
            $this->auditTrailService->log(
                'Rating Scale Created',
                $ratingScale->id,
                "Created rating scale: {$ratingScale->name}",
                [
                    'rating_scale_id' => $ratingScale->id,
                    'status' => 'success',
                ]
            );

            return redirect()
                ->route('admin.rating-scales.index')
                ->with('success', 'Rating scale created successfully.');

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();

            $this->auditTrailService->log(
                'Rating Scale Creation Failed',
                null,
                "Failed to create rating scale: {$e->getMessage()}",
                [
                    'status' => 'error',
                ]
            );

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to create rating scale: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified rating scale.
     */
    public function show(RatingScale $ratingScale): View
    {
        $ratingScale->load([
            'ratingValues' => function ($query) {
                $query->ordered();
            },
            'offices' => function ($query) {
                $query->wherePivot('is_active', true);
            }
        ]);

        $officeUsage = OfficeRatingScale::where('rating_scale_id', $ratingScale->id)
            ->currentlyActive()
            ->with('office')
            ->get();

        return view('admin.rating-scales.show', compact('ratingScale', 'officeUsage'));
    }

    /**
     * Show the form for editing the specified rating scale.
     */
    public function edit(RatingScale $ratingScale): View
    {
        if ($ratingScale->is_default) {
            return redirect()
                ->route('admin.rating-scales.show', $ratingScale)
                ->with('error', 'Default rating scale cannot be edited. Create a new scale or assign a different default first.');
        }

        $ratingScale->load(['ratingValues' => function ($query) {
            $query->ordered();
        }]);

        return view('admin.rating-scales.edit', compact('ratingScale'));
    }

    /**
     * Update the specified rating scale.
     */
    public function update(Request $request, RatingScale $ratingScale): RedirectResponse
    {
        if ($ratingScale->is_default) {
            return redirect()
                ->back()
                ->with('error', 'Default rating scale cannot be modified.');
        }

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:100|unique:rating_scales,name,' . $ratingScale->id,
                'description' => 'nullable|string|max:255',
                'is_active' => 'boolean',
                'qet_weights' => 'required|array',
                'qet_weights.quantity' => 'required|numeric|min:0|max:1',
                'qet_weights.efficiency' => 'required|numeric|min:0|max:1',
                'qet_weights.timeliness' => 'required|numeric|min:0|max:1',
                'rating_values' => 'required|array|min:2',
                'rating_values.*.rating_value' => 'required|integer|min:1|max:5',
                'rating_values.*.rating_label' => 'required|string|max:50',
                'rating_values.*.min_percentage' => 'required|numeric|min:0|max:100',
                'rating_values.*.max_percentage' => 'required|numeric|min:0|max:100',
                'rating_values.*.color_code' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
                'rating_values.*.display_order' => 'required|integer|min:0|max:10',
            ]);

            // Validate QET weights sum to 1.0
            $weightSum = array_sum($validated['qet_weights']);
            if (abs($weightSum - 1.0) > 0.01) {
                throw ValidationException::withMessages([
                    'qet_weights' => 'QET weights must sum to 100% (1.0). Current sum: ' . ($weightSum * 100) . '%'
                ]);
            }

            // Validate rating values don't overlap
            $this->validateRatingValueRanges($validated['rating_values']);

            DB::beginTransaction();

            $ratingScale->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
                'qet_weights' => $validated['qet_weights'],
                'updated_by' => Auth::user()->name,
            ]);

            // Delete existing rating values and create new ones
            $ratingScale->ratingValues()->delete();

            foreach ($validated['rating_values'] as $value) {
                RatingScaleValue::create([
                    'rating_scale_id' => $ratingScale->id,
                    'rating_value' => $value['rating_value'],
                    'rating_label' => $value['rating_label'],
                    'min_percentage' => $value['min_percentage'],
                    'max_percentage' => $value['max_percentage'],
                    'color_code' => $value['color_code'],
                    'display_order' => $value['display_order'],
                ]);
            }

            DB::commit();

            // Log audit trail
            $this->auditTrailService->log(
                'Rating Scale Updated',
                $ratingScale->id,
                "Updated rating scale: {$ratingScale->name}",
                [
                    'rating_scale_id' => $ratingScale->id,
                    'status' => 'success',
                ]
            );

            return redirect()
                ->route('admin.rating-scales.show', $ratingScale)
                ->with('success', 'Rating scale updated successfully.');

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();

            $this->auditTrailService->log(
                'Rating Scale Update Failed',
                $ratingScale->id,
                "Failed to update rating scale: {$ratingScale->name} - {$e->getMessage()}",
                [
                    'rating_scale_id' => $ratingScale->id,
                    'status' => 'error',
                ]
            );

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to update rating scale: ' . $e->getMessage());
        }
    }

    /**
     * Set a rating scale as the default.
     */
    public function setDefault(RatingScale $ratingScale): RedirectResponse
    {
        if (!$ratingScale->is_active) {
            return redirect()
                ->back()
                ->with('error', 'Only active rating scales can be set as default.');
        }

        try {
            DB::beginTransaction();

            // Unset current default
            RatingScale::where('is_default', true)->update(['is_default' => false]);

            // Set new default
            $ratingScale->update([
                'is_default' => true,
                'updated_by' => Auth::user()->name,
            ]);

            DB::commit();

            // Log audit trail
            $this->auditTrailService->log(
                'Rating Scale Set as Default',
                $ratingScale->id,
                "Set rating scale as default: {$ratingScale->name}",
                [
                    'rating_scale_id' => $ratingScale->id,
                    'status' => 'success',
                ]
            );

            return redirect()
                ->back()
                ->with('success', 'Rating scale set as default successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->with('error', 'Failed to set rating scale as default: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified rating scale.
     */
    public function destroy(RatingScale $ratingScale): RedirectResponse
    {
        if ($ratingScale->is_default) {
            return redirect()
                ->back()
                ->with('error', 'Default rating scale cannot be deleted.');
        }

        if (!$ratingScale->canBeDeleted()) {
            return redirect()
                ->back()
                ->with('error', 'This rating scale is currently assigned to offices and cannot be deleted.');
        }

        try {
            DB::beginTransaction();

            $scaleName = $ratingScale->name;
            $scaleId = $ratingScale->id;

            // Delete rating values first (cascade delete will handle this)
            $ratingScale->ratingValues()->delete();
            $ratingScale->delete();

            DB::commit();

            // Log audit trail
            $this->auditTrailService->log(
                'Rating Scale Deleted',
                $scaleId,
                "Deleted rating scale: {$scaleName}",
                [
                    'rating_scale_id' => $scaleId,
                    'status' => 'success',
                ]
            );

            return redirect()
                ->route('admin.rating-scales.index')
                ->with('success', 'Rating scale deleted successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            $this->auditTrailService->log(
                'Rating Scale Deletion Failed',
                $ratingScale->id,
                "Failed to delete rating scale: {$ratingScale->name} - {$e->getMessage()}",
                [
                    'rating_scale_id' => $ratingScale->id,
                    'status' => 'error',
                ]
            );

            return redirect()
                ->back()
                ->with('error', 'Failed to delete rating scale: ' . $e->getMessage());
        }
    }

    /**
     * Validate that rating value ranges don't overlap.
     */
    private function validateRatingValueRanges(array $ratingValues): void
    {
        $ranges = [];

        foreach ($ratingValues as $index => $value) {
            $min = (float) $value['min_percentage'];
            $max = (float) $value['max_percentage'];

            if ($min > $max) {
                throw ValidationException::withMessages([
                    "rating_values.{$index}.min_percentage" => 'Minimum percentage cannot be greater than maximum percentage.'
                ]);
            }

            // Check for overlaps with existing ranges
            foreach ($ranges as $existingIndex => $existingRange) {
                if (($min >= $existingRange['min'] && $min <= $existingRange['max']) ||
                    ($max >= $existingRange['min'] && $max <= $existingRange['max']) ||
                    ($min <= $existingRange['min'] && $max >= $existingRange['max'])) {

                    throw ValidationException::withMessages([
                        "rating_values.{$index}.min_percentage" => "Rating value range overlaps with rating value at index {$existingIndex}."
                    ]);
                }
            }

            $ranges[] = ['min' => $min, 'max' => $max];
        }
    }
}
