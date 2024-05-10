<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServerInfo extends Model
    {
    use HasFactory;

    protected $table = 'server_infos';

    protected $fillable = [
        'title',
        'ucount',
        'remark',
        'flag',
        'active',
        'state',
    ];

    const STATES = [
        'inactive' => 0,
        'active' => 1,
        'suspended' => 2,
    ];

    /**
     * Get the human-readable state of the server.
     *
     * @return string
     */
    public function getStateAttribute($value)
        {
        return array_search($value, self::STATES);
        }

    /**
     * Set the state of the server.
     *
     * @param string $state
     * @return void
     */
    public function setStateAttribute($value)
        {
        $this->attributes['state'] = self::STATES[$value] ?? null;
        }

    /**
     * Activate the server.
     *
     * @return void
     */
    public function activate()
        {
        $this->state = 'active';
        $this->active = true;
        $this->save();
        }

    /**
     * Deactivate the server.
     *
     * @return void
     */
    public function deactivate()
        {
        $this->state = 'inactive';
        $this->active = false;
        $this->save();
        }

    /**
     * Suspend the server.
     *
     * @return void
     */
    public function suspend()
        {
        $this->state = 'suspended';
        $this->active = false;
        $this->save();
        }

    public function servers(): HasMany
        {
        return $this->hasMany(Server::class);
        }
    }