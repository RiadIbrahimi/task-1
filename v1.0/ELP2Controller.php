<?php

namespace App\Http\Controllers;

use App\ELP2;

class ELP2Controller extends Controller
{
    public function index() 
    {
        $elp2Code ='I8,A,001


Q192,024
q831
rN
S4
D7
ZT
JF
O
R255,0
f100
N
A24,19,0,1,2,3,N,"Beispieltext"
A48,56,0,4,2,2,N,"€ 15,60"
B56,102,0,E30,2,4,69,B,"0000123445672"
P1';

		$imagePath = 'img/image.png';

        $elp2 = new ELP2;

        $elp2->render($elp2Code, $imagePath);
    }
}