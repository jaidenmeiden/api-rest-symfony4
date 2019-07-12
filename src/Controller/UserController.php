<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\User;
use App\Entity\Video;

class UserController extends AbstractController
{
    public function responseJsonPersonalizado($data) {
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

        return $this->responseJsonPersonalizado($users);
    }

    public function show()
    {
        $user_repo = $this->getDoctrine()->getRepository(User::class);

        $user = $user_repo->find(1);

        return $this->responseJsonPersonalizado($user);
    }

    public function create(Request $request) {
        //Recorger los datos por POST
        $json = $request->get('json', null);

        //Decodificar el Json
        $params = json_decode($json);//Objeto
        
        //Respuesta por defecto
        $data = [
            'status' => 'error',
            'code' => 400,
            'message' => 'El usuario no se ha creado',
            'json' => $params,
        ];

        //Comprobar y validad datos
        //Si la validación es correcta, crear el objeto usuario
        //Cifrar la contraseña
        //Comprobar si el suario existe (duplicados)}
        //Si no existe, guardar
        //Crear respuesta en JSON
        return new JsonResponse($data);
    }
}
