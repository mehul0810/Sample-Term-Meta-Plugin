<?php
/*
 *	Plugin Name: Color Term Meta Plugin
 *	Version: 1.0
 *	Author: Mehul Gohil
 *	Author URI: http://mehulgohil.in/
 *  Text Domain: color-term-meta
 *  License:     GPL-2.0+
 *  License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 *  Domain Path: /languages
 */


/*
 *	Register Term Meta
 */

add_action( 'init', 'mg_register_meta' );

function mg_register_meta() {

    register_meta( 'term', 'color', 'mg_sanitize_hex' );
}


/*
 *	Sanitize Hex Color Code
 */

function mg_sanitize_hex( $color ) {

    $color = ltrim( $color, '#' );

    return preg_match( '/([A-Fa-f0-9]{3}){1,2}$/', $color ) ? $color : '';
}

/*
 *	Add Form Fields to a specific taxonomy
 */

add_action( 'category_add_form_fields', 'mg_new_term_color_field' );

function mg_new_term_color_field() {

    wp_nonce_field( basename( __FILE__ ), 'mg_term_color_nonce' ); ?>

    <div class="form-field jt-term-color-wrap">
        <label for="mg-term-color"><?php _e( 'Color', 'mg' ); ?></label>
        <input type="text" name="mg_term_color" value="" class="color-picker" data-default-color="#FFFFFF" />
    </div>
<?php }


/*
 *	Edit Form Fields to a specific taxonomy
 */

add_action( 'category_edit_form_fields', 'mg_edit_term_color_field' );

function mg_edit_term_color_field( $term ) {

    $default = '#FFFFFF';
    $color   = mg_get_term_color( $term->term_id, true );

    if ( ! $color )
        $color = $default; ?>

    <tr class="form-field mg-term-color-wrap">
        <th scope="row"><label for="mg-term-color"><?php _e( 'Color', 'mg' ); ?></label></th>
        <td>
            <?php wp_nonce_field( basename( __FILE__ ), 'mg_term_color_nonce' ); ?>
            <input type="text" name="mg_term_color" value="<?php echo esc_attr( $color ); ?>" class="color-picker" data-default-color="<?php echo esc_attr( $default ); ?>" />
        </td>
    </tr>
<?php }

/*
 *	Saving Term Meta on creating and editing terms
 */

add_action( 'edit_category',   'mg_save_term_color' );
add_action( 'create_category', 'mg_save_term_color' );

function mg_save_term_color( $term_id ) {

    if ( ! isset( $_POST['mg_term_color_nonce'] ) || ! wp_verify_nonce( $_POST['mg_term_color_nonce'], basename( __FILE__ ) ) )
        return;

    $old_color = mg_get_term_color( $term_id );
    $new_color = isset( $_POST['mg_term_color'] ) ? mg_sanitize_hex( $_POST['mg_term_color'] ) : '';

    if ( $old_color && '' === $new_color )
        delete_term_meta( $term_id, 'color' );

    else if ( $old_color !== $new_color )
        update_term_meta( $term_id, 'color', $new_color );
}


/*
 *	Display Color of particular term in column.
 */

add_filter( 'manage_edit-category_columns', 'mg_edit_term_columns' );

function mg_edit_term_columns( $columns ) {

    $columns['color'] = __( 'Color', 'mg' );

    return $columns;
}

/*
 *	Display Color of particular term in column.
 */
 
add_filter( 'manage_category_custom_column', 'mg_manage_term_custom_column', 10, 3 );

function mg_manage_term_custom_column( $out, $column, $term_id ) {

    if ( 'color' === $column ) {

        $color = mg_get_term_color( $term_id, true );

        if ( ! $color )
            $color = '#ffffff';

        $out = sprintf( '<span class="color-block" style="background:%s;">&nbsp;</span>', esc_attr( $color ) );
    }

    return $out;
}

/*
 *  Fetch Color Term Meta and sanitize it to confirm
 */
function mg_get_term_color( $term_id, $hash = false ) {

    $color = get_term_meta( $term_id, 'color', true );
    $color = mg_sanitize_hex( $color );

    return $hash && $color ? "#{$color}" : $color;
}

/*
 *	Add Color Picker Script
 */
 
add_action( 'admin_enqueue_scripts', 'mg_admin_enqueue_scripts_callback' );

function mg_admin_enqueue_scripts_callback( $hook_suffix ) {

	# Calls a script only on category page
    if ( ( 'edit-tags.php' !== $hook_suffix && 'term.php' !== $hook_suffix ) || 'category' !== get_current_screen()->taxonomy )
        return;

	# Load Color Picker Script
    wp_enqueue_style( 'wp-color-picker' );
    wp_enqueue_script( 'wp-color-picker' );

    add_action( 'admin_head',   'mg_term_colors_print_styles' );
    add_action( 'admin_footer', 'mg_term_colors_print_scripts' );
}

function mg_term_colors_print_styles() { ?>

    <style type="text/css">
        .column-color { width: 50px; }
        .column-color .color-block { display: inline-block; width: 28px; height: 28px; border: 1px solid #ddd; border-radius: 50%; }
    </style>
<?php }

function mg_term_colors_print_scripts() { ?>

    <script type="text/javascript">
        jQuery( document ).ready( function( $ ) {
            $( '.color-picker' ).wpColorPicker();
        } );
    </script>
<?php }

?>