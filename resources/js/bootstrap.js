import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.headers.common['Accept'] = 'application/json';
window.axios.defaults.withCredentials = true;

// Add CSRF token to all requests
const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
}

// Add request interceptor for CORS
window.axios.interceptors.request.use(
    config => {
        // Ensure proper content type for API requests
        if (config.url && config.url.includes('/api/')) {
            config.headers['Content-Type'] = 'application/json';
        }
        return config;
    },
    error => {
        return Promise.reject(error);
    }
);

// Add response interceptor for error handling
window.axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response?.status === 419) {
            // CSRF token mismatch
            window.location.reload();
        }
        return Promise.reject(error);
    }
);

// Enhanced fetch function with proper CORS handling
window.fetchWithCORS = async (url, options = {}) => {
    const defaultOptions = {
        mode: 'cors',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...options.headers
        }
    };

    // Add CSRF token if available
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) {
        defaultOptions.headers['X-CSRF-TOKEN'] = csrfToken;
    }

    const finalOptions = { ...defaultOptions, ...options };
    
    try {
        const response = await fetch(url, finalOptions);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        return response;
    } catch (error) {
        console.error('Fetch error:', error);
        throw error;
    }
};

// Global error handler for unhandled promise rejections
window.addEventListener('unhandledrejection', event => {
    console.error('Unhandled promise rejection:', event.reason);
    
    // Show user-friendly error message
    if (typeof showToast === 'function') {
        showToast('An unexpected error occurred. Please try again.', 'error');
    }
});