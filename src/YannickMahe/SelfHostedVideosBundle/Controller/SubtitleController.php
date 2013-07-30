<?php 

namespace YannickMahe\SelfHostedVideosBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use YannickMahe\SelfHostedVideosBundle\Entity\Video;
use YannickMahe\SelfHostedVideosBundle\Entity\Subtitle;

class SubtitleController extends Controller
{
	public function addAction(){
        $subtitle = new Subtitle();

        $form = $this->createFormBuilder($subtitle)
        ->add('file')
        ->add('video_id')
        ->getForm();

        if($this->getRequest()->isMethod('POST')) {
            $form->bind($this->getRequest());

            if($form->isValid()) {
                $em = $this->getDoctrine()->getManager();


        		$repo = $em->getRepository('YannickMaheSelfHostedVideosBundle:Video');
        		$video = $repo->find($subtitle->getVideoId());
        		$subtitle->setVideo($video);

                $subtitle->setName($subtitle->file->getClientOriginalName());
                $em->persist($subtitle);
                $em->flush();
                $subtitle->upload();
                $em->persist($subtitle);
                $em->flush();

                $data = array('success' => true);
            } else {
                $errors = $form->getErrors();
                $errorMessage = '';
                foreach($errors as $error){
                    $errorMessage .= $error->getMessage().' ';
                }
                $data = array('error' => $errorMessage);
            }
        }

        return new Response(json_encode($data));
	}

	public function deleteAction(){

	}
}