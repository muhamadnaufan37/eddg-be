<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\logs;
use App\Models\User;
use App\Models\dataCenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Jenssegers\Agent\Agent;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    public function load_data_center(Request $request)
    {
        $request->validate([
            'id' => 'required|numeric|digits_between:1,5',
        ]);

        $config = dataCenter::select([
            'config_status',
        ])->where('id', '=', $request->id)->first();

        if (!empty($config)) {
            // Jika config_status memiliki nilai 1, tampilkan hanya config_status
            if ($config->config_status == 1) {
                return response()->json([
                    'config_status' => $config->config_status,
                    'success' => true,
                ], 200);
            }

            // Jika config_status bukan 1, tampilkan seluruh data config
            return response()->json([
                'message' => 'Sukses',
                'data_config' => $config,
                'success' => true,
            ], 200);
        }

        return response()->json([
            'message' => 'Data Config tidak ditemukan',
            'success' => false,
        ], 200);
    }
    public function register(Request $request)
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
            'nama_lengkap' => 'required|unique:users',
            'email' => 'required|email|unique:users',
            'username' => 'required|unique:users',
            'password' => 'required',
            'role_id' => 'required|max:1',
            'status' => 'required|max:1',
        ], $customMessages);

        $register_akun = new User();
        $register_akun->nama_lengkap = $request->nama_lengkap;
        $register_akun->email = $request->email;
        $register_akun->username = $request->username;
        $register_akun->password = bcrypt($request->password);
        $register_akun->role_id = $request->role_id;
        $register_akun->status = $request->status;
        try {
            $register_akun->save();
        } catch (\Exception $exception) {
            return response()->json([
                'message' => 'Gagal menambah Akun' . $exception->getMessage(),
                'success' => false,
            ], 500);
        }

        unset($register_akun->created_at, $register_akun->updated_at);

        return response()->json([
            'message' => 'Akun berhasil ditambahkan',
            'data_kelompok' => $register_akun,
            'success' => true,
        ], 200);
    }

    public function login(Request $request)
    {
        $user = User::where('username', $request->username)->first();
        $agent = new Agent();

        if (empty($user)) {
            return response()->json([
                'message' => 'User tidak ditemukan atau tidak terdaftar',
                'success' => false,
            ], 200);
        }

        // Fetch config status related to the user
        $config = dataCenter::select('config_status', 'config_name', 'config_comment')
            ->where('config.id', '=', $user->id)
            ->first();

        // Block login if config_status is 0 and show config_comment message
        if (!empty($config) && $config->config_status == 0) {
            $logAccount = [
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'aktifitas' => 'Login - [proses login ilegal]',
                'status_logs' => 'unsuccessfully',
                'browser' => $agent->browser(),
                'os' => $agent->platform(),
                'device' => $agent->device(),
            ];
            logs::create($logAccount);

            return response()->json([
                'title' => $config->config_name,
                'message' => $config->config_comment, // Use config_comment for the error message
                'success' => false,
            ], 403); // Use 403 Forbidden status for system restriction
        }

        if (!$user || !Hash::check($request->password, $user->password)) {
            $logAccount = [
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'aktifitas' => 'Login - [username atau passwor salah]',
                'status_logs' => 'unsuccessfully',
                'browser' => $agent->browser(),
                'os' => $agent->platform(),
                'device' => $agent->device(),
            ];
            logs::create($logAccount);

            return response()->json([
                'message' => 'username atau password salah',
                'success' => false,
            ]);
        }

        if ($user->status == -1 || $user->status == 0) {
            $statusMessage = ($user->status == -1) ? 'Akun Di Blokir' : 'Akun Tidak AKtif';

            $logAccount = [
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'aktifitas' => "Login - [$statusMessage]",
                'status_logs' => 'unsuccessfully',
                'browser' => $agent->browser(),
                'os' => $agent->platform(),
                'device' => $agent->device(),
            ];

            logs::create($logAccount);

            $user_balikan = [
                'reason_ban' => $user['reason_ban'],
            ];

            $responseMessage = ($user->status == -1)
                ? 'Akses akun anda di Dibatasi, Silahkan hubungi Administrator. Karena : ' . ($user_balikan['reason_ban'] ?? '-')
                : 'Mohon maaf, akun anda Non-Aktif. Silahkan hubungi Administrator';

            return response()->json([
                'status' => $statusMessage,
                'message' => $responseMessage,
                'success' => false,
            ]);
        }

        $role = $user->role_id ? Role::findById($user->role_id) : null;

        User::where('id', '=', $user->id)->update([
            'login_terakhir' => date('Y-m-d H:i:s'),
        ]);

        $user->save();

        $logAccount = [
            'user_id' => $user->id,
            'ip_address' => $request->ip(),
            'aktifitas' => 'Login',
            'status_logs' => 'successfully',
            'browser' => $agent->browser(),
            'os' => $agent->platform(),
            'device' => $agent->device(),
        ];
        logs::create($logAccount);

        $user_balikan = [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'status' => $user['status'],
            'role_id' => $user['role_id'],
            'role_name' => $role ? $role->name : null,
            'nama_lengkap' => $user['nama_lengkap'],
            'tanggal' => $user['login_terakhir'],
            'akses_daerah' => $user['role_daerah'],
            'akses_desa' => $user['role_desa'],
            'akses_kelompok' => $user['role_kelompok'],
        ];

        $token = $user->createToken('token')->plainTextToken;

        return response()->json([
            'message' => 'Login Berhasil',
            'user' => $user_balikan,
            'token' => $token,
            'success' => true,
        ], 200);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        $user->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Berhasil logout',
            'success' => true,
        ]);
    }
}
