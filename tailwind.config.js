import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.{js,ts,jsx,tsx}',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            colors: {
                'textcolor': '#ffffff',
                'backgroundcolor': '#343434',
                'inputcolor': '#242424',
                'inputbtncolor': '#1e1e1e'
            },
            fontFamily: {
                sans: ['Nunito', ...defaultTheme.fontFamily.sans],

            },
            letterSpacing: {
                heading: '.01em',
            }
        },
    },
    plugins: [],
};
