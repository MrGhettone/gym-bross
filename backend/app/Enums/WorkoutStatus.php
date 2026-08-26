<?php

namespace App\Enums;

enum WorkoutStatus: string
{
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
