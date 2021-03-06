<?php

namespace App\Http\Controllers;

use App\Departamento;
use App\Http\Resources\DepartamentoResource;
use Illuminate\Support\Facades\DB;

class DepartamentoController extends Controller
{
    public function getAllDepartments()
    {
        $response = Departamento::all() ?: null;

        return response($response);
    }

    public function getByPais($idPais)
    {
        $departamentos = DB::table('departamentos')
            ->where('id_pais_fk', $idPais)->get();

        return response()->json($departamentos, 200);
    }

    public function getAllCitiesDepartment($dep)
    {
        $response = Departamento::find($dep);
        if ($response) {
            return response()->json($response->ciudades, 200);
        }

        return response()->json($response, 200);
    }

    public function findAll($idPais)
    {
        $departamentos = Departamento::where('id_pais_fk', $idPais)->get();

        return $this->success(DepartamentoResource::collection($departamentos));
    }
}
