<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Administrateur extends User
{
    public function __construct()
    {
        parent::__construct();
        $this->setRoles(['ROLE_ADMIN']);
    }
}
