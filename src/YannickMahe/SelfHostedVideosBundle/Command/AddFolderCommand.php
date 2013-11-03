<?php

namespace YannickMahe\SelfHostedVideosBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Monolog\Logger;
use Monolog\Handler\NullHandler;

use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;

use YannickMahe\SelfHostedVideosBundle\Entity\Video;


class AddFolderCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('shv:folder:add')
            ->setDescription('Add all the videos in a folder (non recursively) to the site')
            ->addArgument(
                'path',
                InputArgument::REQUIRED,
                'Which folder do you want to add?'
            )
            ->addOption(
               'remove',
               null,
               InputOption::VALUE_NONE,
               'If set, the task will remove the original files and the containing folder if it is empty'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = $input->getArgument('path');
        $path = realpath($path);

        if(!is_dir($path)){
            Throw new \Exception("No folder at at ".$path);
        }
        
        $output->writeln("Adding videos at ".$path); 

        $dir = opendir($path);
        while($file = readdir($dir)){
            $filepath = $path.DIRECTORY_SEPARATOR.$file;
            $ext = pathinfo($filepath, PATHINFO_EXTENSION);
            if(in_array($ext, array('mov','mpeg','avi','mkv','mp4','mpg'))){  //Todo: check if actually a video file
                $output->writeln("Adding video at ".$filepath);        
                try{
                    $video = new Video();
                    $em = $this->getContainer()->get('doctrine')->getEntityManager();
                    $video->setName(basename($filepath));
                    $em->persist($video);
                    $em->flush();
                    $video->moveFromDisc($filepath);

                    $ffmpeg = FFMpeg::create();
                    $ffprobe = FFProbe::create();
                    
                    $video->postProcess($ffmpeg,$ffprobe);
                    
                    $em->persist($video);
                    $em->flush();

                    if ($input->getOption('remove')) {
                        unlink($filepath);
                        $output->writeln("Deleted ".$filepath); 
                    }
                } catch (\Exception $e){
                    Throw $e;
                }
                
                $output->writeln("Video n° ".$video->getId()." has been added"); 
            } else {
                $output->writeln($filepath." is not a video");
            }
        }

        if ($input->getOption('remove')) {
            rmdir($path);
            $output->writeln("Deleted ".$path); 
        }
        /*

        */
    }
}