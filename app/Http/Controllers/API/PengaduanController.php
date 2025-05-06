<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Pengaduan;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class PengaduanController extends Controller
{
    public function cekStatus(Request $request)
    {
        $request->validate([
            'id_operator' => 'required|exists:users,uuid',
            'kontak' => 'required|string'
        ], [
            'required' => 'Kolom :attribute wajib diisi',
            'exists' => ':attribute tidak ditemukan dalam sistem',
        ]);

        // Cek role_id operator
        $operator = User::where('uuid', $request->id_operator)->first();

        if (!$operator || $operator->role_id != 5) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Operator tidak memiliki izin',
            ], 403);
        }

        // Ambil semua pengaduan berdasarkan kontak
        $pengaduanList = Pengaduan::select([
            'pengaduan.uuid AS kode_pengaduan',
            'pengaduan.nama_lengkap AS nama_pelapor',
            'pengaduan.kontak',
            'pengaduan.jenis_pengaduan',
            'pengaduan.subjek',
            'pengaduan.isi_pengaduan',
            'pengaduan.lampiran',
            'pengaduan.status_pengaduan',
            'pengaduan.nama_kelompok',
            'pengaduan.ip_address',
            'pengaduan.user_agent',
            'pengaduan.tanggal_dibalas',
            'users.nama_lengkap AS dibalas_oleh',
            'pengaduan.balasan_admin',
            'pengaduan.created_at',
        ])
            ->leftJoin('users', 'users.id', '=', DB::raw('CAST(pengaduan.dibalas_oleh AS UNSIGNED)'))
            ->where('pengaduan.kontak', $request->kontak)
            ->orderBy('pengaduan.created_at', 'desc')
            ->get();

        // Tambahkan lampiran_url ke setiap item
        $pengaduanList->transform(function ($item) {
            $item->lampiran_url = $item->lampiran
                ? asset('storage/' . str_replace('public/', '', $item->lampiran))
                : null;

            unset($item->updated_at);
            return $item;
        });

        if ($pengaduanList->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada data pengaduan ditemukan untuk kontak tersebut',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Sukses',
            'data' => $pengaduanList,
        ], 200);
    }

    public function cekStatusDetail(Request $request)
    {
        $request->validate([
            'uuid' => 'required|numeric|digits_between:1,5',
        ]);

        $pengaduan = Pengaduan::select([
            'pengaduan.uuid',
            'pengaduan.nama_lengkap AS nama_pelapor',
            'pengaduan.kontak',
            'pengaduan.jenis_pengaduan',
            'pengaduan.subjek',
            'pengaduan.isi_pengaduan',
            'pengaduan.lampiran',
            'pengaduan.status_pengaduan',
            'pengaduan.nama_kelompok',
            'pengaduan.ip_address',
            'pengaduan.user_agent',
            'pengaduan.tanggal_dibalas',
            'users.nama_lengkap AS dibalas_oleh',
            'pengaduan.balasan_admin',
            'pengaduan.created_at',
        ])
            ->leftJoin('users', 'users.id', '=', DB::raw('CAST(pengaduan.dibalas_oleh AS UNSIGNED)'))
            ->where('pengaduan.uuid', $request->uuid)
            ->first();

        if ($pengaduan) {
            // Generate the correct URL for the image
            $pengaduan->lampiran_url = $pengaduan->lampiran
                ? asset('storage/' . str_replace('public/', '', $pengaduan->lampiran))
                : null;

            unset($pengaduan->created_at, $pengaduan->updated_at);

            return response()->json([
                'message' => 'Sukses',
                'data_pengaduan' => $pengaduan,
                'success' => true,
            ], 200);
        }

        return response()->json([
            'message' => 'Data Pengaduan tidak ditemukan',
            'success' => false,
        ], 200);
    }

    public function kirimPengaduan(Request $request)
    {
        $customMessages = [
            'required' => 'Kolom :attribute wajib diisi',
            'unique' => ':attribute sudah terdaftar di sistem',
            'email' => ':attribute harus berupa alamat email yang valid',
            'max' => ':attribute tidak boleh lebih dari :max karakter',
            'confirmed' => 'Konfirmasi :attribute tidak cocok',
            'min' => ':attribute harus memiliki setidaknya :min karakter',
            'regex' => ':attribute harus mengandung setidaknya satu huruf kapital dan satu angka',
            'numeric' => ':attribute harus berupa angka',
            'digits_between' => ':attribute harus memiliki panjang antara :min dan :max digit',
        ];

        $request->validate([
            'id_operator' => 'required|exists:users,uuid',
            'nama_lengkap' => 'required|string|max:255',
            'kontak' => 'required|string',
            'jenis_pengaduan' => 'required',
            'subjek' => 'required|string|max:255',
            'isi_pengaduan' => 'required|string',
            'nama_kelompok' => 'required',
            'lampiran' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ], $customMessages);

        $operator = User::firstWhere('uuid', $request->id_operator);

        if (!$operator || $operator->role_id != 5) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Operator tidak memiliki izin',
            ], 403);
        }

        $pengaduanHariIni = Pengaduan::whereDate('created_at', now()->toDateString())
            ->where('kontak', $request->kontak)
            ->count();

        if ($pengaduanHariIni >= 3) {
            return response()->json([
                'success' => false,
                'message' => 'Anda hanya dapat mengirim maksimal 3 pengaduan dalam 1 hari',
            ], 429);
        }

        $pengaduan = new Pengaduan();
        $pengaduan->uuid = Str::uuid()->toString();
        $pengaduan->nama_lengkap = $request->nama_lengkap;
        $pengaduan->kontak = $request->kontak;
        $pengaduan->jenis_pengaduan = $request->jenis_pengaduan;
        $pengaduan->subjek = $request->subjek;
        $pengaduan->isi_pengaduan = $request->isi_pengaduan;
        $pengaduan->nama_kelompok = $request->nama_kelompok;
        $pengaduan->ip_address = $request->ip();
        $pengaduan->user_agent = $request->header('User-Agent');

        if ($request->hasFile('lampiran')) {
            $foto = $request->file('lampiran');
            $namaFile = Str::slug($pengaduan->uuid) . '.' . $foto->getClientOriginalExtension();
            $path = $foto->storeAs('public/images/pengaduan', $namaFile);
            $pengaduan->lampiran = $path;
        } else {
            $pengaduan->lampiran = null;
        }

        try {
            $pengaduan->save();

            unset($pengaduan->created_at, $pengaduan->updated_at);

            return response()->json([
                'message' => 'Data Pengaduan berhasil ditambahkan',
                'data' => $pengaduan,
                'success' => true,
            ], 200);
        } catch (\Exception $exception) {
            return response()->json([
                'message' => 'Gagal menambah data Pengaduan: ' . $exception->getMessage(),
                'success' => false,
            ], 500);
        }
    }

    public function balasPengaduan(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:pengaduan,id',
            'status_pengaduan' => 'required',
            'balasan_admin' => 'string',
        ]);

        $pengaduan = Pengaduan::findOrFail($request->id);

        if ($pengaduan->status_pengaduan === 'selesai') {
            return response()->json([
                'success' => false,
                'message' => 'Pengaduan ini sudah dibalas sebelumnya',
            ], 400);
        }

        $pengaduan->balasan_admin = $request->balasan_admin;
        $pengaduan->tanggal_dibalas = now();
        $pengaduan->dibalas_oleh = auth()->id(); // pastikan user admin terautentikasi
        $pengaduan->status_pengaduan = $request->status_pengaduan;

        try {
            $pengaduan->save();

            return response()->json([
                'success' => true,
                'message' => 'Balasan pengaduan berhasil dikirim',
                'data' => $pengaduan
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membalas pengaduan ' . $e->getMessage(),
            ], 500);
        }
    }

    public function list_pengaduan(Request $request)
    {
        $keyword = $request->get('keyword');
        $perPage = min($request->get('per-page', 10), 100);
        $jenisPengaduan = $request->get('jenis_pengaduan');
        $statusPengaduan = $request->get('status_pengaduan');

        $query = Pengaduan::select([
            'pengaduan.id',
            'pengaduan.nama_lengkap AS nama_pelapor',
            'pengaduan.kontak',
            'pengaduan.jenis_pengaduan',
            'pengaduan.subjek',
            'pengaduan.status_pengaduan',
            'users.nama_lengkap AS dibalas_oleh',
            'pengaduan.created_at',
        ])
            ->leftJoin('users', 'users.id', '=', DB::raw('CAST(pengaduan.dibalas_oleh AS UNSIGNED)'))
            ->orderByRaw('pengaduan.created_at IS NULL, pengaduan.created_at DESC');

        if (!is_null($jenisPengaduan)) {
            $query->where('pengaduan.jenis_pengaduan', $jenisPengaduan);
        }

        if (!is_null($statusPengaduan)) {
            $query->where('pengaduan.status_pengaduan', $statusPengaduan);
        }

        if (!empty($keyword)) {
            $query->where(function ($q) use ($keyword) {
                $q->where('pengaduan.nama_lengkap', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('pengaduan.kontak', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('pengaduan.subjek', 'LIKE', '%' . $keyword . '%');
            });
        }

        $data = $query->paginate($perPage)->appends([
            'per-page' => $perPage,
            'keyword' => $keyword,
            'jenis_pengaduan' => $jenisPengaduan,
            'status_pengaduan' => $statusPengaduan,
        ]);

        return response()->json([
            'message' => 'Data Ditemukan',
            'data_pengaduan' => $data,
            'success' => true,
        ], 200);
    }

    public function detail_pengaduan(Request $request)
    {
        $request->validate([
            'id' => 'required|numeric|digits_between:1,5',
        ]);

        $pengaduan = Pengaduan::select([
            'pengaduan.id',
            'pengaduan.nama_lengkap AS nama_pelapor',
            'pengaduan.kontak',
            'pengaduan.jenis_pengaduan',
            'pengaduan.subjek',
            'pengaduan.isi_pengaduan',
            'pengaduan.lampiran',
            'pengaduan.status_pengaduan',
            'pengaduan.nama_kelompok',
            'pengaduan.ip_address',
            'pengaduan.user_agent',
            'pengaduan.tanggal_dibalas',
            'users.nama_lengkap AS dibalas_oleh',
            'pengaduan.balasan_admin',
            'pengaduan.created_at',
        ])
            ->leftJoin('users', 'users.id', '=', DB::raw('CAST(pengaduan.dibalas_oleh AS UNSIGNED)'))
            ->where('pengaduan.id', $request->id)
            ->first();

        if ($pengaduan) {
            // Generate the correct URL for the image
            $pengaduan->lampiran_url = $pengaduan->lampiran
                ? asset('storage/' . str_replace('public/', '', $pengaduan->lampiran))
                : null;

            unset($pengaduan->created_at, $pengaduan->updated_at);

            return response()->json([
                'message' => 'Sukses',
                'data_pengaduan' => $pengaduan,
                'success' => true,
            ], 200);
        }

        return response()->json([
            'message' => 'Data Pengaduan tidak ditemukan',
            'success' => false,
        ], 200);
    }
}
