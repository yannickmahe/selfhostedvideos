<?php

namespace YannickMahe\SelfHostedVideosBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Subtitle
 *
 * @ORM\Table()
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="YannickMahe\SelfHostedVideosBundle\Entity\SubtitleRepository")
 */
class Subtitle
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="video_id", type="integer")
     */
    private $videoId;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity="Video", cascade={"all"}, fetch="EAGER")
     */
    private $video;

    /**
     * @var string 
     *
     * @ORM\Column(type="string", length=511, nullable=true)
     */
    private $path;    

    /**
     * @Assert\File(maxSize="5000000000")
     */
    public $file; 

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set videoId
     *
     * @param integer $videoId
     * @return Subtitle
     */
    public function setVideoId($videoId)
    {
        $this->videoId = $videoId;
    
        return $this;
    }

    /**
     * Get videoId
     *
     * @return integer 
     */
    public function getVideoId()
    {
        return $this->videoId;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Subtitle
     */
    public function setName($name)
    {
        $this->name = $name;
    
        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set video
     *
     * @param \YannickMahe\SelfHostedVideosBundle\Entity\Video $video
     * @return Subtitle
     */
    public function setVideo(\YannickMahe\SelfHostedVideosBundle\Entity\Video $video = null)
    {
        $this->video = $video;
    
        return $this;
    }

    /**
     * Get video
     *
     * @return \YannickMahe\SelfHostedVideosBundle\Entity\Video 
     */
    public function getVideo()
    {
        return $this->video;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * Set path
     *
     * @param string $path
     * @return Video
     */
    public function setPath($path)
    {
        $this->path = $path;
    
        return $this;
    }

    /**
     * Get path
     *
     * @return string 
     */
    public function getPath()
    {
        return $this->path;
    }

    public function getAbsolutePath()
    {
        return null === $this->path
            ? null
            : $this->getUploadRootDir().DIRECTORY_SEPARATOR.$this->path;
    }

    public function getWebPath()
    {
        return null === $this->path
            ? null
            : $this->getUploadDir().DIRECTORY_SEPARATOR.$this->path;
    }

    protected function getUploadRootDir()
    {
        // the absolute directory path where uploaded
        // documents should be saved
        return __DIR__.'/../../../../web/'.$this->getUploadDir();
    }

    protected function getUploadDir()
    {
        // get rid of the __DIR__ so it doesn't screw up
        // when displaying uploaded doc/image in the view.
        return 'uploads/subtitles';
    }

    private function getTargetUploadRootDir(){
        return $this->getUploadRootDir().DIRECTORY_SEPARATOR.date('Y-m-d').DIRECTORY_SEPARATOR.$this->id;
    }

    /**
     * @ORM\PreRemove()
     */
    public function storeFilenameForRemove()
    {
        $this->toRemove = array();
        $this->toRemove[] = $this->getAbsolutePath();
        $this->toRemove[] = $this->getThumbnailAbsolutePath();

    }

    /**
     * @ORM\PostRemove()
     */
    public function removeUpload()
    {
        foreach($this->toRemove as $toRemoveFile){
            unlink($toRemoveFile);
        }
    }

    public function moveFromDisc($filepath){

        if(!is_file($filepath)){
            Throw new \Exception("No file at ".$filepath);//Todo: check if actually a video file
        }

        if(!is_dir($this->getTargetUploadRootDir())){
            mkdir($this->getTargetUploadRootDir(), 0777, true);
        }

        copy($filepath,$this->getTargetUploadRootDir().DIRECTORY_SEPARATOR.basename($filepath));
    
        $this->path = date('Y-m-d').DIRECTORY_SEPARATOR.$this->id.DIRECTORY_SEPARATOR.basename($filepath);
    }


    public function upload()
    {
        // the file property can be empty if the field is not required
        if (null === $this->file) {
            return;
        }

        if(!is_dir($this->getTargetUploadRootDir())){
            mkdir($this->getTargetUploadRootDir(), 0777, true);
        }

        // move takes the target directory and then the
        // target filename to move to
        $this->file->move(
            $this->getTargetUploadRootDir(),
            $this->file->getClientOriginalName()
        );

        // set the path property to the filename where you've saved the file
        $this->path = date('Y-m-d').DIRECTORY_SEPARATOR.$this->id.DIRECTORY_SEPARATOR.$this->file->getClientOriginalName();

        // clean up the file property as you won't need it anymore
        $this->file = null;
    }
}