<?php

namespace YannickMahe\SelfHostedVideosBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('YannickMaheSelfHostedVideosBundle:Default:index.html.twig');
    }
}
