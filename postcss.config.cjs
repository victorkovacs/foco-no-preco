// postcss.config.cjs

module.exports = {
    plugins: [
        // Usamos a função require() para garantir que o Node.js encontre o plugin
        require('tailwindcss'), 
        require('autoprefixer'),
    ],
};