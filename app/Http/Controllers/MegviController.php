<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MegviController extends Controller
{
    public function testar(Request $request)
    {
        $request->validate([
            'image' => 'required|image',
        ]);

        $imagePath = $request->file('image')->getRealPath();

        $response = Http::asMultipart()->post('https://api-cn.faceplusplus.com/facepp/v1/dognosedetect', [
            [
                'name' => 'api_key',
                'contents' => env('MEGVII_API_KEY'),
            ],
            [
                'name' => 'api_secret',
                'contents' => env('MEGVII_API_SECRET'),
            ],
            [
                'name' => 'image_file',
                'contents' => fopen($imagePath, 'r'),
            ],
        ]);

        return response()->json($response->json());
    }
}
