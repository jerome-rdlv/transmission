<?php
/*
Plugin Name: Transmission
Plugin URI: http://transmission.cymru/
Description: Continuous play of radio broadcast for Transmission project
Version: 0.1
Author: Jérôme Mulsant
Author URI: https://rue-de-la-vieille.fr
License: GPL3
 */

use Rdlv\JDanger\Transmission;

require_once __DIR__ .'/vendor/autoload.php';

add_action('init', [Transmission::getInstance(), 'init']);