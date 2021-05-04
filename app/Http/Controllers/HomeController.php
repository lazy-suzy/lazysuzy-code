<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\DepartmentMapping;
use Illuminate\Http\Request;
use Auth;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth')->except('index');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $departments = Department::get();
        
        if (Auth::check()) {
            setcookie('__user_id', Auth::user()->id);
        }
        return view('pages.homepage' ,compact('departments'));
    }
}
