// Smart Notification System - Service Worker (Fixed)
// Simple and reliable version without complex message handling

const CACHE_NAME = 'smart-notification-v1';
const CACHE_VERSION = '1.0.0';

// Files to cache - only include files that actually exist
const urlsToCache = [
    '/',
    '/offline.html'
    // Note: Don't include files that don't exist like /favicon.ico if it's not there
];

// Install event - cache initial resources
self.addEventListener('install', event => {
    console.log('🔧 SW: Installing...');
    
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('📦 SW: Caching resources');
                // Only cache URLs that actually exist
                return Promise.allSettled(
                    urlsToCache.map(url => 
                        cache.add(url).catch(error => {
                            console.warn(`Failed to cache ${url}:`, error);
                            return Promise.resolve(); // Don't fail the install
                        })
                    )
                );
            })
            .then(() => {
                console.log('✅ SW: Installation complete');
                return self.skipWaiting();
            })
            .catch(error => {
                console.error('❌ SW: Installation failed', error);
                return Promise.resolve(); // Don't fail completely
            })
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
    console.log('🚀 SW: Activating...');
    
    event.waitUntil(
        Promise.all([
            // Clean up old caches
            caches.keys().then(cacheNames => {
                return Promise.all(
                    cacheNames.map(cacheName => {
                        if (cacheName !== CACHE_NAME) {
                            console.log('🗑️ SW: Deleting old cache', cacheName);
                            return caches.delete(cacheName);
                        }
                    })
                );
            }),
            // Take control of all pages
            self.clients.claim()
        ]).then(() => {
            console.log('✅ SW: Activation complete');
            
            // Notify all clients that SW is ready - FIXED VERSION
            return self.clients.matchAll().then(clients => {
                clients.forEach(client => {
                    try {
                        client.postMessage({
                            type: 'SW_ACTIVATED',
                            version: CACHE_VERSION
                        });
                    } catch (error) {
                        console.warn('Failed to send message to client:', error);
                    }
                });
            });
        })
    );
});

// Fetch event - handle all network requests
self.addEventListener('fetch', event => {
    const request = event.request;
    const url = new URL(request.url);
    
    // Skip cross-origin requests
    if (url.origin !== self.location.origin) {
        return;
    }
    
    // Skip non-GET requests
    if (request.method !== 'GET') {
        return;
    }
    
    // Simple network-first strategy with offline fallback
    event.respondWith(
        fetch(request)
            .then(response => {
                // If successful, clone and cache the response
                if (response.ok) {
                    const responseClone = response.clone();
                    caches.open(CACHE_NAME).then(cache => {
                        cache.put(request, responseClone).catch(error => {
                            console.warn('Failed to cache response:', error);
                        });
                    });
                }
                return response;
            })
            .catch(error => {
                console.log('⚠️ SW: Network failed for', request.url);
                
                // Try cache first
                return caches.match(request).then(cachedResponse => {
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    
                    // For navigation requests, show offline page
                    if (request.mode === 'navigate') {
                        return caches.match('/offline.html').then(offlineResponse => {
                            return offlineResponse || new Response('Offline - Service Unavailable', {
                                status: 503,
                                statusText: 'Service Unavailable'
                            });
                        });
                    }
                    
                    // For other requests, throw the original error
                    throw error;
                });
            })
    );
});

// Handle messages from main thread - FIXED VERSION
self.addEventListener('message', event => {
    console.log('📨 SW: Received message', event.data);
    
    if (!event.data || !event.data.type) {
        return;
    }
    
    try {
        switch (event.data.type) {
            case 'SKIP_WAITING':
                self.skipWaiting();
                break;
                
            case 'GET_VERSION':
                // Fixed: Check if event.ports exists and has elements
                if (event.ports && event.ports.length > 0) {
                    event.ports[0].postMessage({
                        type: 'VERSION',
                        version: CACHE_VERSION
                    });
                } else {
                    // Alternative: Send back via clients.matchAll
                    self.clients.matchAll().then(clients => {
                        clients.forEach(client => {
                            client.postMessage({
                                type: 'VERSION_RESPONSE',
                                version: CACHE_VERSION
                            });
                        });
                    });
                }
                break;
                
            case 'CLEAR_CACHE':
                caches.delete(CACHE_NAME).then(() => {
                    // Fixed: Check if event.ports exists
                    if (event.ports && event.ports.length > 0) {
                        event.ports[0].postMessage({
                            type: 'CACHE_CLEARED'
                        });
                    } else {
                        // Alternative method
                        self.clients.matchAll().then(clients => {
                            clients.forEach(client => {
                                client.postMessage({
                                    type: 'CACHE_CLEARED'
                                });
                            });
                        });
                    }
                });
                break;
                
            default:
                console.log('🤷 SW: Unknown message type', event.data.type);
        }
    } catch (error) {
        console.error('❌ SW: Error handling message:', error);
    }
});

// Error handling
self.addEventListener('error', event => {
    console.error('❌ SW: Global error', event.error || event);
    
    // Send error to main thread (simplified)
    self.clients.matchAll().then(clients => {
        clients.forEach(client => {
            try {
                client.postMessage({
                    type: 'ERROR',
                    error: {
                        message: (event.error && event.error.message) || 'Unknown error',
                        stack: (event.error && event.error.stack) || ''
                    }
                });
            } catch (postError) {
                console.error('Failed to send error message:', postError);
            }
        });
    }).catch(clientError => {
        console.error('Failed to get clients:', clientError);
    });
});

// Unhandled promise rejection
self.addEventListener('unhandledrejection', event => {
    console.error('❌ SW: Unhandled promise rejection', event.reason || event);
    
    // Send error to main thread (simplified)
    self.clients.matchAll().then(clients => {
        clients.forEach(client => {
            try {
                client.postMessage({
                    type: 'ERROR',
                    error: {
                        message: (event.reason && event.reason.message) || 'Unhandled promise rejection',
                        stack: (event.reason && event.reason.stack) || ''
                    }
                });
            } catch (postError) {
                console.error('Failed to send rejection message:', postError);
            }
        });
    }).catch(clientError => {
        console.error('Failed to get clients:', clientError);
    });
});

console.log('🎯 Service Worker: Script loaded successfully');
console.log('📋 Cache Name:', CACHE_NAME);
console.log('🔢 Version:', CACHE_VERSION);