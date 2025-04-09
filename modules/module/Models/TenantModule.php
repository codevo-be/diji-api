<?php

namespace Diji\Module\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class TenantModule extends Pivot
{
    protected $table = 'tenant_modules';

    protected $fillable = ['tenant_id','module_id'];
}
