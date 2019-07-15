<?php

namespace App\Services;

use Firebase\JWT\JWT;
use App\Entity\User;

class JwtAuthService {

    public $manager;

    public function __construct($manager)
    {
        $this->manager = $manager;
    }

    public function signupp() {
        return "Hola mundo, desde el servicio!";
    }
}
