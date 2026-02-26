<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    /**
     * GET /api/holidays
     * List all holidays. Supports ?year= filter.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Holiday::orderBy('date');

        if ($year = $request->query('year')) {
            $query->whereYear('date', $year);
        }

        $holidays = $query->get();

        return response()->json([
            'success'  => true,
            'holidays' => $holidays->map->toFrontendArray()->values(),
        ]);
    }

    /**
     * POST /api/holidays
     * Add a new holiday (HR/Admin only).
     */
    public function store(Request $request): JsonResponse
    {
        if (! $request->user()->canApproveLeaves()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'date' => 'required|date',
            'type' => 'required|in:public,company',
        ]);

        $holiday = Holiday::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Holiday added successfully.',
            'holiday' => $holiday->toFrontendArray(),
        ], 201);
    }

    /**
     * DELETE /api/holidays/{id}
     * Delete a holiday (HR/Admin only).
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        if (! $request->user()->canApproveLeaves()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $holiday = Holiday::findOrFail($id);
        $holiday->delete();

        return response()->json([
            'success' => true,
            'message' => 'Holiday deleted successfully.',
        ]);
    }
}
