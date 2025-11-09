<?php

namespace App\Events;

use App\Models\Employe;
use App\Services\MatriculeGenerator;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;

class EmployeCreated
{
    use Dispatchable, SerializesModels;
    // public Employe $employe;

    // public function __construct(Employe $employe)
    // {
    //     $this->employe = $employe;
    // }
}
