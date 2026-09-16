/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.vue",
    ],
    theme: {
        extend: {
            colors: {
                primary: '#0F766E',
            },
            fontFamily: {
                sans: ['Be Vietnam Pro', 'sans-serif'],
            }
        },
    },
    plugins: [],
}
