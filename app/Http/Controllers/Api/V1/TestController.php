<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TestController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/test",
     *     @OA\Response(response="200", description="Test endpoint")
     * )
     */
    public function index()
    {
        return response()->json(['message' => 'Hello World']);
    }
}
