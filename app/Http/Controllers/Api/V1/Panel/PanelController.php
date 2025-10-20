<?php

namespace App\Http\Controllers\Api\V1\Panel;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class PanelController extends Controller
{
    /**
     * Return a welcome message for the authenticated user's panel.
     *
     * @return JsonResponse JSON payload with a localized welcome message.
     */
    public function index(): JsonResponse
    {
        return Response::json([
            'message' => trans('msg.welcome'),
        ]);
    }
}
