<?php

use Rdlv\JDanger\Transmission;

require_once __DIR__ .'/vendor/autoload.php';

add_action('init', [Transmission::getInstance(), 'init']);