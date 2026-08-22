<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PublicUserResource;
use App\Models\User;

class UserController extends Controller
{
    public function show(string $username): PublicUserResource
    {
        $user = User::where('username', $username)->firstOrFail();

        return new PublicUserResource($user);
    }
}
