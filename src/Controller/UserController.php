<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\User;
use App\Entity\Video;

class UserController extends AbstractController
{
    public function responseJson($data) {
        //Serializar datos con servicio serializer
        $json = $this->get('serializer')->serialize($data, 'json');

        //Response con http fundation
        $response = new Response();

        //Asignar contenido a la respuesta
        $response->setContent($json);

        //Indicar formato de respuesta
        $response->headers->set('Content-Type', 'application/json');
        //Devolver respuesta

        return $response;
    }


    public function index()
    {
        return $this->render('user/index.html.twig', [
            'controller_name' => 'UserController',
        ]);
    }

    public function lista()
    {
        $user_repo = $this->getDoctrine()->getRepository(User::class);

        $users = $user_repo->findAll();

        return $this->responseJson($users);
    }

    public function show()
    {
        $user_repo = $this->getDoctrine()->getRepository(User::class);

        $user = $user_repo->find(1);

        return $this->responseJson($user);
    }

    public function create(Request $request) {
        //Recorger los datos por POST
        //Decodificar el Json
        //Respuesta por defecto
        $data = [
            'status' => 'error',
            'code' => 400,
            'message' => 'El usuario no se ha creado',
        ];

        //Comprobar y validad datos
        //Si la validación es correcta, crear el objeto usuario
        //Cifrar la contraseña
        //Comprobar si el suario existe (duplicados)}
        //Si no existe, guardar
        //Crear respuesta en JSON
        return $this->responseJson($data);

    }
}
