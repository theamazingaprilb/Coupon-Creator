<?php

function yourslug_coupons_posttype() {
  register_post_type( 'coupons',
    array(
      'labels' => array(
        'name' => __( 'Coupons' ),
        'singular_name' => __( 'Coupons' ),
        'add_new' => __( 'Add New Coupon' ),
        'add_new_item' => __( 'Add New Coupon' ),
        'edit_item' => __( 'Edit Coupon' ),
        'new_item' => __( 'Add New Coupon' ),
        'view_item' => __( 'View Coupon' ),
        'search_items' => __( 'Search Coupon' ),
        'not_found' => __( 'No Coupon found' ),
        'not_found_in_trash' => __( 'No Coupons found in trash' )
      ),
      'public' => true,
      'supports' => array( 'title' ),
      'capability_type' => 'post',
      'rewrite' => array("slug" => "coupons"), // Permalinks format
      'menu_position' => 5,
      'register_meta_box_cb' => 'add_coupons_metaboxes'
    )
  );
}

add_action( 'init', 'yourslug_coupons_posttype' );

add_action( 'init', 'remove_custom_post_comment' );

function remove_custom_post_comment() {
    remove_post_type_support( 'coupons', 'comments' );
}


// Add the Coupons Meta Boxes

function add_coupons_metaboxes() {
  add_meta_box('yourslug_coupons_meta', 'Offer Details', 'yourslug_coupons_meta', 'coupons', 'normal', 'high');
}

// The Coupons Metabox

function yourslug_coupons_meta() {
  global $post;

  // Noncename needed to verify where the data originated
  echo '<input type="hidden" name="couponsmeta_noncename" id="couponsmeta_noncename" value="' .
  wp_create_nonce( plugin_basename(__FILE__) ) . '" />';

  // Get the data if its already been entered
  $headline = get_post_meta($post->ID, '_headline', true);
  $dealvalue = get_post_meta($post->ID, '_dealvalue', true);
  $terms = get_post_meta($post->ID, '_terms', true);
  $id = get_the_ID();
  echo '<h3>Headline</h3><input type="text" name="_headline" value="' . $headline  . '" class="widefat" />';
  echo '<h3>Value</h3><input type="text" name="_dealvalue" value="' . $dealvalue . '" class="widefat" />';
  echo '<h3>Terms</h3><input type="text" name="_terms" value="' . $terms . '" class="widefat" />';
  echo '<br /><br /><h3>Single Coupon Shortcode:</h3>[pkccoupons id="' . $id . '"]';
  echo '<br /><br /><h3>All Coupons Shortcode:</h3>[pkccoupons]';

}

// Save the Metabox Data

function wpt_save_coupons_meta($post_id, $post) {

  // verify this came from the our screen and with proper authorization,
  // because save_post can be triggered at other times
  if ( !wp_verify_nonce( $_POST['couponsmeta_noncename'], plugin_basename(__FILE__) )) {
  return $post->ID;
  }

  // Is the user allowed to edit the post or page?
  if ( !current_user_can( 'edit_post', $post->ID ))
    return $post->ID;


  $coupons_meta['_headline'] = $_POST['_headline'];
  $coupons_meta['_dealvalue'] = $_POST['_dealvalue'];
  $coupons_meta['_terms'] = $_POST['_terms'];


  foreach ($coupons_meta as $key => $value) { // Cycle through the $events_meta array!
    if( $post->post_type == 'revision' ) return; // Don't store custom data twice
    $value = implode(',', (array)$value); // If $value is an array, make it a CSV (unlikely)
    if(get_post_meta($post->ID, $key, FALSE)) { // If the custom field already has a value
      update_post_meta($post->ID, $key, $value);
    } else { // If the custom field doesn't have a value
      add_post_meta($post->ID, $key, $value);
    }
    if(!$value) delete_post_meta($post->ID, $key); // Delete if blank
  }

}

add_action('save_post', 'yourslug_save_coupons_meta', 1, 2); // save the custom fields

//Shortcode Support
add_shortcode( 'yourslugcoupons', 'yourslug_coupons_shortcode' );
function yourslug_coupons_shortcode( $atts ) {
    ob_start();
    $query = new WP_Query( array(
        'post_type' => 'coupons',
        'posts_per_page' => -1,
        'order' => 'ASC',
        'orderby' => 'title',
    ) );
    if ( $query->have_posts() ) { ?>
      <div id="yourslug-coupon">
        <?php while ( $query->have_posts() ) : $query->the_post(); ?>
          <div class="yourslug-coupon">
            <h3 class="coupon">
              <?php $headline = get_post_meta(get_the_ID(), '_headline', true);
              print_r($headline); ?>
            </h3>
            <div class="value">
              <p>
                <?php $dealvalue = get_post_meta(get_the_ID(), '_dealvalue', true);
                print_r($dealvalue); ?>
              </p>
            </div>
            <div class="terms">
              <p>
                <?php $terms = get_post_meta(get_the_ID(), '_terms', true);
                print_r($terms); ?>
              </p>
            </div>
          </div>
            <?php endwhile;
            wp_reset_postdata(); ?>
          </div>

    <?php $myvariable = ob_get_clean();
    return $myvariable;
    }
}
