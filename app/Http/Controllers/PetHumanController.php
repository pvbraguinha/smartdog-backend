public function upload(Request $request)
{
    return response()->json([
        'received' => true,
        'focinho' => $request->hasFile('focinho'),
        'frontal' => $request->hasFile('frontal'),
        'angulo' => $request->hasFile('angulo'),
    ]);
}

