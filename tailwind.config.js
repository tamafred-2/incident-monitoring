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
                    50: '#f0f6fe',
                    100: '#dceafd',
                    200: '#c2dbfb',
                    300: '#96c3f5',
                    400: '#64a4ee',
                    500: '#3f86e3',
                    600: '#2a78d6',
                    700: '#1e5fb3',
                    800: '#1c4f92',
                    900: '#1c4278',
                    950: '#142c52',
                },
            },
        },
    },

    plugins: [forms],
};
