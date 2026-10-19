<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class AccountNotificationController extends Controller
{
    public function index(Request $request)
    {
        $items = Notification::where('user_id', $request->user()->id)->latest('id')->limit(50)->get();

        return response()->json(['data' => $items->map(fn ($n) => [
            'id' => 'server-'.$n->id, 'server_id' => $n->id, 'type' => $n->type, 'title' => $n->title,
            'description' => $n->content, 'link' => $n->data['link'] ?? '/profile',
            'isRead' => $n->read_at !== null, 'createdAt' => $n->created_at,
        ])]);
    }

    public function read(Request $request, Notification $notification)
    {
        abort_unless($notification->user_id === $request->user()->id, 403);
        $notification->markAsRead();

        return response()->json(['message' => 'Đã đánh dấu đã đọc.']);
    }

    public function readAll(Request $request)
    {
        Notification::where('user_id', $request->user()->id)->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json(['message' => 'Đã đánh dấu tất cả đã đọc.']);
    }
}
