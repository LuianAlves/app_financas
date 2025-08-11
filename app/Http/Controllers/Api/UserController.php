<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdditionalUser;
use App\Models\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public $user;
    public $additionalUser;

    public function __construct(User $user, AdditionalUser $additionalUser)
    {
        $this->user = $user;
        $this->additionalUser = $additionalUser;
    }

    public function index(Request $request)
    {
        $ownerId = AdditionalUser::ownerIdFor();

        $users = User::select('users.id','users.name','users.email','users.is_active','users.image')
            ->join('additional_users', 'additional_users.linked_user_id', '=', 'users.id')
            ->where('additional_users.user_id', $ownerId)
            ->orderBy('users.name')
            ->get();

        return $request->wantsJson()
            ? response()->json($users)
            : view('app.users.user_index', compact('users'));
    }


    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => ['required','string','max:255'],
            'email'     => ['required','email','max:255','unique:users,email'],
            'password'  => ['required','confirmed','string','min:6'],
            'is_active' => ['nullable','boolean']
        ]);


        $imagemBase64 = null;

        if ($request->hasFile('image')) {
            $userImage = $request->file('image');

            $imageData = file_get_contents($userImage->getRealPath());

            $image = imagecreatefromstring($imageData);


            if ($image !== false) {
                $w = 250;
                $h = 250;
                $resizedImage = imagescale($image, $w, $h);

                ob_start();
                imagejpeg($resizedImage);
                $rawImage = ob_get_clean();

                $imagemBase64 = base64_encode($rawImage);

                imagedestroy($resizedImage);
                imagedestroy($image);
            }
        }

        $ownerId = AdditionalUser::ownerIdFor();

        $newUser = null;
        DB::transaction(function () use (&$newUser, $data, $ownerId, $imagemBase64) {
            //dd('dentro', $imagemBase64);
            $newUser = User::create([
                'name'      => $data['name'],
                'email'     => $data['email'],
                'password'  => Hash::make($data['password']),
                'is_active' => $data['is_active'] ?? true,
                'image'     => $imagemBase64,
            ]);

            $newUser->assignRole('additional_user');

            AdditionalUser::firstOrCreate([
                'user_id'        => $ownerId,
                'linked_user_id' => $newUser->id,
            ]);
        });

        return response()->json([
            'id'        => $newUser->id,
            'name'      => $newUser->name,
            'email'     => $newUser->email,
            'is_active' => (bool) $newUser->is_active,
            'image'     => $newUser->image,
        ], 201);
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
