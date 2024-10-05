<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\dataSensusPeserta;
use App\Models\User;
use App\Models\WalletDigital;
use App\Models\WalletStatus;
use App\Models\WalletTampungan;
use App\Models\WalletTypePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SetorKasCashlessController extends Controller
{
    public function list_status_wallet()
    {
        $sensus = WalletStatus::select(['id', 'name_status'])
            ->groupBy('id', 'name_status')->orderBy('name_status')->get();

        return response()->json([
            'message' => 'Sukses',
            'data_wallet_kas_digital' => $sensus,
            'success' => true,
        ], 200);
    }

    public function list_tampungan_wallet()
    {
        $sensus = WalletTampungan::select(['id', 'nama_tampungan'])
            ->groupBy('id', 'nama_tampungan')->orderBy('nama_tampungan')->get();

        return response()->json([
            'message' => 'Sukses',
            'data_wallet_kas_digital' => $sensus,
            'success' => true,
        ], 200);
    }

    public function list_payment_wallet_data_aktif()
    {
        $query = WalletTypePayment::select(['id', 'string_name_payment'])
            ->where('info_status_payment', 1) // Memfilter hasil berdasarkan info_status_payment
            ->orderBy('string_name_payment');

        $tabel_data_payment = $query->get();

        return response()->json([
            'message' => 'Sukses',
            'data_wallet_kas_digital' => $tabel_data_payment,
            'success' => true,
        ], 200);
    }

    public function list_payment_wallet()
    {
        $sensus = WalletTypePayment::select(['id', 'string_name_payment'])
            ->groupBy('id', 'string_name_payment')->orderBy('string_name_payment')->get();

        return response()->json([
            'message' => 'Sukses',
            'data_wallet_kas_digital' => $sensus,
            'success' => true,
        ], 200);
    }

    public function list_data_peserta()
    {
        // Asumsikan bahwa kita memiliki informasi pengguna yang sedang login di variabel $user
        $user = auth()->user(); // atau $user = Auth::user(); jika menggunakan facade Auth

        // Query dasar untuk data sensus peserta
        $sensusQuery = dataSensusPeserta::select(['id', 'nama_lengkap']);

        // Tambahkan filter berdasarkan peran pengguna (role_daerah, role_desaa, role_kelompok)
        if ($user->role_daerah) {
            $sensusQuery->where('tmpt_daerah', $user->role_daerah);
        }

        if ($user->role_desa) {
            $sensusQuery->where('tmpt_desa', $user->role_desa);
        }

        if ($user->role_kelompok) {
            $sensusQuery->where('tmpt_kelompok', $user->role_kelompok);
        }

        // Dapatkan data yang difilter, lalu grup dan urutkan sesuai kebutuhan
        $sensus = $sensusQuery->groupBy('id', 'nama_lengkap')
            ->orderBy('nama_lengkap')
            ->get();

        return response()->json([
            'message' => 'Sukses',
            'data_wallet_kas_digital' => $sensus,
            'success' => true,
        ], 200);
    }

    public function list(Request $request)
    {
        $keyword = $request->get('keyword', null);
        $perPage = $request->get('per-page', 10);
        $kolom = $request->get('kolom', null);
        $jnsTampungan = $request->get('jenis_tampungan', null);
        $paymentType = $request->get('payment_type', null);
        $trnsksiStatus = $request->get('transaction_status', null);

        if ($perPage > 100) {
            $perPage = 100;
        }

        $model = WalletDigital::select([
            'wallet_kas_digital.id',
            'wallet_kas_digital.transaction_id',
            'wallet_kas_digital.order_id',
            'users.nama_lengkap AS nama_petugas',
            'data_peserta.nama_lengkap AS nama_peserta',
            'wallet_kas_digital.bulan',
            'wallet_tampungan.nama_tampungan AS jenis_tampungan',
            'wallet_type_payment.string_name_payment as payment_type',
            'wallet_status.id as transaction_status_id',
            'wallet_status.name_status as transaction_status',
            'wallet_kas_digital.amount',
            'wallet_kas_digital.created_at',
        ])
            ->leftJoin('users', function ($join) {
                $join->on('wallet_kas_digital.wallet_user_id', '=', DB::raw('CAST(users.id AS CHAR)'));
            })
            ->leftJoin('data_peserta', function ($join) {
                $join->on('wallet_kas_digital.wallet_sensus_id', '=', DB::raw('CAST(data_peserta.id AS CHAR)'));
            })
            ->leftJoin('wallet_tampungan', function ($join) {
                $join->on('wallet_kas_digital.jenis_tampungan', '=', DB::raw('CAST(wallet_tampungan.id AS CHAR)'));
            })
            ->leftJoin('wallet_status', function ($join) {
                $join->on('wallet_kas_digital.transaction_status', '=', DB::raw('CAST(wallet_status.id AS CHAR)'));
            })
            ->leftJoin('wallet_type_payment', function ($join) {
                $join->on('wallet_kas_digital.payment_type', '=', DB::raw('CAST(wallet_type_payment.id AS CHAR)'));
            });

        // Apply orderByRaw before executing the query
        $model->orderByRaw('wallet_kas_digital.created_at IS NULL, wallet_kas_digital.created_at DESC');

        if (!is_null($jnsTampungan)) {
            $model->where('wallet_kas_digital.jenis_tampungan', '=', $jnsTampungan);
        }

        if (!is_null($paymentType)) {
            $model->where('wallet_kas_digital.payment_type', '=', $paymentType);
        }

        if (!is_null($trnsksiStatus)) {
            $model->where('wallet_kas_digital.transaction_status', '=', $trnsksiStatus);
        }

        if (!empty($keyword) && empty($kolom)) {
            $WalletDigital = $model->where(DB::raw("DATE_FORMAT(wallet_kas_digital.bulan, '%M %Y') COLLATE utf8mb4_unicode_ci"), 'LIKE', '%' . $keyword . '%')
                ->orWhere('wallet_kas_digital.order_id', 'LIKE', '%' . $keyword . '%')
                ->orWhere('users.nama_lengkap', 'LIKE', '%' . $keyword . '%')
                ->orWhere('data_peserta.nama_lengkap', 'LIKE', '%' . $keyword . '%')
                ->paginate($perPage);
        } elseif (!empty($keyword) && !empty($kolom)) {
            if ($kolom == 'order_id') {
                $kolom = 'wallet_kas_digital.order_id';
            } elseif ($kolom == 'nama_petugas') {
                $kolom = 'users.nama_lengkap';
            } elseif ($kolom == 'nama_peserta') {
                $kolom = 'data_peserta.nama_lengkap';
            } else {
                $kolom = 'wallet_kas_digital.transaction_id';
            }

            $WalletDigital = $model->where($kolom, 'LIKE', '%' . $keyword . '%')
                ->paginate($perPage);
        } else {
            $WalletDigital = $model->paginate($perPage);
        }

        $WalletDigital->appends(['per-page' => $perPage]);

        return response()->json([
            'message' => 'Sukses',
            'data_wallet_kas_digital' => $WalletDigital,
            'success' => true,
        ], 200);
    }

    public function create(Request $request)
    {
        $idPtgsInput = User::find($request->wallet_user_id);
        $idCustomer = dataSensusPeserta::find($request->wallet_sensus_id);
        $jnsTransaksi = WalletTampungan::find($request->jenis_tampungan);
        $typePayment = WalletTypePayment::find($request->payment_type);

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
            'wallet_user_id' => 'required',
            'wallet_sensus_id' => 'required',
            'bulan' => 'required',
            'jenis_tampungan' => 'required',
            'payment_type' => 'required',
            'keterangan' => 'required',
            'amount' => 'required',
        ], $customMessages);

        // Generate order_id
        $userId = auth()->user()->id;
        $tglTransaksi = now()->format('Ymd');
        $characterCombination = Str::upper(Str::random(4));
        $uniqueCode = rand(1000, 9999);
        $order_id = 'INVOICE' . $userId . $tglTransaksi . $characterCombination . $uniqueCode;

        $walletKasDigital = new WalletDigital();
        $walletKasDigital->transaction_id = Str::uuid()->toString();
        $walletKasDigital->order_id = $order_id;
        $walletKasDigital->wallet_user_id = $request->wallet_user_id;
        $walletKasDigital->wallet_sensus_id = $request->wallet_sensus_id;
        $walletKasDigital->bulan = $request->bulan;
        $walletKasDigital->jenis_tampungan = $request->jenis_tampungan;
        $walletKasDigital->payment_type = $request->payment_type;
        $walletKasDigital->keterangan = $request->keterangan;
        $walletKasDigital->transaction_status = 1;
        $walletKasDigital->amount = $request->amount;

        try {
            if (!$idPtgsInput) {
                return response()->json([
                    'message' => 'Data Petugas tidak ditemukan',
                    'success' => false,
                ], 404);
            }

            if (!$idCustomer) {
                return response()->json([
                    'message' => 'Data Customer tidak ditemukan',
                    'success' => false,
                ], 404);
            }

            if (!$jnsTransaksi) {
                return response()->json([
                    'message' => 'Data Tampungan tidak ditemukan',
                    'success' => false,
                ], 404);
            }

            if (!$typePayment) {
                return response()->json([
                    'message' => 'Data Channel Pembayaran tidak ditemukan',
                    'success' => false,
                ], 404);
            }

            // Pengecekan tanggal transaksi unik
            if (WalletDigital::where('bulan', $request->bulan)
                ->where('wallet_user_id', $request->wallet_user_id)
                ->exists()
            ) {
                return response()->json([
                    'message' => 'Tanggal transaksi ' . $request->bulan . ' sudah terdaftar untuk pengguna ini. Mohon agar tidak menambahkan data di bulan yang sama.',
                    'success' => false,
                ]);
            }

            $walletKasDigital->save();
        } catch (\Exception $exception) {
            return response()->json([
                'message' => 'Gagal menambah data Kas: ' . $exception->getMessage(),
                'success' => false,
            ], 500);
        }

        unset($walletKasDigital->created_at, $walletKasDigital->updated_at);

        return response()->json([
            'message' => 'Data Kas berhasil ditambahkan',
            'data_wallet_kas_digital' => $walletKasDigital,
            'success' => true,
        ], 200);
    }

    public function edit(Request $request)
    {
        $request->validate([
            'id' => 'required|numeric|digits_between:1,5',
            'transaction_id' => 'required', // Tambahkan validasi untuk transaction_id
        ]);

        $walletKasDigital = WalletDigital::where('id', '=', $request->id)->where('transaction_id', '=', $request->transaction_id)->first();

        $walletKasDigital = WalletDigital::select([
            'wallet_kas_digital.id',
            'wallet_kas_digital.transaction_id',
            'wallet_kas_digital.order_id',
            'users.nama_lengkap AS nama_petugas',
            'data_peserta.id AS peserta_id',
            'data_peserta.nama_lengkap AS nama_peserta',
            'data_peserta.no_telepon AS phone',
            'data_peserta.alamat AS address',
            'wallet_kas_digital.bulan',
            'wallet_tampungan.id AS jenis_tampungan_id',
            'wallet_tampungan.nama_tampungan AS jenis_tampungan_name',
            'wallet_type_payment.id as payment_type_id',
            'wallet_type_payment.channel_name_payment as payment_type_name',
            'wallet_status.id as transaction_status_id',
            'wallet_status.name_status as transaction_status_name',
            'wallet_kas_digital.keterangan',
            'wallet_kas_digital.amount',
            'wallet_kas_digital.created_at',
        ])
            ->leftJoin('users', function ($join) {
                $join->on('wallet_kas_digital.wallet_user_id', '=', DB::raw('CAST(users.id AS CHAR)'));
            })->leftJoin('data_peserta', function ($join) {
                $join->on('wallet_kas_digital.wallet_sensus_id', '=', DB::raw('CAST(data_peserta.id AS CHAR)'));
            })->leftJoin('wallet_tampungan', function ($join) {
                $join->on('wallet_kas_digital.jenis_tampungan', '=', DB::raw('CAST(wallet_tampungan.id AS CHAR)'));
            })->leftJoin('wallet_type_payment', function ($join) {
                $join->on('wallet_kas_digital.payment_type', '=', DB::raw('CAST(wallet_type_payment.id AS CHAR)'));
            })->leftJoin('wallet_status', function ($join) {
                $join->on('wallet_kas_digital.transaction_status', '=', DB::raw('CAST(wallet_status.id AS CHAR)'));
            })->where('wallet_kas_digital.id', '=', $request->id)->first();

        if (!$walletKasDigital) {
            return response()->json([
                'message' => 'Data dengan ID dan Order ID tersebut tidak ditemukan.',
                'success' => false,
            ], 404);
        }
        if (!empty($walletKasDigital)) {
            return response()->json([
                'message' => 'Sukses',
                'data_wallet_kas_digital' => $walletKasDigital,
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
        $idCustomer = dataSensusPeserta::find($request->wallet_sensus_id);
        $jnsTransaksi = WalletTampungan::find($request->jenis_tampungan);
        $statusTransaksi = WalletStatus::find($request->transaction_status);

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
            'transaction_id' => 'required', // Tambahkan validasi untuk transaction_id
            'bulan' => [
                'nullable', // Mengubah menjadi nullable agar validasi tidak wajib
                'sometimes',
                Rule::unique('wallet_kas_digital', 'bulan')->ignore($request->id, 'id')->where(function ($query) use ($request) {
                    return $query->where('bulan', '!=', $request->bulan);
                }),
            ],
            'jenis_tampungan' => 'required',
            'keterangan' => 'required',
            'transaction_status' => 'required',
            'amount' => 'required',
        ], $customMessages);

        $walletKasDigital = WalletDigital::where('id', '=', $request->id)->where('transaction_id', '=', $request->transaction_id)
            ->first();

        if (!empty($walletKasDigital)) {
            try {
                if (!$walletKasDigital) {
                    return response()->json([
                        'message' => 'Data Kas tidak ditemukan',
                        'success' => false,
                    ], 404);
                }

                if (!$idCustomer) {
                    return response()->json([
                        'message' => 'Data Customer tidak ditemukan',
                        'success' => false,
                    ], 404);
                }

                if (!$jnsTransaksi) {
                    return response()->json([
                        'message' => 'Data Tampungan tidak ditemukan',
                        'success' => false,
                    ], 404);
                }

                if (!$statusTransaksi) {
                    return response()->json([
                        'message' => 'Data Status tidak ditemukan',
                        'success' => false,
                    ], 404);
                }

                // Pengecekan tanggal transaksi unik
                if (WalletDigital::where('bulan', $request->bulan)
                    ->where('wallet_user_id', $request->wallet_user_id)
                    ->exists()
                ) {
                    return response()->json([
                        'message' => 'Tanggal transaksi ' . $request->bulan . ' sudah terdaftar untuk pengguna ini. Mohon agar tidak menambahkan data di bulan yang sama.',
                        'success' => false,
                    ]);
                }

                $walletKasDigital->update([
                    'id' => $request->id,
                    'transaction_id' => $request->transaction_id,
                    'wallet_sensus_id' => $request->wallet_sensus_id,
                    'bulan' => $request->bulan,
                    'jenis_tampungan' => $request->jenis_tampungan,
                    'keterangan' => $request->keterangan,
                    'transaction_status' => $request->transaction_status,
                    'amount' => $request->amount,
                ]);
            } catch (\Exception $exception) {
                return response()->json([
                    'message' => 'Gagal mengupdate data Kas: ' . $exception->getMessage(),
                    'success' => false,
                ], 500);
            }
        }

        return response()->json([
            'message' => 'Data Kas berhasil diupdate',
            'data_wallet_kas_digital' => $walletKasDigital,
            'success' => true,
        ], 200);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|numeric|digits_between:1,5',
            'transaction_id' => 'required', // Tambahkan validasi untuk transaction_id
        ]);

        $walletKasDigital = WalletDigital::where('id', '=', $request->id)->where('transaction_id', '=', $request->transaction_id)
            ->first();

        if (!empty($walletKasDigital)) {
            try {
                $walletKasDigital = WalletDigital::where('id', '=', $request->id)
                    ->delete();

                return response()->json([
                    'message' => 'Data Kas berhasil dihapus',
                    'success' => true,
                ], 200);
            } catch (\Exception $exception) {
                return response()->json([
                    'message' => 'Gagal menghapus data Kas' . $exception->getMessage(),
                    'success' => false,
                ], 500);
            }
        }

        return response()->json([
            'message' => 'Data Kas tidak ditemukan',
            'success' => false,
        ], 200);
    }

    public function bayar_digital(Request $request)
    {
        $idPtgsInput = User::find($request->wallet_user_id);
        $idCustomer = dataSensusPeserta::find($request->wallet_sensus_id);
        $jnsTransaksi = WalletTampungan::find($request->jenis_tampungan);
        $typePayment = WalletTypePayment::find($request->payment_type);

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
            'order_id' => 'required',
            'wallet_user_id' => 'required',
            'wallet_sensus_id' => 'required',
            'bulan' => 'required',
            'jenis_tampungan' => 'required',
            'nama_tampungan' => 'required',
            'payment_type' => 'required',
            'payment_type_name' => 'required',
            'keterangan' => 'required',
            'customer_nama' => 'required',
            'customer_email' => 'string|email',
            'customer_phone' => 'required',
            'customer_address' => 'required',
            'amount' => 'required',
        ], $customMessages);

        try {
            DB::beginTransaction();

            if (!$idPtgsInput) {
                return response()->json([
                    'message' => 'Data Petugas tidak ditemukan',
                    'success' => false,
                ], 404);
            }

            if (!$idCustomer) {
                return response()->json([
                    'message' => 'Data Customer tidak ditemukan',
                    'success' => false,
                ], 404);
            }

            if (!$jnsTransaksi) {
                return response()->json([
                    'message' => 'Data Tampungan tidak ditemukan',
                    'success' => false,
                ], 404);
            }

            if (!$typePayment) {
                return response()->json([
                    'message' => 'Data Channel Pembayaran tidak ditemukan',
                    'success' => false,
                ], 404);
            }

            // Konfigurasi Midtrans dan kirim permintaan
            $serverKey = config('midtrans.server_key');
            $urlEnv = 'https://api.sandbox.midtrans.com/v2/charge'; // Ubah sesuai environment

            $response = Http::withBasicAuth($serverKey, '')->post($urlEnv, [
                'payment_type' => $request->payment_type_name,
                'transaction_details' => [
                    'order_id' => $request->order_id,
                    'gross_amount' => $request->amount,
                ],
                // 'bank_transfer' => [
                //     'bank' => 'bca',
                //     'free_text' => [
                //         'inquiry' => [
                //             [
                //                 'id' => 'Pembayaran dengan nomor'.$request->order_id.'dengan nominal'.$request->amount,
                //                 'en' => 'test',
                //             ],
                //         ],
                //         'payment' => [
                //             [
                //                 'id' => 'Pembayaran harus diselesaikan dalam waktu 24 jam.',
                //                 'en' => 'Payment must be completed within 24 hours.',
                //             ],
                //         ],
                //     ],
                // ],
                'customer_details' => [
                    'id' => $request->wallet_sensus_id,
                    'first_name' => $request->customer_nama,
                    'email' => $request->customer_email,
                    'phone' => $request->customer_phone,
                    'billing_address' => [
                        'id' => $request->wallet_sensus_id,
                        'first_name' => $request->customer_nama,
                        'email' => $request->customer_email,
                        'phone' => $request->customer_phone,
                        'address' => $request->customer_address,
                    ],
                    'shipping_address' => [
                        'id' => $request->wallet_sensus_id,
                        'first_name' => $request->customer_nama,
                        'email' => $request->customer_email,
                        'phone' => $request->customer_phone,
                        'address' => $request->customer_address,
                    ],
                ],
                'item_details' => [
                    'id' => $request->order_id,
                    'price' => $request->amount,
                    'quantity' => 1,
                    'name' => 'Pembayaran ' . $request->nama_tampungan . '.  (Keterangan: ' . $request->keterangan . ')',
                ],
                'qris' => [
                    'acquirer' => 'gopay',
                ],
            ]);

            // Mengecek apakah respons dari Midtrans berhasil
            if ($response->successful()) {
                DB::commit();

                return response()->json($response->json(), 200);
            } else {
                DB::rollBack();

                return response()->json([
                    'message' => 'Gagal memproses pembayaran',
                    'errors' => $response->json(),
                    'success' => false,
                ], $response->status());
            }
        } catch (\Exception $exception) {
            DB::rollBack();

            return response()->json([
                'message' => 'Gagal menambah data Kas: ' . $exception->getMessage(),
                'success' => false,
            ], 500);
        }
    }
}
