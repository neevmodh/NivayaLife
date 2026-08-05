<?php

namespace Tests\Unit;

use App\Support\AvatarPresets;
use PHPUnit\Framework\TestCase;

class AvatarPresetsTest extends TestCase
{
    public function test_there_are_ten_presets(): void
    {
        $this->assertCount(10, AvatarPresets::all());
    }

    public function test_every_preset_is_fully_defined(): void
    {
        foreach (AvatarPresets::all() as $key => $preset) {
            foreach (['label', 'bg', 'skin', 'cloth', 'hair'] as $field) {
                $this->assertArrayHasKey($field, $preset, "Preset {$key} is missing {$field}");
                $this->assertNotSame('', $preset[$field], "Preset {$key} has an empty {$field}");
            }
        }
    }

    public function test_unknown_presets_are_rejected(): void
    {
        $this->assertTrue(AvatarPresets::has('a1'));
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
