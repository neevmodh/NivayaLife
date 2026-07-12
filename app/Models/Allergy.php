<?php

namespace App\Models;

use App\Models\Concerns\HasValidation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Allergy extends Model
{
    use HasValidation;

    protected $fillable = [
        'family_member_id',
        'allergen_name',
        'severity',
        'reaction_description',
        'diagnosed_date',
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
            'allergen_name' => ['required', 'string', 'max:255'],
            'severity' => ['required', 'in:mild,moderate,severe'],
            'diagnosed_date' => ['nullable', 'date', 'before_or_equal:tomorrow'],
        ];
    }

    public function familyMember(): BelongsTo
    {
        return $this->belongsTo(FamilyMember::class);
    }
}
