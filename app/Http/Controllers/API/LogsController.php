<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\logs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LogsController extends Controller
{
    public function list_browser()
    {
        $logs_browser = logs::select(['browser'])
            ->groupBy('browser')->orderBy('browser')->get();

        return response()->json([
            'message' => 'Sukses',
            'data_logs' => $logs_browser,
            'success' => true,
        ], 200);
    }

    public function list_os()
    {
        $logs_os = logs::select(['os'])
            ->groupBy('os')->orderBy('os')->get();

        return response()->json([
            'message' => 'Sukses',
            'data_logs' => $logs_os,
            'success' => true,
        ], 200);
    }

    public function list_device()
    {
        $logs_device = logs::select(['device'])
            ->groupBy('device')->orderBy('device')->get();

        return response()->json([
            'message' => 'Sukses',
            'data_logs' => $logs_device,
            'success' => true,
        ], 200);
    }

    public function list_status()
    {
        $logs_status = logs::select(['status_logs'])
            ->groupBy('status_logs')->orderBy('status_logs')->get();

        return response()->json([
            'message' => 'Sukses',
            'data_logs' => $logs_status,
            'success' => true,
        ], 200);
    }

    public function list(Request $request)
    {
        $keyword = $request->get('keyword', null);
        $perPage = $request->get('per-page', 10);
        $kolom = $request->get('kolom', null);
        $status = $request->get('status', null);
        $browser = $request->get('browser', null);
        $os = $request->get('os', null);
        $device = $request->get('device', null);

        if ($perPage > 100) {
            $perPage = 100;
        }

        $model = logs::select([
            'logs.id',
            'logs.user_id',
            'users.nama_lengkap',
            'logs.ip_address',
            'logs.aktifitas',
            'logs.status_logs',
            'logs.browser',
            'logs.os',
            'logs.device',
            'logs.engine_agent',
            'logs.latitude',
            'logs.longitude',
            'logs.updated_fields',
            'logs.created_at',
        ])->leftJoin('users', function ($join) {
            $join->on(DB::raw('logs.user_id'), '=', DB::raw('CAST(users.id AS CHAR)'))
                ->orOn(DB::raw('logs.user_id'), '=', DB::raw('users.uuid'));
        });

        $model->orderByRaw('logs.created_at IS NULL, logs.created_at DESC');

        if (!is_null($status)) {
            $model->where('logs.status_logs', '=', $status);
        }

        if (!is_null($browser)) {
            $model->where('logs.browser', '=', $browser);
        }

        if (!is_null($os)) {
            $model->where('logs.os', '=', $os);
        }

        if (!is_null($device)) {
            $model->where('logs.device', '=', $device);
        }

        if (!empty($keyword) && empty($kolom)) {
            $model->where(function ($q) use ($keyword) {
                $q->where('logs.aktifitas', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('users.nama_lengkap', 'LIKE', '%' . $keyword . '%');
            });
        } elseif (!empty($keyword) && !empty($kolom)) {
            if ($kolom == 'aktifitas') {
                $kolom = 'logs.aktifitas';
            } else {
                $kolom = 'users.nama_lengkap';
            }

            $model->where($kolom, 'LIKE', '%' . $keyword . '%');
        }

        $logUsers = $model->paginate($perPage);

        $logUsers->appends(['per-page' => $perPage]);

        return response()->json([
            'message' => 'Sukses',
            'data_logs' => $logUsers,
            'success' => true,
        ], 200);
    }

    public function listOp(Request $request)
    {
        $user = $request->user();
        $keyword = $request->get('keyword', null);
        $perPage = $request->get('per-page', 10);
        $kolom = $request->get('kolom', null);
        $status = $request->get('status', null);
        $browser = $request->get('browser', null);
        $os = $request->get('os', null);
        $device = $request->get('device', null);
        $userId = $request->get('user_id', $user->id);

        if ($perPage > 100) {
            $perPage = 100;
        }

        $model = logs::select([
            'logs.id',
            'logs.user_id',
            'users.nama_lengkap',
            'logs.ip_address',
            'logs.aktifitas',
            'logs.status_logs',
            'logs.browser',
            'logs.os',
            'logs.device',
            'logs.engine_agent',
            'logs.latitude',
            'logs.longitude',
            'logs.updated_fields',
            'logs.created_at',
        ])
            ->leftJoin('users', function ($join) {
                $join->on('logs.user_id', '=', DB::raw('CAST(users.id AS CHAR)'));
            });

        $model->orderByRaw('logs.created_at IS NULL, logs.created_at DESC');

        if (!is_null($status)) {
            $model->where('logs.status_logs', '=', $status);
        }

        if (!is_null($browser)) {
            $model->where('logs.browser', '=', $browser);
        }

        if (!is_null($os)) {
            $model->where('logs.os', '=', $os);
        }

        if (!is_null($device)) {
            $model->where('logs.device', '=', $device);
        }

        if (!is_null($userId)) {
            $model->where('users.id', '=', $userId);
        }

        if (!empty($keyword) && empty($kolom)) {
            $model->where(function ($q) use ($keyword) {
                $q->where('logs.aktifitas', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('users.nama_lengkap', 'LIKE', '%' . $keyword . '%');
            });
        } elseif (!empty($keyword) && !empty($kolom)) {
            if ($kolom == 'aktifitas') {
                $kolom = 'logs.aktifitas';
            } else {
                $kolom = 'users.nama_lengkap';
            }

            $model->where($kolom, 'LIKE', '%' . $keyword . '%');
        }

        $logUsers = $model->paginate($perPage);

        $logUsers->appends(['per-page' => $perPage]);

        return response()->json([
            'message' => 'Sukses',
            'data_logs' => $logUsers,
            'success' => true,
        ], 200);
    }
}
