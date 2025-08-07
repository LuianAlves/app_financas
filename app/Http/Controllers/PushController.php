<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Notifications\PushNotification;

class PushController extends Controller
{
    public function subscribe(Request $request)
    {
        $user = auth()->user();

        $user->updatePushSubscription(
            $request->input('endpoint'),
            $request->input('keys.p256dh'),
            $request->input('keys.auth'),
            $request->input('options', [])
        );

        return response()->json(['success' => true]);
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
