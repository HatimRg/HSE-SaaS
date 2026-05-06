<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingParticipant extends BaseModel
{
    public $timestamps = false;

    protected $fillable = [
        'company_id', 'training_session_id', 'worker_id',
        'status', 'score', 'result', 'feedback',
    ];

    protected $casts = [
        'score' => 'decimal:2',
    ];

    public function trainingSession(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class);
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(Worker::class);
    }
}
