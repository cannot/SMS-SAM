<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index');
    }

    public function notifications()
    {
        return view('reports.notifications');
    }

    public function users()
    {
        return view('reports.users');
    }

    public function performance()
    {
        return view('reports.performance');
    }

    public function export(Request $request)
    {
        $type = $request->get('type', 'pdf');
        // TODO: Implement report export
        return response()->json(['message' => 'Export functionality coming soon']);
    }
}