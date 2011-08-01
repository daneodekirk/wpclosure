<?php

//[TODO] make validators or use external function

if ( isset( $_POST[ 'submit_wpclosure_options' ] ) ) 
{

    $options = get_option( 'wpclosure' );


    if ( isset( $_POST['debug_mode'] ) ) {
        $_POST['debug_mode'] = 'checked';
    } else {
        $_POST['debug_mode'] = '';
    }

    $options[ 'debug_mode' ]        = $_POST[ 'debug_mode' ];
    $options[ 'compilation_level' ] = $_POST[ 'compilation_level' ];

    update_option( 'wpclosure' , $options ); 


}

?>


<div class="wrap">

    <h2> WP Closure Options </h2>

    <form method="post" name="wpclosure_options" target="_self"> 

        <?php $options = get_option( 'wpclosure' ); ?>

        <table class="form-table">

            <tr valign="top"><th scope="row">Debug Mode</th>

                <td>

                    <input name="debug_mode" type="checkbox" <?php echo $options[ 'debug_mode' ]; ?> />

                </td>

            </tr>

            <tr valign="top"><th scope="row">Compilation Level</th>

                <td>

            <select name="compilation_level">

                <option <?php echo $options[ 'compilation_level' ] == 'WHITESPACE_ONLY' ? 'selected' : '' ?> value="WHITESPACE_ONLY">
                    Whitespace Only
                </option>

                <option <?php echo $options[ 'compilation_level' ] == 'SIMPLE_OPTIMIZATIONS' ? 'selected' : '' ?> value="SIMPLE_OPTIMIZATIONS">
                    Simple Optimizations
                </option>

            </select>

                </td>

            </tr>

        </table>

        <p class="submit">

            <input type="submit" class="button-primary" name="submit_wpclosure_options" value="<?php _e('Save Changes'); ?>" />

        </p>

    </form>

</div>

