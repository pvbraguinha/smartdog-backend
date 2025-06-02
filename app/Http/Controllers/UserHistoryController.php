<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserHistoryController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $date = $request->query('date');

        $mockHistory = [
            [
                'dog_id' => 101,
                'name' => 'Rex',
                'status' => 'em_casa',
                'recognized_at' => '2025-06-01 14:32'
            ],
            [
                'dog_id' => 102,
                'name' => 'Luna',
                'status' => 'perdido',
                'recognized_at' => '2025-06-02 09:18'
            ]
        ];

        return response()->json([
            'success' => true,
            'history' => $mockHistory
        ]);
    }
}
