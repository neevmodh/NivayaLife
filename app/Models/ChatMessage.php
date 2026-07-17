<?php

namespace App\Models;

use App\Models\Concerns\HasValidation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    use HasValidation;

    protected $fillable = [
        'family_member_id',
        'asked_by_user_id',
        'role',
        'content',
        'input_tokens',
        'output_tokens',
    ];

    public function rules(): array
    {
        return [
            'family_member_id' => ['required', 'integer', 'exists:family_members,id'],
            'role' => ['required', 'in:user,assistant'],
            'content' => ['required', 'string'],
        ];
    }

    public function familyMember(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class);
    }

    public function askedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asked_by_user_id');
    }
}
