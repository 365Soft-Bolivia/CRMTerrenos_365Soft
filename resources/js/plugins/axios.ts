import axios from 'axios';

// Obtener el token CSRF del meta tag
const token = document.head.querySelector('meta[name="csrf-token"]');

if (token) {
    // Configurar axios para que envíe el token CSRF en todas las solicitudes
    axios.defaults.headers.common['X-CSRF-TOKEN'] = (token as HTMLMetaElement).content;
} else {
    console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
}

// Configurar axios para enviar cookies (important for Laravel authentication)
axios.defaults.withCredentials = true;

// Configurar base URL para API
axios.defaults.baseURL = window.location.origin;

export default axios;