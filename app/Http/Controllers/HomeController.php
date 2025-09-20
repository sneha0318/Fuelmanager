<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function root()
    {
        return view('index');
    }

    public function index(Request $request)
    {
        if (view()->exists($request->path())) {
            return view($request->path());
        }
        return view('pages-404');
    }
}
