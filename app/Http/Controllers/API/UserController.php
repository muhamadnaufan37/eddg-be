<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    // List data User
    public function list(Request $request)
    {
        $keyword = $request->get('keyword', null);
        $perPage = $request->get('per-page', 10);
        $kolom = $request->get('kolom', null);
        $role = $request->get('role', null);
        $status = $request->get('status', null);

        if ($perPage > 100) {
            $perPage = 100;
        }

        $model = User::leftjoin('roles', 'roles.id', '=', 'users.role_id')
            ->select([
                'users.id',
                'users.username',
                'users.email',
                'users.nama_lengkap',
                'roles.name as role_id',
                'users.status',
                'users.login_terakhir',
            ]);

        if (!is_null($role)) {
            $model->where('roles.id', '=', $role);
        }

        if (!is_null($status)) {
            $model->where('users.status', '=', $status);
        }

        $model->orderByRaw('users.created_at DESC NULLS LAST');

        if (!empty($keyword) && empty($kolom)) {
            $user = $model->where('users.username', 'ILIKE', '%'.$keyword.'%')
                ->orWhere('users.email', 'ILIKE', '%'.$keyword.'%')
                ->orWhere('users.nama_lengkap', 'ILIKE', '%'.$keyword.'%')
                ->paginate($perPage);
        } elseif (!empty($keyword) && !empty($kolom)) {
            if ($kolom == 'username') {
                $kolom = 'users.username';
            } elseif ($kolom == 'email') {
                $kolom = 'users.email';
            } else {
                $kolom = 'users.nama_lengkap';
            }

            $user = $model->where($kolom, 'ILIKE', '%'.$keyword.'%')
                ->paginate($perPage);
        } else {
            $user = $model->paginate($perPage);
        }

        $user->appends(['per-page' => $perPage]);

        return response()->json([
            'message' => 'Sukses',
            'data_user' => $user,
            'success' => true,
        ], 200);
    }

    // Create data User
    public function create(Request $request)
    {
        $customMessages = [
            'required' => 'Kolom :attribute wajib diisi.',
            'unique' => ':attribute sudah terdaftar di sistem',
            'email' => ':attribute harus berupa alamat email yang valid.',
            'max' => ':attribute tidak boleh lebih dari :max karakter.',
            'confirmed' => 'Konfirmasi :attribute tidak cocok.',
            'min' => ':attribute harus memiliki setidaknya :min karakter.',
            'regex' => ':attribute harus mengandung setidaknya satu huruf kapital dan satu angka.',
            'numeric' => ':attribute harus berupa angka.',
            'digits_between' => ':attribute harus memiliki panjang antara :min dan :max digit.',
        ];

        $request->validate([
            'username' => 'required|max:30|unique:users',
            'password' => 'required|min:8|max:30|regex:/^(?=.*[A-Z])(?=.*\d)/',
            'email' => 'required|email|max:50|unique:users',
            'nama_lengkap' => 'required|max:50|unique:users',
            'role_id' => 'required|numeric|digits_between:1,5',
            'status' => 'required|numeric|digits_between:1,5',
            'role_daerah' => 'numeric|nullable|digits_between:1,5',
            'role_desa' => 'numeric|nullable|digits_between:1,5',
            'role_kelompok' => 'numeric|nullable|digits_between:1,5',
        ], $customMessages);

        $user = new User();
        $user->username = $request->username;
        $user->password = bcrypt($request->password);
        $user->email = $request->email;
        $user->nama_lengkap = $request->nama_lengkap;
        $user->role_id = $request->role_id;
        $user->status = $request->status;
        $user->email_verified_at = now();
        $user->role_daerah = $request->role_daerah;
        $user->role_desa = $request->role_desa;
        $user->role_kelompok = $request->role_kelompok;

        try {
            // Periksa apakah role_id valid
            $role = Role::find($request->role_id);

            if (!$role) {
                return response()->json([
                    'message' => 'Role dengan ID yang diberikan tidak ditemukan',
                    'success' => false,
                ], 404);
            }

            if (!empty($role)) {
                $user->assignRole($role);
            }

            // $user->sendEmailVerificationNotification();
            $user->save();
        } catch (\Exception $exception) {
            return response()->json([
                'message' => 'Gagal menambah data User '.$exception->getMessage(),
                'success' => false,
            ], 500);
        }

        unset($user->password);

        return response()->json([
            'message' => 'Data User berhasil ditambahkan',
            'data_user' => $user,
            'success' => true,
        ], 200);
    }

    // Detail data User
    public function edit(Request $request)
    {
        $request->validate([
            'id' => 'required|numeric|digits_between:1,5',
        ]);

        $user = User::leftjoin('roles', 'roles.id', '=', 'users.role_id')
            ->select([
                'users.id',
                'users.username',
                'users.email',
                'users.nama_lengkap',
                'roles.id as role_id',
                'roles.name as role_nama',
                'users.status',
                'users.role_daerah',
                'users.role_desa',
                'users.role_kelompok',
            ])->where('users.id', '=', $request->id)->first();

        if (!empty($user)) {
            return response()->json([
                'message' => 'Sukses',
                'data_user' => $user,
                'success' => true,
            ], 200);
        }

        return response()->json([
            'message' => 'Data User tidak ditemukan',
            'success' => false,
        ], 200);
    }

    // Update data User
    public function update(Request $request)
    {
        $customMessages = [
            'required' => 'Kolom :attribute wajib diisi.',
            'unique' => ':attribute sudah terdaftar di sistem',
            'email' => ':attribute harus berupa alamat email yang valid.',
            'max' => ':attribute tidak boleh lebih dari :max karakter.',
            'confirmed' => 'Konfirmasi :attribute tidak cocok.',
            'min' => ':attribute harus memiliki setidaknya :min karakter.',
            'regex' => ':attribute harus mengandung setidaknya satu huruf kapital dan satu angka.',
            'numeric' => ':attribute harus berupa angka.',
            'digits_between' => ':attribute harus memiliki panjang antara :min dan :max digit.',
        ];

        $request->validate([
            'id' => 'required|numeric|digits_between:1,5',
            'username' => 'sometimes|required|max:30|unique:users,username,'.$request->id.',id',
            'email' => 'sometimes|required|email|max:50|unique:users,email,'.$request->id.',id',
            'nama_lengkap' => 'sometimes|required|max:50|unique:users,nama_lengkap,'.$request->id.',id',
            'role_id' => 'required|numeric|digits_between:1,5',
            'status' => 'required|numeric|digits_between:1,5',
            'role_daerah' => 'numeric|nullable|digits_between:1,5',
            'role_desa' => 'numeric|nullable|digits_between:1,5',
            'role_kelompok' => 'numeric|nullable|digits_between:1,5',
        ], $customMessages);

        $user = User::where('id', '=', $request->id)->first();

        if (!empty($user)) {
            try {
                $user->update([
                    'username' => $request->username,
                    'email' => $request->email,
                    'nama_lengkap' => $request->nama_lengkap,
                    'role_id' => $request->role_id,
                    'status' => $request->status,
                    'role_daerah' => $request->role_daerah,
                    'role_desa' => $request->role_desa,
                    'role_kelompok' => $request->role_kelompok,
                ]);
            } catch (\Exception $exception) {
                return response()->json([
                    'message' => 'Gagal mengupdate data User'.$exception->getMessage(),
                    'success' => false,
                ], 500);
            }

            unset($user->password);

            return response()->json([
                'message' => 'Data User berhasil diupdate',
                'data_user' => $user,
                'success' => true,
            ], 200);
        }

        return response()->json([
            'message' => 'Data User tidak ditemukan',
            'success' => false,
        ], 200);
    }

    // Delete data User
    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|numeric|digits_between:1,5',
        ]);

        $user = User::where('id', '=', $request->id)
            ->first();

        if (!empty($user)) {
            try {
                $user = User::where('id', '=', $request->id)
                    ->delete();

                return response()->json([
                    'message' => 'Data User berhasil dihapus',
                    'success' => true,
                ], 200);
            } catch (\Exception $exception) {
                return response()->json([
                    'message' => 'Gagal menghapus data User'.$exception->getMessage(),
                    'success' => false,
                ], 500);
            }
        }

        return response()->json([
            'message' => 'Data User tidak ditemukan',
            'success' => false,
        ], 200);
    }
}
