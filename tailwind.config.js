/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './resources/views/**/*.blade.php', 
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/js/**/*.js',
        './resources/js/**/*.vue',
    ],
    theme: {
        extend: {
            colors: {
                // CORES CUSTOMIZADAS DEFINIDAS CORRETAMENTE
                'primary-dark': '#002D5A',
                'primary-darker': '#004182', 
                'highlight': '#D00000',
                'highlight-dark': '#B00000', 
            },
        },
    },
    plugins: [],
}