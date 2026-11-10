<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['assessment_id', 'student_id', 'competency_id', 'level'])]
class Score extends Model
{
    public const MIN = 0.9;

    public const MAX = 3.0;

    protected function casts(): array
    {
        return ['level' => 'float'];
    }

    /** CBC achievement band for a score on the 0.9-3.0 scale. */
    public static function band(float $level): string
    {
        return match (true) {
            $level < 1.5 => 'Basic',
            $level < 2.5 => 'Moderate',
            default => 'Outstanding',
        };
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function competency(): BelongsTo
    {
        return $this->belongsTo(Competency::class);
    }
}
