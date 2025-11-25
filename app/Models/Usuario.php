<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

use Illuminate\Database\Eloquent\Model;

class Usuario extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, Notifiable;
    protected $table = 'usuario';
    public static $default_prefix ="user_";
    public static array $campos = [
        'username',
        'email',
        'estado_usuario_id',
        'roles_slug',"rol_ids"
    ];
    public $timestamps = false;
    protected $fillable = [
        'username',
        'email'
    ];

    protected $hidden = [
        'password'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'cambio_password'=>'boolean',
    ];
    
    //
    public function estado()
    {
        return $this->belongsTo(EstadoUsuario::class, 'estado_usuario_id');
    }

    public function datosPersonales()
    {
        return $this->belongsTo(DatosPersonales::class, 'datos_personales_id');
    }
    public function estadoUsuario()
    {
        return $this->belongsTo(EstadoUsuario::class, 'estado_usuario_id');
    }

    public function roles()
    {
        return $this->belongsToMany(Rol::class, 'rol_usuario')
                    ->withPivot(['fecha_alta', 'fecha_baja'])
                    ->wherePivot('fecha_baja', null); // Filtra solo roles activos, con baja null
                    ;
    }
    public function tieneRol(array|string $rolesPermitidos): bool
    {
        $rolesDelUsuario = $this->roles_slug;

        $rolesPermitidos = is_array($rolesPermitidos) ? $rolesPermitidos : [$rolesPermitidos];

        // SUPER_ADMIN tiene acceso a todo
        if (in_array('SUPER_ADMIN', $rolesDelUsuario)) return true;

        // Caso normal
        return count(array_intersect($rolesPermitidos, $rolesDelUsuario)) > 0;
    }

    protected $appends = ['roles_slug',"rol_ids", "habilitado"];

    public function getRolesSlugAttribute()
    {
        return $this->roles->pluck('slug')->toArray();
    }
    public function getRolIdsAttribute()
    {
        return $this->roles->pluck('id')->toArray();
    }

    public function getHabilitadoAttribute()
    {
        return $this->estadoUsuario && $this->estadoUsuario->slug === 'ACTIVO';
    }
}
