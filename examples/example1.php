<?php

use RestClient\Request;
use RestClient\Response;

require '../vendor/autoload.php';

$req = new Request;
$res = $req->get('https://viacep.com.br/ws/01001000/json/');

echo $res->getHeaderLine('content-type');

