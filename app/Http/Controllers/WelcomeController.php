<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\user1;
use App\Models\user2;

class WelcomeController extends Controller
{
    function index1() {
        $show = user1::all();
        print_r($show);
        die();
        return view('pages.test1')->with('list', $show);
    }

    function index2() {
        $show = user2::all();
        print_r($show);
        die();
        return view('pages.test2')->with('list', $show);
    }
}
