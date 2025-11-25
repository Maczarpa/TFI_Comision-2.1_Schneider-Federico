<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use Illuminate\Http\Request;

class RolController extends Controller
{
    //
    public function index(Request $request){
        $roles = Rol::all();
        return response()->json($roles);
    }
}
