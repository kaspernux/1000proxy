<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerInfo extends Model
{
    use HasFactory;

    protected $table = 'server_infos';

    protected $fillable = [
        'server_id',
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

    public function getStateAttribute($value)
    {
        return array_search($value, self::STATES);
    }

    public function setStateAttribute($value)
    {
        $this->attributes['state'] = self::STATES[$value] ?? null;
    }

    public function activate()
    {
        $this->state = 'active';
        $this->active = true;
        $this->save();
    }

    public function deactivate()
    {
        $this->state = 'inactive';
        $this->active = false;
        $this->save();
    }

    public function suspend()
    {
        $this->state = 'suspended';
        $this->active = false;
        $this->save();
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}
