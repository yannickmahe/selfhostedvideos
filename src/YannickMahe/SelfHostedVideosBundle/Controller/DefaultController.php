<?php

namespace YannickMahe\SelfHostedVideosBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('YannickMaheSelfHostedVideosBundle:Default:index.html.twig');
    }

    //List & search engine results
    public function listAction(Request $request)
    {
        $terms = $request->query->get('q');
    	return $this->render('YannickMaheSelfHostedVideosBundle:Default:list.html.twig',array('terms' => $terms));
    }

    //Video page
    public function videoAction($videoId)
    {
    	return $this->render('YannickMaheSelfHostedVideosBundle:Default:video.html.twig');
    }
}
