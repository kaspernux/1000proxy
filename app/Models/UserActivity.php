<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserActivity extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'description',
        'ip_address',
    ];
    public $timestamps = false;
    protected $table = 'user_activities';

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
