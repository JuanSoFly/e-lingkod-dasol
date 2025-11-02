<?php

namespace App\Http\Controllers;

use App\Models\OPCRWorkflow;
use App\Models\Office;
use App\Models\User;
use App\Services\OPCRNotificationService;
use App\Notifications\OPCRWorkflowNotification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class OPCRNotificationController extends Controller
{
    private OPCRNotificationService $notificationService;

    public function __construct(OPCRNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;

        $this->middleware('auth');
        $this->middleware('permission:opcr.notifications.view')->only(['index', 'show']);
        $this->middleware('permission:opcr.notifications.send')->only(['create', 'store', 'sendCustom']);
        $this->middleware('permission:opcr.notifications.manage')->only(['markAsRead', 'markAllAsRead', 'destroy']);
    }

    /**
     * Display a listing of OPCR notifications
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        // Get user's notifications
        $query = $user->notifications()
            ->where('type', OPCRWorkflowNotification::class)
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('read')) {
            if ($request->boolean('read')) {
                $query->whereNotNull('read_at');
            } else {
                $query->whereNull('read_at');
            }
        }

        if ($request->filled('type')) {
            $query->whereJsonContains('data->notification_type', $request->type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $notifications = $query->paginate(20)->withQueryString();

        // Get notification statistics
        $stats = [
            'total_notifications' => $user->notifications()->where('type', OPCRWorkflowNotification::class)->count(),
            'unread_notifications' => $user->unreadNotifications()->where('type', OPCRWorkflowNotification::class)->count(),
            'today_notifications' => $user->notifications()->where('type', OPCRWorkflowNotification::class)
                ->whereDate('created_at', today())->count(),
        ];

        // Get notification types for filter dropdown
        $notificationTypes = [
            'opcr.initialized' => 'OPCR Initialized',
            'opcr.committed' => 'OPCR Committed',
            'opcr.submitted' => 'OPCR Submitted',
            'opcr.assessed' => 'OPCR Assessed',
            'opcr.approved' => 'OPCR Approved',
            'opcr.returned' => 'OPCR Returned',
        ];

        return view('admin.opcr.notifications.index', compact(
            'notifications',
            'stats',
            'notificationTypes'
        ));
    }

    /**
     * Display the specified notification
     */
    public function show($id): View
    {
        $notification = Auth::user()
            ->notifications()
            ->where('id', $id)
            ->where('type', OPCRWorkflowNotification::class)
            ->firstOrFail();

        // Mark as read if not already read
        if (!$notification->read_at) {
            $notification->markAsRead();
        }

        return view('admin.opcr.notifications.show', compact('notification'));
    }

    /**
     * Show the form for sending a custom notification
     */
    public function create(): View
    {
        $offices = Office::where('is_active', true)->orderBy('name')->get();
        $users = User::with(['roles', 'officeAssignments'])
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['Super Admin', 'HR Admin', 'Employee']);
            })
            ->orderBy('name')
            ->get();

        $notificationTypes = [
            'opcr.reminder' => 'OPCR Reminder',
            'opcr.deadline' => 'OPCR Deadline',
            'opcr.announcement' => 'OPCR Announcement',
            'opcr.training' => 'OPCR Training',
        ];

        return view('admin.opcr.notifications.create', compact(
            'offices',
            'users',
            'notificationTypes'
        ));
    }

    /**
     * Send a custom notification
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'recipient_type' => 'required|in:users,offices,roles,all',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'notification_type' => 'required|string',
            'recipients' => 'required_if:recipient_type,users,offices|array',
            'recipients.*' => 'required_if:recipient_type,users|exists:users,id',
            'recipients.*' => 'required_if:recipient_type,offices|exists:offices,id',
            'send_immediately' => 'boolean',
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        try {
            $notificationData = [
                'title' => $request->title,
                'message' => $request->message,
                'notification_type' => $request->notification_type,
                'sender_id' => Auth::id(),
                'sender_name' => Auth::user()->name,
                'is_custom' => true,
            ];

            if ($request->boolean('send_immediately', true)) {
                $this->notificationService->sendCustomNotification(
                    $request->recipient_type,
                    $request->recipients ?? [],
                    $notificationData
                );
            } else {
                // Schedule notification (implementation would depend on your task scheduler)
                $scheduledAt = $request->scheduled_at ?? now();
                $this->notificationService->scheduleNotification(
                    $request->recipient_type,
                    $request->recipients ?? [],
                    $notificationData,
                    $scheduledAt
                );
            }

            return redirect()
                ->route('opcr.notifications.index')
                ->with('success', 'Notification sent successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to send OPCR notification', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
                'user_id' => Auth::id(),
            ]);

            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to send notification: ' . $e->getMessage()]);
        }
    }

    /**
     * Send notification to specific workflow participants
     */
    public function sendToWorkflowParticipants(Request $request, OPCRWorkflow $workflow): RedirectResponse
    {
        $request->validate([
            'target_roles' => 'required|array',
            'target_roles.*' => 'required|in:Department Head,Assessor,Final Approver',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'notification_type' => 'required|string',
        ]);

        try {
            $participants = $this->notificationService->getWorkflowParticipants(
                $workflow,
                $request->target_roles
            );

            $notificationData = [
                'title' => $request->title,
                'message' => $request->message,
                'notification_type' => $request->notification_type,
                'workflow_id' => $workflow->id,
                'workflow_title' => $workflow->title,
                'sender_id' => Auth::id(),
                'sender_name' => Auth::user()->name,
                'is_custom' => true,
            ];

            Notification::send($participants, new OPCRWorkflowNotification(
                $request->notification_type,
                $notificationData
            ));

            return back()
                ->with('success', 'Notification sent to workflow participants.');

        } catch (\Exception $e) {
            Log::error('Failed to send notification to workflow participants', [
                'error' => $e->getMessage(),
                'workflow_id' => $workflow->id,
                'request_data' => $request->all(),
                'user_id' => Auth::id(),
            ]);

            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to send notification: ' . $e->getMessage()]);
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($id): JsonResponse
    {
        try {
            $notification = Auth::user()
                ->notifications()
                ->where('id', $id)
                ->where('type', OPCRWorkflowNotification::class)
                ->firstOrFail();

            $notification->markAsRead();

            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notification as read.',
            ], 500);
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(): RedirectResponse
    {
        try {
            Auth::user()
                ->unreadNotifications()
                ->where('type', OPCRWorkflowNotification::class)
                ->update(['read_at' => now()]);

            return back()
                ->with('success', 'All notifications marked as read.');

        } catch (\Exception $e) {
            Log::error('Failed to mark all notifications as read', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return back()
                ->withErrors(['error' => 'Failed to mark all notifications as read.']);
        }
    }

    /**
     * Delete a notification
     */
    public function destroy($id): RedirectResponse
    {
        try {
            $notification = Auth::user()
                ->notifications()
                ->where('id', $id)
                ->where('type', OPCRWorkflowNotification::class)
                ->firstOrFail();

            $notification->delete();

            return back()
                ->with('success', 'Notification deleted successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to delete notification', [
                'error' => $e->getMessage(),
                'notification_id' => $id,
                'user_id' => Auth::id(),
            ]);

            return back()
                ->withErrors(['error' => 'Failed to delete notification.']);
        }
    }

    /**
     * Get unread notifications count (for AJAX/real-time updates)
     */
    public function getUnreadCount(): JsonResponse
    {
        $count = Auth::user()
            ->unreadNotifications()
            ->where('type', OPCRWorkflowNotification::class)
            ->count();

        return response()->json([
            'success' => true,
            'count' => $count,
        ]);
    }

    /**
     * Get recent notifications (for dashboard widgets)
     */
    public function getRecentNotifications(): JsonResponse
    {
        $notifications = Auth::user()
            ->notifications()
            ->where('type', OPCRWorkflowNotification::class)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->data['title'] ?? 'OPCR Notification',
                    'message' => $notification->data['message'] ?? '',
                    'type' => $notification->data['notification_type'] ?? 'opcr.default',
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at->diffForHumans(),
                    'url' => route('opcr.notifications.show', $notification->id),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $notifications,
        ]);
    }

    /**
     * Get notification preferences for the user
     */
    public function getPreferences(): JsonResponse
    {
        $preferences = Auth::user()->notification_preferences ?? [];

        return response()->json([
            'success' => true,
            'data' => [
                'opcr_initialized' => $preferences['opcr_initialized'] ?? true,
                'opcr_committed' => $preferences['opcr_committed'] ?? true,
                'opcr_submitted' => $preferences['opcr_submitted'] ?? true,
                'opcr_assessed' => $preferences['opcr_assessed'] ?? true,
                'opcr_approved' => $preferences['opcr_approved'] ?? true,
                'opcr_returned' => $preferences['opcr_returned'] ?? true,
                'opcr_deadline_reminders' => $preferences['opcr_deadline_reminders'] ?? true,
                'opcr_announcements' => $preferences['opcr_announcements'] ?? true,
            ],
        ]);
    }

    /**
     * Update notification preferences
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $request->validate([
            'preferences' => 'required|array',
            'preferences.*' => 'boolean',
        ]);

        try {
            Auth::user()->update([
                'notification_preferences' => $request->preferences,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Notification preferences updated successfully.',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update notification preferences', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update preferences.',
            ], 500);
        }
    }

    /**
     * Test notification sending (for development/testing)
     */
    public function testNotification(Request $request): JsonResponse
    {
        if (!app()->environment(['local', 'testing'])) {
            return response()->json([
                'success' => false,
                'message' => 'Test notifications are only available in development environment.',
            ], 403);
        }

        $request->validate([
            'type' => 'required|string',
            'message' => 'required|string',
        ]);

        try {
            $notificationData = [
                'title' => 'Test OPCR Notification',
                'message' => $request->message,
                'notification_type' => $request->type,
                'sender_id' => Auth::id(),
                'sender_name' => Auth::user()->name,
                'is_test' => true,
            ];

            Notification::send(Auth::user(), new OPCRWorkflowNotification(
                $request->type,
                $notificationData
            ));

            return response()->json([
                'success' => true,
                'message' => 'Test notification sent successfully.',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send test notification', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send test notification: ' . $e->getMessage(),
            ], 500);
        }
    }
}