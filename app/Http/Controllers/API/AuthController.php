<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\logs;
use App\Models\User;
use App\Models\dataCenter;
use App\Models\dataSensusPeserta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Jenssegers\Agent\Agent;
use Spatie\Permission\Models\Role;
// use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Illuminate\Support\Str;

class AuthController extends Controller
{
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
        // $responseGeoLocation = Http::get('https://api.ipgeolocation.io/ipgeo', [
        //     'apiKey' => env('IP_GEO_KEY')
        // ]);

        // $responseUserAgent = Http::get('https://api.ipgeolocation.io/user-agent', [
        //     'apiKey' => env('IP_GEO_KEY')
        // ]);
        $user = User::where('username', $request->username)->first();
        $agent = new Agent();
        // $geoInfo = $responseGeoLocation->json();
        // $userAgentInfo = $responseUserAgent->json();

        if (empty($user)) {
            return response()->json([
                'message' => 'User tidak ditemukan atau tidak terdaftar',
                'success' => false,
            ], 200);
        }

        if ($user->role_id !== 1) {
            // Lakukan validasi konfigurasi hanya jika role_id bukan 1
            $config = dataCenter::select('config_status', 'config_name', 'config_comment')
                ->first();

            if (!empty($config) && $config->config_status == 0) {
                // Jika konfigurasi ditemukan dan statusnya 0 (tidak aktif)
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
                    'message' => $config->config_comment, // Gunakan config_comment sebagai pesan kesalahan
                    'success' => false,
                ], 403); // Gunakan status 403 Forbidden untuk pembatasan sistem
            }
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

        // $logAccount = [
        //     'user_id' => $user->id,
        //     'ip_address' => $geoInfo['ip'],
        //     'aktifitas' => 'Login',
        //     'status_logs' => 'successfully',
        //     'string_agent' => $userAgentInfo['userAgentString'],
        //     'browser' => $userAgentInfo['name'] . '/' . $userAgentInfo['type'] . '/' . $userAgentInfo['version'] . '/' . $userAgentInfo['versionMajor'],
        //     'os' => $userAgentInfo['operatingSystem']['name'] . '/' . $userAgentInfo['operatingSystem']['type'] . '/' . $userAgentInfo['operatingSystem']['version'] . '/' . $userAgentInfo['operatingSystem']['versionMajor'],
        //     'device' => $userAgentInfo['device']['name'] . '/' . $userAgentInfo['device']['type'] . '/' . $userAgentInfo['device']['brand'] . '/' . $userAgentInfo['device']['cpu'],
        //     'engine_agent' => $userAgentInfo['engine']['name'] . '/' . $userAgentInfo['engine']['type'] . '/' . $userAgentInfo['engine']['version'] . '/' . $userAgentInfo['engine']['versionMajor'],
        //     'continent_name' => $geoInfo['continent_name'],
        //     'country_code2' => $geoInfo['country_code2'],
        //     'country_code3' => $geoInfo['country_code3'],
        //     'country_name' => $geoInfo['country_name'],
        //     'country_name_official' => $geoInfo['country_name_official'],
        //     'state_prov' => $geoInfo['state_prov'],
        //     'district' => $geoInfo['district'],
        //     'city' => $geoInfo['city'],
        //     'zipcode' => $geoInfo['zipcode'],
        //     'latitude' => $geoInfo['latitude'],
        //     'longitude' => $geoInfo['longitude'],
        //     'isp' => $geoInfo['isp'],
        //     'connection_type' => $geoInfo['connection_type'],
        //     'organization' => $geoInfo['organization'],
        //     'timezone' => $geoInfo['time_zone']['name'],
        // ];
        $logAccount = [
            'user_id' => $user->id,
            'ip_address' => $request->ip(),
            'aktifitas' => 'Login',
            'status_logs' => 'successfully',
            'browser' => $agent->browser(),
            'os' => $agent->platform(),
            'device' => $agent->device(),
            'engine_agent' => $request->header('user-agent'),
        ];
        logs::create($logAccount);

        $user_balikan = [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'status' => $user['status'],
            'role_id' => $role->code_uuid,
            'role_name' => $role ? $role->name : null,
            'nama_lengkap' => $user['nama_lengkap'],
            'tanggal' => $user['login_terakhir'],
            'akses_daerah' => $user['role_daerah'],
            'akses_desa' => $user['role_desa'],
            'akses_kelompok' => $user['role_kelompok'],
        ];

        $token = $user->createToken('token', [], Carbon::now()->addHours(8))->plainTextToken;

        return response()->json([
            'message' => 'Login Berhasil',
            'user' => $user_balikan,
            'token' => $token,
            'expires_at' => Carbon::now()->addHours(8)->toDateTimeString(),
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

    public function generateKodeUnik()
    {
        try {
            $data = dataSensusPeserta::whereNull('kode_cari_data')
                ->whereIn('jenis_data', ['SENSUS', 'KBM'])
                ->get();

            if ($data->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Semua data sudah memiliki kode unik.'
                ], 200);
            }

            foreach ($data as $item) {
                $prefix = $item->jenis_data === 'SENSUS' ? 'SEN' : 'KBM';

                // Contoh: SEN + YmdHis + 3 digit acak = 16 digit
                $kode = $prefix . date('ymdHis') . str_pad(random_int(0, 999), 3, '0', STR_PAD_LEFT);

                // Pastikan unik
                while (dataSensusPeserta::where('kode_cari_data', $kode)->exists()) {
                    $kode = $prefix . date('ymdHis') . str_pad(random_int(0, 999), 3, '0', STR_PAD_LEFT);
                }

                $item->kode_cari_data = $kode;
                $item->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Kode unik berhasil dibuat untuk data yang belum memiliki kode.',
                'total_updated' => $data->count(),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat membuat kode unik.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function generateUuid()
    {
        try {
            // Ambil semua user yang belum memiliki UUID
            $users = User::whereNull('uuid')->get();

            if ($users->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Semua user sudah memiliki UUID'
                ], 200);
            }

            // Update UUID untuk setiap user yang belum memiliki UUID
            foreach ($users as $user) {
                $user->uuid = Str::uuid(); // Generate UUID
                $user->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'UUID berhasil dibuat untuk pengguna yang belum memiliki UUID.',
                'total_updated' => $users->count(),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat membuat UUID.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
