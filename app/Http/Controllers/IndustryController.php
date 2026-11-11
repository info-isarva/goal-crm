<?php

namespace App\Http\Controllers;

use App\Models\Industry;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class IndustryController extends Controller
{
    /**
     * AJAX create industry from lead/deal create & edit modals.
     */
    public function ajaxCreate(Request $request)
    {
        try {
            $messages = [
                'name.required' => 'Please enter the industry name.',
                'name.max' => 'Industry name may not exceed 100 characters.',
                'description.max' => 'Description may not exceed 250 characters.',
            ];

            $validated = $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:100',
                    function ($attribute, $value, $fail) {
                        $exists = Industry::whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($value))])->exists();
                        if ($exists) {
                            $fail('This industry already exists.');
                        }
                    },
                ],
                'description' => 'nullable|string|max:250',
            ], $messages);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        if (!auth()->check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $industry = Industry::create([
            'name' => trim($validated['name']),
            'description' => isset($validated['description']) && trim((string) $validated['description']) !== ''
                ? trim($validated['description'])
                : null,
        ]);

        return response()->json([
            'id' => $industry->id,
            'name' => $industry->name,
            'description' => $industry->description,
        ]);
    }
}
