<?php

namespace App\Http\Controllers\APi;

use App\Http\Controllers\Controller;
use App\Models\dataDaerah;
use App\Models\dataDesa;
use App\Models\dataKelompok;
use App\Models\tblCppdb;
use App\Models\tblPengajar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Jenssegers\Agent\Agent;
use App\Models\logs;

class PengajarPesertaController extends Controller
{
    public function data_pengajar()
    {
        // Ambil role_id dan user_id dari pengguna yang sedang login
        $user = auth()->user();
        $roleID = $user->role_id;
        $userID = $user->id;

        $query = tblPengajar::select(['id', 'nama_pengajar'])
            ->where('status_pengajar', true) // Memfilter hasil berdasarkan status_pengajar
            ->orderBy('nama_pengajar');

        // Tambahkan filter berdasarkan add_by_user_id jika roleID bukan 1
        if ($roleID != 1) {
            $query->where('add_by_user_id', $userID);
        }

        $tabel_data_pengajar = $query->get();

        return response()->json([
            'message' => 'Sukses',
            'data_pengajar' => $tabel_data_pengajar,
            'success' => true,
        ], 200);
    }

    public function list(Request $request)
    {
        $roleId = $request->user()->role_id;
        $keyword = $request->get('keyword', null);
        $perPage = $request->get('per-page', 10);
        $statusMengajar = $request->get('status_pengajar', null);

        if ($perPage > 100) {
            $perPage = 100;
        }

        $model = tblPengajar::select([
            'pengajar.id',
            'pengajar.nama_pengajar',
            'pengajar.status_pengajar',
            'users.nama_lengkap AS nama_user', // Kolom baru untuk nama pengguna
            'tabel_daerah.nama_daerah AS nama_daerah', // Kolom baru untuk nama daerah
            'tabel_desa.nama_desa AS nama_desa', // Kolom baru untuk nama desa
            'tabel_kelompok.nama_kelompok AS nama_kelompok', // Kolom baru untuk nama kelompok
            'pengajar.created_at',
        ])
            ->leftJoin('users', 'pengajar.add_by_user_id', '=', 'users.id')
            ->leftJoin('tabel_daerah', 'pengajar.tmpt_daerah', '=', 'tabel_daerah.id')
            ->leftJoin('tabel_desa', 'pengajar.tmpt_desa', '=', 'tabel_desa.id')
            ->leftJoin('tabel_kelompok', 'pengajar.tmpt_kelompok', '=', 'tabel_kelompok.id');

        // $model->where('pengajar.status_pengajar', '=', true);
        // Apply orderByRaw before executing the query
        $model->orderByRaw('pengajar.created_at IS NULL, pengajar.created_at DESC');

        if ($roleId != 1) {
            // Jika role_id bukan 1, tambahkan kondisi id_user
            $model->where('pengajar.add_by_user_id', $request->user()->id);
        }

        if (!is_null($statusMengajar)) {
            $model->where('pengajar.status_pengajar', '=', $statusMengajar);
        }

        if (!empty($keyword)) {
            $model->where(function ($q) use ($keyword) {
                $q->where('pengajar.nama_pengajar', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('tabel_daerah.nama_daerah', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('tabel_desa.nama_desa', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('tabel_kelompok.nama_kelompok', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('users.nama_lengkap', 'LIKE', '%' . $keyword . '%');
            });
        }

        $table_pengajar = $model->paginate($perPage);
        $table_pengajar->appends(['per-page' => $perPage]);

        return response()->json([
            'message' => 'Sukses',
            'data_pengajar' => $table_pengajar,
            'success' => true,
        ], 200);
    }

    public function create(Request $request)
    {
        $tabel_daerah = dataDaerah::find($request->tmpt_daerah);
        $tabel_desa = dataDesa::find($request->tmpt_desa);
        $tabel_kelompok = dataKelompok::find($request->tmpt_kelompok);
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
            'nama_pengajar' => 'required|max:225|unique:pengajar',
            'status_pengajar' => 'required',
            'tmpt_daerah' => 'required|integer|digits_between:1,5',
            'tmpt_desa' => 'nullable|integer|digits_between:1,5',
            'tmpt_kelompok' => 'nullable|integer|digits_between:1,5',
        ], $customMessages);

        $table_pengajar = new tblPengajar();
        $table_pengajar->nama_pengajar = $request->nama_pengajar;
        $table_pengajar->status_pengajar = $request->status_pengajar;
        $table_pengajar->add_by_user_id = $userId;
        $table_pengajar->tmpt_daerah = $request->tmpt_daerah;
        $table_pengajar->tmpt_desa = $request->tmpt_desa;
        $table_pengajar->tmpt_kelompok = $request->tmpt_kelompok;
        try {
            if (!$tabel_daerah) {
                return response()->json([
                    'message' => 'Daerah tidak ditemukan',
                    'success' => false,
                ], 404);
            }

            if (!$tabel_desa) {
                return response()->json([
                    'message' => 'Desa tidak ditemukan',
                    'success' => false,
                ], 404);
            }

            if (!$tabel_kelompok) {
                return response()->json([
                    'message' => 'Kelompok tidak ditemukan',
                    'success' => false,
                ], 404);
            }

            if (!$userId) {
                return response()->json([
                    'message' => 'Data Petugas tidak ditemukan',
                    'success' => false,
                ], 404);
            }

            $table_pengajar->save();

            $logAccount = [
                'user_id' => $userId,
                'ip_address' => $request->ip(),
                'aktifitas' => 'Create Data Pengajar - [' . $table_pengajar->nama_pengajar . ']',
                'status_logs' => 'successfully',
                'browser' => $agent->browser(),
                'os' => $agent->platform(),
                'device' => $agent->device(),
                'engine_agent' => $request->header('user-agent'),
            ];
            logs::create($logAccount);
        } catch (\Exception $exception) {
            return response()->json([
                'message' => 'Gagal menambah Data Pengajar' . $exception->getMessage(),
                'success' => false,
            ], 500);
        }

        unset($table_pengajar->created_at, $table_pengajar->updated_at);

        return response()->json([
            'message' => 'Data Pengajar berhasil ditambahkan',
            'data_pengajar' => $table_pengajar,
            'success' => true,
        ], 200);
    }

    public function edit(Request $request)
    {
        $request->validate([
            'id' => 'required|numeric|digits_between:1,5',
        ]);

        $table_pengajar = tblPengajar::select([
            'pengajar.id',
            'pengajar.nama_pengajar',
            'pengajar.status_pengajar',
            'pengajar.tmpt_daerah',
            'pengajar.tmpt_desa',
            'pengajar.tmpt_kelompok',
            'users.nama_lengkap AS nama_user', // Kolom baru untuk nama pengguna
            'tabel_daerah.nama_daerah AS nama_daerah', // Kolom baru untuk nama daerah
            'tabel_desa.nama_desa AS nama_desa', // Kolom baru untuk nama desa
            'tabel_kelompok.nama_kelompok AS nama_kelompok', // Kolom baru untuk nama kelompok
            'pengajar.created_at',
        ])
            ->leftJoin('users', 'pengajar.add_by_user_id', '=', 'users.id')
            ->leftJoin('tabel_daerah', 'pengajar.tmpt_daerah', '=', 'tabel_daerah.id')
            ->leftJoin('tabel_desa', 'pengajar.tmpt_desa', '=', 'tabel_desa.id')
            ->leftJoin('tabel_kelompok', 'pengajar.tmpt_kelompok', '=', 'tabel_kelompok.id')
            ->where('pengajar.id', $request->id)->first();

        unset($table_pengajar->created_at, $table_pengajar->updated_at);

        if (!empty($table_pengajar)) {
            return response()->json([
                'message' => 'Sukses',
                'data_pengajar' => $table_pengajar,
                'success' => true,
            ], 200);
        }

        return response()->json([
            'message' => 'Data tidak ditemukan',
            'success' => false,
        ], 200);
    }

    public function update(Request $request)
    {
        $userId = Auth::id();
        $agent = new Agent();
        $tabel_daerah = dataDaerah::find($request->tmpt_daerah);
        $tabel_desa = dataDesa::find($request->tmpt_desa);
        $tabel_kelompok = dataKelompok::find($request->tmpt_kelompok);

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
            'nama_pengajar' => 'sometimes|required|max:225|string|unique:pengajar,nama_pengajar,' . $request->id . ',id',
            'status_pengajar' => 'required',
            'tmpt_daerah' => 'required|integer|digits_between:1,5',
            'tmpt_desa' => 'nullable|integer|digits_between:1,5',
            'tmpt_kelompok' => 'nullable|integer|digits_between:1,5',
        ], $customMessages);

        $table_pengajar = tblPengajar::where('id', '=', $request->id)
            ->first();

        if (!empty($table_pengajar)) {
            try {
                if (!$tabel_daerah) {
                    return response()->json([
                        'message' => 'Daerah tidak ditemukan',
                        'success' => false,
                    ], 404);
                }

                if (!$tabel_desa) {
                    return response()->json([
                        'message' => 'Desa tidak ditemukan',
                        'success' => false,
                    ], 404);
                }

                if (!$tabel_kelompok) {
                    return response()->json([
                        'message' => 'Kelompok tidak ditemukan',
                        'success' => false,
                    ], 404);
                }

                $originalData = $table_pengajar->getOriginal();

                $table_pengajar->fill([
                    'nama_pengajar' => $request->nama_pengajar,
                    'status_pengajar' => $request->status_pengajar,
                    'tmpt_daerah' => $request->tmpt_daerah,
                    'tmpt_desa' => $request->tmpt_desa,
                    'tmpt_kelompok' => $request->tmpt_kelompok,
                ]);

                $updatedFields = [];
                foreach ($table_pengajar->getDirty() as $field => $newValue) {
                    $oldValue = $originalData[$field] ?? null; // Ambil nilai lama
                    $updatedFields[] = "$field: [$oldValue] -> [$newValue]";
                }

                $table_pengajar->save();

                $logAccount = [
                    'user_id' => $userId,
                    'ip_address' => $request->ip(),
                    'aktifitas' => 'Update Data Pengajar',
                    'status_logs' => 'successfully',
                    'browser' => $agent->browser(),
                    'os' => $agent->platform(),
                    'device' => $agent->device(),
                    'engine_agent' => $request->header('user-agent'),
                    'updated_fields' => json_encode($updatedFields),
                ];
                logs::create($logAccount);
            } catch (\Exception $exception) {
                return response()->json([
                    'message' => 'Gagal mengupdate Data' . $exception->getMessage(),
                    'success' => false,
                ], 500);
            }

            return response()->json([
                'message' => 'Data berhasil diupdate',
                'data_pengajar' => $table_pengajar,
                'success' => true,
            ], 200);
        }

        return response()->json([
            'message' => 'Data tidak ditemukan',
            'success' => false,
        ], 200);
    }

    public function delete(Request $request)
    {
        $userId = Auth::id();
        $agent = new Agent();

        $request->validate([
            'id' => 'required|numeric|digits_between:1,5',
        ]);

        $table_pengajar = tblPengajar::where('id', $request->id)->first();

        if ($table_pengajar) {
            $existsInCppdb = tblCppdb::where('id_pengajar', $request->id)->exists();

            if ($existsInCppdb) {
                // Jika data digunakan di tabel lain, cegah penghapusan
                return response()->json([
                    'message' => 'Data Pengajar tidak dapat dihapus karena sudah terdaftar dan digunakan di tabel lain',
                    'success' => false,
                ], 409);
            }

            try {
                $deletedData = $table_pengajar->toArray();

                $table_pengajar->delete();

                $logAccount = [
                    'user_id' => $userId,
                    'ip_address' => $request->ip(),
                    'aktifitas' => 'Delete Data Pengajar - [' . $deletedData['id'] . '] - [' . $deletedData['nama_pengajar'] . ']',
                    'status_logs' => 'successfully',
                    'browser' => $agent->browser(),
                    'os' => $agent->platform(),
                    'device' => $agent->device(),
                    'engine_agent' => $request->header('user-agent'),
                ];
                logs::create($logAccount);

                return response()->json([
                    'message' => 'Data berhasil dihapus',
                    'success' => true,
                ], 200);
            } catch (\Exception $exception) {
                return response()->json([
                    'message' => 'Gagal menghapus Data: ' . $exception->getMessage(),
                    'success' => false,
                ], 500);
            }
        }

        return response()->json([
            'message' => 'Data tidak ditemukan',
            'success' => false,
        ], 404);
    }
}
