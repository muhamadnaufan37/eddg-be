<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WalletKas;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class WalletKasController extends Controller
{
    public function getDataTahun(Request $request)
    {
        $roleId = $request->user()->role_id;

        $walletKasQuery = WalletKas::selectRaw('EXTRACT(YEAR FROM tgl_transaksi) as tahun')
            ->groupByRaw('EXTRACT(YEAR FROM tgl_transaksi)')
            ->orderByRaw('EXTRACT(YEAR FROM tgl_transaksi)');

        if ($roleId != 1) {
            // Jika role_id bukan 1, tambahkan kondisi id_user
            $walletKasQuery->where('id_user', $request->user()->id);
        }

        $walletKas = $walletKasQuery->get();

        return response()->json([
            'message' => 'Sukses',
            'data_wallet_kas' => $walletKas,
            'success' => true,
        ], 200);
    }

    public function getDataTotal()
    {
        try {
            // Mengambil total jumlah pemasukan
            $totalPemasukan = WalletKas::where('jenis_transaksi', 'PEMASUKAN')->sum('jumlah');
            $totalPengeluaran = WalletKas::where('jenis_transaksi', 'PENGELUARAN')->sum('jumlah');

            // Menghitung target pemasukan tahunan dari total pemasukan ditambah 10%
            $targetPemasukanTahunan = $totalPemasukan + ($totalPemasukan * 0.10);

            return response()->json([
                'message' => 'Total Pemasukan berhasil diambil',
                'total_pemasukan' => intval($totalPemasukan),
                'total_pengeluaran' => intval($totalPengeluaran),
                'target_pemasukan_tahunan' => intval($targetPemasukanTahunan),
                'success' => true,
            ], 200);
        } catch (\Exception $exception) {
            return response()->json([
                'message' => 'Gagal mengambil data: '.$exception->getMessage(),
                'success' => false,
            ], 500);
        }
    }

    public function totalSaldoPemasukan(Request $request)
    {
        $roleId = $request->user()->role_id;
        $request->validate([
            'tahun' => 'required|integer',
        ]);

        // Mendapatkan tahun dari permintaan
        $tahun = $request->tahun;

        try {
            if (!$tahun) {
                return response()->json([
                    'message' => 'Tahun harus diisi',
                    'success' => false,
                ], 404);
            }

            // Mendapatkan tanggal awal dan akhir tahun yang diberikan
            $startOfYear = Carbon::create($tahun, 1, 1)->startOfYear()->toDateString();
            $endOfYear = Carbon::create($tahun, 12, 31)->endOfYear()->toDateString();

            $bulanDataPemasukan = WalletKas::select(
                DB::raw('EXTRACT(MONTH FROM tgl_transaksi) as bulan'),
                DB::raw('COALESCE(SUM(jumlah), 0) as total_jumlah')
            )
            ->where(function ($query) use ($roleId, $request) {
                if ($roleId == 1) {
                    // Jika role_id adalah 1, tidak perlu menambahkan kondisi id_user
                    return $query;
                } else {
                    // Jika role_id bukan 1, tambahkan kondisi id_user
                    return $query->where('id_user', $request->user()->id);
                }
            })
            ->where('jenis_transaksi', 'PEMASUKAN')
            ->whereBetween('tgl_transaksi', [$startOfYear, $endOfYear])
            ->groupBy(DB::raw('EXTRACT(MONTH FROM tgl_transaksi)'))
            ->orderBy('bulan')
            ->pluck('total_jumlah', 'bulan')
            ->map(function ($jumlah) {
                return (int) $jumlah; // Mengubah ke tipe data integer
            })
            ->toArray();

            $bulanDataPengeluaran = WalletKas::select(
                DB::raw('EXTRACT(MONTH FROM tgl_transaksi) as bulan'),
                DB::raw('COALESCE(SUM(jumlah), 0) as total_jumlah')
            )
            ->where(function ($query) use ($roleId, $request) {
                if ($roleId == 1) {
                    // Jika role_id adalah 1, tidak perlu menambahkan kondisi id_user
                    return $query;
                } else {
                    // Jika role_id bukan 1, tambahkan kondisi id_user
                    return $query->where('id_user', $request->user()->id);
                }
            })
            ->where('jenis_transaksi', 'PENGELUARAN')
            ->whereBetween('tgl_transaksi', [$startOfYear, $endOfYear])
            ->groupBy(DB::raw('EXTRACT(MONTH FROM tgl_transaksi)'))
            ->orderBy('bulan')
            ->pluck('total_jumlah', 'bulan')
            ->map(function ($jumlah) {
                return (int) $jumlah; // Mengubah ke tipe data integer
            })
            ->toArray();

            $semuaBulan = range(1, 12);

            $dataPerkembangan = [];

            foreach ($semuaBulan as $bulan) {
                $jumlahPemasukan = isset($bulanDataPemasukan[$bulan]) ? $bulanDataPemasukan[$bulan] : 0;
                $jumlahPengeluaran = isset($bulanDataPengeluaran[$bulan]) ? $bulanDataPengeluaran[$bulan] : 0;

                $dataPerkembangan[] = [
                    'bulan' => $bulan,
                    'total_jumlah_pemasukan' => $jumlahPemasukan,
                    'total_jumlah_pengeluaran' => $jumlahPengeluaran,
                ];
            }

            // Mendapatkan tanggal awal dan akhir tahun yang diberikan
            $startOfYear = Carbon::create($tahun, 1, 1)->startOfYear()->toDateString();
            $endOfYear = Carbon::create($tahun, 12, 31)->endOfYear()->toDateString();

            // Mengambil total jumlah pemasukan untuk tahun yang diberikan
            $totalPemasukanTahunIni = WalletKas::where('jenis_transaksi', 'PEMASUKAN')
                ->whereBetween('tgl_transaksi', [$startOfYear, $endOfYear])->sum('jumlah');

            // Menghitung target pemasukan tahunan dari total pemasukan ditambah 10%
            $targetPemasukanTahunan = $totalPemasukanTahunIni + ($totalPemasukanTahunIni * 0.10);

            // Menghitung total pengeluaran untuk tahun yang diberikan
            $totalPengeluaran = WalletKas::where('jenis_transaksi', 'PENGELUARAN')
                ->whereBetween('tgl_transaksi', [$startOfYear, $endOfYear])->sum('jumlah');

            // Menghitung persentase keuntungan
            if ($totalPemasukanTahunIni != 0) {
                $persentaseKeuntungan = ($totalPemasukanTahunIni - $totalPengeluaran) / $totalPemasukanTahunIni * 100;
            } else {
                // Handle case when $totalPemasukanTahunIni is zero
                $persentaseKeuntungan = 0; // or any default value you prefer
            }

            return response()->json([
                'message' => 'Data berhasil diambil',
                'total_pemasukan_tahun_ini' => intval($totalPemasukanTahunIni),
                'total_pengeluaran' => intval($totalPengeluaran),
                'target_pemasukan_tahunan' => intval($targetPemasukanTahunan),
                'persentase_keuntungan' => $persentaseKeuntungan,
                'data_perkembangan' => $dataPerkembangan,
                'success' => true,
            ], 200);
        } catch (\Exception $exception) {
            return response()->json([
                'message' => 'Gagal mengambil data: '.$exception->getMessage(),
                'success' => false,
            ], 500);
        }
    }

    public function list(Request $request)
    {
        $roleId = $request->user()->role_id;
        $keyword = $request->get('keyword', null);
        $perPage = $request->get('per-page', 10);
        $kolom = $request->get('kolom', null);
        $jnsTransaksi = $request->get('jenis_transaksi', null);

        if ($perPage > 100) {
            $perPage = 100;
        }

        $model = WalletKas::select([
            'wallet_kas.id',
            'wallet_kas.id_user',
            'users.nama_lengkap AS nama_petugas',
            'wallet_kas.jenis_transaksi',
            'wallet_kas.tgl_transaksi',
            'wallet_kas.keterangan',
            'wallet_kas.jumlah',
            'wallet_kas.created_at',
        ])
            ->leftJoin('users', function ($join) {
                $join->on('wallet_kas.id_user', '=', DB::raw('CAST(users.id AS TEXT)'));
            });

        $model->orderByRaw('wallet_kas.created_at DESC NULLS LAST');

        if ($roleId != 1) {
            // Jika role_id bukan 1, tambahkan kondisi id_user
            $model->where('wallet_kas.id_user', $request->user()->id);
        }

        if (!is_null($jnsTransaksi)) {
            $model->where('wallet_kas.jenis_transaksi', '=', $jnsTransaksi);
        }

        if (!empty($keyword) && empty($kolom)) {
            $walletKas = $model->where('wallet_kas.tgl_transaksi', 'ILIKE', '%'.$keyword.'%')
                ->orWhere('users.nama_lengkap', 'ILIKE', '%'.$keyword.'%')
                ->paginate($perPage);
        } elseif (!empty($keyword) && !empty($kolom)) {
            if ($kolom == 'tgl_transaksi') {
                $kolom = 'wallet_kas.tgl_transaksi';
            } elseif ($kolom == 'nama_lengkap') {
                $kolom = 'wallet_kas.nama_lengkap';
            } else {
                $kolom = 'users.nama_lengkap';
            }

            $walletKas = $model->where($kolom, 'ILIKE', '%'.$keyword.'%')
                ->paginate($perPage);
        } else {
            $walletKas = $model->paginate($perPage);
        }

        $walletKas->appends(['per-page' => $perPage]);

        return response()->json([
            'message' => 'Sukses',
            'data_wallet_kas' => $walletKas,
            'success' => true,
        ], 200);
    }

    public function create(Request $request)
    {
        $user = User::find($request->id_user);

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
            'id_user' => 'required',
            'jenis_transaksi' => 'required',
            'tgl_transaksi' => 'required',
            'keterangan' => 'required',
            'jumlah' => 'required',
        ], $customMessages);

        $walletKas = new WalletKas();
        $walletKas->id_user = $request->id_user;
        $walletKas->jenis_transaksi = $request->jenis_transaksi;
        $walletKas->tgl_transaksi = $request->tgl_transaksi;
        $walletKas->keterangan = $request->keterangan;
        $walletKas->jumlah = $request->jumlah;
        try {
            if (!$user) {
                return response()->json([
                    'message' => 'Data Petugas tidak ditemukan',
                    'success' => false,
                ], 404);
            }
            // Pengecekan tanggal transaksi unik
            if ($request->jenis_transaksi === 'PEMASUKAN' && WalletKas::where('tgl_transaksi', $request->tgl_transaksi)->exists()) {
                return response()->json([
                    'message' => 'Tanggal transaksi '.$request->tgl_transaksi.' telah dibayarkan sebelumnya, Mohon agar tidak menambahkan data di bulan yang sama',
                    'success' => false,
                ]);
            }

            if ($request->jenis_transaksi == 'PENGELUARAN') {
                $totalPemasukan = WalletKas::where('jenis_transaksi', 'PEMASUKAN')->sum('jumlah');
                if ($request->jumlah > $totalPemasukan) {
                    // Jika jumlah pengeluaran melebihi total pemasukan, kembalikan pesan kesalahan
                    return response()->json([
                        'message' => 'Jumlah pengeluaran melebihi total pemasukan',
                        'success' => false,
                    ]);
                }
            }
            $walletKas->save();
        } catch (\Exception $exception) {
            return response()->json([
                'message' => 'Gagal menambah data Kas'.$exception->getMessage(),
                'success' => false,
            ], 500);
        }

        unset($walletKas->created_at, $walletKas->updated_at);

        return response()->json([
            'message' => 'Data Kas berhasil ditambahkan',
            'data_wallet_kas' => $walletKas,
            'success' => true,
        ], 200);
    }

    public function edit(Request $request)
    {
        $request->validate([
            'id' => 'required|numeric|digits_between:1,5',
        ]);

        $walletKas = WalletKas::where('id', '=', $request->id)->first();

        $walletKas = WalletKas::select([
            'wallet_kas.id',
            'wallet_kas.id_user',
            'users.nama_lengkap AS nama_petugas',
            'wallet_kas.jenis_transaksi',
            'wallet_kas.tgl_transaksi',
            'wallet_kas.keterangan',
            'wallet_kas.jumlah',
            'wallet_kas.created_at',
        ])
            ->leftJoin('users', function ($join) {
                $join->on('wallet_kas.id_user', '=', DB::raw('CAST(users.id AS TEXT)'));
            })->where('wallet_kas.id', '=', $request->id)->first();

        if (!empty($walletKas)) {
            return response()->json([
                'message' => 'Sukses',
                'data_wallet_kas' => $walletKas,
                'success' => true,
            ], 200);
        }

        return response()->json([
            'message' => 'Data Kas tidak ditemukan',
            'success' => false,
        ], 200);
    }

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
            'jenis_transaksi' => 'required',
            'tgl_transaksi' => [
                'nullable', // Mengubah menjadi nullable agar validasi tidak wajib
                'sometimes',
                Rule::unique('wallet_kas', 'tgl_transaksi')->ignore($request->id, 'id')->where(function ($query) use ($request) {
                    return $query->where('tgl_transaksi', '!=', $request->tgl_transaksi);
                }),
            ],
            'keterangan' => 'required',
            'jumlah' => 'required',
        ], $customMessages);

        $walletKas = WalletKas::where('id', '=', $request->id)
            ->first();

        if (!empty($walletKas)) {
            try {
                if (!$walletKas) {
                    return response()->json([
                        'message' => 'Data Kas tidak ditemukan',
                        'success' => false,
                    ], 404);
                }

                if ($request->jenis_transaksi === 'PEMASUKAN' && $request->jenis_transaksi === 'PENGELUARAN' && $request->tgl_transaksi && WalletKas::where('tgl_transaksi', $request->tgl_transaksi)->where('id', '!=', $request->id)->exists()) {
                    return response()->json([
                        'message' => 'Tanggal transaksi '.$request->tgl_transaksi.' telah dibayarkan sebelumnya. Mohon untuk tidak menambahkan data di bulan yang sama.',
                        'success' => false,
                    ], 200);
                }

                if ($request->jenis_transaksi == 'PENGELUARAN') {
                    $totalPemasukan = WalletKas::where('jenis_transaksi', 'PEMASUKAN')->sum('jumlah');
                    if ($request->jumlah > $totalPemasukan) {
                        return response()->json([
                            'message' => 'Jumlah pengeluaran melebihi total pemasukan.',
                            'success' => false,
                        ], 200);
                    }
                }

                $walletKas->update([
                    'id' => $request->id,
                    'jenis_transaksi' => $request->jenis_transaksi,
                    'tgl_transaksi' => $request->tgl_transaksi,
                    'keterangan' => $request->keterangan,
                    'jumlah' => $request->jumlah,
                ]);
            } catch (\Exception $exception) {
                return response()->json([
                    'message' => 'Gagal mengupdate data Kas: '.$exception->getMessage(),
                    'success' => false,
                ], 500);
            }
        }

        return response()->json([
            'message' => 'Data Kas berhasil diupdate',
            'data_wallet_kas' => $walletKas,
            'success' => true,
        ], 200);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|numeric|digits_between:1,5',
        ]);

        $walletKas = WalletKas::where('id', '=', $request->id)
            ->first();

        if (!empty($walletKas)) {
            try {
                $walletKas = WalletKas::where('id', '=', $request->id)
                    ->delete();

                return response()->json([
                    'message' => 'Data Kas berhasil dihapus',
                    'success' => true,
                ], 200);
            } catch (\Exception $exception) {
                return response()->json([
                    'message' => 'Gagal menghapus data Kas'.$exception->getMessage(),
                    'success' => false,
                ], 500);
            }
        }

        return response()->json([
            'message' => 'Data Kas tidak ditemukan',
            'success' => false,
        ], 200);
    }
}
