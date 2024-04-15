<?php

namespace App\Http\Controllers\APi;

use App\Http\Controllers\Controller;
use App\Models\dataDaerah;
use App\Models\dataDesa;
use App\Models\dataKelompok;
use App\Models\tblPekerjaan;
use App\Models\tblPesertaDidik;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PesertaDidikController extends Controller
{
    public function list(Request $request)
    {
        $userID = Auth::id();
        $keyword = $request->get('keyword', null);
        $perPage = $request->get('per-page', 10);

        if ($perPage > 100) {
            $perPage = 100;
        }

        $model = tblPesertaDidik::select([
            'peserta_didik.id',
            'peserta_didik.nama_lengkap',
            'peserta_didik.tempat_lahir',
            'peserta_didik.tanggal_lahir',
            tblPesertaDidik::raw('EXTRACT(YEAR FROM AGE(tanggal_lahir)) as umur'),
            'peserta_didik.jenis_kelamin',
            'peserta_didik.status_keluarga',
            'peserta_didik.hoby',
            'peserta_didik.anak_ke',
            'peserta_didik.nama_ayah',
            'ayah.nama_pekerjaan AS pekerjaan_ayah',
            'peserta_didik.nama_ibu',
            'ibu.nama_pekerjaan AS pekerjaan_ibu',
            'peserta_didik.no_telepon_org_tua',
            'peserta_didik.nama_wali',
            'wali.nama_pekerjaan AS pekerjaan_wali',
            'peserta_didik.no_telepon_wali',
            'peserta_didik.alamat',
            'peserta_didik.status_peserta_didik',
            'users.nama_lengkap AS nama_petugas', // Kolom baru untuk nama pengguna
            'tabel_daerah.nama_daerah AS nama_daerah', // Kolom baru untuk nama daerah
            'tabel_desa.nama_desa AS nama_desa', // Kolom baru untuk nama desa
            'tabel_kelompok.nama_kelompok AS nama_kelompok', // Kolom baru untuk nama kelompok
            'peserta_didik.created_at',
        ])
        ->leftJoin('tbl_pekerjaan AS ayah', 'peserta_didik.pekerjaan_ayah', '=', 'ayah.id')
        ->leftJoin('tbl_pekerjaan AS ibu', 'peserta_didik.pekerjaan_ibu', '=', 'ibu.id')
        ->leftJoin('tbl_pekerjaan AS wali', 'peserta_didik.pekerjaan_wali', '=', 'wali.id')
        ->leftJoin('users', 'peserta_didik.add_by_user_id', '=', 'users.id')
        ->leftJoin('tabel_daerah', 'peserta_didik.tmpt_daerah', '=', 'tabel_daerah.id')
        ->leftJoin('tabel_desa', 'peserta_didik.tmpt_desa', '=', 'tabel_desa.id')
        ->leftJoin('tabel_kelompok', 'peserta_didik.tmpt_kelompok', '=', 'tabel_kelompok.id')
        ->where('peserta_didik.add_by_user_id', $userID); // Filter berdasarkan ID pengguna yang sedang login

        $model->where('peserta_didik.status_peserta_didik', '=', true);
        $model->orderByRaw('peserta_didik.created_at DESC NULLS LAST');

        if (!empty($keyword)) {
            $table_peserta_didik = $model->where('nama_lengkap', 'ILIKE', '%'.$keyword.'%')
                ->orWhere('id', 'ILIKE', '%'.$keyword.'%')
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
        $tabel_pekerjaan_ayah = tblPekerjaan::find($request->pekerjaan_ayah);
        $tabel_pekerjaan_ibu = tblPekerjaan::find($request->pekerjaan_ibu);
        $tabel_pekerjaan_wali = tblPekerjaan::find($request->pekerjaan_wali);
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
            'status_keluarga' => 'required',
            'hoby' => 'required|string|max:225',
            'anak_ke' => 'required|integer|digits_between:1,5',
            'nama_ayah' => 'required|string|max:225',
            'pekerjaan_ayah' => 'required|integer|digits_between:1,5',
            'nama_ibu' => 'required|string|max:225',
            'pekerjaan_ibu' => 'required|integer|digits_between:1,5',
            'no_telepon_org_tua' => 'required|string|digits_between:8,13',
            'nama_wali' => 'nullable|string|max:225',
            'pekerjaan_wali' => 'nullable|integer|digits_between:1,5',
            'no_telepon_wali' => 'nullable|string|digits_between:8,13',
            'alamat' => 'required',
            'status_peserta_didik' => 'required',
            'tmpt_daerah' => 'required|integer|digits_between:1,5',
            'tmpt_desa' => 'nullable|integer|digits_between:1,5',
            'tmpt_kelompok' => 'nullable|integer|digits_between:1,5',
        ], $customMessages);

        $table_peserta_didik = new tblPesertaDidik();
        $table_peserta_didik->nama_lengkap = ucwords(strtolower($request->nama_lengkap));
        $table_peserta_didik->tempat_lahir = $request->tempat_lahir;
        $table_peserta_didik->tanggal_lahir = $request->tanggal_lahir;
        $table_peserta_didik->jenis_kelamin = $request->jenis_kelamin;
        $table_peserta_didik->status_keluarga = $request->status_keluarga;
        $table_peserta_didik->hoby = $request->hoby;
        $table_peserta_didik->anak_ke = $request->anak_ke;
        $table_peserta_didik->nama_ayah = $request->nama_ayah;
        $table_peserta_didik->pekerjaan_ayah = $request->pekerjaan_ayah;
        $table_peserta_didik->nama_ibu = $request->nama_ibu;
        $table_peserta_didik->pekerjaan_ibu = $request->pekerjaan_ibu;
        $table_peserta_didik->no_telepon_org_tua = $request->no_telepon_org_tua;
        $table_peserta_didik->nama_wali = $request->nama_wali;
        $table_peserta_didik->pekerjaan_wali = $request->pekerjaan_wali;
        $table_peserta_didik->no_telepon_wali = $request->no_telepon_wali;
        $table_peserta_didik->alamat = $request->alamat;
        $table_peserta_didik->status_peserta_didik = $request->status_peserta_didik;
        $table_peserta_didik->add_by_user_id = $userId;
        $table_peserta_didik->tmpt_daerah = $request->tmpt_daerah;
        $table_peserta_didik->tmpt_desa = $request->tmpt_desa;
        $table_peserta_didik->tmpt_kelompok = $request->tmpt_kelompok;
        $pekerjaan_wali = $request->pekerjaan_wali;
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

            if (!$tabel_pekerjaan_ayah) {
                return response()->json([
                    'message' => 'Pekerjaan tidak ditemukan',
                    'success' => false,
                ], 404);
            }

            if (!$tabel_pekerjaan_ibu) {
                return response()->json([
                    'message' => 'Pekerjaan tidak ditemukan',
                    'success' => false,
                ], 404);
            }

            if (!is_null($pekerjaan_wali)) {
                $tabel_pekerjaan_wali = tblPekerjaan::find($pekerjaan_wali);

                if (!$tabel_pekerjaan_wali) {
                    return response()->json([
                        'message' => 'Pekerjaan wali tidak ditemukan',
                        'success' => false,
                    ], 404);
                }
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
            'peserta_didik.nama_lengkap',
            'peserta_didik.tempat_lahir',
            'peserta_didik.tanggal_lahir',
            tblPesertaDidik::raw('EXTRACT(YEAR FROM AGE(tanggal_lahir)) as umur'),
            'peserta_didik.jenis_kelamin',
            'peserta_didik.status_keluarga',
            'peserta_didik.hoby',
            'peserta_didik.anak_ke',
            'peserta_didik.nama_ayah',
            'ayah.nama_pekerjaan AS pekerjaan_ayah',
            'peserta_didik.nama_ibu',
            'ibu.nama_pekerjaan AS pekerjaan_ibu',
            'peserta_didik.no_telepon_org_tua',
            'peserta_didik.nama_wali',
            'wali.nama_pekerjaan AS pekerjaan_wali',
            'peserta_didik.no_telepon_wali',
            'peserta_didik.alamat',
            'peserta_didik.status_peserta_didik',
            'users.nama_lengkap AS nama_petugas', // Kolom baru untuk nama pengguna
            'tabel_daerah.nama_daerah AS nama_daerah', // Kolom baru untuk nama daerah
            'tabel_desa.nama_desa AS nama_desa', // Kolom baru untuk nama desa
            'tabel_kelompok.nama_kelompok AS nama_kelompok', // Kolom baru untuk nama kelompok
            'peserta_didik.created_at',
        ])
        ->leftJoin('tbl_pekerjaan AS ayah', 'peserta_didik.pekerjaan_ayah', '=', 'ayah.id')
        ->leftJoin('tbl_pekerjaan AS ibu', 'peserta_didik.pekerjaan_ibu', '=', 'ibu.id')
        ->leftJoin('tbl_pekerjaan AS wali', 'peserta_didik.pekerjaan_wali', '=', 'wali.id')
        ->leftJoin('users', 'peserta_didik.add_by_user_id', '=', 'users.id')
        ->leftJoin('tabel_daerah', 'peserta_didik.tmpt_daerah', '=', 'tabel_daerah.id')
        ->leftJoin('tabel_desa', 'peserta_didik.tmpt_desa', '=', 'tabel_desa.id')
        ->leftJoin('tabel_kelompok', 'peserta_didik.tmpt_kelompok', '=', 'tabel_kelompok.id')->first();

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
        $tabel_pekerjaan_ayah = tblPekerjaan::find($request->pekerjaan_ayah);
        $tabel_pekerjaan_ibu = tblPekerjaan::find($request->pekerjaan_ibu);
        $tabel_pekerjaan_wali = tblPekerjaan::find($request->pekerjaan_wali);

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
            'status_keluarga' => 'required',
            'hoby' => 'required|string|max:225',
            'anak_ke' => 'required|integer|digits_between:1,5',
            'nama_ayah' => 'required|string|max:225',
            'pekerjaan_ayah' => 'required|integer|digits_between:1,5',
            'nama_ibu' => 'required|string|max:225',
            'pekerjaan_ibu' => 'required|integer|digits_between:1,5',
            'no_telepon_org_tua' => 'required|string|digits_between:8,13',
            'nama_wali' => 'nullable|string|max:225',
            'pekerjaan_wali' => 'nullable|integer|digits_between:1,5',
            'no_telepon_wali' => 'nullable|string|digits_between:8,13',
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

                if (!$tabel_pekerjaan_ayah) {
                    return response()->json([
                        'message' => 'Pekerjaan tidak ditemukan',
                        'success' => false,
                    ], 404);
                }

                if (!$tabel_pekerjaan_ibu) {
                    return response()->json([
                        'message' => 'Pekerjaan tidak ditemukan',
                        'success' => false,
                    ], 404);
                }

                if (!is_null($request->pekerjaan_wali)) {
                    $tabel_pekerjaan_wali = tblPekerjaan::find($request->pekerjaan_wali);

                    if (!$tabel_pekerjaan_wali) {
                        return response()->json([
                            'message' => 'Pekerjaan wali tidak ditemukan',
                            'success' => false,
                        ], 404);
                    }
                }

                if (!$userId) {
                    return response()->json([
                        'message' => 'Data Petugas tidak ditemukan',
                        'success' => false,
                    ], 404);
                }

                $table_peserta_didik->update([
                    'id' => $request->id,
                    'nama_lengkap' => ucwords(strtolower($request->nama_lengkap)),
                    'tempat_lahir' => $request->tempat_lahir,
                    'tanggal_lahir' => $request->tanggal_lahir,
                    'jenis_kelamin' => $request->jenis_kelamin,
                    'status_keluarga' => $request->status_keluarga,
                    'hoby' => $request->hoby,
                    'anak_ke' => $request->anak_ke,
                    'nama_ayah' => $request->nama_ayah,
                    'pekerjaan_ayah' => $request->pekerjaan_ayah,
                    'nama_ibu' => $request->nama_ibu,
                    'pekerjaan_ibu' => $request->pekerjaan_ibu,
                    'no_telepon_org_tua' => $request->no_telepon_org_tua,
                    'nama_wali' => $request->nama_wali,
                    'pekerjaan_wali' => $request->pekerjaan_wali,
                    'no_telepon_wali' => $request->no_telepon_wali,
                    'alamat' => $request->alamat,
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
