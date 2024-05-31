import forms from '@tailwindcss/forms'
import typography from '@tailwindcss/typography'
import colors from 'tailwindcss/colors'

/** @type {import('tailwindcss').Config} */
export const content =[
    './app/Filament/**/*.php',
    './resources/views/**/*.blade.php',
]

export const theme = {
    extend: {
        colors: {
            gray: colors.neutral
        }
    }
}

export const darkMode = 'class'

export const plugins = [
    forms,
    typography
]
