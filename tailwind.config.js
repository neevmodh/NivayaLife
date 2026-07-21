import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

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
            // "Modern Teal" — a 2026 refresh of the original warm-forest
            // theme. Token names deliberately kept as-is (green/mint/pink/
            // etc.) even though the actual hues moved, rather than renaming
            // every text-novix-green/bg-novix-mint/etc. call site across the
            // whole app — only the palette definition changes here, and it
            // cascades everywhere automatically. Every text-usable value
            // below is checked against WCAG AA (4.5:1) on both cream and
            // white, the app's two real background colors.
            colors: {
                novix: {
                    cream: '#F5FAF9',
                    card: '#FFFFFF',
                    green: {
                        DEFAULT: '#0F6A61',
                        dark: '#0B4F48',
                        light: '#17847A',
                    },
                    mint: '#CFEFEA',
                    pink: {
                        DEFAULT: '#FFB9A3',
                        dark: '#B8452A',
                    },
                    yellow: '#946012',
                    blue: '#3D6F9E',
                    ink: '#12211F',
                    muted: '#5C7876',
                },
            },
            boxShadow: {
                novix: '0 12px 32px -12px rgba(15, 106, 97, 0.20)',
                'novix-sm': '0 4px 14px -4px rgba(15, 106, 97, 0.14)',
            },
            borderRadius: {
                novix: '1.5rem',
            },
            keyframes: {
                'novix-shake': {
                    '0%, 100%': { transform: 'translateX(0)' },
                    '20%, 60%': { transform: 'translateX(-6px)' },
                    '40%, 80%': { transform: 'translateX(6px)' },
                },
                'novix-fade-up': {
                    '0%': { opacity: '0', transform: 'translateY(10px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
            },
            animation: {
                'novix-shake': 'novix-shake 0.4s ease-in-out',
                'novix-fade-up': 'novix-fade-up 0.5s ease-out both',
            },
        },
    },

    plugins: [forms],
};
