<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAssistantMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_id',
        'role',
        'content',
    ];

    public function chat(): BelongsTo
    {
        return $this->belongsTo(AiAssistantChat::class, 'chat_id');
    }
}
