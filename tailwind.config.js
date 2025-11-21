import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],
    safelist: [
        // For dynamic background colors
        {
            pattern: /bg-(red|green|blue|yellow|indigo|purple|pink|gray|amber|emerald|teal|lime|cyan|sky|violet|fuchsia|rose)-(100|200|300|400|500|600|700|800|900)/,
        },
        // For text colors if needed
        {
            pattern: /text-(red|green|blue|yellow|indigo|purple|pink|gray|amber|emerald|teal|lime|cyan|sky|violet|fuchsia|rose)-(500|600|700)/,
        },
        // For border colors
        {
            pattern: /border-(red|green|blue|yellow|indigo|purple|pink|gray|amber|emerald|teal|lime|cyan|sky|violet|fuchsia|rose)-(500|600)/,
        },
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms, typography],
};
