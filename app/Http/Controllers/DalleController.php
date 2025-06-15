<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DalleService;

class DalleController extends Controller
{
    public function gerar(Request $request, DalleService $dalle)
    {
        $request->validate([
            'prompt' => 'required|string',
        ]);

        $url = $dalle->gerarImagem($request->input('prompt'));

        return response()->json(['image_url' => $url]);
    }
}