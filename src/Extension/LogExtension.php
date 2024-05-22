<?php

namespace RestClient\Extension;

use RestClient\Log\LogData;

interface LogExtension{

    public function register(LogData $data):void;

}