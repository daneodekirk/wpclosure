<?php

    /*
    Plugin Name: WP Closure  
    Plugin URI: http://github.com/daneodekirk/wpclosure
    Description: Uses Google Closure to minify and deliver Javascript.
    Version: 0.1
    Author: Dane Odekirk
    */

if ( !class_exists( "WPClosure" ) ) 
{

    class WPClosure
    {
        var $debug;
        
        var $scripts;
        var $options;
        var $closure;

        var $urls       = array();

        const COMPILER   = 'http://closure-compiler.appspot.com/compile';
        const SECONDS_IN_FIVE_MINUTES = 300;

        function WPClosure() 
        {

            if ( is_admin() ) {

                add_action('admin_menu', array( &$this , 'create_closure_admin_options' ) );

            }

            add_action( 'init' , array( &$this , 'initialize' ) );

        }

        function initialize ( ) 
        {

            $this->options = get_option( 'wpclosure' );

            $this->set_debug();

            if ( $this->debug ) 
            {
                echo '<pre>';
                print_r( $this->options );
                echo '</pre>';
            }

            $this->intercept_scripts();
             
            if ( $this->is_to_soon() ) 
            {
                return false;
            }

            $this->get_urls();

            $this->remove_scripts();

            $this->compile_scripts();

            $this->save_options();

            $this->enqueue_scripts();

        }
        function set_debug() 
        {
            $this->debug = ( $this->options[ 'debug_mode' ] != '' );
        }

        function intercept_scripts ( ) 
        {

            global $wp_scripts;

            $this->scripts = $wp_scripts->queue;

        }

        function remove_scripts ( ) 
        {

            foreach ( $this->scripts as $script ) 
            {
                
                wp_deregister_script( $script );

            }

        }

        function enqueue_scripts() 
        {
            $name = $this->options[ 'script' ];

            $url = plugins_url( 'cache/' . $name , __FILE__ );

            wp_register_script( 'closure.script' , $url );
            wp_enqueue_script( 'closure.script' , $url );

        }

        function reenqueue_scripts() 
        {

            foreach ( $this->scripts as $script ) 
            {
                
                wp_register_script( $script );

            }

        }

        function compile_scripts ( )
        {

            $this->closure = array(
                'code_url'          => $this->urls,
                'compilation_level' => $this->options[ 'compilation_level' ],
                'output_format'     => 'json',
                'output_info'       => 'compiled_code',
                'output_file_name'  => true
            );

            $params = array(
                'method' => 'POST',
                'timeout' => 15,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking' => true,
                'headers' => array(),
                'body' => $this->create_post_data(),
                'cookies' => array()
            );

            $response = wp_remote_post( self::COMPILER , $params );

            if( is_wp_error( $response ) ) {

               $this->reenqueue_scripts();
               
            } else {

                $this->save_to_cache( json_decode( $response[ 'body' ] ) );

            }

        }

        function save_to_cache( $code ) 
        {

            if ( $this->debug ) 
            {
                echo '<pre>';
                print_r( $code );
                echo '</pre>';
            }

            if ( $code->serverErrors ) 
            {
                return false;
            }


            $directory = dirname(__FILE__) . '/cache/';

            $name = str_replace( '/' , '-' , $code->outputFilePath ) . '.js';

            $contents = $code->compiledCode;

            file_put_contents( $directory . $name , $contents );

            $this->options[ 'script' ]  = $name;
            $this->options[ 'updated' ] = time();

        }

        function get_urls()
        {
            global $wp_scripts;

            foreach ($this->scripts as $script)
            {
                $url = $wp_scripts->registered[ $script ]->src;
                array_push( $this->urls ,  get_bloginfo( 'url' ) . $url );

            }

        }

        function create_post_data () 
        {

            foreach ($this->closure as $key=>$value)
            {
                if ( $key == 'code_url' ) {

                    foreach ($value as $code_url)
                    {
                        $body .= $key . '=' . $code_url . '&';
                    }
                    continue;
                }

                $body .= $key . '=' . $value . '&';

            };

            return substr($body, 0, -1);

        }

        function is_to_soon() 
        {
            //if ( $this->debug ) {
            //    echo 'Time since update: ' . ( time() - $this->options[ 'updated' ] );
            //}
            return ( time() - $this->options[ 'updated' ] < self::SECONDS_IN_FIVE_MINUTES ); 
        }

        function save_options() 
        {
            update_option( 'wpclosure' , $this->options ); 
        }

        function create_closure_admin_options()
        {
            
            add_options_page('Closure', 
                             'Closure', 
                             10,
                             'wpclosure',
                             array( &$this , 'wpclosure_options') );

        }

        function wpclosure_options()
        {

            include 'closure.options.php';

        }

    }

    new WPClosure;

}

?>
