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

    public function getUserByEmail($email)
    {
        // ใช้ findTeamsUser() ที่มีอยู่แล้ว
        $user = \App\Models\User::where('email', $email)->first();
        return $user ? $this->findTeamsUser($user) : null;
    }

    /**
     * Send direct message to user ID with adaptive card support
     */
    public function sendDirectMessage($userId, $body, $cardTemplate = null)
    {
        try {
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
                Log::error('Teams direct message failed', [
                    'user_id' => $userId,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);

                return [
                    'success' => false,
                    'error' => 'Failed to send Teams message: ' . $response->body()
                ];
            }

        } catch (\Exception $e) {
            Log::error('Teams direct message exception', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get or create chat with user
     */
    private function getOrCreateChatWithUser($userId, $token)
    {
        try {
            $httpClient = $this->getHttpClient();
            
            // Try to find existing chat first
            $response = $httpClient
                ->withToken($token)
                ->get("https://graph.microsoft.com/v1.0/me/chats", [
                    '$filter' => "chatType eq 'oneOnOne'",
                    '$expand' => 'members'
                ]);

            if ($response->successful()) {
                $chats = $response->json()['value'];
                
                // Look for existing chat with this user
                foreach ($chats as $chat) {
                    $members = $chat['members'] ?? [];
                    foreach ($members as $member) {
                        if (isset($member['userId']) && $member['userId'] === $userId) {
                            return $chat['id'];
                        }
                    }
                }
            }

            // Create new chat if not found
            $createResponse = $httpClient
                ->withToken($token)
                ->post("https://graph.microsoft.com/v1.0/chats", [
                    'chatType' => 'oneOnOne',
                    'members' => [
                        [
                            '@odata.type' => '#microsoft.graph.aadUserConversationMember',
                            'userId' => $userId
                        ]
                    ]
                ]);

            if ($createResponse->successful()) {
                return $createResponse->json()['id'];
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Failed to get/create chat with user', [
                'user_id' => $userId,
                'error' => $e->getMessage()
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
            ],
            'actions' => []
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
            $token = $this->getAccessToken();
            if (!$token) {
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
                    return $users[0]; // Return first match
                }
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
     * Send notification with retry logic
     */
    public function sendNotificationWithRetry($userId, $subject, $body, $priority = 'normal', $maxRetries = 3)
    {
        $attempt = 0;
        $lastError = null;

        while ($attempt < $maxRetries) {
            $attempt++;
            
            try {
                // Create appropriate card based on priority
                $card = $this->createPriorityAdaptiveCard($subject, $body, $priority);
                
                // Send message
                $result = $this->sendDirectMessage($userId, $body, $card);
                
                if ($result['success']) {
                    Log::info('Teams notification sent successfully', [
                        'user_id' => $userId,
                        'attempt' => $attempt,
                        'priority' => $priority
                    ]);
                    return $result;
                }
                
                $lastError = $result['error'];
                
            } catch (\Exception $e) {
                $lastError = $e->getMessage();
            }

            if ($attempt < $maxRetries) {
                // Wait before retry (exponential backoff)
                $waitTime = pow(2, $attempt - 1) * 5; // 5, 10, 20 seconds
                sleep($waitTime);
                
                Log::warning("Teams notification attempt {$attempt} failed, retrying in {$waitTime}s", [
                    'user_id' => $userId,
                    'error' => $lastError
                ]);
            }
        }

        // All attempts failed
        Log::error("Teams notification failed after {$maxRetries} attempts", [
            'user_id' => $userId,
            'last_error' => $lastError
        ]);

        return [
            'success' => false,
            'error' => "Failed after {$maxRetries} attempts: {$lastError}"
        ];
    }

    /**
     * Send Teams message directly (for test notifications)
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
                    'error' => 'Teams integration not configured. Please check configuration in admin panel.'
                ];
            }

            // Get access token
            $token = $this->getAccessToken();
            
            if (!$token) {
                return [
                    'success' => false, 
                    'error' => 'Failed to obtain Teams access token. Please check Teams app configuration.'
                ];
            }

            // Send message based on delivery method
            if ($teamsData['delivery_method'] === 'direct') {
                $result = $this->sendDirectMessage($teamsData['user'], $teamsData['subject'], $teamsData['message']);
            } else {
                $result = $this->sendChannelMessage($teamsData['subject'], $teamsData['message']);
            }

            return [
                'success' => true, 
                'result' => $result,
                'message' => 'Teams message sent successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Teams direct send failed', [
                'error' => $e->getMessage(),
                'user' => $teamsData['user']->username ?? 'unknown'
            ]);

            return [
                'success' => false, 
                'error' => $this->getUserFriendlyError($e->getMessage())
            ];
        }
    }

    /**
     * Check if Teams is properly configured
     */
    private function isConfigured()
    {
        return !empty($this->clientId) && 
               !empty($this->clientSecret) && 
               !empty($this->tenantId);
    }

    /**
     * Get access token with SSL handling for development
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

            Log::info('Requesting new Teams access token');

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
            } else {
                // Production: Use proper SSL certificate path
                $caPath = config('services.teams.ca_bundle_path');
                if ($caPath && file_exists($caPath)) {
                    $httpClient = $httpClient->withOptions([
                        'verify' => $caPath
                    ]);
                }
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
                
                Log::info('Teams access token obtained successfully');
                return $token;
            } else {
                Log::error('Teams token request failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return null;
            }

        } catch (\Exception $e) {
            Log::error('Teams Access Token Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Send direct message to user
     */
    private function sendDirectMessagex($user, $subject, $message)
    {
        try {
            $token = $this->getAccessToken();
            if (!$token) {
                throw new \Exception('No access token available');
            }

            // Find user in Teams
            $teamsUserId = $this->findTeamsUser($user);
            if (!$teamsUserId) {
                throw new \Exception('User not found in Teams directory');
            }

            // Create chat message
            $httpClient = $this->getHttpClient();
            
            $response = $httpClient
                ->withToken($token)
                ->post("https://graph.microsoft.com/v1.0/chats", [
                    'chatType' => 'oneOnOne',
                    'members' => [
                        [
                            '@odata.type' => '#microsoft.graph.aadUserConversationMember',
                            'userId' => $teamsUserId
                        ]
                    ]
                ]);

            if ($response->successful()) {
                $chatId = $response->json()['id'];
                
                // Send message to chat
                $messageResponse = $httpClient
                    ->withToken($token)
                    ->post("https://graph.microsoft.com/v1.0/chats/{$chatId}/messages", [
                        'body' => [
                            'contentType' => 'html',
                            'content' => "<h3>{$subject}</h3><p>{$message}</p>"
                        ]
                    ]);

                return $messageResponse->successful();
            }

            return false;

        } catch (\Exception $e) {
            Log::error('Teams direct message failed', [
                'error' => $e->getMessage(),
                'user' => $user->username
            ]);
            return false;
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

            return $response->successful();

        } catch (\Exception $e) {
            Log::error('Teams channel message failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Find user in Teams directory
     */
    private function findTeamsUser($user)
    {
        try {
            $token = $this->getAccessToken();
            if (!$token) {
                return null;
            }

            // Try to find user by email
            $httpClient = $this->getHttpClient();
            
            $response = $httpClient
                ->withToken($token)
                ->get("https://graph.microsoft.com/v1.0/users", [
                    '$filter' => "mail eq '{$user->email}' or userPrincipalName eq '{$user->email}'"
                ]);

            if ($response->successful()) {
                $users = $response->json()['value'];
                if (!empty($users)) {
                    return $users[0]['id'];
                }
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Teams user lookup failed', [
                'error' => $e->getMessage(),
                'user_email' => $user->email
            ]);
            return null;
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

            return [
                'success' => true,
                'message' => 'Teams connection successful',
                'token_length' => strlen($token)
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'details' => 'Connection test failed'
            ];
        }
    }

    /**
     * Mock Teams service for development/testing
     */
    public function mockSend(array $teamsData)
    {
        Log::info('Mock Teams message sent', [
            'user' => $teamsData['user']->username ?? 'unknown',
            'subject' => $teamsData['subject'] ?? 'No subject',
            'delivery_method' => $teamsData['delivery_method'] ?? 'direct'
        ]);

        return [
            'success' => true,
            'result' => 'mock_sent',
            'message' => 'Mock Teams message sent (development mode)'
        ];
    }
}