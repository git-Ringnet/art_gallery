<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use App\Exports\ActivityLogsExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ActivityLogController extends Controller
{
    /**
     * Display a listing of activity logs (Admin only)
     */
    public function index(Request $request)
    {
        // Check if user is admin
        if (!Auth::user() || Auth::user()->email !== 'admin@example.com') {
            abort(403, 'Bạn không có quyền truy cập trang này');
        }

        $query = ActivityLog::with('user')->orderBy('created_at', 'desc');

        // Filter by user
        if ($request->filled('user_id')) {
            $query->byUser($request->user_id);
        }

        // Filter by activity type
        if ($request->filled('activity_type')) {
            $query->byActivityType($request->activity_type);
        }

        // Filter by module
        if ($request->filled('module')) {
            $query->byModule($request->module);
        }

        // Filter by date range
        if ($request->filled('from_date') || $request->filled('to_date')) {
            $query->byDateRange($request->from_date, $request->to_date);
        }

        // Filter by IP address
        if ($request->filled('ip_address')) {
            $query->byIpAddress($request->ip_address);
        }

        // Filter suspicious activities
        if ($request->filled('is_suspicious') && $request->is_suspicious == '1') {
            $query->suspicious();
        }

        // Search by description
        if ($request->filled('search')) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }

        $logs = $query->paginate(20)->withQueryString();

        // Get filter options
        $users = User::orderBy('name')->get();
        $activityTypes = ActivityLog::getActivityTypes();
        $modules = ActivityLog::getModules();

        return view('activity-logs.index', compact('logs', 'users', 'activityTypes', 'modules'));
    }

    /**
     * Display the specified activity log
     */
    public function show($id)
    {
        // Check if user is admin
        if (!Auth::user() || Auth::user()->email !== 'admin@example.com') {
            abort(403, 'Bạn không có quyền truy cập trang này');
        }

        $log = ActivityLog::with(['user', 'subject'])->findOrFail($id);

        return view('activity-logs.show', compact('log'));
    }

    /**
     * Display current user's activity history
     */
    public function myActivity(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('login');
        }

        $query = ActivityLog::where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        // Filter by activity type
        if ($request->filled('activity_type')) {
            $query->byActivityType($request->activity_type);
        }

        // Filter by date range
        if ($request->filled('from_date') || $request->filled('to_date')) {
            $query->byDateRange($request->from_date, $request->to_date);
        }

        $logs = $query->paginate(20)->withQueryString();

        $activityTypes = ActivityLog::getActivityTypes();

        return view('activity-logs.my-activity', compact('logs', 'activityTypes'));
    }

    /**
     * Export activity logs to Excel
     */
    public function exportExcel(Request $request)
    {
        // Check if user is admin
        if (!Auth::user() || Auth::user()->email !== 'admin@example.com') {
            abort(403, 'Bạn không có quyền truy cập trang này');
        }

        $query = ActivityLog::with('user')->orderBy('created_at', 'desc');

        // Apply same filters as index
        if ($request->filled('user_id')) {
            $query->byUser($request->user_id);
        }

        if ($request->filled('activity_type')) {
            $query->byActivityType($request->activity_type);
        }

        if ($request->filled('module')) {
            $query->byModule($request->module);
        }

        if ($request->filled('from_date') || $request->filled('to_date')) {
            $query->byDateRange($request->from_date, $request->to_date);
        }

        if ($request->filled('ip_address')) {
            $query->byIpAddress($request->ip_address);
        }

        if ($request->filled('is_suspicious') && $request->is_suspicious == '1') {
            $query->suspicious();
        }

        if ($request->filled('search')) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }

        $logs = $query->get();

        $filename = 'activity-logs-' . date('Y-m-d-His') . '.xlsx';
        
        return Excel::download(new ActivityLogsExport($logs), $filename);
    }

    /**
     * Export activity logs to PDF
     */
    public function exportPdf(Request $request)
    {
        // Check if user is admin
        if (!Auth::user() || Auth::user()->email !== 'admin@example.com') {
            abort(403, 'Bạn không có quyền truy cập trang này');
        }

        $query = ActivityLog::with('user')->orderBy('created_at', 'desc');

        // Apply same filters as index
        if ($request->filled('user_id')) {
            $query->byUser($request->user_id);
        }

        if ($request->filled('activity_type')) {
            $query->byActivityType($request->activity_type);
        }

        if ($request->filled('module')) {
            $query->byModule($request->module);
        }

        if ($request->filled('from_date') || $request->filled('to_date')) {
            $query->byDateRange($request->from_date, $request->to_date);
        }

        if ($request->filled('ip_address')) {
            $query->byIpAddress($request->ip_address);
        }

        if ($request->filled('is_suspicious') && $request->is_suspicious == '1') {
            $query->suspicious();
        }

        if ($request->filled('search')) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }

        $logs = $query->get();

        $pdf = Pdf::loadView('activity-logs.export-pdf', compact('logs'));
        
        $filename = 'activity-logs-' . date('Y-m-d-His') . '.pdf';
        return $pdf->download($filename);
    }
}
