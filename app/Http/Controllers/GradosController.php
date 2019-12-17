<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Http\Controllers;

use \App\Grado;
use Illuminate\Support\Facades\DB;
use \App\Http\Resources\GradosResource;
/**
 * Description of GradosController
 *
 * @author sebastian
 */
class GradosController extends Controller {
    
    public function getByIdEgresado($idEgresado) {
        $grados = Grado::where('id_estudiante', $idEgresado)->get();
        return $this->success(GradosResource::collection($grados));
    }
    
}
