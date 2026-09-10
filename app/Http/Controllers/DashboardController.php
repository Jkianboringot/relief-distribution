<?php

namespace App\Http\Controllers;

use App\Models\barangay;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
      
        return Inertia::render('dashboard');
    }
}