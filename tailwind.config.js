/** @type {import('tailwindcss').Config} */
module.exports = {
    // CRUCIAL: Aqui informamos ao Tailwind onde procurar as classes que você usou
    content: [
        './resources/views/**/*.blade.php', // Escaneia suas Views Blade
        
        // Caminhos padrão do Laravel
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/js/**/*.js',
        './resources/js/**/*.vue',
    ],
    theme: {
        extend: {},
    },
    plugins: [],
}