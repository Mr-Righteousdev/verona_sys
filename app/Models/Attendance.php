<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['student_id', 'date', 'present'])]
class Attendance extends Model
{
    protected function casts(): array
    {
        return ['present' => 'boolean']; // date stays a plain Y-m-d string so updateOrCreate matches on SQLite too
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
