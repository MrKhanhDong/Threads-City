<?php

namespace App\Http\Controllers;

use App\Services\MusicCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class MusicController extends Controller
{
    public function index(Request $request, MusicCatalog $catalog): JsonResponse
    {
        $data = $request->validate(['q' => 'nullable|string|max:100', 'offset' => 'nullable|integer|min:0|max:500']);
        try {
            return response()->json($catalog->search(trim($data['q'] ?? ''), (int) ($data['offset'] ?? 0)));
        } catch (RuntimeException $error) {
            return response()->json(['message' => $error->getMessage()], 503);
        }
    }
}
