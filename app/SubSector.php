<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SubSector extends Model
{
    protected $table = "sub_sectores";
    protected $primaryKey = 'id_aut_sub_sector';
    protected $fillable = ['nombre'];
    public $timestamps = false;


    public function sector()
    {
        return $this->belongsTo(Sector::class, 'id_sectores', 'id_aut_sector');
    }

    public function empresas()
    {
        return $this->belongsToMany(Empresa::class, 'empresas_sectores', 'id_sub_sector', 'id_empresa');
    }


}
