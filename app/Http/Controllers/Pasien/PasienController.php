<?php

namespace App\Http\Controllers\Pasien;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\master\pasien;

class PasienController extends Controller
{
    function getPasien($rm) {
        $show = pasien::where('NORM',$rm)->first();

        return response()->json($show, 200);
        // return view('pages.test1')->with('list', $show);
    }
}
