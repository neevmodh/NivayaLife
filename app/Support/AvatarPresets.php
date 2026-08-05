<?php

namespace App\Support;

/**
 * Illustrated avatars spanning the ages and presentations a family actually
 * contains — infants through grandparents — plus the palette used to colour
 * initials when nobody has picked one.
 *
 * Deliberately illustrations rather than photorealistic faces. These sit
 * beside real uploaded photos, including on the emergency card a paramedic
 * may read: a lifelike face that is not the patient's would be actively
 * misleading there, in a way a drawing never is.
 */
class AvatarPresets
{
    /**
     * Drawn on a 48x48 canvas. Each entry supplies the palette plus optional
     * SVG fragments layered in a fixed order — hair behind the head, then
     * features and accessories on top.
     *
     * @return array<string, array{label: string, group: string, bg: string, skin: string, cloth: string, hairBack?: string, hair?: string, extras?: string, headY?: float, headR?: float}>
     */
    public static function all(): array
    {
        return [
            // ---- Children ----
            'baby' => [
                'label' => 'Baby', 'group' => 'Children',
                'bg' => '#FFF4E0', 'skin' => '#F3CDA8', 'cloth' => '#F5C879',
                'headY' => 21, 'headR' => 9.5,
                'hair' => '<path d="M19.5 13.5c1.4-1.8 3.4-2.6 5-2.2" stroke="#8A6234" stroke-width="1.7" stroke-linecap="round" fill="none"/>',
                'extras' => '<circle cx="14.6" cy="24" r="1.7" fill="#EFA9A0" opacity=".55"/><circle cx="33.4" cy="24" r="1.7" fill="#EFA9A0" opacity=".55"/>',
            ],
            'girl' => [
                'label' => 'Girl', 'group' => 'Children',
                'bg' => '#FDE8F0', 'skin' => '#F0C9A0', 'cloth' => '#E8615A',
                'headY' => 21, 'headR' => 9,
                'hairBack' => '<path d="M13 22c0-6.6 4.6-10.5 11-10.5S35 15.4 35 22v7c0 1.2-.9 2-2 2s-2-.8-2-2v-7c0-1.6-2.9-3-7-3s-7 1.4-7 3v7c0 1.2-.9 2-2 2s-2-.8-2-2v-7Z" fill="#4A2C17"/>',
                'hair' => '<path d="M15 20.5c0-5.6 3.9-8.8 9-8.8s9 3.2 9 8.8c0-1.6-3.4-3.2-9-3.2s-9 1.6-9 3.2Z" fill="#4A2C17"/>',
                'extras' => '<circle cx="13.2" cy="19" r="2.1" fill="#F06292"/><circle cx="34.8" cy="19" r="2.1" fill="#F06292"/>',
            ],
            'boy' => [
                'label' => 'Boy', 'group' => 'Children',
                'bg' => '#E3F6EE', 'skin' => '#E0AC7E', 'cloth' => '#2FA98A',
                'headY' => 21, 'headR' => 9,
                'hair' => '<path d="M15 20c0-5.8 4-9 9-9s9 3.2 9 9c0-1.7-3.4-3.3-9-3.3S15 18.3 15 20Z" fill="#3B2412"/><path d="M27 11.6c1.8-1.2 3.6-.6 4.3.9" stroke="#3B2412" stroke-width="1.6" stroke-linecap="round" fill="none"/>',
            ],
            'teen' => [
                'label' => 'Teen', 'group' => 'Children',
                'bg' => '#E8EAF6', 'skin' => '#C98D5E', 'cloth' => '#5C6BC0',
                'hair' => '<path d="M14.6 20.8c0-6.2 4.2-9.6 9.4-9.6s9.4 3.4 9.4 9.6c0-1.4-1.8-2.4-4-2.9-2.6 1.6-6.6 2-9.6.4-3 .6-5.2 1.5-5.2 2.5Z" fill="#241A12"/>',
            ],

            // ---- Adults ----
            'woman_long' => [
                'label' => 'Long hair', 'group' => 'Adults',
                'bg' => '#FCE7E4', 'skin' => '#EBC49B', 'cloth' => '#C2185B',
                'hairBack' => '<path d="M12.5 22c0-7 5-11 11.5-11S35.5 15 35.5 22v11c0 1.3-1 2.2-2.2 2.2s-2.2-.9-2.2-2.2V22c0-1.8-3.2-3.3-7.1-3.3s-7.1 1.5-7.1 3.3v11c0 1.3-1 2.2-2.2 2.2s-2.2-.9-2.2-2.2V22Z" fill="#2E1B10"/>',
                'hair' => '<path d="M14.8 20.4c0-5.8 4-9.2 9.2-9.2s9.2 3.4 9.2 9.2c0-1.7-3.5-3.3-9.2-3.3s-9.2 1.6-9.2 3.3Z" fill="#2E1B10"/>',
            ],
            'woman_bun' => [
                'label' => 'Bun', 'group' => 'Adults',
                'bg' => '#FFF1D6', 'skin' => '#D9A273', 'cloth' => '#C9941A',
                'hair' => '<circle cx="24" cy="7.6" r="3.6" fill="#2C2016"/><path d="M15 20.2c0-5.9 4-9.2 9-9.2s9 3.3 9 9.2c0-1.7-3.5-3.3-9-3.3s-9 1.6-9 3.3Z" fill="#2C2016"/>',
                'extras' => '<circle cx="24" cy="16.6" r="1" fill="#C2185B"/>',
            ],
            'woman_short' => [
                'label' => 'Short bob', 'group' => 'Adults',
                'bg' => '#E0F2F1', 'skin' => '#8D5A3B', 'cloth' => '#00897B',
                'hairBack' => '<path d="M13.6 22c0-6.8 4.8-10.6 10.4-10.6S34.4 15.2 34.4 22v4.6c0 1.1-.9 1.9-1.9 1.9s-1.9-.8-1.9-1.9V22c0-1.7-2.9-3.2-6.6-3.2S17.4 20.3 17.4 22v4.6c0 1.1-.9 1.9-1.9 1.9s-1.9-.8-1.9-1.9V22Z" fill="#181008"/>',
                'hair' => '<path d="M15 20.4c0-5.8 4-9.2 9-9.2s9 3.4 9 9.2c0-1.7-3.5-3.3-9-3.3s-9 1.6-9 3.3Z" fill="#181008"/>',
            ],
            'man_short' => [
                'label' => 'Short hair', 'group' => 'Adults',
                'bg' => '#DCEFE3', 'skin' => '#E8B98C', 'cloth' => '#14503F',
                'hair' => '<path d="M15 20c0-6 4-9.2 9-9.2s9 3.2 9 9.2c0-1.7-3.5-3.2-9-3.2s-9 1.5-9 3.2Z" fill="#2C2016"/>',
            ],
            'man_beard' => [
                'label' => 'Beard', 'group' => 'Adults',
                'bg' => '#E8F0E4', 'skin' => '#C98D5E', 'cloth' => '#2A6B55',
                'hair' => '<path d="M15 20c0-6 4-9.2 9-9.2s9 3.2 9 9.2c0-1.7-3.5-3.2-9-3.2s-9 1.5-9 3.2Z" fill="#33241A"/>',
                'extras' => '<path d="M15.6 23.4c0 6.6 3.8 10 8.4 10s8.4-3.4 8.4-10c0 3.3-3.8 5-8.4 5s-8.4-1.7-8.4-5Z" fill="#33241A"/>',
            ],
            'man_curly' => [
                'label' => 'Curly', 'group' => 'Adults',
                'bg' => '#E3EEF9', 'skin' => '#7A4A2E', 'cloth' => '#1565C0',
                'hair' => '<circle cx="16.6" cy="15.4" r="4.4" fill="#140C08"/><circle cx="24" cy="12.4" r="4.9" fill="#140C08"/><circle cx="31.4" cy="15.4" r="4.4" fill="#140C08"/><circle cx="20" cy="12.8" r="4" fill="#140C08"/><circle cx="28" cy="12.8" r="4" fill="#140C08"/>',
            ],
            'glasses' => [
                'label' => 'Glasses', 'group' => 'Adults',
                'bg' => '#EDE7F6', 'skin' => '#EBC49B', 'cloth' => '#5E35B1',
                'hair' => '<path d="M15 20c0-6 4-9.2 9-9.2s9 3.2 9 9.2c0-1.7-3.5-3.2-9-3.2s-9 1.5-9 3.2Z" fill="#3E3E3E"/>',
                'extras' => '<g stroke="#2C2016" stroke-width="1.5" fill="none"><circle cx="19.9" cy="22.3" r="3.5"/><circle cx="28.1" cy="22.3" r="3.5"/><path d="M23.4 22.3h1.2M16.4 21.4l-2.2-.7M31.6 21.4l2.2-.7"/></g>',
            ],
            'headscarf' => [
                'label' => 'Headscarf', 'group' => 'Adults',
                'bg' => '#FCE4EC', 'skin' => '#D9A273', 'cloth' => '#AD1457',
                'hairBack' => '<path d="M12.6 23c0-7.4 5-11.6 11.4-11.6S35.4 15.6 35.4 23v8.4c0 1.4-1.1 2.4-2.4 2.4H14.9c-1.3 0-2.3-1-2.3-2.4V23Z" fill="#AD1457"/>',
                'extras' => '<path d="M15.4 25.4h17.2v3.4c0 1.3-1.1 2.3-2.4 2.3H17.8c-1.3 0-2.4-1-2.4-2.3v-3.4Z" fill="#880E4F"/>',
            ],
            'turban' => [
                'label' => 'Turban', 'group' => 'Adults',
                'bg' => '#E8F5E9', 'skin' => '#B87B4E', 'cloth' => '#14503F',
                'hairBack' => '<path d="M13.4 21.4c0-7.2 4.8-11.2 10.6-11.2s10.6 4 10.6 11.2c0 1.4-.9 2.3-2.1 2.3H15.5c-1.2 0-2.1-.9-2.1-2.3Z" fill="#1E5AA8"/>',
                'extras' => '<path d="M15 17.6c3-2.6 5.6-3.7 9-3.7s6 1.1 9 3.7" stroke="#164A8A" stroke-width="1.6" fill="none" stroke-linecap="round"/><path d="M14.2 20.8c3.4-1.8 6.2-2.5 9.8-2.5s6.4.7 9.8 2.5" stroke="#164A8A" stroke-width="1.4" fill="none" stroke-linecap="round"/>',
            ],
            'bindi' => [
                'label' => 'Bindi', 'group' => 'Adults',
                'bg' => '#FFF3E0', 'skin' => '#D9A273', 'cloth' => '#E65100',
                'hairBack' => '<path d="M12.8 22c0-7 5-11 11.2-11S35.2 15 35.2 22v10c0 1.2-1 2.1-2.1 2.1S31 33.2 31 32V22c0-1.7-3.1-3.2-7-3.2S17 20.3 17 22v10c0 1.2-1 2.1-2.1 2.1S12.8 33.2 12.8 32V22Z" fill="#241009"/>',
                'hair' => '<path d="M15 20.2c0-5.8 4-9.2 9-9.2s9 3.4 9 9.2c0-1.7-3.5-3.3-9-3.3s-9 1.6-9 3.3Z" fill="#241009"/>',
                'extras' => '<circle cx="24" cy="17.6" r="1.15" fill="#C2185B"/>',
            ],

            // ---- Older ----
            'silver_woman' => [
                'label' => 'Grandmother', 'group' => 'Older',
                'bg' => '#F3F1EC', 'skin' => '#DDBFA0', 'cloth' => '#7B6D8D',
                'hair' => '<circle cx="24" cy="9.4" r="3.2" fill="#D6DAD8"/><path d="M14.8 20.6c0-5.9 4.1-9.4 9.2-9.4s9.2 3.5 9.2 9.4c0-1.8-3.6-3.4-9.2-3.4s-9.2 1.6-9.2 3.4Z" fill="#D6DAD8"/>',
                'extras' => '<path d="M17.6 25.4c.9.7 2.1.7 3 0M27.4 25.4c.9.7 2.1.7 3 0" stroke="#B89676" stroke-width="1" stroke-linecap="round" fill="none"/>',
            ],
            'silver_man' => [
                'label' => 'Grandfather', 'group' => 'Older',
                'bg' => '#EEF1F0', 'skin' => '#D2A97F', 'cloth' => '#5A6960',
                'hair' => '<path d="M15 20.2c0-6 4-9.2 9-9.2s9 3.2 9 9.2c0-1.8-3.5-3.3-9-3.3s-9 1.5-9 3.3Z" fill="#C9CFCC"/>',
                'extras' => '<path d="M16.4 24c0 6.2 3.6 9.4 7.6 9.4s7.6-3.2 7.6-9.4c0 3-3.6 4.6-7.6 4.6s-7.6-1.6-7.6-4.6Z" fill="#C9CFCC"/><path d="M17.6 19.6c1-.6 2.2-.6 3.2 0M27.2 19.6c1-.6 2.2-.6 3.2 0" stroke="#9AA3A0" stroke-width="1.1" stroke-linecap="round" fill="none"/>',
            ],
        ];
    }

    public static function has(?string $key): bool
    {
        return $key !== null && array_key_exists($key, self::all());
    }

    public static function keys(): array
    {
        return array_keys(self::all());
    }

    /** Presets bucketed by their group, in declaration order, for the picker. */
    public static function grouped(): array
    {
        $grouped = [];
        foreach (self::all() as $key => $preset) {
            $grouped[$preset['group']][$key] = $preset;
        }

        return $grouped;
    }

    /**
     * Colour for an initials avatar, chosen from the name so the same person
     * always keeps the same colour — the point is that a family strip becomes
     * scannable, which only works if the colour is stable.
     *
     * @return array{bg: string, fg: string}
     */
    public static function initialsColor(string $name): array
    {
        $palette = [
            ['bg' => '#DCEFE3', 'fg' => '#14503F'],
            ['bg' => '#FCE7E4', 'fg' => '#B3453D'],
            ['bg' => '#FFF1D6', 'fg' => '#8A6410'],
            ['bg' => '#E3EEF9', 'fg' => '#2C5B8A'],
            ['bg' => '#EDE7F6', 'fg' => '#553F82'],
            ['bg' => '#E3F6EE', 'fg' => '#186B57'],
            ['bg' => '#FDE8F0', 'fg' => '#96375C'],
            ['bg' => '#EAF2E0', 'fg' => '#4A6B24'],
        ];

        return $palette[abs(crc32(mb_strtolower(trim($name)))) % count($palette)];
    }

    /** Up to two initials — "Aarav Shah" becomes AS, "Priya" becomes P. */
    public static function initials(string $name): string
    {
        $words = preg_split('/\s+/', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($words === []) {
            return '?';
        }

        $first = mb_strtoupper(mb_substr($words[0], 0, 1));
        $last = count($words) > 1 ? mb_strtoupper(mb_substr(end($words), 0, 1)) : '';

        return $first.$last;
    }
}
