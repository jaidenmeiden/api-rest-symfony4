<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Validation;

use Knp\Component\Pager\PaginatorInterface;

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

    public function list(Request $request, JwtAuthService $jwt_auth_service, PaginatorInterface $paginator) {
        //Recoger la cabecera de autentificación}
        $token = $request->headers->get('Authorization');
        $checkToken = $jwt_auth_service->checkToken($token);

        //Array por defecto para devolver
        $data = [
            'status' => 'error',
            'code' => 400,
            'message' => 'No se puede realizar la consulta'
        ];

        //Si es correcto, hacer la actualización del usuario
        if ($checkToken) {
            //Conseguir los datos de usuario identificado
            $identity = $jwt_auth_service->checkToken($token, true);

            //Conseguir el entity manager
            $doctrine = $this->getDoctrine();
            $em = $doctrine->getManager();

            $dql = "SELECT v from App\Entity\Video v WHERE v.user = {$identity->sub} ORDER BY v.id DESC";
            $query = $em->createQuery($dql);

            //Recoger el parametro page de la url
            $page = $request->query->getInt('page', 1);
            $items_per_page = 5;

            //Invocar paginación
            $pagination = $paginator->paginate($query, $page, $items_per_page);
            $total = $pagination->getTotalItemCount();

            $data = [
                'status' => 'success',
                'code' => 200,
                'total_items_count' => $total,
                'page_actual' => $page,
                'items_per_page' => $items_per_page,
                'total_pages' => ceil($total / $items_per_page),
                'videos' => $pagination,
                'user_id' => $identity->sub
            ];
        }

        return $this->responseJsonPersonalizado($data);
    }

    public function show()
    {
        $video_repo = $this->getDoctrine()->getRepository(Video::class);

        $video = $video_repo->find(1);

        return $this->responseJsonPersonalizado($video);
    }

    public function create(Request $request, JwtAuthService $jwt_auth_service, $id = null) {
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

                    $data = [
                        'status' => 'success',
                        'code' => 200,
                        'message' => 'No fue actualizado'
                    ];

                    //Si la validación es correcta, crear el objeto usuario
                    $control = false;
                    if($id == null) {
                        $video = new Video();
                        $video->setUser($user);
                        $video->setStatus(1);
                        $video->setCreatedAt(new \DateTime('now'));

                        $data['message'] = 'Video creado correctamente';
                        $control = !$control;
                    } else {
                        $video_repo = $doctrine->getRepository(Video::class);
                        $video = $video_repo->findOneBy(array(
                            'id' => $id,
                            'user' => $identity->sub
                        ));

                        if($video && is_object($video) && $identity->sub == $video->getUser()->getId()) {
                            $data['message'] = 'Video actualizado correctamente';
                            $control = !$control;
                        } else {
                            $data['message'] = 'Video no existe';
                        }
                    }
                    if($control) {
                        $video->setTitle($title);
                        $video->setDescription($description);
                        $video->setUrl($url);
                        $video->setUpdatedAt(new \DateTime('now'));

                        //Guardar usuario
                        $em->persist($video);
                        $em->flush();

                        $data['video'] = $video;
                    }
                } else {
                    $data['message'] = 'Validación incorrecta';
                }
            }
        }

        //Crear respuesta en JSON
        return $this->responseJsonPersonalizado($data);
    }

    public function detail(Request $request, JwtAuthService $jwt_auth_service, $id = null) {
        //Obtener el token y comprobar si es correcto
        $token = $request->headers->get('Authorization');
        $checkToken = $jwt_auth_service->checkToken($token);

        //Array por defecto para devolver
        $data = [
            'status' => 'error',
            'code' => 400,
            'message' => 'Video no encontrado',
            'id' => $id
        ];

        //Si es correcto, hacer la actualización del usuario
        if ($checkToken) {
            //Obtener la identificación de usuario
            $identity = $jwt_auth_service->checkToken($token, true);

            //Obtener el video con respecto al ID
            $doctrine = $this->getDoctrine();

            $video_repo = $doctrine->getRepository(Video::class);
            $video = $video_repo->findOneBy(array(
                'id' => $id
            ));

            //Comprobar si el video existe y es propiedad del usuario
            if($video && is_object($video) && $identity->sub == $video->getUser()->getId()) {
                $data = [
                    'status' => 'success',
                    'code' => 200,
                    'message' => 'Video encontrado',
                    'video' => $video
                ];
            }
        }

        //DEvolver una respuesta
        return $this->responseJsonPersonalizado($data);
    }

    public function remove(Request $request, JwtAuthService $jwt_auth_service, $id = null) {
        //Obtener el token y comprobar si es correcto
        $token = $request->headers->get('Authorization');
        $checkToken = $jwt_auth_service->checkToken($token);

        //Array por defecto para devolver
        $data = [
            'status' => 'error',
            'code' => 400,
            'message' => 'Video no encontrado',
            'id' => $id
        ];

        //Si es correcto, hacer la actualización del usuario
        if ($checkToken) {
            //Obtener la identificación de usuario
            $identity = $jwt_auth_service->checkToken($token, true);

            //Obtener el video con respecto al ID
            $doctrine = $this->getDoctrine();
            $em = $doctrine->getManager();

            $video_repo = $doctrine->getRepository(Video::class);
            $video = $video_repo->findOneBy(array(
                'id' => $id
            ));

            //Comprobar si el video existe y es propiedad del usuario
            if($video && is_object($video) && $identity->sub == $video->getUser()->getId()) {
                $em->remove($video);
                $em->flush();

                $data = [
                    'status' => 'success',
                    'code' => 200,
                    'message' => 'Video eliminado',
                    'video' => $video
                ];
            }
        }

        //Devolver una respuesta
        return $this->responseJsonPersonalizado($data);
    }
}
