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
                'inputcolor': '#181818',
                'inputbtncolor': '#1e1e1e'
            },
            fontFamily: {
                sans: ['Nunito', ...defaultTheme.fontFamily.sans],

            },
            letterSpacing: {
                heading: '.01em',
            },
            animation: {
                'gradient-x': 'gradient-x 10s linear infinite',
                'gradient-x-slow': 'gradient-x 20s linear infinite'
              },
              keyframes: {
                'gradient-x': {
                  '0%': {
                    'background-size': '200% 200%',
                    'background-position': '200% center'
                  },
                  '50%': {
                    'background-size': '200% 200%',
                    'background-position': '100% center'
                  },
                  '100%': {
                    'background-size': '200% 200%',
                    'background-position': '00% center'
                  },
                },
              },
        },
    },
    plugins: [],
};
