<?php
/**
 * Main functions.
 *
 * @package WHEREGO
 */

/**
 * Main function to generate the list of followed posts
 *
 * @since 2.0.0
 *
 * @param string|array $args Parameters in a query string format or array.
 * @return string HTML formatted list of related posts.
 */
function get_wherego( $args = array() ) {
	global $post, $wherego_settings;

	$defaults = array(
		'is_widget'    => false,
		'is_shortcode' => false,
		'is_manual'    => false,
		'echo'         => true,
		'heading'      => true,
	);
	$defaults = array_merge( $defaults, wherego_settings_defaults(), $wherego_settings );

	// Parse incomming $args into an array and merge it with $defaults.
	$args = wp_parse_args( $args, $defaults );

	$exclude_categories = array_map( 'intval', explode( ',', $args['exclude_categories'] ) );       // Extract categories to exclude.

	// If post_types is empty or contains a query string then use parse_str else consider it comma-separated.
	if ( ! empty( $args['post_types'] ) && false === strpos( $args['post_types'], '=' ) ) {
		$post_types = explode( ',', $args['post_types'] );
	} else {
		parse_str( $args['post_types'], $post_types );
	}

	$results = get_post_meta( $post->ID, 'wheredidtheycomefrom', true );    // Extract posts list from the meta field.

	if ( $results ) {
		$results = array_diff( $results, array_map( 'intval', explode( ',', $args['exclude_post_ids'] ) ) );
	}

	$widget_class    = $args['is_widget'] ? 'wherego_related_widget' : 'wherego_related ';
	$shortcode_class = $args['is_shortcode'] ? 'wherego_related_shortcode ' : '';

	$post_classes = $widget_class . $shortcode_class;

	/**
	 * Filter the classes added to the div wrapper of the Contextual Related Posts.
	 *
	 * @since   2.0.0
	 *
	 * @param   string   $post_classes  Post classes string.
	 */
	$post_classes = apply_filters( 'wherego_post_class', $post_classes );

	$output = '<div class="' . $post_classes . '">';

	if ( $results ) {
		$loop_counter = 0;

		$output .= wherego_heading_title( $args );

		$output .= wherego_before_list( $args );

		foreach ( $results as $result ) {

			if ( 0 === (int) $result ) {
				break;
			}

			$result = get_post( $result );

			if ( ! $result || ! in_array( $result->post_type, $post_types, true ) ) {
				break; // If this is not from our select post types, end loop.
			}

			$p_in_c = false;    // Variable to check if post exists in a particular category.

			$cats = get_the_category( $result->ID );    // Fetch categories of the plugin.

			foreach ( $cats as $cat ) { // Loop to check if post exists in excluded category.
				$p_in_c = ( in_array( $cat->cat_ID, $exclude_categories, true ) ) ? true : false;
				if ( $p_in_c ) {
					break;  // End loop if post found in category.
				}
			}

			if ( ! $p_in_c ) {
				$output .= wherego_before_list_item( $args, $result );

				$output .= wherego_list_link( $args, $result );

				if ( isset( $args['show_excerpt'] ) && $args['show_excerpt'] ) {
					$output .= '<span class="wherego_excerpt"> ' . wherego_excerpt( $result->ID, $args['excerpt_length'] ) . '</span>';
				}

				$output .= wherego_after_list_item( $args, $result );

				$loop_counter++;
			}
			if ( $loop_counter === (int) $args['limit'] ) {
				break;  // End loop when related posts limit is reached.
			}
		} // End foreach().
		if ( isset( $args['show_credit'] ) && $args['show_credit'] ) {
			$output .= wherego_before_list_item( $args, $result );

			/* translators: %s: Plugin URL. */
			$output .= sprintf( __( 'Powered by <a href="%s" rel="nofollow">Where did they go from here</a>', 'where-did-they-go-from-here' ), esc_url( 'https://ajaydsouza.com/wordpress/plugins/where-did-they-go-from-here/' ) );

			$output .= wherego_after_list_item( $args, $result );

		}
		$output .= wherego_after_list( $args );

	} else {
		$output .= ( $args['blank_output'] ) ? ' ' : '<p>' . $args['blank_output_text'] . '</p>';
	}// End if().

	// Check if the opening list tag is missing in the output, it means all of our results were eliminated cause of the category filter.
	if ( false === ( strpos( $output, $args['before_list_item'] ) ) ) {
		$output  = '<div class="wherego_related">';
		$output .= ( $args['blank_output'] ) ? ' ' : '<p>' . $args['blank_output_text'] . '</p>';
	}

	$output .= '</div>'; // Closing div of 'wherego_related'.

	/**
	 * Filter the output
	 *
	 * @since   2.0.0
	 *
	 * @param   string  $output Formatted list of followed posts
	 * @param   array   $args   Complete set of arguments
	 */
	return apply_filters( 'get_wherego', $output, $args );
}


/**
 * Header function.
 *
 * @since 1.6
 */
function wherego_header() {
	global $wherego_settings;

	$wherego_custom_css = '<style type="text/css">' . stripslashes( $wherego_settings['custom_CSS'] ) . '</style>';

	// Add CSS to header.
	if ( '' !== $wherego_custom_css ) {
		if ( ( is_single() ) ) {
			echo $wherego_custom_css; // WPCS: XSS OK.
		} elseif ( ( is_page() ) ) {
			echo $wherego_custom_css; // WPCS: XSS OK.
		} elseif ( ( is_home() ) && ! empty( $wherego_settings['add_to']['home'] ) ) {
			echo $wherego_custom_css; // WPCS: XSS OK.
		} elseif ( ( is_category() ) && ! empty( $wherego_settings['add_to']['category_archives'] ) ) {
			echo $wherego_custom_css; // WPCS: XSS OK.
		} elseif ( ( is_tag() ) && ! empty( $wherego_settings['add_to']['tag_archives'] ) ) {
			echo $wherego_custom_css; // WPCS: XSS OK.
		} elseif ( ( ( is_tax() ) || ( is_author() ) || ( is_date() ) ) && ! empty( $wherego_settings['add_to']['archives'] ) ) {
			echo $wherego_custom_css; // WPCS: XSS OK.
		} elseif ( is_active_widget( false, false, 'Widgetwherego', true ) ) {
			echo $wherego_custom_css; // WPCS: XSS OK.
		}
	}
}
add_action( 'wp_head', 'wherego_header' );


/**
 * Filter for 'the_content' to add the related posts.
 *
 * @since 1.0
 * @param string $content Post content to be filtered.
 * @return string Filtered post content.
 */
function wherego_content( $content ) {

	global $post, $wherego_id, $wherego_settings;
	$wherego_id = intval( $post->ID );

	$exclude_on_post_ids = explode( ',', $wherego_settings['exclude_on_post_ids'] );

	// Exit if the post is in the exclusion list.
	if ( in_array( $post->ID, $exclude_on_post_ids, true ) ) {
		return $content;
	}

	if ( ( is_single() ) && ! empty( $wherego_settings['add_to']['content'] ) ) {
		return $content . get_wherego( 'is_widget=0' );
	} elseif ( ( is_page() ) && ! empty( $wherego_settings['add_to']['page'] ) ) {
		return $content . get_wherego( 'is_widget=0' );
	} elseif ( ( is_home() ) && ! empty( $wherego_settings['add_to']['home'] ) ) {
		return $content . get_wherego( 'is_widget=0' );
	} elseif ( ( is_category() ) && ! empty( $wherego_settings['add_to']['category_archives'] ) ) {
		return $content . get_wherego( 'is_widget=0' );
	} elseif ( ( is_tag() ) && ! empty( $wherego_settings['add_to']['tag_archives'] ) ) {
		return $content . get_wherego( 'is_widget=0' );
	} elseif ( ( ( is_tax() ) || ( is_author() ) || ( is_date() ) ) && ! empty( $wherego_settings['add_to']['archives'] ) ) {
		return $content . get_wherego( 'is_widget=0' );
	} else {
		return $content;
	}
}
add_filter( 'the_content', 'wherego_content' );


/**
 * Filter to add related posts to feeds.
 *
 * @since 1.6
 *
 * @param string $content Feed content.
 * @return string
 */
function wherego_rss( $content ) {

	$show_excerpt_feed  = wherego_get_option( 'show_excerpt_feed' );
	$limit_feed         = wherego_get_option( 'limit_feed' );
	$post_thumb_op_feed = wherego_get_option( 'post_thumb_op_feed' );
	$add_to             = wherego_get_option( 'add_to' );

	if ( ! empty( $add_to['feed'] ) ) {
		return $content . get_wherego( 'is_widget=0&limit=' . $limit_feed . '&show_excerpt=' . $show_excerpt_feed . '&post_thumb_op=' . $post_thumb_op_feed );
	} else {
		return $content;
	}
}
add_filter( 'the_excerpt_rss', 'wherego_rss' );
add_filter( 'the_content_feed', 'wherego_rss' );


/**
 * Manual install.
 *
 * @since 1.0
 *
 * @param array $args Settings array.
 */
function echo_wherego( $args = array() ) {

	$defaults = array(
		'is_manual' => true,
	);

	// Parse incomming $args into an array and merge it with $defaults.
	$args = wp_parse_args( $args, $defaults );

	echo get_wherego( $args ); // WPCS: XSS OK.
}


