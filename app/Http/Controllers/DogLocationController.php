<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Dog;

class DogLocationController extends Controller
{
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:em_casa,perdido,em_busca_de_tutor',
            'show_phone' => 'boolean'
        ]);

        $dog = Dog::find($id);

        if (!$dog) {
            return response()->json([
                'success' => false,
                'message' => 'Cão não encontrado.'
            ], 404);
        }

        $dog->status = $validated['status'];
        $dog->show_phone = $validated['show_phone'] ?? true;
        $dog->save();

        return response()->json([
            'success' => true,
            'dog_id' => $dog->id,
            'new_status' => $dog->status,
            'show_phone' => $dog->show_phone,
            'message' => 'Status atualizado com sucesso.'
        ]);
    }
}