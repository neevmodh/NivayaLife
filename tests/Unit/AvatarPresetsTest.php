<?php

namespace Tests\Unit;

use App\Support\AvatarPresets;
use PHPUnit\Framework\TestCase;

class AvatarPresetsTest extends TestCase
{
    public function test_the_set_spans_every_age_group(): void
    {
        $groups = AvatarPresets::grouped();

        $this->assertSame(['Children', 'Adults', 'Older'], array_keys($groups));
        foreach ($groups as $group => $presets) {
            $this->assertGreaterThanOrEqual(2, count($presets), "{$group} needs a real choice, not one option");
        }
        $this->assertGreaterThanOrEqual(10, count(AvatarPresets::all()));
    }

    public function test_every_preset_is_fully_defined(): void
    {
        foreach (AvatarPresets::all() as $key => $preset) {
            foreach (['label', 'group', 'bg', 'skin', 'cloth'] as $field) {
                $this->assertArrayHasKey($field, $preset, "Preset {$key} is missing {$field}");
                $this->assertNotSame('', $preset[$field], "Preset {$key} has an empty {$field}");
            }
        }
    }

    public function test_unknown_presets_are_rejected(): void
    {
        $this->assertTrue(AvatarPresets::has('baby'));
        $this->assertFalse(AvatarPresets::has('nope'));
        $this->assertFalse(AvatarPresets::has(null));
    }

    /** The whole point of the colour is that a person keeps the same one. */
    public function test_initials_colour_is_stable_for_a_name(): void
    {
        $first = AvatarPresets::initialsColor('Aarav Shah');
        $second = AvatarPresets::initialsColor('  aarav shah  ');

        $this->assertSame($first, $second);
    }

    public function test_initials_handle_one_and_many_word_names(): void
    {
        $this->assertSame('AS', AvatarPresets::initials('Aarav Shah'));
        $this->assertSame('P', AvatarPresets::initials('Priya'));
        // First and last, not first and second — matches how names are read aloud.
        $this->assertSame('RN', AvatarPresets::initials('Ravi Kumar Nair'));
        $this->assertSame('?', AvatarPresets::initials('   '));
    }
}
