<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use Illuminate\Database\Eloquent\Collection;

class GenreController extends Controller
{
    public function all(): \Illuminate\Http\JsonResponse
    {
        $genres = Genre::all(['id', 'name']);
        return response()->json([
            'message' => 'Genres fetched successfully',
            'data' => $genres
        ]);
    }
}
