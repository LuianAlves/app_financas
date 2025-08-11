<?php

namespace App\Http\Controllers;

use App\Services\ProjectionService;
use Illuminate\Http\Request;

class ProjectionController extends Controller
{
    public function index()
    {
        return view('app.projection.projection_index');
    }

    public function data(Request $req, ProjectionService $svc)
    {
        $userId = $req->user()->id;
        $start  = $req->query('start', now('America/Sao_Paulo')->startOfMonth()->toDateString());
        $end    = $req->query('end',   now('America/Sao_Paulo')->endOfMonth()->toDateString());

        return response()->json($svc->build($userId, $start, $end));
    }
}
