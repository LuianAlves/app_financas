<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Notifications\PushNotification;
use Illuminate\Support\Facades\Log;

class PushController extends Controller
{
    public function subscribe(Request $request)
    {
        if (!$request->user()) {
            return response()->json(['success' => false, 'error' => 'Unauthenticated'], 401);
        }

        $data = $request->validate([
            'endpoint'    => 'required|url',
            'keys.p256dh' => 'required|string',
            'keys.auth'   => 'required|string',
        ]);

        try {
            $request->user()->updatePushSubscription(
                $data['endpoint'],
                $data['keys']['p256dh'],
                $data['keys']['auth']
            );

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            Log::error('Push subscribe error: '.$e->getMessage());

            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function showForm()
    {
        return view('app.push.index');
    }

    public function send(Request $request)
    {
        $user = auth()->user();
        $title = $request->input('title', 'Título Padrão');
        $body = $request->input('body', 'Conteúdo da notificação');

        $user->notify(new PushNotification($title, $body));

        return back()->with('success', 'Notificação enviada com sucesso!');
    }
}
