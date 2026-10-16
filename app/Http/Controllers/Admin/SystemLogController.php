<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemLog;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SystemLogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = SystemLog::query()
            ->with('user')
            ->when($request->filled('search'), fn ($query) => $query->where(fn ($query) => $query
                ->where('description', 'like', '%'.$request->string('search').'%')
                ->orWhere('action', 'like', '%'.$request->string('search').'%')))
            ->when($request->filled('action'), fn ($query) => $query->where('action', $request->string('action')))
            ->latest()
            ->paginate((int) SystemSetting::valueFor('items_per_page', 10))
            ->withQueryString();

        return view('admin.logs.index', [
            'logs' => $logs,
            'actions' => SystemLog::query()->distinct()->orderBy('action')->pluck('action'),
            'totalLogCount' => SystemLog::count(),
        ]);
    }

    public function clearAll(): RedirectResponse
    {
        SystemLog::query()->delete();

        return redirect()->route('admin.logs.index')
            ->with('success', 'All system logs were cleared.');
    }
}
