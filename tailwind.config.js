import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
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
                brand: {
                    DEFAULT: '#b00000',
                    light: '#cf1f1f',
                    dark: '#8a0000',
                    50: '#fdecec',
                    950: '#2a0808',
                },
                // Steel gray, lifted from the silver wing of the Alfajar mark —
                // used anywhere the site previously reached for generic Tailwind gray.
                steel: {
                    50: '#f4f5f6',
                    100: '#e8eaec',
                    200: '#d3d7db',
                    300: '#b0b6bd',
                    400: '#8c949e',
                    500: '#6b7280',
                    600: '#565d68',
                    700: '#3a3f47',
                    800: '#292d33',
                    900: '#1c1f24',
                },
            },
        },
    },

    plugins: [forms],
};
