<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class TeamsService
{
    protected $clientId;
    protected $clientSecret;
    protected $tenantId;
    protected $accessToken;

    public function __construct()
    {
        $this->clientId = config('services.teams.client_id');
        $this->clientSecret = config('services.teams.client_secret');
        $this->tenantId = config('services.teams.tenant_id');
    }

    /**
     * Get user by email (fix the return type issue)
     */
    public function getUserByEmail($email)
    {
        try {
            Log::info('Looking up Teams user by email', ['email' => $email]);
            
            $user = \App\Models\User::where('email', $email)->first();
            if (!$user) {
                Log::warning('User not found in database', ['email' => $email]);
                return null;
            }

            // Get Teams user object
            $teamsUser = $this->getTeamsUserObject($email);
            if (!$teamsUser) {
                Log::warning('User not found in Teams directory', ['email' => $email]);
                return null;
            }

            // Return a simple object with the Teams user ID
            return (object) [
                'id' => $teamsUser['id'],
                'email' => $teamsUser['mail'] ?? $teamsUser['userPrincipalName'],
                'displayName' => $teamsUser['displayName'],
                'getId' => function() use ($teamsUser) {
                    return $teamsUser['id'];
                }
            ];

        } catch (\Exception $e) {
            Log::error('getUserByEmail failed', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Send direct message to user ID with adaptive card support
     */
    public function sendDirectMessage($userId, $body, $cardTemplate = null)
    {
        try {
            Log::info('Attempting to send Teams direct message', [
                'user_id' => $userId,
                'has_card' => !empty($cardTemplate)
            ]);

            $token = $this->getAccessToken();
            if (!$token) {
                throw new \Exception('No access token available');
            }

            $httpClient = $this->getHttpClient();
            
            // Create or get existing chat with user
            $chatId = $this->getOrCreateChatWithUser($userId, $token);
            
            if (!$chatId) {
                throw new \Exception('Failed to create chat with user');
            }

            // Prepare message content
            $messageContent = [];
            
            if ($cardTemplate) {
                // Send as adaptive card
                $messageContent = [
                    'body' => [
                        'contentType' => 'html',
                        'content' => $body
                    ],
                    'attachments' => [
                        [
                            'contentType' => 'application/vnd.microsoft.card.adaptive',
                            'content' => $cardTemplate
                        ]
                    ]
                ];
            } else {
                // Send as simple HTML message
                $messageContent = [
                    'body' => [
                        'contentType' => 'html', 
                        'content' => $body
                    ]
                ];
            }

            // Send message
            $response = $httpClient
                ->withToken($token)
                ->post("https://graph.microsoft.com/v1.0/chats/{$chatId}/messages", $messageContent);

            if ($response->successful()) {
                Log::info('Teams direct message sent successfully', [
                    'user_id' => $userId,
                    'chat_id' => $chatId,
                    'has_card' => !empty($cardTemplate)
                ]);

                return [
                    'success' => true,
                    'message_id' => $response->json()['id'] ?? null,
                    'chat_id' => $chatId
                ];
            } else {
                $errorBody = $response->body();
                Log::error('Teams direct message failed', [
                    'user_id' => $userId,
                    'status' => $response->status(),
                    'response' => $errorBody
                ]);

                return [
                    'success' => false,
                    'error' => 'Failed to send Teams message: ' . $errorBody
                ];
            }

        } catch (\Exception $e) {
            Log::error('Teams direct message exception', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get or create chat with user - Fixed version
     */
    private function getOrCreateChatWithUser($userId, $token)
    {
        try {
            Log::info('Getting or creating chat with user', ['user_id' => $userId]);
            
            $httpClient = $this->getHttpClient();
            
            // Get current user's ID (app identity)
            $meResponse = $httpClient
                ->withToken($token)
                ->get("https://graph.microsoft.com/v1.0/me");

            if (!$meResponse->successful()) {
                Log::error('Failed to get app user identity', [
                    'status' => $meResponse->status(),
                    'response' => $meResponse->body()
                ]);
                return null;
            }

            $appUserId = $meResponse->json()['id'];
            Log::info('App user ID obtained', ['app_user_id' => $appUserId]);

            // Create new chat directly (don't try to find existing)
            $createChatData = [
                'chatType' => 'oneOnOne',
                'members' => [
                    [
                        '@odata.type' => '#microsoft.graph.aadUserConversationMember',
                        'userId' => $appUserId,
                        'roles' => ['owner']
                    ],
                    [
                        '@odata.type' => '#microsoft.graph.aadUserConversationMember',
                        'userId' => $userId,
                        'roles' => ['owner']
                    ]
                ]
            ];

            Log::info('Creating new chat', ['data' => $createChatData]);

            $createResponse = $httpClient
                ->withToken($token)
                ->post("https://graph.microsoft.com/v1.0/chats", $createChatData);

            if ($createResponse->successful()) {
                $chatId = $createResponse->json()['id'];
                Log::info('Chat created successfully', ['chat_id' => $chatId]);
                return $chatId;
            } else {
                $errorBody = $createResponse->body();
                Log::error('Failed to create chat', [
                    'status' => $createResponse->status(),
                    'response' => $errorBody
                ]);
                return null;
            }

        } catch (\Exception $e) {
            Log::error('Failed to get/create chat with user', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Create adaptive card for Teams
     */
    public function createAdaptiveCard($subject, $body)
    {
        // Create a basic adaptive card template
        $card = [
            'type' => 'AdaptiveCard',
            'version' => '1.3',
            'body' => [
                [
                    'type' => 'TextBlock',
                    'text' => $subject,
                    'weight' => 'Bolder',
                    'size' => 'Medium',
                    'color' => 'Accent'
                ],
                [
                    'type' => 'TextBlock',
                    'text' => strip_tags($body), // Remove HTML tags for adaptive card
                    'wrap' => true,
                    'spacing' => 'Medium'
                ]
            ]
        ];

        // Add timestamp
        $card['body'][] = [
            'type' => 'TextBlock',
            'text' => 'Sent: ' . now()->format('Y-m-d H:i:s'),
            'size' => 'Small',
            'color' => 'Dark',
            'spacing' => 'Medium'
        ];

        return $card;
    }

    /**
     * Create priority-based adaptive card
     */
    public function createPriorityAdaptiveCard($subject, $body, $priority = 'normal')
    {
        // Color scheme based on priority
        $colors = [
            'low' => 'Good',
            'normal' => 'Default', 
            'medium' => 'Warning',
            'high' => 'Attention',
            'urgent' => 'Accent'
        ];

        $color = $colors[$priority] ?? 'Default';
        
        // Priority icons
        $icons = [
            'low' => '🔵',
            'normal' => '⚪',
            'medium' => '🟡', 
            'high' => '🟠',
            'urgent' => '🔴'
        ];

        $icon = $icons[$priority] ?? '⚪';

        $card = [
            'type' => 'AdaptiveCard',
            'version' => '1.3',
            'body' => [
                [
                    'type' => 'Container',
                    'style' => $priority === 'urgent' ? 'attention' : 'default',
                    'items' => [
                        [
                            'type' => 'ColumnSet',
                            'columns' => [
                                [
                                    'type' => 'Column',
                                    'width' => 'auto',
                                    'items' => [
                                        [
                                            'type' => 'TextBlock',
                                            'text' => $icon,
                                            'size' => 'Medium'
                                        ]
                                    ]
                                ],
                                [
                                    'type' => 'Column',
                                    'width' => 'stretch',
                                    'items' => [
                                        [
                                            'type' => 'TextBlock',
                                            'text' => $subject,
                                            'weight' => 'Bolder',
                                            'size' => 'Medium',
                                            'color' => $color
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        [
                            'type' => 'TextBlock',
                            'text' => strip_tags($body),
                            'wrap' => true,
                            'spacing' => 'Medium'
                        ]
                    ]
                ]
            ]
        ];

        // Add priority indicator
        $card['body'][] = [
            'type' => 'FactSet',
            'facts' => [
                [
                    'title' => 'Priority:',
                    'value' => ucfirst($priority)
                ],
                [
                    'title' => 'Time:',
                    'value' => now()->format('Y-m-d H:i:s')
                ]
            ],
            'spacing' => 'Medium'
        ];

        return $card;
    }

    /**
     * Get Teams user object by email (enhanced version)
     */
    public function getTeamsUserObject($email)
    {
        try {
            Log::info('Looking up Teams user object', ['email' => $email]);
            
            $token = $this->getAccessToken();
            if (!$token) {
                Log::error('No access token for Teams user lookup');
                return null;
            }

            $httpClient = $this->getHttpClient();
            
            // Search for user by email
            $response = $httpClient
                ->withToken($token)
                ->get("https://graph.microsoft.com/v1.0/users", [
                    '$filter' => "mail eq '{$email}' or userPrincipalName eq '{$email}'",
                    '$select' => 'id,mail,userPrincipalName,displayName,givenName,surname'
                ]);

            if ($response->successful()) {
                $users = $response->json()['value'];
                if (!empty($users)) {
                    Log::info('Teams user found', [
                        'email' => $email,
                        'user_id' => $users[0]['id']
                    ]);
                    return $users[0]; // Return first match
                }
            } else {
                Log::error('Teams user lookup API failed', [
                    'email' => $email,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
            }

            Log::warning('Teams user not found', ['email' => $email]);
            return null;

        } catch (\Exception $e) {
            Log::error('Teams user lookup failed', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Send Teams message directly (for test notifications) - Fixed version
     */
    public function sendDirect(array $teamsData)
    {
        try {
            Log::info('Attempting to send Teams message', [
                'user' => $teamsData['user']->username ?? 'unknown',
                'delivery_method' => $teamsData['delivery_method'] ?? 'direct'
            ]);

            // Check if Teams is configured
            if (!$this->isConfigured()) {
                return [
                    'success' => false, 
                    'error' => 'Teams integration not configured. Please check configuration in admin panel.',
                    'method' => 'config_check'
                ];
            }

            // Get access token
            $token = $this->getAccessToken();
            
            if (!$token) {
                return [
                    'success' => false, 
                    'error' => 'Failed to obtain Teams access token. Please check Teams app configuration.',
                    'method' => 'token_check'
                ];
            }

            // Get Teams user
            $user = $teamsData['user'];
            $teamsUser = $this->getUserByEmail($user->email);
            
            if (!$teamsUser) {
                return [
                    'success' => false, 
                    'error' => "User {$user->email} not found in Teams directory",
                    'method' => 'user_lookup'
                ];
            }

            // Send message based on delivery method
            if ($teamsData['delivery_method'] === 'direct') {
                // Create adaptive card
                $priority = $teamsData['priority'] ?? 'normal';
                $card = $this->createPriorityAdaptiveCard(
                    $teamsData['subject'], 
                    $teamsData['message'], 
                    $priority
                );
                
                $result = $this->sendDirectMessage(
                    $teamsUser->getId(), 
                    $teamsData['message'], 
                    $card
                );
            } else {
                $result = $this->sendChannelMessage(
                    $teamsData['subject'], 
                    $teamsData['message']
                );
            }

            if ($result['success']) {
                return [
                    'success' => true, 
                    'result' => $result,
                    'message' => 'Teams message sent successfully',
                    'method' => 'direct_message'
                ];
            } else {
                return [
                    'success' => false, 
                    'error' => $result['error'],
                    'method' => 'send_message'
                ];
            }

        } catch (\Exception $e) {
            Log::error('Teams direct send failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user' => $teamsData['user']->username ?? 'unknown'
            ]);

            return [
                'success' => false, 
                'error' => $this->getUserFriendlyError($e->getMessage()),
                'method' => 'exception'
            ];
        }
    }

    /**
     * Check if Teams is properly configured
     */
    private function isConfigured()
    {
        $configured = !empty($this->clientId) && 
                     !empty($this->clientSecret) && 
                     !empty($this->tenantId);
        
        Log::info('Teams configuration check', [
            'configured' => $configured,
            'has_client_id' => !empty($this->clientId),
            'has_client_secret' => !empty($this->clientSecret),
            'has_tenant_id' => !empty($this->tenantId)
        ]);
        
        return $configured;
    }

    /**
     * Get access token with enhanced error handling
     */
    private function getAccessToken()
    {
        try {
            // Check cache first
            $cacheKey = 'teams_access_token';
            $token = Cache::get($cacheKey);
            
            if ($token) {
                Log::info('Using cached Teams access token');
                return $token;
            }

            Log::info('Requesting new Teams access token', [
                'tenant_id' => $this->tenantId,
                'client_id' => substr($this->clientId, 0, 8) . '...'
            ]);

            // Prepare HTTP client with SSL configuration
            $httpClient = Http::timeout(30);
            
            // SSL configuration for development environment
            if (app()->environment(['local', 'development'])) {
                $httpClient = $httpClient->withOptions([
                    'verify' => false, // Disable SSL verification for development
                    'curl' => [
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => false,
                    ]
                ]);
                
                Log::info('SSL verification disabled for development environment');
            }

            // Request token
            $response = $httpClient
                ->asForm()
                ->post("https://login.microsoftonline.com/{$this->tenantId}/oauth2/v2.0/token", [
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'scope' => 'https://graph.microsoft.com/.default',
                    'grant_type' => 'client_credentials'
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $token = $data['access_token'];
                $expiresIn = $data['expires_in'] ?? 3600;
                
                // Cache token (expires 5 minutes before actual expiry)
                Cache::put($cacheKey, $token, $expiresIn - 300);
                
                Log::info('Teams access token obtained successfully', [
                    'expires_in' => $expiresIn,
                    'token_length' => strlen($token)
                ]);
                return $token;
            } else {
                Log::error('Teams token request failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return null;
            }

        } catch (\Exception $e) {
            Log::error('Teams Access Token Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Send message to Teams channel
     */
    private function sendChannelMessage($subject, $message)
    {
        try {
            $token = $this->getAccessToken();
            if (!$token) {
                throw new \Exception('No access token available');
            }

            $teamId = config('services.teams.default_team_id');
            $channelId = config('services.teams.default_channel_id');

            if (!$teamId || !$channelId) {
                throw new \Exception('Default team/channel not configured');
            }

            $httpClient = $this->getHttpClient();
            
            $response = $httpClient
                ->withToken($token)
                ->post("https://graph.microsoft.com/v1.0/teams/{$teamId}/channels/{$channelId}/messages", [
                    'body' => [
                        'contentType' => 'html',
                        'content' => "<h3>{$subject}</h3><p>{$message}</p>"
                    ]
                ]);

            return [
                'success' => $response->successful(),
                'response' => $response->json()
            ];

        } catch (\Exception $e) {
            Log::error('Teams channel message failed', [
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get HTTP client with proper SSL configuration
     */
    private function getHttpClient()
    {
        $httpClient = Http::timeout(30);
        
        if (app()->environment(['local', 'development'])) {
            $httpClient = $httpClient->withOptions([
                'verify' => false,
                'curl' => [
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                ]
            ]);
        } else {
            // Production SSL configuration
            $caPath = config('services.teams.ca_bundle_path');
            if ($caPath && file_exists($caPath)) {
                $httpClient = $httpClient->withOptions([
                    'verify' => $caPath
                ]);
            }
        }

        return $httpClient;
    }

    /**
     * Convert technical error to user-friendly message
     */
    private function getUserFriendlyError($error)
    {
        if (strpos($error, 'SSL certificate') !== false) {
            return 'Teams connection error (SSL). Please contact IT support to configure SSL certificates.';
        }
        
        if (strpos($error, 'cURL error 60') !== false) {
            return 'Network connection error. Please check internet connectivity and firewall settings.';
        }
        
        if (strpos($error, 'access token') !== false) {
            return 'Teams authentication failed. Please check Teams app configuration in admin panel.';
        }
        
        if (strpos($error, 'not configured') !== false) {
            return 'Teams integration not configured. Please contact administrator.';
        }

        if (strpos($error, 'not found in Teams directory') !== false) {
            return 'User not found in Microsoft Teams directory. Please ensure user has Teams access.';
        }

        return 'Teams service temporarily unavailable. Please try again later.';
    }

    /**
     * Test Teams connectivity
     */
    public function testConnection()
    {
        try {
            if (!$this->isConfigured()) {
                return [
                    'success' => false,
                    'error' => 'Teams not configured',
                    'details' => 'Missing client_id, client_secret, or tenant_id'
                ];
            }

            $token = $this->getAccessToken();
            
            if (!$token) {
                return [
                    'success' => false,
                    'error' => 'Failed to get access token',
                    'details' => 'Check client credentials and tenant configuration'
                ];
            }

            // Test API call
            $httpClient = $this->getHttpClient();
            $response = $httpClient
                ->withToken($token)
                ->get("https://graph.microsoft.com/v1.0/me");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Teams connection successful',
                    'token_length' => strlen($token),
                    'app_info' => $response->json()
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'API test failed',
                    'details' => $response->body()
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'details' => 'Connection test failed'
            ];
        }
    }
}