<?php

namespace App\Http\Controllers;

use App\AdministradorEmpresa;
use App\Cargo;
use App\Ciudad;
use App\Departamento;
use Illuminate\Http\Request;

use App\Empresa;
use App\Helpers\JwtAuth;
use App\Http\Requests\EmpresaFormRequest;
use App\Localizacion;
use App\RepresentanteEmpresa;
use App\Role;
use App\Sector;
use App\SubSector;
use App\User;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmpresaController extends Controller
{

    public function index()
    {
        $empresas = Empresa::all();

        return response()->json($empresas, 200);
    }

    public function getEmpresasEnEspera()
    {
        $empresas = Empresa::orderBy('fecha_registro', 'ASC')
            ->where('estado', 'En espera')->get();

        return response()->json($empresas, 200);
    }

    public function showAllInfo($id)
    {
        // Codigo de error por defecto
        $code = 404;
        $empresa = Empresa::find($id);

        if (is_object($empresa)) {
            $empresa->load('direccion', 'representante', 'administrador');
            $empresa->direccion->load('ciudad');
            $empresa->direccion->ciudad->load('departamento');
            $empresa->direccion->ciudad->departamento->load('pais');

            $empresa->administrador->load('direccion', 'user', 'cargo');

            $sectores = [];

            //Por cada subsector obtengo el sector
            foreach ($empresa->subSectores as $subSector) {
                $res = Sector::find($subSector->id_sectores)->first();
                if (!isset($sectores->$res)) {
                    $sectores[] = $res;
                }
                // Se borra el atributo pivot, el cual no es necesario
                unset($subSector['pivot']);
            }
            $empresa['sectores'] = $sectores;

            foreach ($empresa->sectores as $sector) {
                $subsec = [];
                foreach ($empresa->subSectores as $subSector) {
                    if ($subSector->id_sectores == $sector->id_aut_sector) {
                        $subsec[] = $subSector;
                    }
                }
                $sector['subSectores'] = $subsec;
            }

            unset($empresa['subSectores']);

            $code = 200;
            $data = $empresa;
        } else {
            $data = null;
        }
        return response()->json($data, $code);
    }

    public function update(Request $request)
    {
        // // Recoger los datos por POST
        // $json = $request->input('json', null);
        // $params_array = json_decode($json, true);

        // Código de error por defecto
        $code = 400;
        $data = null;

        // if (!empty($params_array)) {

        //     // Eliminar lo que no queremos actualizar
        //     unset($params_array['id']);
        //     unset($params_array['nombre']);
        //     unset($params_array['razon_social']);
        //     unset($params_array['anio_creacion']);
        //     unset($params_array['fecha_registro']);
        //     unset($params_array['fecha_activacion']);


        //     // Buscar el registro
        //     // $empresa = Empresa::where('id', $id)->first();
        //     $empresa = Empresa::find($id);

        //     if (!empty($empresa) && is_object($empresa)) {

        //         // Actualizar el registro en concreto
        //         $empresa->update($params_array);

        //         $data = $empresa;
        //         $code = 200;
        //     }
        // }
        // return response()->json($data, $code);


        $empresa = Empresa::first();
        // return response()->json($empresa->administrador->id_aut_user);
        if (!$empresa) {
            return response()->json($empresa);
        }
        try {
            // return response()->json($request);
            $this->validate(request(), [

                'datos-cuenta.email' => 'required|max:255|email|unique:users,email,' . $empresa->administrador->id_aut_user . ',id_aut_user',
                'datos-cuenta.contrasenia' => 'string|min:6',

                // Datos empresa
                'datos-generales-empresa.NIT' => 'required|integer|digits:8|unique:empresas,nit,' . $empresa->id_aut_empresa . ',id_aut_empresa',
                'datos-generales-empresa.razonSocial' => 'required|string',
                'datos-generales-empresa.nombreEmpresa' => 'required|unique:empresas,nombre,' . $empresa->id_aut_empresa . ',id_aut_empresa',
                'datos-generales-empresa.anioCreacion' => 'required|numeric|between:1900,' . Carbon::now()->format("Y"),
                'datos-generales-empresa.numEmpleados' => 'required|integer',
                'datos-generales-empresa.ingresosEmp' => 'required|string',
                'datos-generales-empresa.descripcionEmpresa' => 'required|string',

                'loc-contact-empresa.ciudadEmp' => 'required|exists:ciudades,id_aut_ciudad',
                'loc-contact-empresa.direccionEmp' => 'required|string',
                'loc-contact-empresa.barrioEmp' => 'required|string',
                'loc-contact-empresa.codigoPostalEmp' => 'integer',
                'loc-contact-empresa.telefonoEmp' => 'integer',
                'loc-contact-empresa.emailEmp' => 'email',
                'loc-contact-empresa.sitioWebEmp' => 'url',
                // 'loc-contact-empresa.sitioWebEmp' => 'url|active_url',

                // 'sectores' => 'required|array',
                'sectores.sectores' => 'required|array',
                'sectores.sectores.*.subSectores.*.idSector' => 'required|integer|exists:sectores,id_aut_sector',
                // 'sectores.sectores.*' => 'required|integer|exists:sectores,id_aut_sector',
                // //datos representante
                'datos-resp.nombrereplegal'  => 'required|string',
                'datos-resp.apellidoreplegal'  => 'required|string',
                'datos-resp.telefonoreplegal'  => 'integer',
                'datos-resp.telefonoMovilreplegal'  => 'required|integer',
                // 'datos-resp.barrioResp' => 'required|string',
                // 'datos-resp.ciudadResp' => 'required|exists:ciudades,id_aut_ciudad',
                // 'datos-resp.codigoPostalResp' => 'required|integer',
                'datos-resp.nombreResp' => 'required|string',
                'datos-resp.apellidoResp'  => 'required|string',
                'datos-resp.cargo'  => 'required|string',
                'datos-resp.telefonoResp'  => 'integer',
                'datos-resp.telefonoMovilResp'  => 'required|integer',
                'datos-resp.horarioContactoResp'  => 'required|string',
                'datos-resp.direccionTrabajoResp' => 'required|string',
                'datos-resp.emailCorpResp'  => 'required|email',
            ]);
            // return response()->json(request());
            $user = $empresa->administrador->user;
            // return response()->json($user);
            // $user->email = request('datos-cuenta.email');
            $user->email = $request['datos-cuenta']['email'];
            if (!empty($request['datos-cuenta']['contrasenia'])) {
                $user->password = bcrypt($request['datos-cuenta']['contrasenia']);
            }
            // return response()->json($user); 

            $direccionEmpr = $empresa->direccion;
            // return response()->json($direccionEmpr);
            if (!empty($request['loc-contact-empresa']['codigoPostalEmp'])) {
                $direccionEmpr->codigo_postal = $request['loc-contact-empresa']['codigoPostalEmp'];
            }
            $direccionEmpr->direccion = $request['loc-contact-empresa']['direccionEmp'];
            $direccionEmpr->barrio = $request['loc-contact-empresa']['barrioEmp'];
            $direccionEmpr->ciudad()->associate(Ciudad::find($request['loc-contact-empresa']['ciudadEmp']));
            // return response()->json($direccionEmpr);

            // $empresa = new Empresa();
            $empresa->nit = $request['datos-generales-empresa']['NIT'];
            $empresa->nombre = $request['datos-generales-empresa']['nombreEmpresa'];
            $empresa->razon_social = $request['datos-generales-empresa']['razonSocial'];
            $empresa->numero_empleados = $request['datos-generales-empresa']['numEmpleados'];
            $empresa->ingresos = $request['datos-generales-empresa']['ingresosEmp'];
            if (!empty($request['loc-contact-empresa']['sitioWebEmp'])) {
                $empresa->sitio_web = $request['loc-contact-empresa']['sitioWebEmp'];
            }
            $empresa->anio_creacion = $request['datos-generales-empresa']['anioCreacion'];
            $empresa->url_doc_camaracomercio = "url pdf camara y comercio";

            if (!empty($request['loc-contact-empresa']['telefonoEmp'])) {
                $empresa->telefono = $request['loc-contact-empresa']['telefonoEmp'];
            }
            if (!empty($request['loc-contact-empresa']['emailEmp'])) {
                $empresa->correo = $request['loc-contact-empresa']['emailEmp'];
            }
            // return response()->json($empresa);

            // $empresa->estado = "En espera";
            // $empresa->fecha_registro = Carbon::now();
            // $empresa->total_publicaciones = 0;
            // $empresa->limite_publicaciones = 0;
            // $empresa->num_publicaciones_actuales = 0;
            // return response()->json($empresa);

            $direccionAdministrador = $empresa->administrador->direccion;
            // $direccionAdministrador->codigo_postal = $request['datos-resp']['codigoPostalResp'];
            $direccionAdministrador->codigo_postal = $request['loc-contact-empresa']['codigoPostalEmp'];
            $direccionAdministrador->direccion = $request['datos-resp']['direccionTrabajoResp'];
            // $direccionAdministrador->barrio = $request['datos-resp']['barrioResp'];
            $direccionAdministrador->barrio = $request['loc-contact-empresa']['barrioEmp'];
            $direccionAdministrador->ciudad()->associate(Ciudad::find($request['loc-contact-empresa']['ciudadEmp']));
            // return response()->json($direccionAdministrador);
            // if (!$dir_empresa) { }

            $representanteLegal = $empresa->representante;
            $representanteLegal->nombre = $request['datos-resp']['nombrereplegal'];
            $representanteLegal->apellidos = $request['datos-resp']['apellidoreplegal'];
            if (!empty($request['datos-resp']['telefonoreplegal'])) {
                $representanteLegal->telefono = $request['datos-resp']['telefonoreplegal'];
            }
            $representanteLegal->telefono_movil = $request['datos-resp']['telefonoMovilreplegal'];
            // return response()->json($representanteLegal);


            $administradorEmp = $empresa->administrador;
            $administradorEmp->nombres = $request['datos-resp']['nombreResp'];
            $administradorEmp->apellidos = $request['datos-resp']['apellidoResp'];
            if (!empty($request['datos-resp']['telefonoResp'])) {
                $administradorEmp->telefono = $request['datos-resp']['telefonoResp'];
            }
            $administradorEmp->telefono_movil = $request['datos-resp']['telefonoMovilResp'];

            $administradorEmp->correo_corporativo = $request['datos-resp']['emailCorpResp'];
            // return response()->json([$administradorEmp, $request['datos-resp']['cargo']]);
            // return response()->json(Cargo::find(request('rep_id_cargo'))->firstOrFail());
            // return response()->json($administradorEmp);
            // return response()->json([$user, $empresa, $representanteLegal, $administradorEmp]);

            $ids = array();
            foreach ($request['sectores']['sectores'] as $sect) {
                foreach ($sect["subSectores"] as $subs) {
                    array_push($ids, $subs["idSector"]);
                }
            }
            // return response()->json($ids);
            // return response()->json($request['sectores']['sectores'][0]['subSectores'][0]['idSector']);

            // DB::transaction(function () use ($user, $direccionEmpr, $empresa, $direccionAdministrador, $administradorEmp, $representanteLegal) {
            DB::beginTransaction();
            $user->update();
            // return response()->json("aqui va");
            $direccionEmpr->update();
            // $empresa->direccion()->associate($direccionEmpr);
            $empresa->update();

            // $empresa->subSectores()->sync($request['sectores']['sectores']);
            $empresa->subSectores()->sync($ids);


            // if ($dir_empresa) {
            //     $administradorEmp->direccion()->associate($direccionEmpr);
            // } else {
            $direccionAdministrador->update();
            $cargo = Cargo::firstOrCreate(["nombre" => $request['datos-resp']['cargo']], ["estado" => false]);
            // return response()->json($cargo);
            if (!$cargo) {
                $cargo = new Cargo();
                $cargo->nombre = $request['datos-resp']['cargo'];
                $cargo->estado = false;
                $current_id = DB::table('cargos')->max('id_aut_cargos');
                $cargo->id_aut_cargos = $current_id + 1;
                $cargo->save();
            }
            // return response()->json($request['datos-resp']['cargo']);
            // $administradorEmp->direccion()->associate($direccionAdministrador);
            // $administradorEmp->user()->associate($user);
            $administradorEmp->cargo()->associate($cargo);
            // $administradorEmp->empresa()->associate($empresa);
            // }
            $administradorEmp->update();
            // return response()->json($representante);

            // $representanteLegal->empresa()->associate($empresa);
            $representanteLegal->update();
            // });

            DB::commit();
            return response()->json($empresa, 200);
        } catch (ValidationException $ev) {
            return response()->json($ev->validator->errors(), $code);
        } catch (Exception $e) {
            return response()->json($e);
        }

        return response()->json($data, $code);
    }


    public function updateEstado($id, Request $request)
    {
        // Recoger los datos por PUT
        $json = $request->input('json', null);
        $params_array = json_decode($json, true);

        // Código de error por defecto
        $code = 400;
        $data = null;

        if (!empty($params_array)) {
            // Buscar el registro
            $empresa = Empresa::find($id);

            if (!empty($empresa) && is_object($empresa)) {

                if ($params_array['estado'] == 'Activo' && !empty($params_array['limite_publicaciones'])) {
                    // Actualizar el registro en concreto

                    $empresa->update([
                        'estado' => $params_array['estado'],
                        'limite_publicaciones' => $params_array['limite_publicaciones'],
                        'fecha_activacion' => Carbon::now('-5:00'),
                    ]);
                    $data = $empresa;
                    $code = 200;
                } else if ($params_array['estado'] == 'En espera' || $params_array['estado'] == 'Inactivo') {
                    // Actualizar el registro en concreto
                    $empresa->update(['estado' => $params_array['estado']]);
                    $data = $empresa;
                    $code = 200;
                }
            }
        }
        return response()->json($data, $code);
    }


    /**
     * Almacene un recurso recién creado en el almacenamiento.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    // public function store(EmpresaFormRequest $request)
    public function store(Request $request)
    {
        // Código de error por defecto
        $code = 400;
        $data = null;
        try {
            // $files = requ
            // return response()->json([$request['archivos']['camaraycomercio'], "AQUI"], 400);
            
            $this->validate($request, [
                'datos-cuenta.email' => 'required|max:255|email|unique:users,email',
                'datos-cuenta.contrasenia' => 'required|string|min:6',

                // Datos empresa
                'datos-generales-empresa.NIT' => 'required|integer||digits_between:8,16|unique:empresas,nit',
                'datos-generales-empresa.razonSocial' => 'required|string',
                'datos-generales-empresa.nombreEmpresa' => 'required|unique:empresas,nombre',
                'datos-generales-empresa.anioCreacion' => 'required|numeric|between:1900,' . Carbon::now()->format("Y"),
                'datos-generales-empresa.numEmpleados' => 'required|string',
                'datos-generales-empresa.ingresosEmp' => 'required|string',
                'datos-generales-empresa.descripcionEmpresa' => 'required|string',

                'loc-contact-empresa.ciudadEmp' => 'required|exists:ciudades,id_aut_ciudad',
                'loc-contact-empresa.direccionEmp' => 'required|string',
                'loc-contact-empresa.barrioEmp' => 'required|string',
                'loc-contact-empresa.codigoPostalEmp' => 'numeric',
                'loc-contact-empresa.telefonoEmp' => 'integer',
                'loc-contact-empresa.emailEmp' => 'email',
                'loc-contact-empresa.sitioWebEmp' => 'url',
                // 'loc-contact-empresa.sitioWebEmp' => 'url|active_url',

                'sectores.sectores' => 'required|array',
                'sectores.sectores.*' => 'required|integer|exists:sectores,id_aut_sector',
                // 'sectores.sectores' => 'required|array',
                // 'sectores.sectores.*.subSectores.*.idSector' => 'required|integer|exists:sectores,id_aut_sector',
                // //datos representante
                'datos-resp.nombrereplegal'  => 'required|string',
                'datos-resp.apellidoreplegal'  => 'required|string',
                'datos-resp.telefonoreplegal'  => 'numeric|digits_between:1,16',
                'datos-resp.telefonoMovilreplegal'  => 'required|numeric|digits_between:1,16',
                // 'datos-resp.barrioResp' => 'required|string',
                // 'datos-resp.ciudadResp' => 'required|exists:ciudades,id_aut_ciudad',
                // 'datos-resp.codigoPostalResp' => 'required|integer',
                'datos-resp.nombreResp' => 'required|string',
                'datos-resp.apellidoResp'  => 'required|string',
                'datos-resp.cargo'  => 'required|string',
                'datos-resp.telefonoResp'  => 'numeric|digits_between:1,16',
                'datos-resp.telefonoMovilResp'  => 'required|digits_between:1,16',
                'datos-resp.horarioContactoResp'  => 'string',
                'datos-resp.direccionTrabajoResp' => 'required|string',
                'datos-resp.emailCorpResp'  => 'required|email',

                'archivos.logo' => 'image',
                'archivos.camaraycomercio' => 'required|mimes:pdf',
            ]);
            // return response()->json(request());
            $user = new User();
            // $user->email = request('datos-cuenta.email');
            $user->email = $request['datos-cuenta']['email'];
            $user->password = bcrypt($request['datos-cuenta']['contrasenia']);
            // return response()->json(request());
            $rol = Role::whereNombre('Empresa')->firstOrFail();
            if (!$rol) {
                return response()->json("AL pareces no hay datos en la BD!");
            }
            $user->rol()->associate(Role::whereNombre('Empresa')->firstOrFail());
            // return response()->json($user);

            $direccionEmpr = new Localizacion();
            if ($request['loc-contact-empresa']['codigoPostalEmp']) {
                $direccionEmpr->codigo_postal = $request['loc-contact-empresa']['codigoPostalEmp'];
            }
            $direccionEmpr->direccion = $request['loc-contact-empresa']['direccionEmp'];
            $direccionEmpr->barrio = $request['loc-contact-empresa']['barrioEmp'];
            $direccionEmpr->ciudad()->associate(Ciudad::find($request['loc-contact-empresa']['ciudadEmp']));
            // return response()->json($direccionEmpr);

            $empresa = new Empresa();
            $empresa->nit = $request['datos-generales-empresa']['NIT'];
            $empresa->nombre = $request['datos-generales-empresa']['nombreEmpresa'];
            $empresa->razon_social = $request['datos-generales-empresa']['razonSocial'];
            $empresa->numero_empleados = $request['datos-generales-empresa']['numEmpleados'];
            $empresa->ingresos = $request['datos-generales-empresa']['ingresosEmp'];
            if ($request['loc-contact-empresa']['sitioWebEmp']) {
                $empresa->sitio_web = $request['loc-contact-empresa']['sitioWebEmp'];
            }
            $empresa->anio_creacion = $request['datos-generales-empresa']['anioCreacion'];
            $empresa->url_doc_camaracomercio = "url pdf camara y comercio";

            if ($request['loc-contact-empresa']['telefonoEmp']) {
                $empresa->telefono = $request['loc-contact-empresa']['telefonoEmp'];
            }
            if ($request['loc-contact-empresa']['emailEmp']) {
                $empresa->correo = $request['loc-contact-empresa']['emailEmp'];
            }
            // return response()->json($empresa);

            $empresa->estado = "En espera";
            $empresa->fecha_registro = Carbon::now();
            $empresa->total_publicaciones = 0;
            $empresa->limite_publicaciones = 0;
            $empresa->num_publicaciones_actuales = 0;
            // return response()->json($empresa);

            // $dir_empresa = request('dir_empresa');
            $direccionAdministrador = new Localizacion();
            // $direccionAdministrador->codigo_postal = $request['datos-resp']['codigoPostalResp'];
            $direccionAdministrador->codigo_postal = $request['loc-contact-empresa']['codigoPostalEmp'];
            $direccionAdministrador->direccion = $request['datos-resp']['direccionTrabajoResp'];
            // $direccionAdministrador->barrio = $request['datos-resp']['barrioResp'];
            $direccionAdministrador->barrio = $request['loc-contact-empresa']['barrioEmp'];
            $direccionAdministrador->ciudad()->associate(Ciudad::find($request['loc-contact-empresa']['ciudadEmp']));
            // return response()->json($direccionAdministrador);
            // if (!$dir_empresa) { }

            $representanteLegal = new RepresentanteEmpresa();
            $representanteLegal->nombre = $request['datos-resp']['nombrereplegal'];
            $representanteLegal->apellidos = $request['datos-resp']['apellidoreplegal'];
            if ($request['datos-resp']['telefonoreplegal']) {
                $representanteLegal->telefono = $request['datos-resp']['telefonoreplegal'];
            }
            $representanteLegal->telefono_movil = $request['datos-resp']['telefonoMovilreplegal'];
            // return response()->json($representanteLegal);


            $representante = new AdministradorEmpresa();
            $representante->nombres = $request['datos-resp']['nombreResp'];
            $representante->apellidos = $request['datos-resp']['apellidoResp'];
            if ($request['datos-resp']['telefonoResp']) {
                $representante->telefono = $request['datos-resp']['telefonoResp'];
            }
            $representante->telefono_movil = $request['datos-resp']['telefonoMovilResp'];

            $representante->correo_corporativo = $request['datos-resp']['emailCorpResp'];
            $ids = array();
            // return response()->json($request['sectores']['sectores']);
            foreach ($request['sectores']['sectores'] as $sect) {
                // foreach ($sect["subSectores"] as $subs) {
                // return response()->json($sect);
                // array_push($ids, $subs["idSubSector"]);
                array_push($ids, $sect);
                // }
            }
            // return response()->json("aqui va");

            // DB::transaction(function () use ($user, $direccionEmpr, $empresa, $direccionAdministrador, $representante, $representanteLegal) {
            DB::beginTransaction();
            $user->save();
            // return response()->json("aqui va");
            $direccionEmpr->save();
            $empresa->direccion()->associate($direccionEmpr);
            $empresa->save();
            // $empresa->subSectores()->sync($request['sectores']['sectores']);
            $empresa->subSectores()->sync($ids);


            // if ($dir_empresa) {
            //     $representante->direccion()->associate($direccionEmpr);
            // } else {
            $direccionAdministrador->save();
            $cargo = Cargo::whereNombre($request['datos-resp']['cargo'])->first();
            // $cargo = Cargo::firstOrCreate(["nombre" => $request['datos-resp']['cargo']], ["estado" => false]);
            // return response()->json($cargo);
            if (!$cargo) {
                $cargo = new Cargo();
                $cargo->nombre = $request['datos-resp']['cargo'];
                $cargo->estado = false;
                $current_id = DB::table('cargos')->max('id_aut_cargos');
                $cargo->id_aut_cargos = $current_id + 1;
                $cargo->save();
            }
            // return response()->json($request['datos-resp']['cargo']);
            // return response()->json($cargo);
            $representante->direccion()->associate($direccionAdministrador);
            $representante->user()->associate($user);
            $representante->cargo()->associate($cargo);
            $representante->empresa()->associate($empresa);
            // }
            $representante->save();
            // return response()->json($representante);

            $representanteLegal->empresa()->associate($empresa);
            $representanteLegal->save();
            // });
            DB::commit();
            return response()->json($empresa, 201);
        } catch (ValidationException $ev) {
            return response()->json($ev->validator->errors(), 422);
        } catch (Exception $e) {
            return response()->json($e);
        }

        return response()->json($data, $code);
    }

    public function uploadFiles(Request $request)
    {
        return response()->json($request->allFiles());
    }
}
