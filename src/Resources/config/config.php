<?php
/**
 * This is a configuration file for module SplotFrameworkExtraModule.
 * 
 * Define any options you would like to use in the module.
 */
return array(

    'ajax' => array(
        'enable' => true,
        'json' => array(
            'enable' => true,
            'http_code_key' => '__code'
        ),
        'controller_access' => array(
            'enable' => true
        )
    ),

    'databridge' => array(
        'enable' => true
    ),

    'imaging' => array(
        'enable' => true
    ),

    'filestorage' => array(
        'simple' => array(
            'enable' => true,
            'web' => true,
            'dir' => 'filestorage/'
        )
    ),

    'form' => array(
        'simple_handler' => array(
            'enable' => true
        )
    ),

    'router' => array(
        'use_request' => true
    ),

    'mailer' => array(
        'enable' => true,
        'use_worker' => false,
        'from' => 'no-reply@localhost.dev',
        'force_to' => null,
        'bcc' => null,
        'transport' => 'mail',
        'sendmail_cmd' => '/usr/sbin/exim -bs',
        'smtp_host' => null,
        'smtp_port' => 25,
        'smtp_encrypt' => null,
        'smtp_username' => null,
        'smtp_password' => null
    )

);