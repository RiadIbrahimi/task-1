<?php

namespace App;

use Picqer\Barcode\BarcodeGeneratorPNG;
use Imagick;
use ImagickDraw;
use ImagickPixel;

/*
@name EPL2 to PNG
@version 1.1
@author Riad Ibrahimi
@title Senior Developer
@email ibrahimi.riad@gmail.com
@date Febrary 2018
@web www.riadibrahimi.com
*/

class EPL2
{
	private $imageFrame;
	private $draw;
	private $pixel;
	private $text = FALSE;
	private $price = FALSE;
	private $barcode = FALSE;

	public function __construct()
    {
        $this->imageFrame = new Imagick();
        $this->draw = new ImagickDraw();
        $this->pixel = new ImagickPixel('white');
    }

	public function render($epl2Code, $imagePath)
    {
        $epl2Commands = preg_split('/\r\n|[\r\n]/', $epl2Code);

        foreach ($epl2Commands as $command)
        {
            $commandName = str_getcsv($command, ',', '"');

            if (strpos($commandName[0], 'q') !== FALSE){
                $this->setup_image_size($command);
            }

            if (strpos($commandName[0], 'A') !== FALSE){
                $this->A_command($command);
            }

            if(strpos($commandName[0], 'B') !== FALSE){
				$this->B_command($command);
            }
        }

        $this->imageFrame->setImageFormat('png');
        $this->imageFrame->writeImage($imagePath);
    }

    public function setup_image_size($stringCommand)
    {
    	/*
		*	Description Use this command to set the width of the printable area of the media.
		*	Syntax qp1
		*	Parameters p1 =The width of the label measured in dots. 
		*	The q command will cause the image buffer to reformat and position to match the selected label width (p1).
		*/

    	$labelWidth = substr($stringCommand, 1);
    	$labelWidth = 640;

        $this->imageFrame->newImage($labelWidth, 360, $this->pixel);

        $this->draw->setFillColor('black');
    }

    public function A_command($stringCommand)
    {
        /*
        *   Description Prints an ASCII text string.
        *   Syntax Ap1,p2,p3,p4,p5,p6,p7,"DATA"
        *   p1 = Horizontal start position (X) in dots.
        *   p2 = Vertical start position (Y) in dots.
        *   p3 = Rotation
        *   p4 = Font selection
        *   p5 = Horizontal multiplier, expands the text horizontally. Values: 1, 2, 3, 4, 5, 6, & 8.
        *   p6 = Vertical multiplier, expands the text vertically. Values: 1, 2, 3, 4, 5, 6, 7, 8, & 9.
        *   p7 = N for normal or R for reverse image
        *   “DATA” = Represents a fixed data field.
        */

        $parameters = str_getcsv($stringCommand, ',', '"');

        if(count($parameters) === 8)
        {
        	$p1 = substr($parameters[0], 1);
        	$p2 = $parameters[1];
        	$p3 = $parameters[2];
        	$p4 = $parameters[3];
        	$p5 = $parameters[4];
        	$p6 = $parameters[5];
        	$p7 = $parameters[6];
        	$data = $parameters[7];

       		if (preg_match('#[0-9]#',$data))
       		{
       			//Looks like a price
       			$this->price = TRUE;
				$fontName = "fonts/segoeuisl-semilight.ttf";
				$fontSize = "72";
				$fontKerning = 20;

				if($this->text){
					$positionY = 80;
				}else{
					$positionY = 20;
				}
				$positionX = 80;
			}
			else
			{
				//Looks like a text
				$this->text = TRUE;
				$fontName = "fonts/elementa-regular.otf";
				$fontSize = "60";
				$fontKerning = 5;
				$positionX = 80;
				$positionY = 20;
			}  

			$this->draw->setFont($fontName);
    		$this->draw->setFontSize($fontSize);
			$this->draw->settextkerning($fontKerning);
			$this->draw->setGravity(Imagick::GRAVITY_NORTHEAST);

			$this->imageFrame->annotateImage($this->draw, $positionX, $positionY, 0, $data);
        }
    }

    public function B_command($stringCommand)
    {
        /*
        *   Description Use this command to print standard bar codes.
        *   Syntax Bp1,p2,p3,p4,p5,p6,p7,p8,"DATA"
        *   p1 = Horizontal start position (X) in dots.
        *   p2 = Vertical start position (Y) in dots.
        *   p3 = Rotation
        *   p4 = Bar Code selection (see Table 2-1 on next page).
        *   p5 = Narrow bar width in dots. (see Table 2-1 on next page).
        *   p6 = Wide bar width in dots. Acceptable values are 2-30.
        *   p7 = Bar code height in dots.
        *	p8 = Print human readable code. Values: B=yes or N=no.
        *   “DATA” = Represents a fixed data field. The data in this field must comply with the selected bar code’s specified format.
        */

        $parameters = str_getcsv($stringCommand, ',', '"');

        if(count($parameters) === 9)
        {
        	$this->barcode = TRUE;

        	$p1 = substr($parameters[0], 1);
        	$p2 = $parameters[1];
        	$p3 = $parameters[2];
        	$p4 = $parameters[3];
        	$p5 = $parameters[4];
        	$p6 = $parameters[5];
        	$p7 = $parameters[6];
        	$p8 = $parameters[7];
        	$data = $parameters[8];

        	$barcodeWidht = 3;
        	$barcodeHeight = 100;    

	        $generator = new BarcodeGeneratorPNG();
	        $generatedBarcode = $generator->getBarcode($data, $generator::TYPE_CODE_128, $barcodeWidht, $barcodeHeight);
	        $generatedBarcodeImage = new Imagick();
	        $generatedBarcodeImage->readImageBlob($generatedBarcode);
			
	        $imgFrameGeometry = $this->imageFrame->getImageGeometry(); 
			$imgFrameWidth = $imgFrameGeometry['width']; 
			$barcodeImageGeometry = $generatedBarcodeImage->getImageGeometry();
			$barcodeImageWidth = $barcodeImageGeometry['width'];
			$barcodeImageRightMargin = 80;
			$positionX = $imgFrameWidth - $barcodeImageWidth - $barcodeImageRightMargin;

			if($this->price && $this->text){
	        	$positionY = 180;  	
    		}elseif($this->price && !$this->text){
	        	$positionY = 180;  	
    		}elseif(!$this->price && $this->text){
	        	$positionY = 180;  	
    		}elseif(!$this->price && !$this->text){
	        	$positionY = 180;  	
    		}

	        $this->imageFrame->compositeImage($generatedBarcodeImage, Imagick::COMPOSITE_MATHEMATICS, $positionX, $positionY);

			$this->draw->setGravity(Imagick::GRAVITY_NORTHWEST);
			$this->draw->settextkerning(5);
			$this->draw->setFontSize(24);
			$this->imageFrame->annotateImage($this->draw, $positionX, $positionY + $barcodeHeight, 0, $data);
        }
    }
}