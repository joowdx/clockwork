const defaultTheme = require('tailwindcss/defaultTheme');
const colors = require('tailwindcss/colors');

delete colors.lightBlue;
delete colors.warmGray;
delete colors.trueGray;
delete colors.coolGray;
delete colors.blueGray;

/** @type {import('tailwindcss').Config} */
module.exports = {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Nunito', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                ...colors,
                gray: colors.neutral,
            }
        },
    },

    plugins: [
        require("@tailwindcss/typography"),
        require("daisyui"),
    ],

    daisyui: {
        themes: [
            {
                light: {
                    "color-scheme": "light",
                    "primary": "#0D0D0D",
                    "secondary": "#1A1919",
                    "accent": "#262626",
                    "neutral": "#000000",
                    "base-100": "#ffffff",
                    "base-200": "#E2E2E2",
                    "base-300": "#D5D5D5",
                    "base-content": "#000000",
                    info: "#0000ff",
                    success: "#008000",
                    warning: "#ffff00",
                    error: "#ff0000",
                    "--rounded-box": "0.25rem",
                    "--rounded-btn": "0.125rem",
                    "--rounded-badge": "0.125rem",
                    "--animation-btn": "0",
                    "--animation-input": "0",
                    "--btn-focus-scale": "1",
                    "--tab-radius": "0.25rem",
                },
                dark: {
                    "color-scheme": "dark",
                    "primary": "#343232",
                    "secondary": "#343232",
                    "accent": "#343232",
                    "neutral": "#272626",
                    "base-100": "#000000",
                    "base-200": "#1D1D1D",
                    "base-300": "#292929",
                    "base-content": "#ffffff",
                    info: "#0000ff",
                    success: "#008000",
                    warning: "#ffff00",
                    error: "#ff0000",
                    "--rounded-box": "0.25rem",
                    "--rounded-btn": "0.125rem",
                    "--rounded-badge": "0.125rem",
                    "--animation-btn": "0",
                    "--animation-input": "0",
                    "--btn-focus-scale": "1",
                    "--tab-radius": "0.25rem",
                }
            }
        ]

    },
};
