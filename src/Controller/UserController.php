<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints\Email;

use App\Entity\User;
use App\Entity\Video;
use App\Services\JwtAuthService;

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
            'message' => 'El usuario no se ha creado'
        ];

        //Comprobar y validad datos
        if($json != null) {
            $name = (isset($params->name)) ? $params->name : null;
            $surname = (isset($params->surname)) ? $params->surname : null;
            $email = (isset($params->email)) ? $params->email : null;
            $password = (isset($params->password)) ? $params->password : null;

            $validator = Validation::createValidator();
            $validate_email = $validator->validate($email, [
                new Email()
            ]);

            if(!empty($email) && count($validate_email) == 0 && !empty($name) && !empty($surname) && !empty($password)) {
                //Si la validación es correcta, crear el objeto usuario
                $user = new User();
                $user->setName($name);
                $user->setSurname($surname);
                $user->setEmail($email);
                $user->setRole('ROLE_USER');
                $user->setCreatedAt(new \DateTime('now'));
                $user->setUpdatedAt(new \DateTime('now'));

                //Cifrar la contraseña
                $pwd = hash('sha256', $password);
                $user->setPassword($pwd);

                //Comprobar si el suario existe (duplicados)
                $doctrine = $this->getDoctrine();
                $em = $doctrine->getManager();

                $user_repo = $doctrine->getRepository(User::class);
                $isset_user = $user_repo->findBy(array(
                   'email' => $email
                ));

                //Si no existe, guardar
                if(count($isset_user) == 0) {
                    //Guardar usuario
                    $em->persist($user);
                    $em->flush();

                    $data = [
                        'status' => 'success',
                        'code' => 200,
                        'message' => 'Usuario creado correctamente',
                        'user' => $user
                    ];
                } else {
                    $data['message'] = 'El usuario ya existe!';
                }
            } else {
                $data['message'] = 'Validación incorrecta';
            }
        }

        //Crear respuesta en JSON
        return new JsonResponse($data);
    }

    public function login(Request $request, JwtAuthService $jwt_auth_service) {
        //Recorger los datos por POST
        $json = $request->get('json', null);

        //Decodificar el Json
        $params = json_decode($json);//Objeto

        //Array por defecto para devolver
        $data = [
            'status' => 'error',
            'code' => 400,
            'message' => 'El usuario no se ha podido identificar'
        ];

        //Comprobar y validad los datos
        if($json != null) {
            $email = (isset($params->email)) ? $params->email : null;
            $password = (isset($params->password)) ? $params->password : null;
            $getToken = (isset($params->getToken)) ? $params->getToken : null;

            $validator = Validation::createValidator();
            $validate_email = $validator->validate($email, [
                new Email()
            ]);

            if(!empty($email) && count($validate_email) == 0 && !empty($password)) {
                //Cifrar la contraseña
                $pwd = hash('sha256', $password);

                //Devolver token o datos
                if($getToken) {
                    $signup = $jwt_auth_service->signup($params->email, $pwd, $getToken);
                } else {
                    $signup = $jwt_auth_service->signup($params->email, $pwd);
                }

                return new JsonResponse($signup);
            } else {
                $data['message'] = 'Validación incorrecta';
            }
        }

        //Crear respuesta en JSON
        return $this->responseJsonPersonalizado($data);
    }

    public function edit(Request $request, JwtAuthService $jwt_auth_service) {
        //Recoger la cabecera de autentificación}
        $token = $request->headers->get('Authorization');

        //Crear un método para comprobar si el token es correcto
        $checkToken = $jwt_auth_service->checkToken($token);

        //Array por defecto para devolver
        $data = [
            'status' => 'error',
            'code' => 400,
            'message' => 'Usuario no actualizado'
        ];

        //Si es correcto, hacer la actualización del usuario
        if($checkToken) {
            //Conseguir el entity manager
            $doctrine = $this->getDoctrine();
            $em = $doctrine->getManager();

            //Conseguir los datos de usuario identificado
            $identity = $jwt_auth_service->checkToken($token, true);

            //Conseguir actualizar completo
            $user_repo = $doctrine->getRepository(User::class);
            $user = $user_repo->findOneBy(array(
                'id' => $identity->sub
            ));

            //Recoger los datos por POST
            $json = $request->get('json', null);
            $params = json_decode($json);//Objeto

            //Comprobar y validador los datos
            if(!empty($json)) {
                $name = (isset($params->name)) ? $params->name : null;
                $surname = (isset($params->surname)) ? $params->surname : null;
                $email = (isset($params->email)) ? $params->email : null;

                $validator = Validation::createValidator();
                $validate_email = $validator->validate($email, [
                    new Email()
                ]);

                if(!empty($email) && count($validate_email) == 0 && !empty($name) && !empty($surname)) {
                    //Asignar nuevos datos
                    $user->setName($name);
                    $user->setSurname($surname);
                    $user->setEmail($email);
                    $user->setUpdatedAt(new \DateTime('now'));

                    //Comprobar los duplicados
                    $isset_user = $user_repo->findBy([
                       'email' => $email
                    ]);

                    if(count($isset_user) == 0 || strcasecmp($identity->email, $email) == 0) {
                        //Almacenar cambios
                        $em->persist($user);
                        $em->flush();

                        //Array por defecto para devolver
                        $data = [
                            'status' => 'success',
                            'code' => 200,
                            'message' => 'Usuario actualizado',
                            'user' => $user
                        ];
                    } else {
                        $data['message'] = 'No puedes usar ese email';
                    }
                }
            }
        }

        //Crear respuesta en JSON
        return $this->responseJsonPersonalizado($data);
    }
}
