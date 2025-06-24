<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserPreference;
use App\Services\NotificationService;  // ← เพิ่มบรรทัดนี้
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Activitylog\Models\Activity;

class UserPreferenceController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Display user preferences
     */
    public function show(User $user = null)
    {
        // If no user specified, use current user
        $user = $user ?? Auth::user();
        
        // Check if user can edit these preferences
        if ($user->id !== Auth::id() && !Auth::user()->can('manage-users')) {
            abort(403, 'Unauthorized to view these preferences.');
        }

        $preferences = $user->preferences ?? new UserPreference([
            'user_id' => $user->id,
            'enable_teams' => true,
            'enable_email' => true,
            'min_priority' => 'low',
            'language' => 'th',
            'timezone' => 'Asia/Bangkok',
            'email_format' => 'html',
            'teams_channel_preference' => 'direct',
            'enable_grouping' => true,
            'grouping_method' => 'sender'
        ]);

        $timezones = [
            'Asia/Bangkok' => 'Bangkok (UTC+7)',
            'Asia/Singapore' => 'Singapore (UTC+8)',
            'Asia/Tokyo' => 'Tokyo (UTC+9)',
            'Europe/London' => 'London (UTC+0/+1)',
            'America/New_York' => 'New York (UTC-5/-4)',
            'America/Los_Angeles' => 'Los Angeles (UTC-8/-7)',
            'UTC' => 'UTC (UTC+0)'
        ];

        return view('users.preferences', compact('user', 'preferences', 'timezones'));
    }

    /**
     * Update user preferences
     */
    public function update(Request $request, User $user = null)
    {
        // If no user specified, use current user
        $user = $user ?? Auth::user();
        
        // Check if user can edit these preferences
        if ($user->id !== Auth::id() && !Auth::user()->can('manage-users')) {
            abort(403, 'Unauthorized to update these preferences.');
        }

        $request->validate([
            'enable_teams' => 'boolean',
            'enable_email' => 'boolean',
            'min_priority' => 'required|in:low,medium,high,critical',
            'language' => 'required|in:th,en',
            'timezone' => 'required|string',
            'email_format' => 'required|in:html,plain',
            'email_address' => 'nullable|email',
            'teams_user_id' => 'nullable|string|max:255',
            'teams_channel_preference' => 'required|in:direct,channel',
            'enable_grouping' => 'boolean',
            'grouping_method' => 'required|in:sender,priority,time',
            'notification_sound' => 'boolean',
            'digest_frequency' => 'required|in:none,daily,weekly',
            'digest_time' => 'nullable|date_format:H:i',
            'auto_mark_read' => 'boolean',
            'show_preview' => 'boolean'
        ]);

        try {
            $preferences = $user->preferences ?? new UserPreference(['user_id' => $user->id]);
            
            $preferences->fill([
                'enable_teams' => $request->boolean('enable_teams'),
                'enable_email' => $request->boolean('enable_email'),
                'min_priority' => $request->min_priority,
                'language' => $request->language,
                'timezone' => $request->timezone,
                'email_format' => $request->email_format,
                'email_address' => $request->email_address,
                'teams_user_id' => $request->teams_user_id,
                'teams_channel_preference' => $request->teams_channel_preference,
                'enable_grouping' => $request->boolean('enable_grouping'),
                'grouping_method' => $request->grouping_method,
                'notification_sound' => $request->boolean('notification_sound'),
                'digest_frequency' => $request->digest_frequency,
                'digest_time' => $request->digest_time,
                'auto_mark_read' => $request->boolean('auto_mark_read'),
                'show_preview' => $request->boolean('show_preview')
            ]);

            $preferences->save();

            // Log activity
            activity()
                ->performedOn($user)
                ->causedBy(Auth::user())
                ->withProperties([
                    'updated_by_self' => $user->id === Auth::id(),
                    'channels_enabled' => [
                        'teams' => $preferences->enable_teams,
                        'email' => $preferences->enable_email
                    ]
                ])
                ->log('User preferences updated');

            $message = $user->id === Auth::id() 
                ? 'Your preferences have been updated successfully.'
                : "Preferences for {$user->display_name} have been updated successfully.";

            return back()->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Error updating user preferences: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to update preferences.');
        }
    }

    /**
     * Reset preferences to default
     */
    public function reset(User $user = null)
    {
        $user = $user ?? Auth::user();
        
        // Check permissions
        if ($user->id !== Auth::id() && !Auth::user()->can('manage-users')) {
            abort(403, 'Unauthorized to reset these preferences.');
        }

        try {
            $preferences = $user->preferences;
            
            if ($preferences) {
                $preferences->delete();
            }

            // Log activity
            activity()
                ->performedOn($user)
                ->causedBy(Auth::user())
                ->log('User preferences reset to default');

            $message = $user->id === Auth::id() 
                ? 'Your preferences have been reset to default.'
                : "Preferences for {$user->display_name} have been reset to default.";

            return back()->with('success', $message);

        } catch (\Exception $e) {
            Log::error('Error resetting user preferences: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to reset preferences.');
        }
    }

    /**
     * Test notification delivery with current preferences
     */
    public function testNotification(Request $request, User $user = null)
    {
        $user = $user ?? Auth::user();
        
        // Check permissions
        if ($user->id !== Auth::id() && !Auth::user()->can('send-notifications')) {
            abort(403, 'Unauthorized to send test notification.');
        }

        $request->validate([
            'channels' => 'required|array|min:1',
            'channels.*' => 'in:email,teams',
            'message' => 'nullable|string|max:500'
        ]);

        try {
            $testMessage = $request->message ?? 'This is a test notification to verify your notification preferences.';
            
            // Create test notification data
            $notification = [
                'title' => 'Test Notification',
                'message' => $testMessage,
                'priority' => 'medium',
                'channels' => $request->channels
            ];

            // Send test notification using the service
            $result = $this->notificationService->sendTest($user, $notification);

            if ($result['success']) {
                $channelCount = count($result['channels_tested']);
                $message = "Test notification sent successfully to {$channelCount} channel(s). Please check your configured channels.";
                
                return back()->with('success', $message);
            } else {
                return back()->with('error', 'Test notification failed: ' . ($result['message'] ?? 'Unknown error'));
            }

        } catch (\Exception $e) {
            Log::error('Error sending test notification: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'channels' => $request->channels ?? [],
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('error', 'Failed to send test notification: ' . $e->getMessage());
        }
    }

    /**
     * Alternative static method call for test notification
     */
    public function testNotificationStatic(Request $request, User $user = null)
    {
        $user = $user ?? Auth::user();
        
        // Check permissions
        if ($user->id !== Auth::id() && !Auth::user()->can('send-notifications')) {
            abort(403, 'Unauthorized to send test notification.');
        }

        $request->validate([
            'channels' => 'required|array|min:1',
            'channels.*' => 'in:email,teams',
            'message' => 'nullable|string|max:500'
        ]);

        try {
            $testMessage = $request->message ?? 'This is a test notification to verify your notification preferences.';
            
            // Create test notification data
            $notification = [
                'title' => 'Test Notification',
                'message' => $testMessage,
                'priority' => 'medium',
                'channels' => $request->channels
            ];

            // Send test notification using static method
            $result = NotificationService::sendTest($user, $notification);

            if ($result['success']) {
                $channelCount = count($result['channels_tested']);
                $message = "Test notification sent successfully to {$channelCount} channel(s). Please check your configured channels.";
                
                return back()->with('success', $message);
            } else {
                return back()->with('error', 'Test notification failed: ' . ($result['message'] ?? 'Unknown error'));
            }

        } catch (\Exception $e) {
            Log::error('Error sending test notification (static): ' . $e->getMessage(), [
                'user_id' => $user->id,
                'channels' => $request->channels ?? [],
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('error', 'Failed to send test notification: ' . $e->getMessage());
        }
    }

    /**
     * Export user preferences
     */
    public function export(User $user = null)
    {
        $user = $user ?? Auth::user();
        
        // Check permissions
        if ($user->id !== Auth::id() && !Auth::user()->can('manage-users')) {
            abort(403, 'Unauthorized to export these preferences.');
        }

        try {
            $preferences = $user->preferences;
            
            if (!$preferences) {
                return back()->with('error', 'No preferences found to export.');
            }

            $data = [
                'user' => [
                    'username' => $user->username,
                    'display_name' => $user->display_name,
                    'email' => $user->email
                ],
                'preferences' => $preferences->toArray(),
                'exported_at' => now()->toISOString(),
                'exported_by' => Auth::user()->username
            ];

            $filename = "preferences_{$user->username}_" . now()->format('Y-m-d_H-i-s') . '.json';

            return response()->json($data, 200, [
                'Content-Type' => 'application/json',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ]);

        } catch (\Exception $e) {
            Log::error('Error exporting user preferences: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to export preferences.');
        }
    }

    /**
     * Import user preferences
     */
    public function import(Request $request, User $user = null)
    {
        $user = $user ?? Auth::user();
        
        // Check permissions
        if ($user->id !== Auth::id() && !Auth::user()->can('manage-users')) {
            abort(403, 'Unauthorized to import preferences.');
        }

        $request->validate([
            'preferences_file' => 'required|file|mimes:json|max:1024' // 1MB max
        ]);

        try {
            $file = $request->file('preferences_file');
            $content = file_get_contents($file->getPathname());
            $data = json_decode($content, true);

            if (!$data || !isset($data['preferences'])) {
                return back()->with('error', 'Invalid preferences file format.');
            }

            $preferencesData = $data['preferences'];
            
            // Validate imported data
            $allowedFields = [
                'enable_teams', 'enable_email', 'min_priority', 'language', 'timezone',
                'email_format', 'email_address', 'teams_user_id', 'teams_channel_preference',
                'enable_grouping', 'grouping_method', 'notification_sound', 'digest_frequency',
                'digest_time', 'auto_mark_read', 'show_preview'
            ];

            $filteredData = array_intersect_key($preferencesData, array_flip($allowedFields));
            $filteredData['user_id'] = $user->id;

            // Update or create preferences
            $preferences = $user->preferences ?? new UserPreference();
            $preferences->fill($filteredData);
            $preferences->save();

            // Log activity
            activity()
                ->performedOn($user)
                ->causedBy(Auth::user())
                ->withProperties([
                    'imported_fields' => array_keys($filteredData),
                    'source_file' => $file->getClientOriginalName()
                ])
                ->log('User preferences imported');

            return back()->with('success', 'Preferences imported successfully.');

        } catch (\Exception $e) {
            Log::error('Error importing user preferences: ' . $e->getMessage());
            
            return back()->with('error', 'Failed to import preferences.');
        }
    }
}