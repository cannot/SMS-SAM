<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class MockTeamsService
{
    /**
     * Mock getUserByEmail for development
     */
    public function getUserByEmail($email)
    {
        Log::info('Mock Teams: Getting user by email', ['email' => $email]);
        
        // Return a mock user object
        return (object) [
            'id' => 'mock-user-' . md5($email),
            'email' => $email,
            'displayName' => 'Mock User (' . $email . ')',
            'getId' => function() use ($email) {
                return 'mock-user-' . md5($email);
            }
        ];
    }

    /**
     * Mock sendDirectMessage for development
     */
    public function sendDirectMessage($userId, $body, $cardTemplate = null)
    {
        Log::info('Mock Teams: Sending direct message', [
            'user_id' => $userId,
            'body_length' => strlen($body),
            'has_card' => !empty($cardTemplate)
        ]);

        // Simulate success/failure based on user ID
        $success = !str_contains($userId, 'fail');

        if ($success) {
            return [
                'success' => true,
                'message_id' => 'mock-message-' . uniqid(),
                'chat_id' => 'mock-chat-' . $userId
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Mock failure for testing'
            ];
        }
    }

    /**
     * Mock sendDirect for development
     */
    public function sendDirect(array $teamsData)
    {
        Log::info('Mock Teams: Sending direct notification', [
            'user' => $teamsData['user']->email ?? 'unknown',
            'subject' => $teamsData['subject'] ?? 'No subject',
            'delivery_method' => $teamsData['delivery_method'] ?? 'direct'
        ]);

        // Simulate random success/failure for testing
        $success = rand(1, 10) > 2; // 80% success rate

        if ($success) {
            return [
                'success' => true,
                'result' => [
                    'message_id' => 'mock-message-' . uniqid(),
                    'chat_id' => 'mock-chat-' . uniqid()
                ],
                'message' => 'Mock Teams message sent successfully',
                'method' => 'mock_direct'
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Mock Teams service error for testing',
                'method' => 'mock_error'
            ];
        }
    }

    /**
     * Mock createAdaptiveCard
     */
    public function createAdaptiveCard($subject, $body)
    {
        return [
            'type' => 'AdaptiveCard',
            'version' => '1.3',
            'body' => [
                [
                    'type' => 'TextBlock',
                    'text' => '[MOCK] ' . $subject,
                    'weight' => 'Bolder'
                ],
                [
                    'type' => 'TextBlock',
                    'text' => '[MOCK] ' . strip_tags($body),
                    'wrap' => true
                ]
            ]
        ];
    }

    /**
     * Mock createPriorityAdaptiveCard
     */
    public function createPriorityAdaptiveCard($subject, $body, $priority = 'normal')
    {
        $icons = [
            'low' => '🔵',
            'normal' => '⚪',
            'medium' => '🟡', 
            'high' => '🟠',
            'urgent' => '🔴'
        ];

        $icon = $icons[$priority] ?? '⚪';

        return [
            'type' => 'AdaptiveCard',
            'version' => '1.3',
            'body' => [
                [
                    'type' => 'TextBlock',
                    'text' => $icon . ' [MOCK] ' . $subject,
                    'weight' => 'Bolder',
                    'color' => 'Accent'
                ],
                [
                    'type' => 'TextBlock',
                    'text' => '[MOCK] ' . strip_tags($body),
                    'wrap' => true
                ],
                [
                    'type' => 'FactSet',
                    'facts' => [
                        [
                            'title' => 'Priority:',
                            'value' => ucfirst($priority) . ' (Mock)'
                        ],
                        [
                            'title' => 'Time:',
                            'value' => now()->format('Y-m-d H:i:s')
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Mock getTeamsUserObject
     */
    public function getTeamsUserObject($email)
    {
        Log::info('Mock Teams: Getting user object', ['email' => $email]);
        
        return [
            'id' => 'mock-user-' . md5($email),
            'mail' => $email,
            'userPrincipalName' => $email,
            'displayName' => 'Mock User (' . $email . ')',
            'givenName' => 'Mock',
            'surname' => 'User'
        ];
    }

    /**
     * Mock testConnection
     */
    public function testConnection()
    {
        return [
            'success' => true,
            'message' => 'Mock Teams connection successful',
            'mock' => true,
            'app_info' => [
                'displayName' => 'Mock Teams App',
                'id' => 'mock-app-id'
            ]
        ];
    }

    /**
     * Mock any other method calls
     */
    public function __call($method, $arguments)
    {
        Log::info("Mock Teams: Called method '{$method}'", [
            'arguments_count' => count($arguments)
        ]);

        return [
            'success' => true,
            'mock' => true,
            'method' => $method,
            'message' => "Mock response for {$method}"
        ];
    }
}