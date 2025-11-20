// resources/js/app.js

import './bootstrap'; 

// Lógica para mostrar/esconder senha (Colocada aqui para ser carregada pelo Vite)
window.togglePasswordVisibility = function(inputId, buttonElement) {
    const input = document.getElementById(inputId);
    const iconEye = buttonElement.querySelector('.icon-eye');
    const iconEyeOff = buttonElement.querySelector('.icon-eye-off');

    if (input.type === 'password') {
        input.type = 'text';
        iconEye.classList.add('hidden');
        iconEyeOff.classList.remove('hidden');
    } else {
        input.type = 'password';
        iconEye.classList.remove('hidden');
        iconEyeOff.classList.add('hidden');
    }
};

// Renderiza os ícones (Lucide)
document.addEventListener('DOMContentLoaded', () => {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});