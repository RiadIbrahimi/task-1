<?php

namespace App;

use Picqer\Barcode\BarcodeGeneratorPNG;
use Imagick;
use ImagickDraw;
use ImagickPixel;

class ELP2
{
	private $image;
	private $draw;
	private $pixel;

	public function __construct()
    {
        $this->image = new Imagick();
        $this->draw = new ImagickDraw();
        $this->pixel = new ImagickPixel('white');
    }

	public function render($elp2Code, $imagePath)
    {
        $elp2Commands = preg_split('/\r\n|[\r\n]/', $elp2Code);

        foreach ($elp2Commands as $command)
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

        $this->image->setImageFormat('png');
        $this->image->writeImage($imagePath);
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

        $this->image->newImage($labelWidth, 250, $this->pixel);

        $this->draw->setFillColor('black');
        $this->draw->setFont('Bookman-DemiItalic');
        $this->draw->setFontSize( 30 );

    	echo $labelWidth . "<br />";
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

        echo $stringCommand . "<br />";

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

			$this->image->annotateImage($this->draw, $p1, $p2, 0, $data);
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

        echo $stringCommand . "<br />";

        $parameters = str_getcsv($stringCommand, ',', '"');

        if(count($parameters) === 9)
        {
        	$p1 = substr($parameters[0], 1);
        	$p2 = $parameters[1];
        	$p3 = $parameters[2];
        	$p4 = $parameters[3];
        	$p5 = $parameters[4];
        	$p6 = $parameters[5];
        	$p7 = $parameters[6];
        	$p8 = $parameters[7];
        	$data = $parameters[8];        	

	        $generator = new BarcodeGeneratorPNG();
	        $generatedBarcode = $generator->getBarcode($data, $generator::TYPE_CODE_128, 2, 30, array(255, 255, 255));

	        $generatedBarcodeImage = new Imagick();
	        $generatedBarcodeImage->readImageBlob($generatedBarcode);

	        $this->image->compositeImage($generatedBarcodeImage, Imagick::COMPOSITE_MATHEMATICS, $p1, $p2);
        }
    }
}