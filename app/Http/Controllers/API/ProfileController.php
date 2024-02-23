<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\dataProfile;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        $request->validate([
            'id' => 'required|numeric|digits_between:1,5',
        ]);

        $profile = dataProfile::select([
                'nama_lengkap',
                'username',
                'email',
                'created_at',
            ])->where('users.id', '=', $request->id)->first();

        if (!empty($profile)) {
            return response()->json([
                'message' => 'Sukses',
                'data_profile' => $profile,
                'success' => true,
            ], 200);
        }

        return response()->json([
            'message' => 'Data Profile tidak ditemukan',
            'success' => false,
        ], 200);
    }

    // Update data Profile
    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|numeric|digits_between:1,5',
            'nama_lengkap' => 'required|max:50',
            'username' => 'required|max:20',
            'email' => 'required|email|max:50',
        ]);

        $profile = dataProfile::where('id', '=', $request->id)
            ->first();

        if (!empty($profile)) {
            try {
                $profile->update([
                    'nama_lengkap' => $request->nama_lengkap,
                    'username' => $request->username,
                    'email' => $request->email,
                ]);
            } catch (\Exception $exception) {
                return response()->json([
                    'message' => 'Gagal mengupdate data Profile'.$exception->getMessage(),
                    'success' => false,
                ], 500);
            }

            unset(
                $profile->nama_lengkap,
                $profile->username,
                $profile->email
            );

            return response()->json([
                'message' => 'Data Profile berhasil diupdate',
                'data_profile' => $profile,
                'success' => true,
            ], 200);
        }

        return response()->json([
            'message' => 'Data Profile tidak ditemukan',
            'success' => false,
        ], 200);
    }

    // Cek Password User
    public function cek_password(Request $request)
    {
        $request->validate([
            'id' => 'required|numeric|digits_between:1,5',
        ]);

        $profile = dataProfile::select([
            'nama_lengkap',
            'username',
            'email',
            'password',
        ])->where('id', '=', $request->id)->first();

        if (!empty($profile)) {
            if (!empty($profile->password)) {
                return response()->json([
                    'message' => 'Sukses',
                    'success' => true,
                ], 200);
            }

            return response()->json([
                'message' => 'Sukses',
                'success' => false,
            ], 200);
        }

        return response()->json([
            'message' => 'Data Profile tidak ditemukan',
            'success' => false,
        ], 200);
    }

    public function update_password(Request $request)
    {
        $request->validate([
            'id' => 'required|numeric|digits_between:1,5',
            'password' => 'required|max:30',
        ]);

        $profile = dataProfile::where('id', '=', $request->id)
            ->first();

        if (!empty($profile)) {
            try {
                $profile->update([
                    'password' => bcrypt($request->password),
                ]);
            } catch (\Exception $exception) {
                return response()->json([
                    'message' => 'Gagal mengupdate password'.$exception->getMessage(),
                    'success' => false,
                ], 500);
            }

            unset($profile->password);

            return response()->json([
                'message' => 'Password berhasil diupdate',
                'success' => true,
            ], 200);
        }

        return response()->json([
            'message' => 'Data Profile tidak ditemukan',
            'success' => false,
        ], 200);
    }
}
