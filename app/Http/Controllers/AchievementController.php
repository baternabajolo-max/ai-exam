<?php

namespace App\Http\Controllers;

use App\Http\Requests\ParseAchievementRequirementsRequest;
use App\Services\AiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\RateLimiter;

class AchievementController extends Controller
{
    public function parseRequirements(ParseAchievementRequirementsRequest $request, AiService $aiService): JsonResponse
    {
        $rateLimitKey = 'achievement-requirements:'.$request->user()->id;

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            return response()->json(['message' => 'Please wait before requesting another interpretation.'], 429);
        }

        RateLimiter::hit($rateLimitKey, 60);

        try {
            $interpretation = $aiService->interpretAchievementRequirements($request->validated('requirements_text'));

            if ($interpretation['confidence'] < 0.8 || $interpretation['conditions'] === []) {
                return response()->json(['message' => 'The requirement is ambiguous. Please clarify it or configure the rules manually.'], 422);
            }

            return response()->json(['requirements' => $interpretation]);
        } catch (\Throwable) {
            return response()->json(['message' => 'Requirements could not be interpreted right now. Please try again or configure them manually.'], 502);
        }
    }
}
