<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePushSubscriptionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Minishlink\WebPush\ContentEncoding;

class PushSubscriptionController extends Controller
{
    public function store(StorePushSubscriptionRequest $request): JsonResponse
    {
        $request->user()->updatePushSubscription(
            endpoint: $request->string('endpoint')->value(),
            key: $request->input('keys.p256dh'),
            token: $request->input('keys.auth'),
            contentEncoding: ContentEncoding::aes128gcm,
        );

        return response()->json(null, 204);
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->validate(['endpoint' => ['required', 'string']]);

        $request->user()->deletePushSubscription($request->query('endpoint'));

        return response()->json(null, 204);
    }
}
