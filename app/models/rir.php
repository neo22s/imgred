<?php
/**
 * RIR main class
 *
 * @package     rir
 * @subpackage  main
 * @category    Helper
 * @author      Chema Garrido <chema@garridodiaz.com>
 */

class Model_rir extends Model{
   
    private $img; //url image
    private $filename; //only the file name
    private $file; //full file path with name
    
    public function get($img)//run void
    {
        if (H::isURL($img))
        { 
            $this->img=$img;
            //die(var_dump( $this->img));
            $this->copyImg();
        }
        else $this->file=IMG_ERROR;//not valid URL
        
        $this->imageHeaders();
    }
    
    private function imageHeaders()
    { 
        $Size = filesize($this->file);
    	$MIME = $this->GetMIMEType($this->file);
    	$Time = filemtime($this->file); //die($Size.'---'.$MIME.'---'.$Time);
      
            
    	// Send the appropriate headers for the image
    	header( 'Content-Length: ' . $Size );
    	header( 'Content-Type: ' . $MIME );
    	header( 'Date: ' . date('r') );
    	header( 'Expires: ' . date('r', time() + (365*24*60*60)) );//1 year expire
    	header( 'Last-Modified: ' . date('r', $Time) );    	
    	set_time_limit(1);
    	@readfile( $this->file );// Now send the image
    	die();
    }
	/**
	 * copy image from URL to HD
	 *
	 */
	private function copyImg()//background aswell
	{
	    $db=DB::get_instance();
	    
	    $error=FALSE;//in case there's an error...
	    
	    $img_ext  = substr(strrchr($this->img, '.'),1);
		$img_types=explode(',',IMG_TYPES);

		if (in_array($img_ext,$img_types))//is a supported type
		{
			$this->filename = md5(H::friendly_url($this->img));//HD name
			$image_dir=IMG_UPLOAD_DIR.substr($this->filename,0,2).'/';//folder name now just first 2 letters
			$this->file = $image_dir.$this->filename;//full path name
			
			//die($file);
			if (!file_exists($this->file) || time() > (filemtime($this->file)+IMG_EXPIRE) )//if file exists or expired 
			{
        		//copy img 
			    $contents = @file_get_contents($this->img);
			    $size = strlen($contents);//size of the image
    			if ($contents!==FALSE && $size < IMG_MAX_SIZE)
    			{
    			    
    			    if (!is_dir($image_dir))//creating the folder if we need it
    			    {
    			        umask(0000);
                        if(! mkdir($image_dir, 0755,TRUE))
                        {
                            //return FALSE;@todo
                        }
    			    }
    			    
    			    $f = fopen( $this->file, 'wb' );
            		fwrite( $f, $contents );
            		fclose($f);
        			//end copy
        			
            		//insert into DB
            		$dimensions = GetImageSize($this->file);//getting dimensions
            		$db->insert('rir_images',
            		                          array(
            		                          		'MD5'    =>$this->filename,
            										'url'    =>$this->img,
            										'width'  =>$dimensions[0],
            										'height' =>$dimensions[0],
            										'size'   =>filesize($this->file)
            		                                )
            		            );
       
            		
            		if (IMG_WATERMARK!=FALSE)
            		{
            		    $image=$this->createImage();
            		    if(isset($image)) 
            		    {
            		        $this->WaterMarkImage($image,$this->file);//if the image was created we put the watermark
            		    }
            		}
            		//return $filename;
            		
    			}//if contents	
    			else $error=TRUE;//can't retrieve or too big
    			
			}//if exists
			else //exists
			{
			    //update ++views
			    $db->query("UPDATE rir_images SET views=views+1,date_last_view=NOW() WHERE MD5='".$this->filename."' limit 1");
			  
			}
            
   		}	//if type
   		else $error=TRUE;//wrong type
   		
   		if($error==TRUE)
   		{
   		   $this->file=IMG_ERROR; 
   		}

    	//return FALSE;
	}
    
   
    
    private function GetMIMEType($filename)
    {//returns mimetype for images
        $ext = pathinfo($filename);
        $ext = $ext['extension'];
        switch($ext){
            case "bmp": return "image/bmp"; break;
            case "gif": return "image/gif"; break;
            case "jpe": return "image/jpeg"; break;
            case "jpeg": return "image/jpeg"; break;
            case "jpg": return "image/jpeg"; break;
            case "png": return "image/png"; break;
            case "swf": return "application/x-shockwave-flash"; break;
            case "tif": return "image/tiff"; break;
            case "tiff": return "image/tiff"; break;
            default: return ""; break;
        }
    }

    private function createImage()
    {
        $MIME = $this->GetMIMEType($this->file);
		if( $MIME == 'image/gif' ) $image = ImageCreateFromGIF($this->file);
		else if( $MIME == 'image/png' ) $image = ImageCreateFromPNG($this->file);
		else if( $MIME == 'image/jpeg' ) $image = ImageCreateFromJPEG($this->file);
		else{// The file is not a PNG, GIF, or JPEG. We can't create it we destroy it
			unlink($this->file);  
			return FALSE;
		}
		return $image;
    }
    
    private function WaterMarkImage(&$image,$Filename){//watermark for the image
    	$Dimensions = GetImageSize($Filename);//getting dimensions
    	$W = $Dimensions[0];
    	$H = $Dimensions[1];
    	if( ImageIsTrueColor($image) && ($W >= IMG_MAX_WIDTH || $H >= IMG_MAX_HEIGHT) ){//only for large images
    		$Watermark = ImageCreateFromPNG( IMG_WATERMARK );
    
    		for( $Y = 0; $Y < 24; $Y ++ )
    		for( $X = 0; $X < 96; $X ++ ){
    			$Color = ImageColorAt($Watermark, $X, $Y);
    
    			$A_S = ($Color >> 24) / 127;
    			$R_S = ($Color >> 16) & 0xFF;
    			$G_S = ($Color >> 8) & 0xFF;
    			$B_S = $Color & 0xFF;
    
    			$Color = ImageColorAt( $image, $X + $W - 96, $Y + $H - 24 );
    			$R_D = ($Color >> 16) & 0xFF;
    			$G_D = ($Color >> 8) & 0xFF;
    			$B_D = $Color & 0xFF;
    
    			$R = (int)($A_S * $R_D) + (int)((1 - $A_S) * $R_S);
    			$G = (int)($A_S * $G_D) + (int)((1 - $A_S) * $G_S);
    			$B = (int)($A_S * $B_D) + (int)((1 - $A_S) * $B_S);
    
    			$Color = ImageColorAllocate( $image, $R, $G, $B );
    			ImageSetPixel( $image, $X + $W - 96, $Y + $H - 24, $Color );
    		}
    		ImageDestroy($Watermark);
    
    		if( $MIME == 'image/gif' ) ImageGIF( $image, $Filename );
    		else if( $MIME == 'image/png' )ImagePNG( $image, $Filename );
    		else ImageJPEG( $image, $Filename, 75 );
    	}//end if image watermark
    	ImageDestroy($image);	
    }
     
}


/*
 
 CREATE TABLE IF NOT EXISTS `rir_images` (
  `id_image` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `MD5` VARCHAR(40) NOT NULL,
  `url` VARCHAR(500) NOT NULL,
  `date_created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_last_view` DATETIME,
  `size` INT(10) NOT NULL DEFAULT 0,
  `width` INT(10) NOT NULL DEFAULT 0,
  `height` INT(10) NOT NULL DEFAULT 0,
  `views` BIGINT  NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_image`),
  INDEX `rir_images_IK_MD5` (`MD5` ASC)
) 
ENGINE = MyISAM
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;*/
 
