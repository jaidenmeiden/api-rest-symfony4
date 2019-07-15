<?php

namespace App\Services;

use Firebase\JWT\JWT;
use App\Entity\User;

class JwtAuthService {

    public $manager;
    public $key;

    public function __construct($manager)
    {
        $this->manager = $manager;
        $this->key = '7q4RBo72dNAIG0540UiRXKXIhmUkmBh2';
    }

    public function signup($email, $password, $gettoken = null) {
        //Comprobar si el usuario existe
        $user = $this->manager->getRepository(User::class)->findOneBy([
            'email' => $email,
            'password' => $password
        ]);

        $signup = false;
        if(is_object($user)) {
            $signup = !$signup;
        }
        //Si existe, generar el token de JWT
        if($signup) {
            $token = [
                'sub' => $user->getId(),//La propiedad 'sub' hace referencia al ID en la base de datos
                'name' => $user->getName(),
                'surname' => $user->getSurname(),
                'email' => $user->getEmail(),
                'iat' => time(), //El tiempo en que fue creado el token
                'exp' => time() + (7 * 24 * 60 * 60) //Cuando expira el token (Una semana)
            ];

            //Comprobar el flag gettoken, condición
            $jwt = JWT::encode($token, $this->key, 'HS256');

            if(!empty($gettoken)) {
                $data = $jwt;
            } else {
                $decoded = JWT::decode($jwt, $this->key, ['HS256']);
                $data = $decoded;
            }
        } else {
            $data = array(
                'status' => 'error',
                'message' => 'Login incorrecto'
            );
        }

        //Devolver los datos decodificados o el objeto token, en función de un parámetro

        return $data;
    }

    public function checkToken($jwt, $identity = false) {
        $auth = false;

        try {
            $decoded = JWT::decode($jwt, $this->key, ['HS256']);

            if(isset($decoded) && !empty($decoded) && is_object($decoded) && isset($decoded->sub)) {
                $auth = !$auth;
            }

            if($identity) {
                $auth = $decoded;
            }
        }
        catch (\UnexpectedValueException $e) {}
        catch (\DomainException $e) {}

        return $auth;
    }
}
