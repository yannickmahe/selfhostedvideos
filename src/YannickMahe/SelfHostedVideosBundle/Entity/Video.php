<?php

namespace YannickMahe\SelfHostedVideosBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use FFMpeg\Coordinate\TimeCode as TimeCode;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Video
 *
 * @ORM\Table()
 * @ORM\HasLifecycleCallbacks
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
    private $path;    

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

    private $info;

    /**
     * @ORM\OneToMany(targetEntity="Subtitle", mappedBy="video", cascade={"remove", "persist"})
     */
    protected $subtitles;



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
        $timecode = new TimeCode(0,0,5,0);
        $ffmpeg->open($this->getAbsolutePath())->frame($timecode)->save($this->getThumbnailAbsolutePath());

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
        $streams = $ffprobe->streams($this->getAbsolutePath());//Warning silenced for dev env

        $found = false;
        foreach ($streams as $stream) {
            if($stream->isVideo()){
                $dimension = $stream->getDimensions();
                $this->setWidth($dimension->getWidth());
                $this->setHeight($dimension->getHeight());
                return;
            }
        }
        Throw new \Exception("Dimensions can't be determined");
        
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

    public function getInfo(){
        if(!is_null($this->info)){
            return $this->info;
        } else {
            $videoNameCorr = str_replace('x264', '', $this->name);
            $videoNameCorr = str_replace('.mp4', '', $videoNameCorr);
            $videoNameCorr = str_replace('h264', '', $videoNameCorr);

            $matches = array();

            //Season & episode
            //Ordered so as to avoid false positives
            if(preg_match('/S(\d{2})E(\d{2})E(\d{2})/i', $videoNameCorr, $matches)){ //S08E11E12 double episode, season 8 ep 11 and 12
                $delimiter = $matches[0];
                $season = intval($matches[1]);
                $episodes = array(intval($matches[2]),intval($matches[3]));
            } elseif (preg_match('/S(\d{2})E(\d{2})/i', $videoNameCorr, $matches)) { //S01E13
                $delimiter = $matches[0];
                $season = intval($matches[1]);
                $episodes = array(intval($matches[2]));
            } elseif (preg_match('/(\d)x(\d{2})/i', $videoNameCorr, $matches)) { //3x05
                $delimiter = $matches[0];
                $season = intval($matches[1]);
                $episodes = array(intval($matches[2]));
            } elseif (preg_match('/(\d)(\d{2})/i', $videoNameCorr, $matches)) { //819
                $delimiter = $matches[0];
                $season = intval($matches[1]);
                $episodes = array(intval($matches[2]));
            } else {
                //Not found !
            }

            $nameRaw = substr($videoNameCorr, 0,strpos($videoNameCorr, $delimiter));

            //Spliters : - _ . 
            $nameRaw = str_replace('-', ' ', $nameRaw);
            $nameRaw = str_replace('_', ' ', $nameRaw);
            $nameRaw = str_replace('.', ' ', $nameRaw);

            //CamelCase
            $nameProcessed = $nameRaw[0];
            for($i = 1; $i < strlen($nameRaw); $i++){
                if(
                        $nameRaw[$i-1] != ' '      //Previous is not a space
                    &&  ctype_upper($nameRaw[$i])  //And next is upper case
                ){
                    //Then it is next word
                    $nameProcessed[strlen($nameProcessed)] = ' ';
                    $nameProcessed[strlen($nameProcessed)] = $nameRaw[$i];
                } else {
                    $nameProcessed[strlen($nameProcessed)] = $nameRaw[$i];
                }
            }

            $nameProcessed = trim($nameProcessed);

            $this->info = array(
                    'series_name' => $nameProcessed,
                    'season' => $season,
                    'episodes' => $episodes,
                );
            return $this->info;
        }
    }

    public function postProcess($ffmpeg, $ffprobe, OutputInterface $output = null){
        //Check format
        $streams = $ffprobe->streams($this->getAbsolutePath());//Warning silenced for dev env
        $found = false;

        foreach ($streams as $stream) {
            if($stream->isVideo()){
                $codec_name = $stream->get('codec_name');
                $found = true;
                break;
            }
        }
        if(!$found){
            Throw new \Exception("File is not a video file");
        }

        if($codec_name != 'h264'){
            $x264Format = new \FFMpeg\Format\Video\X264();
            $newPath =  str_replace('.avi', '.mp4', $this->getAbsolutePath());
            $newPath =  str_replace('.mov', '.mp4', $newPath);
            $newPath =  str_replace('.mpg', '.mp4', $newPath);
            $newPath =  str_replace('.mpeg', '.mp4', $newPath);

            $ffmpeg->setProber($ffprobe);
            if($output){
                $output->writeln("Converting ".$this->getAbsolutePath()." to ".$newPath);
            }
            $ffmpeg->open($this->getAbsolutePath())->encode($x264Format, $newPath)->close();  
            if($output){
                $output->writeln("Conversion done!");
            }

            unlink($this->getAbsolutePath());

            $this->path =  str_replace('.avi', '.mp4', $this->getPath());
            $this->path =  str_replace('.mov', '.mp4', $this->getPath());
            $this->path =  str_replace('.mpg', '.mp4', $this->getPath());
            $this->path =  str_replace('.mpeg', '.mp4', $this->getPath());
        }

        
        $this->generateThumbnail($ffmpeg, 300, 200);//TODO: put thumbnail size in conf
        $this->setDimensions($ffprobe);
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->subtitles = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    /**
     * Add subtitles
     *
     * @param \YannickMahe\SelfHostedVideosBundle\Entity\Subtitle $subtitles
     * @return Video
     */
    public function addSubtitle(\YannickMahe\SelfHostedVideosBundle\Entity\Subtitle $subtitles)
    {
        $this->subtitles[] = $subtitles;
    
        return $this;
    }

    /**
     * Remove subtitles
     *
     * @param \YannickMahe\SelfHostedVideosBundle\Entity\Subtitle $subtitles
     */
    public function removeSubtitle(\YannickMahe\SelfHostedVideosBundle\Entity\Subtitle $subtitles)
    {
        $this->subtitles->removeElement($subtitles);
    }

    /**
     * Get subtitles
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getSubtitles()
    {
        return $this->subtitles;
    }
}