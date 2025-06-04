<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Dog;

class DogRegistrationController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'age' => 'nullable|string|max:50',
            'gender' => 'nullable|in:macho,femea',
            'breed' => 'nullable|string|max:100',
            'owner_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'photo_base64' => 'nullable|string'
        ]);

        $photoUrl = null;

        if (!empty($validated['photo_base64'])) {
            $imageData = base64_decode($validated['photo_base64']);
            $filename = 'snouts/' . uniqid() . '.jpg';
            Storage::disk('public')->put($filename, $imageData);
            $photoUrl = asset('storage/' . $filename);
        }

        $dog = Dog::create([
            'name' => $validated['name'],
            'age' => $validated['age'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'breed' => $validated['breed'] ?? null,
            'owner_name' => $validated['owner_name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'photo_url' => $photoUrl,
            'status' => 'em_casa',
            'show_phone' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Animal registrado com sucesso!',
            'data' => $dog
        ]);
    }
}