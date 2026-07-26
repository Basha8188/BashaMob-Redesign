<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ThemePreferenceController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'theme_name' => ['required', Rule::in([
                'default-violet',
                'forest-moss',
                'cocoa-dust',
                'twilight-blue',
                'moonstone',
            ])],
            'theme_mode' => ['required', Rule::in(['light', 'dark', 'system'])],
            'use_system_mode' => ['required', 'boolean'],
        ]);

        $user = $request->user();
        $before = $user->only(['theme_name', 'theme_mode', 'use_system_mode']);
        $data['use_system_mode'] = $data['theme_mode'] === 'system';
        $user->forceFill($data)->save();

        ActivityLogger::record('user.theme.updated', $user, $before, $data);

        return response()->json([
            'message' => 'تم حفظ مظهر الواجهة.',
            'theme' => $user->only(['theme_name', 'theme_mode', 'use_system_mode']),
        ]);
    }
}
