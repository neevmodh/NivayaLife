import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import plugin from 'tailwindcss/plugin';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            spacing: {
                // 18px — the icon size that sits between Tailwind's 4 (16px) and
                // 5 (20px), used for the small inline icons in buttons.
                4.5: '1.125rem',
            },
            colors: {
                nivayalife: {
                    cream: '#FBF6EA',
                    card: '#FFFFFF',
                    // Brand palette sampled from the Nivaya Life logo
                    // (deep forest green #14503F ground, gold #C9941A mark) —
                    // every button, badge, and header pulls from these
                    // tokens, so a change here cascades across the whole app.
                    green: {
                        DEFAULT: '#14503F',
                        dark: '#0C3A2D',
                        light: '#2A6B55',
                    },
                    gold: {
                        DEFAULT: '#C9941A',
                        light: '#E1C37E',
                    },
                    mint: '#DCEFE3',
                    pink: {
                        DEFAULT: '#F4A9A0',
                        dark: '#E8615A',
                    },
                    yellow: '#F5C879',
                    blue: '#8FB8E0',
                    ink: '#1F2A24',
                    // Dark-mode surface. Deliberately a neutral charcoal, not
                    // a darker green — dark green backgrounds made the whole
                    // dark theme feel murky. Ink stays as the light-mode text
                    // color; night is only ever a background.
                    night: {
                        DEFAULT: '#12161B',
                        card: '#1A1F26',
                    },
                    // Darkened from #6B7A72 (4.19:1 on cream, below WCAG AA's
                    // 4.5:1 for normal text) — this color is used everywhere
                    // for secondary text, so the fix belongs here once rather
                    // than at each call site.
                    muted: '#5A6960',
                },
            },
            boxShadow: {
                nivayalife: '0 12px 32px -12px rgba(20, 80, 63, 0.22)',
                'nivayalife-sm': '0 4px 14px -4px rgba(20, 80, 63, 0.14)',
            },
            borderRadius: {
                nivayalife: '1.5rem',
            },
            keyframes: {
                'nivayalife-shake': {
                    '0%, 100%': { transform: 'translateX(0)' },
                    '20%, 60%': { transform: 'translateX(-6px)' },
                    '40%, 80%': { transform: 'translateX(6px)' },
                },
                'nivayalife-fade-up': {
                    '0%': { opacity: '0', transform: 'translateY(10px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
            },
            animation: {
                'nivayalife-shake': 'nivayalife-shake 0.4s ease-in-out',
                'nivayalife-fade-up': 'nivayalife-fade-up 0.5s ease-out both',
            },
        },
    },

    plugins: [
        forms,
        // `standalone:` targets the installed PWA specifically (Add to Home
        // Screen / desktop install), letting the app skin differ from plain
        // browser visits — e.g. keeping the bottom tab bar at every width.
        plugin(({ addVariant }) => {
            addVariant('standalone', '@media (display-mode: standalone)');
        }),
    ],
};
