<?php

namespace YannickMahe\SelfHostedVideosBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Video
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="YannickMahe\SelfHostedVideosBundle\Entity\VideoRepository")
 */
class Video
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
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=511)
     */
    private $name;

    /**
     * @var string 
     *
     * @ORM\Column(type="string", length=511, nullable=true)
     */
    public $path;    

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
     * Set name
     *
     * @param string $name
     * @return Video
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
            : $this->getUploadRootDir().DIRECTORY_SEPARATOR.$this->id.DIRECTORY_SEPARATOR.$this->path;
    }

    public function getWebPath()
    {
        return null === $this->path
            ? null
            : $this->getUploadDir().DIRECTORY_SEPARATOR.$this->id.DIRECTORY_SEPARATOR.$this->path;
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
        return 'uploads/videos';
    }

    public function upload()
    {
        // the file property can be empty if the field is not required
        if (null === $this->file) {
            return;
        }

        if(!is_dir($this->getUploadRootDir())){
            mkdir($this->getUploadRootDir());
        }

        if(!is_dir($this->getUploadRootDir().DIRECTORY_SEPARATOR.$this->id)){
            mkdir($this->getUploadRootDir().DIRECTORY_SEPARATOR.$this->id);
        }

        // move takes the target directory and then the
        // target filename to move to
        $this->file->move(
            $this->getUploadRootDir().DIRECTORY_SEPARATOR.$this->id,
            $this->file->getClientOriginalName()
        );

        // set the path property to the filename where you've saved the file
        $this->path = $this->file->getClientOriginalName();

        // clean up the file property as you won't need it anymore
        $this->file = null;
    }
}