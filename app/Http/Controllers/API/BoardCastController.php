<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Boarcast;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BoardCastController extends Controller
{
    public function list(Request $request)
    {
        $keyword = $request->get('keyword', null);
        $perPage = $request->get('per-page', 10);
        $kolom = $request->get('kolom', null);
        $status = $request->get('status', null);

        if ($perPage > 100) {
            $perPage = 100;
        }

        $model = Boarcast::select([
            'broadcast.id',
            'broadcast.id_user',
            'users.nama_lengkap as nama_petugas',
            'broadcast.judul_broadcast',
            'broadcast.jenis_broadcast',
            'broadcast.text_broadcast',
            'broadcast.ip',
            'broadcast.created_at',
        ])
            ->leftJoin('users', function ($join) {
                $join->on('broadcast.id_user', '=', DB::raw('CAST(users.id AS TEXT)'));
            });

        if (!is_null($status)) {
            $model->where('broadcast.jenis_broadcast', '=', $status);
        }

        if (!empty($keyword) && empty($kolom)) {
            $boardcast = $model->where('users.nama_lengkap', 'ILIKE', '%'.$keyword.'%')
                ->orWhere('broadcast.judul_broadcast', 'ILIKE', '%'.$keyword.'%')
                ->paginate($perPage);
        } elseif (!empty($keyword) && !empty($kolom)) {
            if ($kolom == 'users.nama_lengkap') {
                $kolom = 'users.nama_lengkap';
            } else {
                $kolom = 'broadcast.judul_broadcast';
            }

            $boardcast = $model->where($kolom, 'ILIKE', '%'.$keyword.'%')
                ->paginate($perPage);
        } else {
            $boardcast = $model->paginate($perPage);
        }

        $boardcast->appends(['per-page' => $perPage]);

        return response()->json([
            'message' => 'Sukses',
            'data_boardcast' => $boardcast,
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
            'id_user' => 'required|max:225',
            'judul_broadcast' => 'required|max:225',
            'jenis_broadcast' => 'required',
            'text_broadcast' => 'required',
        ], $customMessages);

        $boardcast = new Boarcast();
        $boardcast->id_user = $request->id_user;
        $boardcast->judul_broadcast = $request->judul_broadcast;
        $boardcast->jenis_broadcast = $request->jenis_broadcast;
        $boardcast->text_broadcast = $request->text_broadcast;
        $boardcast->ip = $request->ip();
        try {
            if (!$user) {
                return response()->json([
                    'message' => 'Data Petugas tidak ditemukan',
                    'success' => false,
                ], 404);
            }
            $boardcast->save();
        } catch (\Exception $exception) {
            return response()->json([
                'message' => 'Gagal menambah data Boarcast'.$exception->getMessage(),
                'success' => false,
            ], 500);
        }

        unset($boardcast->created_at, $boardcast->updated_at);

        return response()->json([
            'message' => 'Data Boarcast berhasil ditambahkan',
            'data_boardcast' => $boardcast,
            'success' => true,
        ], 200);
    }

    public function edit(Request $request)
    {
        $request->validate([
            'id' => 'required|numeric|digits_between:1,5',
        ]);

        $boardcast = Boarcast::where('id', '=', $request->id)->first();

        $boardcast = Boarcast::select([
            'broadcast.id',
            'broadcast.id_user',
            'users.nama_lengkap as nama_petugas',
            'broadcast.judul_broadcast',
            'broadcast.jenis_broadcast',
            'broadcast.text_broadcast',
            'broadcast.ip',
            'broadcast.created_at',
        ])
            ->leftJoin('users', function ($join) {
                $join->on('broadcast.id_user', '=', DB::raw('CAST(users.id AS TEXT)'));
            })->where('broadcast.id', '=', $request->id)->first();

        if (!empty($boardcast)) {
            return response()->json([
                'message' => 'Sukses',
                'data_boardcast' => $boardcast,
                'success' => true,
            ], 200);
        }

        return response()->json([
            'message' => 'Data Boardcast tidak ditemukan',
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
            'judul_broadcast' => 'required|max:225',
            'jenis_broadcast' => 'required',
            'text_broadcast' => 'required',
        ], $customMessages);

        $boardcast = Boarcast::where('id', '=', $request->id)->first();

        if (!empty($boardcast)) {
            try {
                $boardcast->update([
                    'id' => $request->id,
                    'judul_broadcast' => $request->judul_broadcast,
                    'jenis_broadcast' => $request->jenis_broadcast,
                    'text_broadcast' => $request->text_broadcast,
                ]);
            } catch (\Exception $exception) {
                return response()->json([
                    'message' => 'Gagal mengupdate data Boardcast'.$exception->getMessage(),
                    'success' => false,
                ], 500);
            }

            return response()->json([
                'message' => 'Data Boardcast berhasil diupdate',
                'data_boardcast' => $boardcast,
                'success' => true,
            ], 200);
        }

        return response()->json([
            'message' => 'Data Boardcast tidak ditemukan',
            'success' => false,
        ], 200);
    }

    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required|numeric|digits_between:1,5',
        ]);

        $boardcast = Boarcast::where('id', '=', $request->id)
            ->first();

        if (!empty($boardcast)) {
            try {
                $boardcast = Boarcast::where('id', '=', $request->id)
                    ->delete();

                return response()->json([
                    'message' => 'Data Boardcast berhasil dihapus',
                    'success' => true,
                ], 200);
            } catch (\Exception $exception) {
                return response()->json([
                    'message' => 'Gagal menghapus data Boarcast'.$exception->getMessage(),
                    'success' => false,
                ], 500);
            }
        }

        return response()->json([
            'message' => 'Data Boarcast tidak ditemukan',
            'success' => false,
        ], 200);
    }
}
