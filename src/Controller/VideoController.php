<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Validation;

use App\Entity\User;
use App\Entity\Video;
use App\Services\JwtAuthService;

class VideoController extends AbstractController
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
        return $this->render('video/index.html.twig', [
            'controller_name' => 'VideoController',
        ]);
    }

    public function lista()
    {
        $video_repo = $this->getDoctrine()->getRepository(Video::class);

        $videos = $video_repo->findAll();

        return $this->responseJsonPersonalizado($videos);
    }

    public function show()
    {
        $video_repo = $this->getDoctrine()->getRepository(Video::class);

        $video = $video_repo->find(1);

        return $this->responseJsonPersonalizado($video);
    }

    public function create(Request $request, JwtAuthService $jwt_auth_service) {
        //Recoger la cabecera de autentificación}
        $token = $request->headers->get('Authorization');
        $checkToken = $jwt_auth_service->checkToken($token);

        //Array por defecto para devolver
        $data = [
            'status' => 'error',
            'code' => 400,
            'message' => 'El video no se ha creado'
        ];


        //Si es correcto, hacer la actualización del usuario
        if ($checkToken) {
            //Recorger los datos por POST
            $json = $request->get('json', null);
            $params = json_decode($json);//Objeto

            //Comprobar y validad datos
            if(!empty($json)) {
                //Conseguir los datos de usuario identificado
                $identity = $jwt_auth_service->checkToken($token, true);

                $user_id = (isset($identity->sub)) ? $identity->sub : null;
                $title = (isset($params->title)) ? $params->title : null;
                $description = (isset($params->description)) ? $params->description : null;
                $url = (isset($params->url)) ? $params->url : null;

                if(!empty($user_id) && !empty($title) && !empty($description) && !empty($url)) {
                    //Conseguir el entity manager
                    $doctrine = $this->getDoctrine();
                    $em = $doctrine->getManager();

                    $user_repo = $doctrine->getRepository(User::class);
                    $user = $user_repo->findOneBy(array(
                        'id' => $user_id
                    ));

                    //Si la validación es correcta, crear el objeto usuario
                    $video = new Video();
                    $video->setUser($user);
                    $video->setTitle($title);
                    $video->setDescription($description);
                    $video->setUrl($url);
                    $video->setCreatedAt(new \DateTime('now'));
                    $video->setUpdatedAt(new \DateTime('now'));

                    //Guardar usuario
                    $em->persist($video);
                    $em->flush();

                    $data = [
                        'status' => 'success',
                        'code' => 200,
                        'message' => 'Video creado correctamente',
                        'video' => $video
                    ];
                } else {
                    $data['message'] = 'Validación incorrecta';
                }
            }
        }

        //Crear respuesta en JSON
        return $this->responseJsonPersonalizado($data);
    }
}
