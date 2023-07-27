import { fontFamily, screens } from 'tailwindcss/defaultTheme'
import colors from 'tailwindcss/colors'

/** @type {import('tailwindcss').Config} */
export const content = [
    './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
    './storage/framework/views/*.php',
    './resources/views/**/*.blade.php',
    './resources/js/**/*.vue'
]

export const plugins = [
    require('@tailwindcss/typography'),
    require('daisyui')
]

export const theme = {
    extend: {
        fontFamily: {
            sans: ['Nunito', ...fontFamily.sans]
        }
    },

    colors: { ...colors, gray: colors.neutral },

    screens: {
        'xs': '475px',
        ...screens,
    }
}

export const daisyui = {
    themes: [
        {
            dark: {
                'color-scheme': 'dark',
                primary: '#F2F2F2',
                secondary: '#C5C6C6',
                accent: '#A9A9A9',
                neutral: '#FFFFFF',
                'base-100': '#111',
                'base-200': '#222',
                'base-300': '#333',
                'base-content': '#ffffff',
                info: '#0000ff',
                success: '#008000',
                warning: '#ffc300',
                error: '#f45050',
                '--rounded-box': '0.3rem',
                '--rounded-btn': '0.15rem',
                '--rounded-badge': '0.15rem',
                '--animation-btn': '0',
                '--animation-input': '0',
                '--btn-focus-scale': '1',
                '--tab-radius': '0.3rem'
            }
        }
    ]
}
