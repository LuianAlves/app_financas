<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Auth\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function index()
    {
        $users = $this->user->all();

        return view('app.users.user_index', compact('users'));
    }

    public function create()
    {
        return view('app.users.user_create');
    }
    public function store(Request $request)
    {
        //
    }

    public function show(string $id)
    {
        //
    }

    public function edit($id)
    {
        return view('app.users.user_edit');
    }

    public function update(Request $request, string $id)
    {
        //
    }

    public function destroy(string $id)
    {
        //
    }
}
