<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\jawaban_konsul;
use App\Models\rujukan_keluar;

class WelcomeController extends Controller
{
    function index1() {
        $show = jawaban_konsul::all();
        print_r($show);
        die();
        return response()->json($show, 200);
        // return view('pages.test1')->with('list', $show);
    }

    function index2() {
        $show = rujukan_keluar::all();
        print_r($show);
        die();
        return response()->json($show, 200);
        // return view('pages.test2')->with('list', $show);
    }
}
