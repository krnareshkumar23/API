<?php

namespace App\Models;

use App\Models\Extensions\UnlistedValueAttribute;

class Condition extends BaseModel
{
    use UnlistedValueAttribute;

    protected $hidden = [
        'created_at', 'updated_at', 'category_id'
    ];
}
