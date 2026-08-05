<?php

namespace App\Support;

/**
 * Ten illustrated avatars, plus the palette used to colour initials when
 * nobody has picked one.
 *
 * Faces are drawn deliberately simple and abstract — a warm skin tone, hair,
 * clothing — rather than attempting realistic likenesses. A family sheet full
 * of near-identical grey silhouettes reads as broken; this makes each person
 * instantly distinguishable at avatar size without pretending to be a photo.
 */
class AvatarPresets
{
    /**
     * Each preset: a background, a skin tone, a clothing colour, and an SVG
     * fragment for hair/features drawn on a 48x48 canvas.
     *
     * @return array<string, array{label: string, bg: string, skin: string, cloth: string, hair: string}>
     */
    public static function all(): array
    {
        return [
            'a1' => [
                'label' => 'Short hair',
                'bg' => '#DCEFE3', 'skin' => '#E8B98C', 'cloth' => '#14503F',
                'hair' => '<path d="M15 20c0-6 4-9 9-9s9 3 9 9c0-1.5-3.5-3-9-3s-9 1.5-9 3Z" fill="#2C2016"/>',
            ],
            'a2' => [
                'label' => 'Long hair',
                'bg' => '#FCE7E4', 'skin' => '#F0C9A0', 'cloth' => '#E8615A',
                'hair' => '<path d="M14 21c0-6.5 4.5-10 10-10s10 3.5 10 10v10c0 1-1 1.5-2 1.5V21c0-1.5-3-3-8-3s-8 1.5-8 3v11.5c-1 0-2-.5-2-1.5V21Z" fill="#3A2418"/>',
            ],
            'a3' => [
                'label' => 'Bun',
                'bg' => '#FFF1D6', 'skin' => '#D9A273', 'cloth' => '#C9941A',
                'hair' => '<circle cx="24" cy="8" r="3.4" fill="#2C2016"/><path d="M15 20c0-6 4-9 9-9s9 3 9 9c0-1.5-3.5-3-9-3s-9 1.5-9 3Z" fill="#2C2016"/>',
            ],
            'a4' => [
                'label' => 'Curly',
                'bg' => '#E3EEF9', 'skin' => '#8D5A3B', 'cloth' => '#8FB8E0',
                'hair' => '<circle cx="17" cy="15" r="4.2" fill="#1E1410"/><circle cx="24" cy="12.5" r="4.6" fill="#1E1410"/><circle cx="31" cy="15" r="4.2" fill="#1E1410"/>',
            ],
            'a5' => [
                'label' => 'Beard',
                'bg' => '#DCEFE3', 'skin' => '#C98D5E', 'cloth' => '#2A6B55',
                'hair' => '<path d="M15 20c0-6 4-9 9-9s9 3 9 9c0-1.5-3.5-3-9-3s-9 1.5-9 3Z" fill="#33241A"/><path d="M16.5 24c0 6 3.5 9 7.5 9s7.5-3 7.5-9c0 3-3.5 4.5-7.5 4.5s-7.5-1.5-7.5-4.5Z" fill="#33241A"/>',
            ],
            'a6' => [
                'label' => 'Glasses',
                'bg' => '#EDE7F6', 'skin' => '#EBC49B', 'cloth' => '#6B5B95',
                'hair' => '<path d="M15 20c0-6 4-9 9-9s9 3 9 9c0-1.5-3.5-3-9-3s-9 1.5-9 3Z" fill="#4A4A4A"/><g stroke="#2C2016" stroke-width="1.4" fill="none"><circle cx="20" cy="22.5" r="3.2"/><circle cx="28" cy="22.5" r="3.2"/><path d="M23.2 22.5h1.6"/></g>',
            ],
            'a7' => [
                'label' => 'Headscarf',
                'bg' => '#FCE7E4', 'skin' => '#D9A273', 'cloth' => '#B0446A',
                'hair' => '<path d="M13.5 22c0-7 4.7-11 10.5-11s10.5 4 10.5 11c0 2-1 3-2.2 3H15.7c-1.2 0-2.2-1-2.2-3Z" fill="#B0446A"/><path d="M16 24h16v3c0 1-1 2-2 2H18c-1 0-2-1-2-2v-3Z" fill="#8E3354"/>',
            ],
            'a8' => [
                'label' => 'Turban',
                'bg' => '#FFF1D6', 'skin' => '#B87B4E', 'cloth' => '#14503F',
                'hair' => '<path d="M13.8 21c0-7 4.6-11 10.2-11s10.2 4 10.2 11c0 1.4-.8 2.2-2 2.2H15.8c-1.2 0-2-.8-2-2.2Z" fill="#1E5AA8"/><path d="M15 17.5c3-2.5 5.5-3.5 9-3.5s6 1 9 3.5" stroke="#164A8A" stroke-width="1.5" fill="none"/>',
            ],
            'a9' => [
                'label' => 'Child',
                'bg' => '#E3F6EE', 'skin' => '#F0C9A0', 'cloth' => '#2FA98A',
                'hair' => '<path d="M16 20c0-5.5 3.5-8.5 8-8.5s8 3 8 8.5c0-1.5-3-3-8-3s-8 1.5-8 3Z" fill="#6B4423"/><path d="M24 11.5c1.5-2 4-2 4.5 0" stroke="#6B4423" stroke-width="1.6" fill="none" stroke-linecap="round"/>',
            ],
            'a10' => [
                'label' => 'Silver',
                'bg' => '#EEF1F0', 'skin' => '#D9B08C', 'cloth' => '#5A6960',
                'hair' => '<path d="M15 20c0-6 4-9 9-9s9 3 9 9c0-1.5-3.5-3-9-3s-9 1.5-9 3Z" fill="#C9CFCC"/><path d="M17 24.5c0 5 3 7.5 7 7.5s7-2.5 7-7.5c0 2.5-3 3.5-7 3.5s-7-1-7-3.5Z" fill="#C9CFCC"/>',
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
