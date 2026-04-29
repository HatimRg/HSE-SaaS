<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'display_name', 'description', 'permissions'];

    protected $casts = [
        'permissions' => 'array',
    ];

    /**
     * Get users with this role.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get predefined roles.
     */
    public static function getPredefinedRoles(): array
    {
        return [
            'admin' => ['display_name' => 'Administrateur', 'description' => 'Full system access'],
            'consultation' => ['display_name' => 'Consultation', 'description' => 'View-only access'],
            'hse_director' => ['display_name' => 'Directeur HSE', 'description' => 'HSE department director'],
            'hr_director' => ['display_name' => 'Directeur RH', 'description' => 'HR department director'],
            'pole_director' => ['display_name' => 'Directeur de Pôle', 'description' => 'Division director'],
            'project_director' => ['display_name' => 'Directeur de Projet', 'description' => 'Project director'],
            'hse_manager' => ['display_name' => 'Responsable HSE', 'description' => 'HSE manager'],
            'regional_hse_manager' => ['display_name' => 'Responsable HSE Régional', 'description' => 'Regional HSE manager'],
            'responsable' => ['display_name' => 'Responsable', 'description' => 'Team lead'],
            'supervisor' => ['display_name' => 'Superviseur', 'description' => 'Site supervisor'],
            'animateur' => ['display_name' => 'Animateur HSE', 'description' => 'HSE trainer/animator'],
            'magasinier' => ['display_name' => 'Magasinier', 'description' => 'Storekeeper'],
            'engineer' => ['display_name' => 'Ingénieur', 'description' => 'Engineer'],
            'hr' => ['display_name' => 'Ressources Humaines', 'description' => 'HR staff'],
        ];
    }

    /**
     * Get admin-like roles.
     */
    public static function getAdminRoles(): array
    {
        return ['admin', 'hse_director', 'hr_director', 'pole_director', 'project_director', 'regional_hse_manager'];
    }

    /**
     * Get HSE roles.
     */
    public static function getHseRoles(): array
    {
        return ['admin', 'hse_director', 'hse_manager', 'regional_hse_manager', 'responsable', 'animateur'];
    }
}
