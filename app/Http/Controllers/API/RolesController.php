<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;
use Illuminate\Support\Facades\Auth;
use App\Models\logs;

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
                $q->where('name', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('description', 'LIKE', '%' . $keyword . '%');
            });
        } elseif (!empty($keyword) && !empty($kolom)) {
            if ($kolom == 'name') {
                $kolom = 'name';
            } else {
                $kolom = 'description';
            }

            $model->where($kolom, 'LIKE', '%' . $keyword . '%');
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
        $userId = Auth::id();
        $agent = new Agent();

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
        $role->code_uuid = Str::uuid()->toString();
        $role->name = $request->name;
        $role->guard_name = 'api';
        $role->description = $request->description;
        try {
            $role->save();

            $logAccount = [
                'user_id' => $userId,
                'ip_address' => $request->ip(),
                'aktifitas' => 'Create Roles - [' . $role->id . '] - [' . $role->name . '] - [' . $role->guard_name . ']',
                'status_logs' => 'successfully',
                'browser' => $agent->browser(),
                'os' => $agent->platform(),
                'device' => $agent->device(),
                'engine_agent' => $request->header('user-agent'),
            ];
            logs::create($logAccount);
        } catch (\Exception $exception) {
            return response()->json([
                'message' => 'Gagal menambah data Role' . $exception->getMessage(),
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
        $userId = Auth::id();
        $agent = new Agent();

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

        if (!$role) {
            return response()->json([
                'message' => 'Data tidak ditemukan',
                'success' => false,
            ], 404);
        }

        try {

            $originalData = $role->getOriginal();

            $role->fill([
                'name' => $request->name,
                'description' => $request->description,
            ]);

            $updatedFields = [];
            foreach ($role->getDirty() as $field => $newValue) {
                $oldValue = $originalData[$field] ?? null; // Ambil nilai lama
                $updatedFields[] = "$field: [$oldValue] -> [$newValue]";
            }

            $role->save();

            $logAccount = [
                'user_id' => $userId,
                'ip_address' => $request->ip(),
                'aktifitas' => 'Update Roles - [' . $role->id . '] - [' . $role->name . '] - [' . $role->guard_name . ']',
                'status_logs' => 'successfully',
                'browser' => $agent->browser(),
                'os' => $agent->platform(),
                'device' => $agent->device(),
                'engine_agent' => $request->header('user-agent'),
            ];
            logs::create($logAccount);

            return response()->json([
                'message' => 'Data Role berhasil diupdate',
                'data_role' => $role,
                'success' => true,
            ], 200);
        } catch (\Exception $exception) {
            return response()->json([
                'message' => 'Gagal mengupdate data Role' . $exception->getMessage(),
                'success' => false,
            ], 500);
        }
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
                    'message' => 'Gagal menghapus data Role' . $exception->getMessage(),
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
