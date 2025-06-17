<?php

namespace App\Http\Controllers;

use App\Models\Notification;

class NotificationController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $notifications = $user->notifications()
            ->with('letter')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($notifications);
    }

    public function markAsRead($id)
    {
        $notification = Notification::findOrFail($id);
        $notification->update(['is_read' => true]);

        return response()->json(['message' => 'Notifikasi dibaca']);
    }

    public function storeFromForm(Request $request)
    {
        $request->validate([
            'letter_format_id' => 'required|exists:letter_formats,id',
            'name' => 'required|string',
            'content' => 'required|string',
            'type' => 'required|in:arsip,kirim',
            'target_role' => 'nullable|in:admin,employee',
        ]);

        // Simulasi simpan file sebagai PDF (nanti bisa pakai dompdf atau laravel-snappy)
        $pdfPath = 'letters/' . time() . '.txt';
        \Storage::disk('public')->put($pdfPath, $request->content);

        $letter = Letters::create([
            'letter_format_id' => $request->letter_format_id,
            'user_id' => auth()->id(),
            'name' => $request->name,
            'file_path' => $pdfPath,
            'type' => $request->type,
            'target_role' => $request->target_role,
            'is_sent' => $request->type === 'kirim',
        ]);

        // Kirim notifikasi jika perlu
        if ($letter->is_sent && $letter->target_role) {
            $receivers = User::where('role', $letter->target_role)->get();

            foreach ($receivers as $receiver) {
                Notification::create([
                    'user_id' => $receiver->id,
                    'letter_id' => $letter->id,
                ]);
            }
        }

        return response()->json(['message' => 'Surat berhasil dibuat dari form', 'data' => $letter], 201);
    }
}
