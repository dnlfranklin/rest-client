<?php

use RestClient\Request;

require '../vendor/autoload.php';

$req = new Request;
$res = $req->get('https://viacep.com.br/ws/01001000/json/');

var_dump($res->get_data());