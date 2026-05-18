<?php

namespace SrArchitects\ScribeToolkit\Tests\Fixtures;

use Illuminate\Http\JsonResponse;

class TestController
{
    /**
     * @custom-status released
     * @custom-stability stable
     * @custom-desc Returns the released resource.
     */
    public function withReleasedStatus(): JsonResponse
    {
        return response()->json(['ok' => true]);
    }

    /**
     * @custom-stability stable
     */
    public function withStableStability(): JsonResponse
    {
        return response()->json(['ok' => true]);
    }

    /**
     * @custom-desc Creates a new resource with a detailed description.
     * Supports filtering by status and search by username.
     */
    public function withCustomDesc(): JsonResponse
    {
        return response()->json(['ok' => true]);
    }

    /** Plain endpoint — no annotations. */
    public function plain(): JsonResponse
    {
        return response()->json(['ok' => true]);
    }

    /**
     * @noApiKey
     */
    public function withNoApiKey(): JsonResponse
    {
        return response()->json(['ok' => true]);
    }

    /**
     * @custom-headerOptional X-Publisher-Id pub_test123
     * @custom-headerOptional X-Locale en
     */
    public function withOptionalHeaders(): JsonResponse
    {
        return response()->json(['ok' => true]);
    }

    /**
     * @custom-stability experimental
     */
    public function withExperimentalStability(): JsonResponse
    {
        return response()->json(['ok' => true]);
    }

    /**
     * @custom-status deprecated
     */
    public function withDeprecatedStatus(): JsonResponse
    {
        return response()->json(['ok' => true]);
    }

    /**
     * @custom-status invalid-value-not-in-list
     */
    public function withInvalidStatus(): JsonResponse
    {
        return response()->json(['ok' => true]);
    }
}
