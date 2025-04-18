<?php

namespace App\Http\Controllers;

use App\Models\Mood;
use Illuminate\Http\JsonResponse;

class MoodController extends Controller
{
    public function all(): JsonResponse
    {
        $moods = Mood::all(['id', 'name']);

        return response()->json([
            'message' => 'Moods fetched successfully',
            'data' => $moods,
        ]);
    }
}
