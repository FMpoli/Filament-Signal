<?php

namespace Voodflow\Voodflow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CredentialScope extends Model
{
    protected $table = 'voodflow_credential_scopes';

    public $timestamps = false;

    protected $fillable = [
        'credential_id',
        'scope',
    ];

    public function credential(): BelongsTo
    {
        return $this->belongsTo(Credential::class);
    }
}
