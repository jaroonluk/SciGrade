<?php

namespace App\Http\Controllers;

use App\Services\StaffAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        private readonly StaffAuthService $staffAuth,
    ) {}

    public function index(): View
    {
        return view('home', [
            'role' => session('scigrade_role', 'instructor'),
            'staffDisplayName' => $this->staffAuth->displayNameFor(
                auth()->user()->email,
                auth()->user()->name,
            ),
        ]);
    }

    public function setRole(Request $request): RedirectResponse
    {
        $request->validate([
            'role' => ['required', 'in:instructor,dept_admin,faculty_admin'],
        ]);

        session(['scigrade_role' => $request->role]);

        return redirect()->route('dashboard');
    }
}
