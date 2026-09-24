<?php

namespace App\Http\Controllers;

use App\Models\Barangay;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
      
        return Inertia::render('dashboard');
    }
}