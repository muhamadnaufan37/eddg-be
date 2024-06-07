<?php

namespace App\Http\Controllers\APi;

use App\Http\Controllers\Controller;
use App\Models\dataDaerah;
use App\Models\dataDesa;
use App\Models\dataKelompok;
use App\Models\tblPesertaDidik;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PesertaDidikController extends Controller
{
    public function data_all_peserta_didik_aktif()
    {
        // Ambil role_id dan user_id dari pengguna yang sedang login
        $user = auth()->user();
        $roleID = $user->role_id;
        $userID = $user->id;

        $table_peserta_didik = tblPesertaDidik::select(['id', 'nama_lengkap'])
            ->where('status_peserta_didik', true);

        // Tampilkan semua data jika role_id adalah 1, atau berdasarkan add_by_user_id jika tidak
        if ($roleID != 1) {
            $table_peserta_didik = $table_peserta_didik->where('add_by_user_id', $userID);
        }

        $table_peserta_didik = $table_peserta_didik
            ->groupBy('id', 'nama_lengkap')
            ->orderBy('nama_lengkap')
            ->get();

        return response()->json([
            'message' => 'Sukses',
            'data_peserta_didik' => $table_peserta_didik,
            'success' => true,
        ], 200);
    }

    public function list(Request $request)
    {
        $keyword = $request->get('keyword', null);
        $perPage = $request->get('per-page', 10);

        if ($perPage > 100) {
            $perPage = 100;
        }

        $model = tblPesertaDidik::select([
            'peserta_didik.id',
            'peserta_didik.nama_lengkap',
            'peserta_didik.status_peserta_didik',
            'users.nama_lengkap AS nama_petugas', // Kolom baru untuk nama pengguna
            'tabel_daerah.nama_daerah AS nama_daerah', // Kolom baru untuk nama daerah
            'tabel_desa.nama_desa AS nama_desa', // Kolom baru untuk nama desa
            'tabel_kelompok.nama_kelompok AS nama_kelompok', // Kolom baru untuk nama kelompok
            'peserta_didik.created_at',
        ])
        ->leftJoin('users', 'peserta_didik.add_by_user_id', '=', 'users.id')
        ->leftJoin('tabel_daerah', 'peserta_didik.tmpt_daerah', '=', 'tabel_daerah.id')
        ->leftJoin('tabel_desa', 'peserta_didik.tmpt_desa', '=', 'tabel_desa.id')
        ->leftJoin('tabel_kelompok', 'peserta_didik.tmpt_kelompok', '=', 'tabel_kelompok.id');

        $model->orderByRaw('peserta_didik.created_at DESC NULLS LAST');

        if (!empty($keyword)) {
            $model->where(function ($query) use ($keyword) {
                $query->where('peserta_didik.nama_lengkap', 'ILIKE', '%'.$keyword.'%')
                    ->orWhere('peserta_didik.id', 'ILIKE', '%'.$keyword.'%');
            });
        } else {
            $table_peserta_didik = $model->paginate($perPage);
        }

        $table_peserta_didik->appends(['per-page' => $perPage]);

        return response()->json([
            'message' => 'Sukses',
            'data_peserta_didik' => $table_peserta_didik,
            'success' => true,
        ], 200);
    }

    public function listByKbm(Request $request)
    {
        $user = $request->user();
        $keyword = $request->get('keyword', null);
        $perPage = $request->get('per-page', 10);
        $dataDaerah = $request->get('data-daerah', $user->role_daerah);
        $dataDesa = $request->get('data-desa', $user->role_desa);
        $dataKelompok = $request->get('data-kelompok', $user->role_kelompok);

        if ($perPage > 100) {
            $perPage = 100;
        }

        $model = tblPesertaDidik::select([
            'peserta_didik.id',
            'peserta_didik.nama_lengkap',
            'peserta_didik.status_peserta_didik',
            'users.nama_lengkap AS nama_petugas', // Kolom baru untuk nama pengguna
            'tabel_daerah.nama_daerah AS nama_daerah', // Kolom baru untuk nama daerah
            'tabel_desa.nama_desa AS nama_desa', // Kolom baru untuk nama desa
            'tabel_kelompok.nama_kelompok AS nama_kelompok', // Kolom baru untuk nama kelompok
            'peserta_didik.created_at',
        ])
        ->leftJoin('users', 'peserta_didik.add_by_user_id', '=', 'users.id')
        ->leftJoin('tabel_daerah', 'peserta_didik.tmpt_daerah', '=', 'tabel_daerah.id')
        ->leftJoin('tabel_desa', 'peserta_didik.tmpt_desa', '=', 'tabel_desa.id')
        ->leftJoin('tabel_kelompok', 'peserta_didik.tmpt_kelompok', '=', 'tabel_kelompok.id');

        if (!is_null($dataDaerah)) {
            $model->where('tabel_daerah.id', '=', $dataDaerah);
        }

        if (!is_null($dataDesa)) {
            $model->where('tabel_desa.id', '=', $dataDesa);
        }

        if (!is_null($dataKelompok)) {
            $model->where('tabel_kelompok.id', '=', $dataKelompok);
        }

        $model->orderByRaw('peserta_didik.created_at DESC NULLS LAST');

        if (!empty($keyword)) {
            $table_peserta_didik = $model->where('peserta_didik.nama_lengkap', 'ILIKE', '%'.$keyword.'%')
                ->orWhere('peserta_didik.id', 'ILIKE', '%'.$keyword.'%')
                ->paginate($perPage);
        } else {
            $table_peserta_didik = $model->paginate($perPage);
        }

        $table_peserta_didik->appends(['per-page' => $perPage]);

        return response()->json([
            'message' => 'Sukses',
            'data_peserta_didik' => $table_peserta_didik,
            'success' => true,
        ], 200);
    }

    public function create(Request $request)
    {
        $tabel_daerah = dataDaerah::find($request->tmpt_daerah);
        $tabel_desa = dataDesa::find($request->tmpt_desa);
        $tabel_kelompok = dataKelompok::find($request->tmpt_kelompok);
        $userId = Auth::id();

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
            'nama_lengkap' => 'required|max:225|unique:peserta_didik',
            'tempat_lahir' => 'required|string',
            'tanggal_lahir' => 'required|date',
            'jenis_kelamin' => 'required|in:LAKI-LAKI,PEREMPUAN',
            'hoby' => 'required|string|max:225',
            'nama_ortu' => 'required|string|max:225',
            'no_telepon_ortu' => 'required|string|digits_between:8,13',
            'alamat' => 'required',
            'status_peserta_didik' => 'required',
            'tmpt_daerah' => 'required|integer|digits_between:1,5',
            'tmpt_desa' => 'required|integer|digits_between:1,5',
            'tmpt_kelompok' => 'required|integer|digits_between:1,5',
            'nomor_induk_santri' => 'unique:peserta_didik',
        ], $customMessages);

        $table_peserta_didik = new tblPesertaDidik();
        $table_peserta_didik->nama_lengkap = $request->nama_lengkap;
        $table_peserta_didik->tempat_lahir = ucwords(strtolower($request->tempat_lahir));
        $table_peserta_didik->tanggal_lahir = $request->tanggal_lahir;
        $table_peserta_didik->jenis_kelamin = $request->jenis_kelamin;
        $table_peserta_didik->hoby = $request->hoby;
        $table_peserta_didik->nama_ortu = $request->nama_ortu;
        $table_peserta_didik->no_telepon_ortu = $request->no_telepon_ortu;
        $table_peserta_didik->alamat = ucwords(strtolower($request->alamat));
        $table_peserta_didik->status_peserta_didik = $request->status_peserta_didik;
        $table_peserta_didik->add_by_user_id = $userId;
        $table_peserta_didik->tmpt_daerah = $request->tmpt_daerah;
        $table_peserta_didik->tmpt_desa = $request->tmpt_desa;
        $table_peserta_didik->tmpt_kelompok = $request->tmpt_kelompok;
        // Generate nomor induk santri dengan nomor random 4 digit
        $random_number = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT); // Menghasilkan nomor random 4 digit
        $table_peserta_didik->nomor_induk_santri = $request->tmpt_daerah.$request->tmpt_desa.$request->tmpt_kelompok.$userId.$random_number.'313354';
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

            $table_peserta_didik->save();
        } catch (\Exception $exception) {
            return response()->json([
                'message' => 'Gagal menambah Data Peserta Didik'.$exception->getMessage(),
                'success' => false,
            ], 500);
        }

        unset($table_peserta_didik->created_at, $table_peserta_didik->updated_at);

        return response()->json([
            'message' => 'Data Peserta Didik berhasil ditambahkan',
            'data_peserta_didik' => $table_peserta_didik,
            'success' => true,
        ], 200);
    }

    public function edit(Request $request)
    {
        $request->validate([
            'id' => 'required|numeric|digits_between:1,5',
        ]);

        $table_peserta_didik = tblPesertaDidik::select([
            'peserta_didik.id',
            'peserta_didik.nomor_induk_santri',
            'peserta_didik.nama_lengkap',
            'peserta_didik.tempat_lahir',
            'peserta_didik.tanggal_lahir',
            tblPesertaDidik::raw('EXTRACT(YEAR FROM AGE(tanggal_lahir)) as umur'),
            'peserta_didik.jenis_kelamin',
            'peserta_didik.hoby',
            'peserta_didik.nama_ortu',
            'peserta_didik.no_telepon_ortu',
            'peserta_didik.alamat',
            'peserta_didik.status_peserta_didik',
            'peserta_didik.tmpt_daerah',
            'peserta_didik.tmpt_desa',
            'peserta_didik.tmpt_kelompok',
            'users.nama_lengkap AS nama_petugas', // Kolom baru untuk nama pengguna
            'tabel_daerah.nama_daerah AS nama_daerah', // Kolom baru untuk nama daerah
            'tabel_desa.nama_desa AS nama_desa', // Kolom baru untuk nama desa
            'tabel_kelompok.nama_kelompok AS nama_kelompok', // Kolom baru untuk nama kelompok
            'peserta_didik.created_at',
        ])
        ->leftJoin('users', 'peserta_didik.add_by_user_id', '=', 'users.id')
        ->leftJoin('tabel_daerah', 'peserta_didik.tmpt_daerah', '=', 'tabel_daerah.id')
        ->leftJoin('tabel_desa', 'peserta_didik.tmpt_desa', '=', 'tabel_desa.id')
        ->leftJoin('tabel_kelompok', 'peserta_didik.tmpt_kelompok', '=', 'tabel_kelompok.id')
        ->where('peserta_didik.id', $request->id)
        ->first();

        unset($table_peserta_didik->created_at, $table_peserta_didik->updated_at);

        if (!empty($table_peserta_didik)) {
            return response()->json([
                'message' => 'Data Peserta Ditemukan',
                'data_peserta_didik' => $table_peserta_didik,
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
        $tabel_daerah = dataDaerah::find($request->tmpt_daerah);
        $tabel_desa = dataDesa::find($request->tmpt_desa);
        $tabel_kelompok = dataKelompok::find($request->tmpt_kelompok);
        $userId = Auth::id();

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
            'nama_lengkap' => 'sometimes|required|max:225|string|unique:peserta_didik,nama_lengkap,'.$request->id.',id',
            'tempat_lahir' => 'required|string',
            'tanggal_lahir' => 'required|date',
            'jenis_kelamin' => 'required|in:LAKI-LAKI,PEREMPUAN',
            'hoby' => 'required|string|max:225',
            'nama_ortu' => 'required|string|max:225',
            'no_telepon_ortu' => 'required|string|digits_between:8,13',
            'alamat' => 'required',
            'status_peserta_didik' => 'required',
            'tmpt_daerah' => 'required|integer|digits_between:1,5',
            'tmpt_desa' => 'required|integer|digits_between:1,5',
            'tmpt_kelompok' => 'required|integer|digits_between:1,5',
        ], $customMessages);

        $table_peserta_didik = tblPesertaDidik::where('id', '=', $request->id)
            ->first();

        if (!empty($table_peserta_didik)) {
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

                $table_peserta_didik->update([
                    'id' => $request->id,
                    'nama_lengkap' => $request->nama_lengkap,
                    'tempat_lahir' => ucwords(strtolower($request->tempat_lahir)),
                    'tanggal_lahir' => $request->tanggal_lahir,
                    'jenis_kelamin' => $request->jenis_kelamin,
                    'hoby' => $request->hoby,
                    'nama_ortu' => $request->nama_ortu,
                    'no_telepon_ortu' => $request->no_telepon_ortu,
                    'alamat' => ucwords(strtolower($request->alamat)),
                    'status_peserta_didik' => $request->status_peserta_didik,
                    'tmpt_daerah' => $request->tmpt_daerah,
                    'tmpt_desa' => $request->tmpt_desa,
                    'tmpt_kelompok' => $request->tmpt_kelompok,
                ]);
            } catch (\Exception $exception) {
                return response()->json([
                    'message' => 'Gagal mengupdate Data'.$exception->getMessage(),
                    'success' => false,
                ], 500);
            }

            return response()->json([
                'message' => 'Data Peserta Didik berhasil diupdate',
                'data_peserta_didik' => $table_peserta_didik,
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
        $request->validate([
            'id' => 'required|numeric|digits_between:1,5',
        ]);

        $table_peserta_didik = tblPesertaDidik::where('id', '=', $request->id)
            ->first();

        if (!empty($table_peserta_didik)) {
            try {
                $table_peserta_didik = tblPesertaDidik::where('id', '=', $request->id)
                    ->delete();

                return response()->json([
                    'message' => 'Data Peserta Didik berhasil dihapus',
                    'success' => true,
                ], 200);
            } catch (\Exception $exception) {
                return response()->json([
                    'message' => 'Gagal menghapus Data'.$exception->getMessage(),
                    'success' => false,
                ], 500);
            }
        }

        return response()->json([
            'message' => 'Data tidak ditemukan',
            'success' => false,
        ], 200);
    }
}
