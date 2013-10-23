<?php

defined('SYSPATH') or die('No direct script access.');

// Static file serving (CSS, JS, images)
Route::set('soclogin', 'soclogin(/<action>)', array())
        ->defaults(array(
            'controller' => 'Soclogin',
            'action' => 'index',
//            'class' => 'Soclogin',
//            'file' => NULL,
        ));

