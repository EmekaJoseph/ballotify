<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Platform-wide key/value settings (e.g. plan prices), edited by the admin. */
class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    protected $casts = ['value' => 'json'];
}
