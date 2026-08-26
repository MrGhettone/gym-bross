<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExerciseRequest;
use App\Http\Resources\ExerciseResource;
use App\Models\Exercise;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ExerciseController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Exercise::query()->orderBy('name');

        if ($search = $request->query('search')) {
            $query->where('name', 'like', '%'.$search.'%');
        }

        return ExerciseResource::collection($query->get());
    }

    public function store(StoreExerciseRequest $request): JsonResponse
    {
        $exercise = Exercise::create($request->validated());

        return (new ExerciseResource($exercise))->response()->setStatusCode(201);
    }
}
