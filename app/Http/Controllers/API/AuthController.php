<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\logs;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Jenssegers\Agent\Agent;
use Spatie\Permission\Models\Role;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'logout']]);
    }

    public function login(Request $request)
    {
        $user = User::where('username', $request->username)->first();
        $agent = new Agent();

        // set validation
        $validator = Validator::make($request->all(), [
            'username' => 'required',
            'password' => 'required',
        ]);

        // if validation fails
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if (empty($user)) {
            return response()->json([
                'message' => 'User tidak ditemukan',
                'success' => false,
            ], 200);
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

            $responseMessage = ($user->status == -1)
                ? 'Akses akun anda di Blokir, Silahkan hubungi admin bidang'
                : 'Mohon maaf, akun anda Non-Aktif. Silahkan hubungi admin bidang';

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

        // get credentials from request
        $credentials = $request->only('username', 'password');

        // if auth failed
        if (!$token = auth()->guard('api')->attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Username atau Password Anda salah',
            ], 401);
        }

        return response()->json([
            'message' => 'Login Berhasil',
            'user' => $user_balikan,
            'user1' => auth()->guard('api')->user(),
            'token' => $token,
            'success' => true,
        ], 200);
    }

    public function logout(Request $request)
    {
        // $user = $request->user();
        // $user->currentAccessToken()->delete();
        $removeToken = JWTAuth::invalidate(JWTAuth::getToken());

        if ($removeToken) {
            // return response JSON
            return response()->json([
                'success' => true,
                'message' => 'Logout Berhasil!',
            ]);
        }

        // return response()->json([
        //     'message' => 'Berhasil logout',
        //     'success' => true,
        // ]);
    }
}
