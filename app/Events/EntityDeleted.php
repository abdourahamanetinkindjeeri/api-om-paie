<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EntityDeleted
{
    use Dispatchable, SerializesModels;

    public object $entity;

    public function __construct(object $entity)
    {
        $this->entity = $entity;
    }
}
