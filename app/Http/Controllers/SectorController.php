<?php

namespace App\Http\Controllers;

use App\Http\Resources\SectorResource;
use App\Sector;
use Illuminate\Http\Request;

class SectorController extends Controller
{
    public function getAllSectors()
    {
        // $response = Sector::all() ?: null;
        // return response($response);
        return SectorResource::collection(Sector::all());
    }
}