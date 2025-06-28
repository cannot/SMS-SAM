// Service Worker compatible with Vite
const CACHE_NAME = 'smart-notification-v2';
const STATIC_CACHE = 'static-v2';

// Files to cache (updated for Vite)
const urlsToCache = [
    '/',
    '/favicon.ico'
];

// Install - cache essential files
self.addEventListener('install', function(event) {
    console.log('SW: Installing...');
    
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then(function(cache) {
                console.log('SW: Caching static files');
                return cache.addAll(urlsToCache);
            })
            .then(function() {
                // Force activation
                return self.skipWaiting();
            })
            .catch(function(error) {
                console.error('SW: Install failed', error);
            })
    );
});

// Activate - cleanup old caches
self.addEventListener('activate', function(event) {
    console.log('SW: Activating...');
    
    event.waitUntil(
        caches.keys()
            .then(function(cacheNames) {
                return Promise.all(
                    cacheNames.map(function(cacheName) {
                        if (cacheName !== CACHE_NAME && cacheName !== STATIC_CACHE) {
                            console.log('SW: Deleting old cache:', cacheName);
                            return caches.delete(cacheName);
                        }
                    })
                );
            })
            .then(function() {
                return self.clients.claim();
            })
    );
});

// Fetch - handle requests
self.addEventListener('fetch', function(event) {
    const requestURL = new URL(event.request.url);
    
    // Skip non-GET requests
    if (event.request.method !== 'GET') {
        return;
    }
    
    // Skip Chrome extensions and data URIs
    if (requestURL.protocol !== 'http:' && requestURL.protocol !== 'https:') {
        return;
    }
    
    // Skip API requests
    if (requestURL.pathname.startsWith('/api/')) {
        return;
    }
    
    // Skip Vite HMR
    if (requestURL.pathname.includes('vite') || requestURL.pathname.includes('@vite')) {
        return;
    }
    
    // Handle build files differently
    if (requestURL.pathname.startsWith('/build/')) {
        event.respondWith(
            caches.open(CACHE_NAME)
                .then(function(cache) {
                    return cache.match(event.request)
                        .then(function(response) {
                            if (response) {
                                return response;
                            }
                            
                            return fetch(event.request)
                                .then(function(response) {
                                    if (response && response.status === 200) {
                                        cache.put(event.request, response.clone());
                                    }
                                    return response;
                                });
                        });
                })
        );
        return;
    }
    
    // Handle other requests with network first strategy
    event.respondWith(
        fetch(event.request)
            .then(function(response) {
                // Clone the response
                const responseClone = response.clone();
                
                // Cache successful responses
                if (response.status === 200) {
                    caches.open(CACHE_NAME)
                        .then(function(cache) {
                            cache.put(event.request, responseClone);
                        });
                }
                
                return response;
            })
            .catch(function() {
                // Fallback to cache
                return caches.match(event.request)
                    .then(function(response) {
                        if (response) {
                            return response;
                        }
                        
                        // Return offline page for HTML requests
                        if (event.request.headers.get('accept').includes('text/html')) {
                            return new Response(`
                                <!DOCTYPE html>
                                <html>
                                <head>
                                    <title>Offline</title>
                                    <meta charset="utf-8">
                                    <meta name="viewport" content="width=device-width, initial-scale=1">
                                    <style>
                                        body { 
                                            font-family: system-ui, sans-serif; 
                                            text-align: center; 
                                            padding: 50px; 
                                            background: #f8f9fa;
                                        }
                                        .offline { max-width: 400px; margin: 0 auto; }
                                        .btn { 
                                            background: #007bff; 
                                            color: white; 
                                            padding: 10px 20px; 
                                            border: none; 
                                            border-radius: 5px; 
                                            cursor: pointer; 
                                        }
                                    </style>
                                </head>
                                <body>
                                    <div class="offline">
                                        <h1>📡 You're Offline</h1>
                                        <p>Please check your connection and try again.</p>
                                        <button class="btn" onclick="window.location.reload()">Retry</button>
                                    </div>
                                </body>
                                </html>
                            `, {
                                headers: { 'Content-Type': 'text/html' }
                            });
                        }
                    });
            })
    );
});

// Handle push notifications
self.addEventListener('push', function(event) {
    if (event.data) {
        const data = event.data.json();
        const options = {
            body: data.message,
            icon: '/favicon.ico',
            badge: '/favicon.ico',
            tag: 'notification-' + Date.now()
        };
        
        event.waitUntil(
            self.registration.showNotification(data.title || 'Smart Notification', options)
        );
    }
});

console.log('SW: Loaded successfully');