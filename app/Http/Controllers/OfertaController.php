<?php

namespace App\Http\Controllers;

use App\AreaConocimiento;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Oferta;
use App\Cargo;
use App\Contrato;
use App\CategoriaCargo;
use App\ContactoHV;
use App\Empresa;
use App\Http\Requests\OfertaStoreRequest;
use App\Http\Resources\AreaConocimientoResource;
use App\Http\Resources\SalarioResource;
use App\OfertaSoftware;
use App\PreguntaOferta;
use App\Salario;
use Exception;
use Illuminate\Support\Facades\DB;

class OfertaController extends Controller
{
  public function getOfertasEnEspera()
  {
    $ofertas = Oferta::where('estado', 'Pendiente')->get();

    $ofertas->load('empresa');
    $ofertas->load('areasConocimiento');
    $ofertas->load('salario');
    $ofertas->load('ubicaciones');

    // Se borra el atributo pivot, el cual no es necesario
    foreach ($ofertas as $oferta) {
      foreach ($oferta->areasConocimiento as $areacon) {
        unset($areacon['pivot']);
      }
      foreach ($oferta->ubicaciones as $ubicacion) {
        $nombre = $ubicacion->departamento->nombre;
        unset($ubicacion->departamento);
        unset($ubicacion->id_departamento);
        $ubicacion['departamento'] = $nombre;
        unset($ubicacion['pivot']);
      }
    }

    return response()->json($ofertas, 200);
  }

  public function getOferta($id)
  {
    // Codigo de error por defecto
    $code = 404;
    $oferta = Oferta::find($id);

    if (is_object($oferta)) {

      // Contacto HV
      $oferta->load('contacto_hv');
      if (!empty($oferta['contacto_hv'])){
        $oferta['contactoHv'] = $oferta->contacto_hv;
        unset($oferta['contacto_hv']);
        unset($oferta['contactoHv']['id_aut_recepcionhv']);
        unset($oferta['contactoHv']['id_oferta']);
        $oferta['contactoHv']['telefonoMovil'] = $oferta['contactoHv']['telefono_movil'];
        unset($oferta['contactoHv']['telefono_movil']);
      } else {
        $oferta['contactoHv'] = [];
        unset($oferta['contacto_hv']);
      }

      // Contrato
      $oferta->load('contrato');
      if(!empty($oferta['contrato'])){
        $oferta->load('salario');

        $oferta['contrato']['comentariosSalario'] = $oferta['contrato']['comentarios_salario'];
        unset($oferta['contrato']['comentarios_salario']);

        $oferta['contrato']['formaPago'] = $oferta['salario']['forma_pago'];

        $oferta['contrato']['idRangoSalarial'] = $oferta['salario']['id_aut_salario'];

        $oferta['contrato']['jornada'] = $oferta['contrato']['jornada_laboral'];
        unset($oferta['contrato']['jornada_laboral']);

        $oferta['contrato']['rangoSalarial'] = $oferta['salario']['rango'];

        $oferta['contrato']['tipoContrato'] = $oferta['contrato']['tipo_contrato'];
        unset($oferta['contrato']['tipo_contrato']);

        unset($oferta['contrato']['id_oferta']);
        unset($oferta['contrato']['id_aut_contrato']);
        unset($oferta['salario']);
      } else {
        $oferta['contrato'] = [];
      }

      // Informacion Principal





      $data = $oferta;
    } else {
      $data = null;
    }
    return response()->json($data, $code);
  }

  public function getAllOfertas()
  {
    $ofertas = Oferta::all();

    $ofertas->load('empresa');
    $ofertas->load('areasConocimiento');
    $ofertas->load('salario');
    $ofertas->load('ubicaciones');

    // Se borra el atributo pivot, el cual no es necesario
    foreach ($ofertas as $oferta) {
      foreach ($oferta->areasConocimiento as $areacon) {
        unset($areacon['pivot']);
      }
      foreach ($oferta->ubicaciones as $ubicacion) {
        $nombre = $ubicacion->departamento->nombre;
        unset($ubicacion->departamento);
        unset($ubicacion->id_departamento);
        $ubicacion['departamento'] = $nombre;
        unset($ubicacion['pivot']);
      }
    }

    return response()->json($ofertas, 200);
  }

  public function getOfertasEmpresa(Request $request, $id)
  {
    $ofertas = Oferta::orderBy('fecha_publicacion', 'ASC')->where('id_empresa', $id)->get();

    foreach ($ofertas as $oferta) {
      $nombre = $oferta->cargo->nombre;
      unset($oferta['cargo']);
      $oferta['cargo_nombre'] = $nombre;
    }

    return response()->json($ofertas, 200);
  }

  public function updateEstado(Request $request, $id)
  {
    // Código de error por defecto
    $code = 400;
    $data = null;
    try {
      $this->validate(request(), [
        'estado' => 'required|string',
      ]);
      // Buscar el registro
      $oferta = Oferta::find($id);
      if (!empty($oferta) && is_object($oferta)) {
        switch ($request['estado']) {
          case 'Aceptada':
            $oferta->update([
              'estado' => $request['estado'],
              'estado_proceso' => 'Activa'
            ]);
            $data = $oferta;
            $code = 200;
            break;
          case 'Rechazada':
            $oferta->update(['estado' => $request['estado']]);
            $data = $oferta;
            $code = 200;
            break;
          case 'Pendiente':
            $oferta->update(['estado' => $request['estado']]);
            $data = $oferta;
            $code = 200;
            break;
        }
      }
    } catch (ValidationException $ev) {
      return response()->json($ev->validator->errors(), $code);
    } catch (Exception $e) {
      return response()->json($e);
    }
    return response()->json($data, $code);
  }
  public function updateEstadoProceso(Request $request, $id)
  {
    // Código de error por defecto
    $code = 400;
    $data = null;
    try {
      $this->validate(request(), [
        'estado_proceso' => 'required|string',
      ]);
      // Buscar el registro
      $oferta = Oferta::find($id);
      if (!empty($oferta) && is_object($oferta) && $oferta['estado'] != 'Pendiente') {
        switch ($request['estado_proceso']) {
          case 'En espera':
          case 'Activa':
          case 'En selección':
          case 'Finalizada con contratación':
          case 'Finalizada sin contratación':
          case 'Expirada':
            $oferta->update(['estado_proceso' => $request['estado_proceso']]);
            $data = $oferta;
            $code = 200;
            break;
        }
      }
    } catch (ValidationException $ev) {
      return response()->json($ev->validator->errors(), $code);
    } catch (Exception $e) {
      return response()->json($e, $code);
    }
    return response()->json($data, $code);
  }

  public function getSalarioPorModena($nombreMoneda)
  {
    // return response()->json($nombreMoneda);
    return SalarioResource::collection(Salario::where("forma_pago", $nombreMoneda)->get());
  }
  public function getAllSalario()
  {
    return response()->json(Salario::all());
  }
  public function getAllAreas()
  {
    return AreaConocimientoResource::collection(AreaConocimiento::all());
  }

  // public function storeOferta(Empresa $empresa, Request $request)
  public function storeOferta(Empresa $empresa, OfertaStoreRequest $request)
  {
    // return response()->json($request);
    try {

      DB::beginTransaction();
      // Se busca o crea el cargo

      // $id_cargo = null;

      // if (isset($request['informacionPrincipal']['cargo'])) {
      //   $id_cargo = $request['informacionPrincipal']['cargo'];
      // } else {
      //   $cargo = new Cargo();
      //   $cargo->nombre = $request['informacionPrincipal']['otroCargo'];
      //   $cargo->estado = false;
      //   $current_id = DB::table('cargos')->max('id_aut_cargos');
      //   $cargo->id_aut_cargos = $current_id + 1;
      //   $cargo->save();
      //   $id_cargo =  $cargo->id_aut_cargos;
      // }

      $cargo = Cargo::whereNombre($request['informacionPrincipal']['cargo'])->first();
      if(!$cargo){
        $cargo = Cargo::create(["nombre" => $request['informacionPrincipal']['cargo']]);
      }
      $id_cargo = $cargo->id_aut_cargos;

      $oferta = new Oferta();
      $oferta->id_empresa = $empresa->id_aut_empresa;
      $oferta->nombre_oferta = $request['informacionPrincipal']['nombreOferta']; //
      $oferta->descripcion = $request['informacionPrincipal']['descripcion']; //
      $oferta->id_cargo = $id_cargo;

      $oferta->numero_vacantes = $request['informacionPrincipal']['numVacantes']; //
      $oferta->id_forma_pago = $request['contrato']['idRangoSalarial'];
      $oferta->experiencia = $request['requisitos']['experienciaLaboral']; // Enum ('Sin experiencia', 'Igual a', 'Mayor o igual que', 'Menor o igual que')
      $oferta->anios_experiencia = $request['requisitos']['anios']; //
      // $oferta->fecha_publicacion = ""; //
      // $oferta->fecha_cierre = ""; //
      $oferta->estado = "Pendiente"; // Enum ('Aceptada', 'Rechazada', 'Pendiente');  --Administrador
      $oferta->estado_proceso = "En espera"; // ('En seleccion', 'Desactivada', 'Expirada');  --Empresa
      $oferta->id_sector = $request['informacionPrincipal']['idSector'];
      if (isset($request['informacionPrincipal']['nombreTempEmpresa'])) {
        $oferta->nombre_temporal_empresa = $request['informacionPrincipal']['nombreTempEmpresa']; //
      }
      if (isset($request['requisitos']['licenciaConduccion'])) {
        $oferta->licencia_conduccion = $request['requisitos']['licenciaConduccion']; // Enum ('A1', 'A2', 'B1', 'B2', 'B3', 'C1', 'C2', 'C3')
      }
      $oferta->requisitos_minimos = $request['requisitos']['requisitosMinimos']; // Texto descriptivo
      if (isset($request['requisitos']['idDiscapacidad'])) {
        $oferta->id_discapacidad = $request['requisitos']['idDiscapacidad']; // Id consultado de la tabla discapacidad
      }
      $oferta->num_dias_oferta = $request['informacionPrincipal']['vigenciaDias']; // Dias de la oferta Max 30

      $oferta->id_aut_nivestud = $request['requisitos']['idrequisitosMinimos']; // NIvel Programa

      $oferta->save();

      // Contrato que tendrá la oferta
      $contrato = new Contrato();
      $contrato->tipo_contrato = $request['contrato']['tipoContrato']; //Enum ('Término indefinido', 'Contrato de aprendizaje', 'Prestación de servicios', 'Obra a labor determinada', 'Término fijo')
      $contrato->jornada_laboral = $request['contrato']['jornada']; //Enum ('Medio tiempo', 'Tiempo completo', 'Tiempo parcial');
      if (isset($request['contrato']['duracion'])) {
        $contrato->duracion = $request['contrato']['duracion'];
      }
      if (isset($request['contrato']['horario'])) {
        $contrato->horario = $request['contrato']['horario'];
      }
      if (isset($request['contrato']['comentariosSalario'])) {
        $contrato->comentarios_salario = $request['contrato']['comentariosSalario'];
      }

      $oferta->contrato()->save($contrato);

      $array_idiomas = array();
      foreach ($request['requisitos']['idiomas'] as $idioma) {
        $array_idiomas[$idioma["id"]] = array(
          "nivel_escritura" => $idioma["nivel_escritura"], //enum ('Nativo', 'Avanzado', 'Medio', 'Bajo')
          "nivel_lectura" => $idioma["nivel_lectura"], //enum ('Nativo', 'Avanzado', 'Medio', 'Bajo')
          "nivel_conversacion" => $idioma["nivel_conversacion"] //enum ('Nativo', 'Avanzado', 'Medio', 'Bajo')
        );
      }
      $oferta->idiomas()->sync($array_idiomas);

      // Asigna los id de las ciudades donde va a estar disponible la oferta
      $oferta->ubicaciones()->sync($request['informacionPrincipal']['idUbicaciones']); // Ids consultados de la tabla discapacidad
      // $oferta->ubicaciones()->sync($request['informacionPrincipal']['ubicacion']); // Ids consultados de la tabla discapacidad

      // Asigna los id de las areas de conocimientos requeridos por la oferta
      $oferta->areasConocimiento()->sync($request['informacionPrincipal']['idAreaConocimiento']); // Ids consultados de la tabla areas de conocimiento


      // // Asigna los id de los software requeridos en la oferta
      // foreach ($request['requisitos']['softwareOferta'] as $soft) {
      //   $software = new OfertaSoftware();
      //   $software->nombre = $soft['nombre'];
      //   $software->nivel = $soft['nivel'];
      //   $oferta->software()->save($software);
      // }

      // Asigna los id de los software requeridos en la oferta
      $array_software = array();
      foreach ($request['requisitos']['softwareOferta'] as $soft) {
        $software = new OfertaSoftware();
        $software->nombre = $soft['nombre'];
        $software->nivel = $soft['nivel'];
        array_push($array_software, $software);
      }

      $oferta->software()->delete();
      $oferta->software()->saveMany($array_software);

      // PREGUNTAS
      foreach ($request['requisitos']['preguntasCandidato'] as $pregunta) {
        $p = new PreguntaOferta();
        $p->pregunta = $pregunta;
        $oferta->preguntas()->save($p);
      }


      $contacto = new ContactoHV();
      $contacto->fill([
        "correo" => $request['contactoHV']['correo'],
        "nombres" => $request['contactoHV']['nombres'],
        "apellidos" => $request['contactoHV']['apellidos'],
        "telefono_movil" => $request['contactoHV']['telefonoMovil']
      ]);

      $oferta->contacto_hv()->save($contacto);


      DB::commit();
      return $this->success($oferta);
    } 
    catch (Exception $e) {
      return $this->fail("Registro oferta => " . $e->getMessage());
    }
  }

  public function updateOferta(Oferta $oferta, OfertaStoreRequest $request)
  {
    try {

      // if($oferta->postulaciones()->count){
      //   return $this->fail("La oferta no se puede modificar porque ya cuenta con postulaciones!", 400);
      // }

      DB::beginTransaction();
      // Contrato que tendrá la oferta
      $contrato = $oferta->contrato;
      $contrato->tipo_contrato = $request['contrato']['tipoContrato']; //Enum ('Término indefinido', 'Contrato de aprendizaje', 'Prestación de servicios', 'Obra a labor determinada', 'Término fijo')
      $contrato->jornada_laboral = $request['contrato']['jornada']; //Enum ('Medio tiempo', 'Tiempo completo', 'Tiempo parcial');
      if (isset($request['contrato']['duracion'])) {
        $contrato->duracion = $request['contrato']['duracion'];
      }
      if (isset($request['contrato']['horario'])) {
        $contrato->horario = $request['contrato']['horario'];
      }
      if (isset($request['contrato']['comentariosSalario'])) {
        $contrato->comentarios_salario = $request['contrato']['comentariosSalario'];
      }

      $contrato->save();
      // Se busca o crea el cargo

      // $id_cargo = $oferta->cargo->id_aut_cargos;
      // if (isset($request['informacionPrincipal']['cargo'])) {
      //   if ($request['informacionPrincipal']['cargo'] != $id_cargo) {
      //     $id_cargo = $request['informacionPrincipal']['cargo'];
      //   }
      // } else {
      //   $cargo = new Cargo();
      //   $cargo->nombre = $request['informacionPrincipal']['otroCargo'];
      //   $cargo->estado = false;
      //   $current_id = DB::table('cargos')->max('id_aut_cargos');
      //   $cargo->id_aut_cargos = $current_id + 1;
      //   $cargo->save();
      //   $id_cargo =  $cargo->id_aut_cargos;
      // }

      $cargo = Cargo::whereNombre($request['informacionPrincipal']['cargo'])->first();
      if(!$cargo){
        $cargo = Cargo::create(["nombre" => $request['informacionPrincipal']['cargo']]);
      }
      $id_cargo = $cargo->id_aut_cargos;

      // $oferta->id_empresa = $empresa->id_aut_empresa;
      $oferta->nombre_oferta = $request['informacionPrincipal']['nombreOferta']; //
      $oferta->descripcion = $request['informacionPrincipal']['descripcion']; //
      $oferta->id_cargo = $id_cargo;
      // $oferta->id_contrato = $contrato->id_aut_contrato;

      $oferta->numero_vacantes = $request['informacionPrincipal']['numVacantes']; //
      $oferta->id_forma_pago = $request['contrato']['idRangoSalarial'];
      $oferta->experiencia = $request['requisitos']['experienciaLaboral']; // Enum ('Sin experiencia', 'Igual a', 'Mayor o igual que', 'Menor o igual que')
      $oferta->anios_experiencia = $request['requisitos']['anios']; //
      // $oferta->fecha_publicacion = ""; //
      // $oferta->fecha_cierre = ""; //
      // $oferta->estado = "Pendiente"; // Enum ('Aceptada', 'Rechazada', 'Pendiente');  --Administrador
      // $oferta->estado_proceso = "En espera"; // ('En seleccion', 'Desactivada', 'Expirada');  --Empresa
      $oferta->id_sector = $request['informacionPrincipal']['idSector'];
      if (isset($request['informacionPrincipal']['nombreTempEmpresa'])) {
        $oferta->nombre_temporal_empresa = $request['informacionPrincipal']['nombreTempEmpresa']; //
      }
      if (isset($request['requisitos']['licenciaConduccion'])) {
        $oferta->licencia_conduccion = $request['requisitos']['licenciaConduccion']; // Enum ('A1', 'A2', 'B1', 'B2', 'B3', 'C1', 'C2', 'C3')
      }
      $oferta->requisitos_minimos = $request['requisitos']['requisitosMinimos']; // TEsto descriptivo
      if (isset($request['requisitos']['idDiscapacidad'])) {
        $oferta->id_discapacidad = $request['requisitos']['idDiscapacidad']; // Id consultado de la tabla discapacidad
      }
      $oferta->num_dias_oferta = $request['informacionPrincipal']['vigenciaDias']; // Dias de la oferta Max 30

      $oferta->id_aut_nivestud = $request['requisitos']['idrequisitosMinimos']; // NIvel Programa

      $oferta->save();
      // $empresa->ofertas()->save($oferta);
      // Asigna los id de los idioma requeridos en la oferta

      $array_idiomas = array();
      foreach ($request['requisitos']['idiomas'] as $idioma) {
        $array_idiomas[$idioma["id"]] = array(
          "nivel_escritura" => $idioma["nivel_escritura"], //enum ('Nativo', 'Avanzado', 'Medio', 'Bajo')
          "nivel_lectura" => $idioma["nivel_lectura"], //enum ('Nativo', 'Avanzado', 'Medio', 'Bajo')
          "nivel_conversacion" => $idioma["nivel_conversacion"] //enum ('Nativo', 'Avanzado', 'Medio', 'Bajo')
        );
      }
      $oferta->idiomas()->sync($array_idiomas);

      // Asigna los id de las ciudades donde va a estar disponible la oferta
      $oferta->ubicaciones()->sync($request['informacionPrincipal']['idUbicaciones']); // Ids consultados de la tabla discapacidad

      // Asigna los id de las areas de conocimientos requeridos por la oferta
      $oferta->areasConocimiento()->sync($request['informacionPrincipal']['idAreaConocimiento']); // Ids consultados de la tabla areas de conocimiento



      // Asigna los id de los software requeridos en la oferta
      $array_software = array();
      foreach ($request['requisitos']['softwareOferta'] as $soft) {
        $software = new OfertaSoftware();
        $software->nombre = $soft['nombre'];
        $software->nivel = $soft['nivel'];
        array_push($array_software, $software);
      }

      $oferta->software()->delete();
      $oferta->software()->saveMany($array_software);

      // PREGUNTAS
      $array_preguntas = array();
      foreach ($request['requisitos']['preguntasCandidato'] as $pregunta) {
        $p = new PreguntaOferta();
        $p->pregunta = $pregunta;
        array_push($array_preguntas, $p);
      }
      $oferta->preguntas()->delete();
      $oferta->preguntas()->saveMany($array_preguntas);

      $contacto = $oferta->contacto_hv;
      $contacto->fill([
        "correo" => $request['contactoHV']['correo'],
        "nombres" => $request['contactoHV']['nombres'],
        "apellidos" => $request['contactoHV']['apellidos'],
        "telefono_movil" => $request['contactoHV']['telefonoMovil']
      ]);

      $oferta->contacto_hv()->save($contacto);

      DB::commit();
      return $this->success($oferta);
    } catch (Exception $e) {
      return $this->fail("Registro oferta => " . $e->getMessage());
    }
  }

  public function getAllPostulados(Oferta $oferta)
  {
    return $this->success($oferta->postulaciones);
  }
}
