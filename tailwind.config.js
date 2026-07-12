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
            colors: {
                novix: {
                    cream: '#FBF6EA',
                    card: '#FFFFFF',
                    green: {
                        DEFAULT: '#1E5A45',
                        dark: '#123D2F',
                        light: '#2E7A5D',
                    },
                    mint: '#DCEFE3',
                    pink: {
                        DEFAULT: '#F4A9A0',
                        dark: '#E8615A',
                    },
                    yellow: '#F5C879',
                    blue: '#8FB8E0',
                    ink: '#1F2A24',
                    muted: '#6B7A72',
                },
            },
            boxShadow: {
                novix: '0 12px 32px -12px rgba(30, 90, 69, 0.18)',
                'novix-sm': '0 4px 14px -4px rgba(30, 90, 69, 0.12)',
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
            },
            animation: {
                'novix-shake': 'novix-shake 0.4s ease-in-out',
            },
        },
    },

    plugins: [forms],
};
