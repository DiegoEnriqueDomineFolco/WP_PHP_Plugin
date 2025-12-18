<?php

/**
* This class stores the database configuration options. It is not instanced.
*
* NOTE: IMPORTANT! THIS FILE SHOULD BE INCLUDED IN YOUR .gitignore FILE.
*       Do not expose your database configuration on your repository.
*/

class Solicitar_Producto_Config {

    /**
    * Set the DB config values.
    *
    * @since     1.0.0
    */
    public static function set_config() {

        // Local values:
        $config = array(

            'host' => 'localhost',
            'user' => 'root',
            'password' => 'root',
            'db_name' => 'local'
        );

        // // // Live values:
        // // $config = array(

        // //     'host' => 'localhost',
        // //     'user' => 'root',
        // //     'password' => 'root',
        // //     'db_name' => 'local'
        // // );

        return $config;
    }
}
