// resources/js/app.js

import './bootstrap';
import * as Sentry from "@sentry/browser";

// --- 1. MONITORAMENTO SENTRY (FRONTEND) ---
// Pega a chave de segurança injetada no HTML pelo Laravel
const sentryDsn = document.querySelector('meta[name="sentry-dsn"]')?.getAttribute('content');

// Só ativa se a chave existir (Production)
if (sentryDsn) {
    Sentry.init({
        dsn: sentryDsn,
        integrations: [
            Sentry.browserTracingIntegration(),
            Sentry.replayIntegration(),
        ],
        // Monitoramento de Performance (1.0 = 100% das cargas de página)
        tracesSampleRate: 1.0,
        // Gravação de Sessão (Vídeo do erro - Útil para debugar)
        replaysSessionSampleRate: 0.1, // Grava 10% das sessões normais
        replaysOnErrorSampleRate: 1.0, // Grava 100% das sessões que derem erro
    });
    console.log('✅ [Sentry] Monitoramento Frontend Ativo');
} else {
    console.log('⚠️ [Sentry] Monitoramento Inativo (DSN não encontrado)');
}

// --- 2. FUNÇÕES DO SISTEMA ---

// Lógica para mostrar/esconder senha
window.togglePasswordVisibility = function (inputId, buttonElement) {
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