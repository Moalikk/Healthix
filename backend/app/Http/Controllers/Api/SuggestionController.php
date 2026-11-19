<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DepartmentSuggester;
use Illuminate\Http\Request;

class SuggestionController extends Controller
{
    public function suggest(Request $request, DepartmentSuggester $suggester)
    {
        $data = $request->validate([
            'message' => 'required|string|min:3|max:500',
        ]);

        $result = $suggester->suggest($data['message']);
        $result['disclaimer'] = 'This is a suggestion only, not a medical diagnosis.';

        return response()->json(['data' => $result]);
    }
}