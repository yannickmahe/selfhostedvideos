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


class AddCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('shv:video:add')
            ->setDescription('Add a video to the site')
            ->addArgument(
                'filepath',
                InputArgument::REQUIRED,
                'Which video do you want to add?'
            )
            ->addOption(
               'remove',
               null,
               InputOption::VALUE_NONE,
               'If set, the task will remove the original file'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filepath = $input->getArgument('filepath');

        if(!is_file($filepath)){
            Throw new \Exception("No file at ".$filepath);
        }
        
        $output->writeln("Adding video at ".$filepath); //Todo: check if actually a video file

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
    }
}