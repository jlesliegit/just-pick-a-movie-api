<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use Illuminate\Database\Eloquent\Collection;

class GenreController extends Controller
{
    public function all(): Collection
    {
        return Genre::all(['id', 'name']);
    }
}
