<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\View\View;

class WelcomeController extends Controller
{
    public function index(): View
    {
        return view('welcome');
    }
}
