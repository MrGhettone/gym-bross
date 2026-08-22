<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\FriendshipStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFriendshipRequest;
use App\Http\Resources\FriendshipResource;
use App\Models\Friendship;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class FriendshipController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Friendship::query()
            ->with(['requester', 'addressee'])
            ->involvingUser($request->user()->id);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return FriendshipResource::collection($query->latest()->get());
    }

    public function store(StoreFriendshipRequest $request): JsonResponse
    {
        $addressee = User::where('username', $request->string('username')->value())->firstOrFail();

        $friendship = Friendship::create([
            'requester_id' => $request->user()->id,
            'addressee_id' => $addressee->id,
            'status' => FriendshipStatus::Pending,
        ]);

        return (new FriendshipResource($friendship->load(['requester', 'addressee'])))
            ->response()
            ->setStatusCode(201);
    }

    public function accept(Friendship $friendship): FriendshipResource
    {
        Gate::authorize('accept', $friendship);

        $friendship->update(['status' => FriendshipStatus::Accepted]);

        return new FriendshipResource($friendship->load(['requester', 'addressee']));
    }

    public function reject(Friendship $friendship): FriendshipResource
    {
        Gate::authorize('reject', $friendship);

        $friendship->update(['status' => FriendshipStatus::Rejected]);

        return new FriendshipResource($friendship->load(['requester', 'addressee']));
    }

    public function block(Friendship $friendship): FriendshipResource
    {
        Gate::authorize('block', $friendship);

        $friendship->update(['status' => FriendshipStatus::Blocked]);

        return new FriendshipResource($friendship->load(['requester', 'addressee']));
    }

    public function destroy(Friendship $friendship): JsonResponse
    {
        $ability = $friendship->status === FriendshipStatus::Accepted ? 'remove' : 'cancel';
        Gate::authorize($ability, $friendship);

        $friendship->delete();

        return response()->json(null, 204);
    }
}
