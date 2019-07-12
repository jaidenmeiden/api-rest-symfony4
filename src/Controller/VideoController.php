<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\User;
use App\Entity\Video;

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


}
