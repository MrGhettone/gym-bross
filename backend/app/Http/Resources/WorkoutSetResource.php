<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkoutSetResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'set_number' => $this->set_number,
            'weight' => $this->weight,
            'repetitions' => $this->repetitions,
            'duration' => $this->duration,
            'distance' => $this->distance,
        ];
    }
}
