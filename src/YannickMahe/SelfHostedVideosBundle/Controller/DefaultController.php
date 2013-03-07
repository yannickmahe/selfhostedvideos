<?php

namespace YannickMahe\SelfHostedVideosBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Monolog\Logger;
use Monolog\Handler\NullHandler;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;

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

                $logger = new Logger('MyLogger');
                $logger->pushHandler(new NullHandler());

                $ffmpeg = FFMpeg::load($logger);
                $video->generateThumbnail($ffmpeg, 200, 300);//TODO: put thumbnail size in conf

                $ffprobe = FFProbe::load($logger);
                $video->setDimensions($ffprobe);

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
        $em = $this->getDoctrineManager();
        if(trim($terms) != ''){
            $search_terms = str_replace(' ', '%', $terms);
            $dql = "SELECT v FROM YannickMaheSelfHostedVideosBundle:Video v WHERE v.name LIKE :terms";
            $query = $em->createQuery($dql);
            $query->setParameter('terms',"%$search_terms%");
        } else {
            $dql = "SELECT v FROM YannickMaheSelfHostedVideosBundle:Video v";
            $query = $em->createQuery($dql);
        }
        
        

        $videos = $query->getResult();

    	return $this->render('YannickMaheSelfHostedVideosBundle:Default:list.html.twig', 
                             array(
                                'terms' => $terms,
                                'videos' => $videos,
                                ));
    }

    //Video page
    public function videoAction($video_id)
    {
        $em = $this->getDoctrineManager();
        $video = $em->getRepository('YannickMaheSelfHostedVideosBundle:Video')->find($video_id);
    	return $this->render('YannickMaheSelfHostedVideosBundle:Default:video.html.twig',array('video' => $video));
    }

    private function getDoctrineManager(){
        return $this->getDoctrine()->getManager();
    }
}
