<?php

namespace App\Models;

use App\Models\Concerns\HasValidation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChronicCondition extends Model
{
    use HasValidation;

    protected $fillable = [
        'family_member_id',
        'condition_name',
        'diagnosed_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'diagnosed_date' => 'date',
        ];
    }

    public function rules(): array
    {
        return [
            'family_member_id' => ['required', 'integer', 'exists:family_members,id'],
            'condition_name' => ['required', 'string', 'max:255'],
            'diagnosed_date' => ['nullable', 'date', 'before_or_equal:tomorrow'],
            'status' => ['required', 'in:active,managed,resolved'],
        ];
    }

    public function familyMember(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class);
    }
}
