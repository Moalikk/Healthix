<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    // 30 notifikasi terbaru milik user yang login
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $notifications = DB::table('user_notifications')
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->limit(30)
            ->select('id', 'appointment_id', 'title', 'message', 'read_at', 'created_at')
            ->get();

        $unread = DB::table('user_notifications')
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->count();

        return response()->json(['data' => $notifications, 'unread_count' => $unread]);
    }

    public function read(Request $request, $id)
    {
        $updated = DB::table('user_notifications')
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->update(['read_at' => now(), 'updated_at' => now()]);

        if (!$updated) {
            return response()->json(['message' => 'Notification not found'], 404);
        }

        return response()->json(['message' => 'Marked as read']);
    }

    public function readAll(Request $request)
    {
        DB::table('user_notifications')
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now(), 'updated_at' => now()]);

        return response()->json(['message' => 'All marked as read']);
    }
}