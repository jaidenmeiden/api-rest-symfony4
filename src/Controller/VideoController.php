<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class VideoController extends AbstractController
{
    public function index()
    {
        return $this->render('video/index.html.twig', [
            'controller_name' => 'VideoController',
        ]);
    }
}
