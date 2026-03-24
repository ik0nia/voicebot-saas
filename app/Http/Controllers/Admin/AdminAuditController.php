<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class AdminAuditController extends Controller
{
    public function index()
    {
        return view('admin.audit');
    }
}
