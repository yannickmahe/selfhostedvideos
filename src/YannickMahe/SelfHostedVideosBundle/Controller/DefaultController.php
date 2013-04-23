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

        return $this->render('YannickMaheSelfHostedVideosBundle:Default:upload.html.twig', array('form' => $form->createView()));
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
                $ffprobe = FFProbe::load($logger);
                
                $video->postProcess($ffmpeg,$ffprobe);
                $em->persist($video);
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

    //List & search engine results
    public function listAction(Request $request)
    {
        $terms = $request->query->get('q');
        $em = $this->getDoctrineManager();
        if(trim($terms) != ''){
            $search_terms = str_replace(' ', '%', $terms);
            $dql = "SELECT v FROM YannickMaheSelfHostedVideosBundle:Video v WHERE v.name LIKE :terms ORDER BY v.id DESC";
            $query = $em->createQuery($dql);
            $query->setParameter('terms',"%$search_terms%");
        } else {
            $dql = "SELECT v FROM YannickMaheSelfHostedVideosBundle:Video v ORDER BY v.id DESC";
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

    public function deleteAction($video_id)
    {

        $em = $this->getDoctrineManager();
        $video = $em->getRepository('YannickMaheSelfHostedVideosBundle:Video')->find($video_id);

        if(!$video){
            throw $this->createNotFoundException('No video found');
        }
        $em->remove($video);
        $em->flush();

        return $this->redirect($this->generateUrl('yannick_mahe_self_hosted_videos_list'));
    }

    public function addVideoFromFileAction(Request $request){
        $filepath = $request->request->get('filepath');
        $delete = $request->request->get('delete');
        $failed = false;

        try{

            if(!is_file($filepath)){
                Throw new \Exception("No file at ".$filepath);
            }
        
            $video = new Video();
            $em = $this->getDoctrineManager();
            $video->setName(basename($filepath));
            $em->persist($video);
            $em->flush();
            $video->moveFromDisc($filepath);

            $logger = new Logger('MyLogger');
            $logger->pushHandler(new NullHandler());
            $ffmpeg = FFMpeg::load($logger);
            $ffprobe = FFProbe::load($logger);
            
            $video->postProcess($ffmpeg,$ffprobe);
            
            $em->persist($video);
            $em->flush();

            if ($delete) {
                unlink($filepath);
            }
        } catch (\Exception $e){
            $failed = true;
            $error = $e->getMessage();
        }
        if($failed){
            $data = array(
                    'success' => false,
                    'errorMessage' => $error,
                );
        } else {
            $data = array(
                    'success' => true,
                    'id' => $video->getId(),
                );
        }

        return new Response(json_encode($data));
    }

    public function folderAction($folder){
        $files = array();
        $videos = array();
        $subfolders = array();

        $points = scandir($folder);

        foreach($points as $point){
            if($point == '.' || $point == '..'){
                continue;
            }
            if(is_dir($folder.DIRECTORY_SEPARATOR.$point)){
                $subfolders[] = $folder.DIRECTORY_SEPARATOR.$point;
            }
            if(is_file($folder.DIRECTORY_SEPARATOR.$point)){
                $ext = pathinfo($folder.DIRECTORY_SEPARATOR.$point, PATHINFO_EXTENSION);
                if(in_array($ext, array('mp4'))){  //Todo: check if actually a video file
                    $videos[] = $folder.DIRECTORY_SEPARATOR.$point;
                } else {
                    $files[] = $folder.DIRECTORY_SEPARATOR.$point;
                }
            }
        }

        sort($files);
        sort($videos);
        sort($subfolders);

        return $this->render('YannickMaheSelfHostedVideosBundle:Default:folder.html.twig', array('folder' => $folder, 'subfolders' => $subfolders, 'files' => $files, 'videos' => $videos));
    }

    public function addFromFileAction(){
        $folders = $this->container->getParameter('folders');
        return $this->render('YannickMaheSelfHostedVideosBundle:Default:add_from_file.html.twig', array('folders' => $folders));
    }

    private function getDoctrineManager(){
        return $this->getDoctrine()->getManager();
    }
}
