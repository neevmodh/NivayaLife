<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Validator;

/**
 * Runs the model's rules() against its attributes before every save, so
 * invalid data can never reach the database even if a caller bypasses form
 * request validation. If the model defines applyDefaults(), it's called
 * first (in the same listener) so generated fields — tokens, IDs, timestamps
 * set via ??= — are always in place before their own rules are checked.
 * This sidesteps Eloquent's event ordering (saving fires before creating),
 * which would otherwise validate a field before its default was ever set.
 */
trait HasValidation
{
    public static function bootHasValidation(): void
    {
        static::saving(function ($model) {
            if (method_exists($model, 'applyDefaults')) {
                $model->applyDefaults();
            }

            if (method_exists($model, 'rules')) {
                Validator::make($model->getAttributes(), $model->rules())->validate();
            }
        });
    }
}
