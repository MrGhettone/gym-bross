<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkoutExerciseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order' => $this->order,
            'exercise' => new ExerciseResource($this->whenLoaded('exercise')),
            'sets' => WorkoutSetResource::collection($this->whenLoaded('sets')),
        ];
    }
}
