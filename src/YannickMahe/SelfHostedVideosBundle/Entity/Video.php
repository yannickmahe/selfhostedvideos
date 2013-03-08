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
     * @var integer
     *
     * @ORM\Column(name="width", type="integer")
     */
    private $width = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="height", type="integer")
     */
    private $height = 0;


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
     * Set name
     *
     * @param string $name
     * @return Video
     */
    public function setWidth($width)
    {
        $this->width = $width;
    
        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Video
     */
    public function setHeight($height)
    {
        $this->height = $height;
    
        return $this;
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
        return 'uploads/videos';
    }

    private function getTargetUploadRootDir(){
        return $this->getUploadRootDir().DIRECTORY_SEPARATOR.date('Y-m-d').DIRECTORY_SEPARATOR.$this->id;
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

    public function getThumbnailAbsolutePath(){
        $res =  str_replace('.mp4', '.jpg', $this->getAbsolutePath());
        $res =  str_replace('.avi', '.jpg', $res);
        $res =  str_replace('.mov', '.jpg', $res);
        return $res;
    }

    public function getThumbnailWebPath(){
        $res =  str_replace('.mp4', '.jpg', $this->getWebPath());
        $res =  str_replace('.avi', '.jpg', $res);
        $res =  str_replace('.mov', '.jpg', $res);
        return $res;
    }

    public function generateThumbnail($ffmpeg, $maxWidth, $maxHeight){
        if(!is_file($this->getAbsolutePath())){
            Throw new \Exception("Video hasn't been uploaded");
        }
        //Extract thumbnail
        $ffmpeg->open($this->getAbsolutePath())
               ->extractImage(5,$this->getThumbnailAbsolutePath());

        //Resize to required dimensions, keeping ratio
        $size = getimagesize($this->getThumbnailAbsolutePath());
        $sourceWidth = $size[0];
        $sourceHeight = $size[1];

        $sourceRatio = $sourceWidth / $sourceHeight;
        $targetRatio = $maxWidth / $maxHeight;

        if ( $sourceRatio > $targetRatio ) {
            $scale = $sourceWidth / $maxWidth;
        } else {
            $scale = $sourceHeight / $maxHeight;
        }

        $resizeWidth = (int)($sourceWidth / $scale);
        $resizeHeight = (int)($sourceHeight / $scale);

        $marginLeft = (int)(($maxWidth - $resizeWidth) / 2);
        $marginTop = (int)(($maxHeight - $resizeHeight) / 2);

        $new_image = imagecreatetruecolor($maxWidth, $maxHeight);
        
        imagecolortransparent($new_image, imagecolorallocate($new_image, 0, 0, 0));
        imagealphablending($new_image, false);
        imagesavealpha($new_image, true);
        imagecopyresampled($new_image, imagecreatefromjpeg($this->getThumbnailAbsolutePath()), 
                            $marginLeft, $marginTop, 0, 0, $resizeWidth, $resizeHeight, $sourceWidth, $sourceHeight);
        
        imagejpeg($new_image, $this->getThumbnailAbsolutePath());
    }

    public function setDimensions($ffprobe){
        if(!is_file($this->getAbsolutePath())){
            Throw new \Exception("Video hasn't been uploaded");
        }
        $info =  @json_decode($ffprobe->probeStreams($this->getAbsolutePath()));//Warning silenced for dev env

        $found = false;
        foreach ($info as $infoSub) {
            if(property_exists($infoSub, 'width')){
                $sizeInfo = $infoSub;
                $found = true;
                break;
            }
        }
        if(!$found){
            Throw new \Exception("Dimensions can't be determined");
        }

        $width = $sizeInfo->width;
        $height = $sizeInfo->height;
        
        $this->setWidth($width);
        $this->setHeight($height);
    }
}