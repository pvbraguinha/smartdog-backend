<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Dog;

class UserHistoryController extends Controller
{
    public function index(Request $request)
    {
        $dogs = Dog::orderBy('created_at', 'desc')->get(['id as dog_id', 'name', 'status', 'created_at as recognized_at']);

        return response()->json([
            'success' => true,
            'history' => $dogs
        ]);
    }
}