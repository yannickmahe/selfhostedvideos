<?php

namespace YannickMahe\SelfHostedVideosBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use YannickMahe\SelfHostedVideosBundle\Entity\Video;

class DefaultController extends Controller
{
    public function indexAction()
    {
        $video = new Video();

        $form = $this->createFormBuilder($video)
        ->add('file')
        ->getForm();

        return $this->render('YannickMaheSelfHostedVideosBundle:Default:index.html.twig', array('form' => $form->createView()));
    }

    public function uploadAction(){
        $video = new Video();

        $form = $this->createFormBuilder($video)
        ->add('file')
        ->getForm();

        if($this->getRequest()->isMethod('POST')) {
            $form->bind($this->getRequest());
            if($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $video->setName($video->file->getClientOriginalName());
                $em->persist($video);
                $em->flush();
                $video->upload();
                $em->persist($video);
                $em->flush();

                $data = array('valid' => true);
            } else {
                $data = array('valid' => false);
            }
        }
        return new Response(json_encode($data));
    }

    //List & search engine results
    public function listAction(Request $request)
    {
        $terms = $request->query->get('q');
    	return $this->render('YannickMaheSelfHostedVideosBundle:Default:list.html.twig', array('terms' => $terms));
    }

    //Video page
    public function videoAction($videoId)
    {
    	return $this->render('YannickMaheSelfHostedVideosBundle:Default:video.html.twig');
    }
}
