<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'agent_contact',
        'agent_branch',
        'agent_reference_from',
        'password',
        'role',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function assignedCustomers()
    {
        return $this->hasMany(Customer::class, 'assigned_counselor_id');
    }

    public function telecallerCustomers()
    {
        return $this->hasMany(Customer::class, 'telecaller_id');
    }

    public function agentCustomers()
    {
        return $this->hasMany(Customer::class, 'agent_id');
    }

    public function mentionHandle(): string
    {
        $emailLocalPart = strtolower((string) Str::before($this->email, '@'));
        $handle = preg_replace('/[^a-z0-9._-]/', '', $emailLocalPart);

        if ($handle) {
            return $handle;
        }

        return (string) Str::of($this->name)->lower()->replaceMatches('/[^a-z0-9]/', '');
    }

    public function mentionAliases(): array
    {
        $name = (string) Str::of($this->name)->lower();

        return collect([
            $this->mentionHandle(),
            (string) Str::of($name)->replaceMatches('/[^a-z0-9]/', ''),
            (string) Str::of($name)->replaceMatches('/[^a-z0-9]+/', '.')->trim('.'),
            (string) Str::of($name)->replaceMatches('/[^a-z0-9]+/', '-')->trim('-'),
        ])->filter()->unique()->values()->all();
    }
}
