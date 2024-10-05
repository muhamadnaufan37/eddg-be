<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class RolesController extends Controller
{
    public function list_data_roles()
    {
        $roles = Role::select(['id', 'name']) // Include the 'id' column
        ->groupBy('id', 'name') // Group by both 'id' and 'name'
        ->orderBy('name') // Order by the role name
        ->get();

        return response()->json([
            'message' => 'Sukses',
            'data_role' => $roles,
            'success' => true,
        ], 200);
    }

    // List data Role
    public function list(Request $request)
    {
        $keyword = $request->get('keyword', null);
        $perPage = $request->get('per-page', 10);
        $kolom = $request->get('kolom', null);

        if ($perPage > 100) {
            $perPage = 100;
        }

        $model = Role::select([
            'id',
            'name',
            'guard_name',
            'description',
            DB::raw('(SELECT COUNT(*) FROM users WHERE users.role_id = roles.id) as users_count'),
        ]);

        if (!empty($keyword) && empty($kolom)) {
            $model->where(function ($q) use ($keyword) {
                $q->where('name', 'LIKE', '%'.$keyword.'%')
                ->orWhere('description', 'LIKE', '%'.$keyword.'%');
            });
        } elseif (!empty($keyword) && !empty($kolom)) {
            if ($kolom == 'name') {
                $kolom = 'name';
            } else {
                $kolom = 'description';
            }

            $model->where($kolom, 'LIKE', '%'.$keyword.'%');
        }

        $role = $model->paginate($perPage);

        $role->appends(['per-page' => $perPage]);

        return response()->json([
            'message' => 'Sukses',
            'data_role' => $role,
            'success' => true,
        ], 200);
    }

    // Create data Role
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
            'name' => 'required|max:225|unique:roles',
            'description' => 'required|max:225',
        ], $customMessages);

        $role = new Role();
        $role->name = $request->name;
        $role->guard_name = 'api';
        $role->description = $request->description;
        try {
            $role->save();
        } catch (\Exception $exception) {
            return response()->json([
                'message' => 'Gagal menambah data Role'.$exception->getMessage(),
                'success' => false,
            ], 500);
        }

        unset($role->created_at, $role->updated_at);

        return response()->json([
            'message' => 'Data Role berhasil ditambahkan',
            'data_role' => $role,
            'success' => true,
        ], 200);
    }

    // Detail data Role
    public function edit(Request $request)
    {
        $request->validate([
            'id' => 'required|numeric|digits_between:1,5',
        ]);

        $role = Role::where('id', '=', $request->id)->first();

        unset($role->created_at, $role->updated_at);

        if (!empty($role)) {
            return response()->json([
                'message' => 'Sukses',
                'data_role' => $role,
                'success' => true,
            ], 200);
        }

        return response()->json([
            'message' => 'Data Role tidak ditemukan',
            'success' => false,
        ], 200);
    }

    // Update data Role
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
            'name' => 'required|max:225',
            'description' => 'required|max:225',
        ], $customMessages);

        $role = Role::where('id', '=', $request->id)
            ->first();

        if (!empty($role)) {
            try {
                $role->update([
                    'id' => $request->id,
                    'name' => $request->name,
                    'description' => $request->description,
                ]);
            } catch (\Exception $exception) {
                return response()->json([
                    'message' => 'Gagal mengupdate data Role'.$exception->getMessage(),
                    'success' => false,
                ], 500);
            }

            return response()->json([
                'message' => 'Data Role berhasil diupdate',
                'data_role' => $role,
                'success' => true,
            ], 200);
        }

        return response()->json([
            'message' => 'Data Role tidak ditemukan',
            'success' => false,
        ], 200);
    }

    // Delete data Role
    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|numeric|digits_between:1,5',
        ]);

        $role = Role::where('id', '=', $request->id)
            ->first();

        if (!empty($role)) {
            try {
                $role = Role::where('id', '=', $request->id)
                    ->delete();

                return response()->json([
                    'message' => 'Data Role berhasil dihapus',
                    'success' => true,
                ], 200);
            } catch (\Exception $exception) {
                return response()->json([
                    'message' => 'Gagal menghapus data Role'.$exception->getMessage(),
                    'success' => false,
                ], 500);
            }
        }

        return response()->json([
            'message' => 'Data Role tidak ditemukan',
            'success' => false,
        ], 200);
    }
}
