<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notification;

class NotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('permission:notification.view')->only(['index', 'show']);
        $this->middleware('permission:notification.manage')->only(['markAsRead', 'markAllAsRead', 'destroy']);
    }

    /**
     * Liste des notifications (avec filtres)
     * GET /api/notifications?unread=1&type=payment_received&limit=10
     */
    public function index(Request $request)
    {
        $query = Notification::query()->latest();

        // Filtre : non lues uniquement
        if ($request->boolean('unread')) {
            $query->unread();
        }

        // Filtre : type spécifique
        if ($request->filled('type')) {
            $query->ofType($request->type);
        }

        // Filtre : récentes (7 derniers jours par défaut)
        if ($request->filled('recent_days')) {
            $query->recent((int) $request->recent_days);
        }

        $limit = min((int) $request->input('limit', 15), 50);
        $notifications = $query->paginate($limit);

        return response()->json($notifications);
    }

    /**
     * Détails d'une notification
     */
    public function show(Notification $notification)
    {
        return response()->json($notification);
    }

    /**
     * Marquer une notification comme lue
     * PATCH /api/notifications/{id}/mark-as-read
     */
    public function markAsRead(Notification $notification)
    {
        $notification->markAsRead();

        return response()->json([
            'message' => 'Notification marked as read',
            'notification' => $notification->fresh(),
        ]);
    }

    /**
     * Marquer toutes les notifications comme lues
     * POST /api/notifications/mark-all-as-read
     */
    public function markAllAsRead(Request $request)
    {
        $annexeIds = auth()->user()->getAccessibleAnnexeIds();
        
        $updated = Notification::whereIn('annexe_id', $annexeIds)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'message' => 'All notifications marked as read',
            'count' => $updated,
        ]);
    }

    /**
     * Nombre de notifications non lues
     * GET /api/notifications/unread-count
     */
    public function unreadCount()
    {
        $count = Notification::unread()->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Supprimer une notification
     */
    public function destroy(Notification $notification)
    {
        $notification->delete();

        return response()->json([
            'message' => 'Notification deleted',
        ]);
    }

    /**
     * Supprimer toutes les notifications lues
     * DELETE /api/notifications/clear-read
     */
    public function clearRead()
    {
        $annexeIds = auth()->user()->getAccessibleAnnexeIds();
        
        $deleted = Notification::whereIn('annexe_id', $annexeIds)
            ->where('is_read', true)
            ->delete();

        return response()->json([
            'message' => 'Read notifications cleared',
            'count' => $deleted,
        ]);
    }
}
