<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        $request->validate([
            'id' => 'required|numeric|digits_between:1,5',
        ]);

        $profile = User::select([
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

        $profile = User::where('id', '=', $request->id)
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
                    'message' => 'Gagal mengupdate data Profile' . $exception->getMessage(),
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

        $profile = User::select([
            'nama_lengkap',
            'username',
            'email',
            'password',
        ])->where('id', '=', $request->id)->first();

        if (!empty($profile)) {
            if (!empty($profile->password)) {
                // Asumsi: $defaultPassword adalah kata sandi default yang ditetapkan saat akun dibuat
                $defaultPassword = '1'; // Ganti dengan nilai sebenarnya atau logika Anda

                if (Hash::check($defaultPassword, $profile->password)) {
                    // Kata sandi belum diubah, masih default
                    return response()->json([
                        'message' => 'User belum mengganti password, masih menggunakan default. silahkan ganti password terlebih dahulu untuk keamanan akun anda',
                        'success' => false,
                    ], 200);
                } else {
                    // Kata sandi sudah diubah
                    return response()->json([
                        'message' => 'User sudah pernah mengganti password',
                        'success' => true,
                    ], 200);
                }
            }

            return response()->json([
                'message' => 'Sukses, tapi password kosong',
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

        $profile = User::where('id', '=', $request->id)
            ->first();

        if (!empty($profile)) {
            try {
                $profile->update([
                    'password' => bcrypt($request->password),
                ]);
            } catch (\Exception $exception) {
                return response()->json([
                    'message' => 'Gagal mengupdate password' . $exception->getMessage(),
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
