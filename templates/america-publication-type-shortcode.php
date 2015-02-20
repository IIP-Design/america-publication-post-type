<?php

/**
* Produces the linked post custom Publication type taxonomy terms list.
*
* Supported shortcode attributes are:
*   after (output after link, default is empty string),
*   before (output before link, default is 'Tagged With: '),
*   sep (separator string between tags, default is ', '),
*   taxonomy (name of the taxonomy, default is 'category').
*
* Output passes through 'genesis_post_terms_shortcode' filter before returning.
*
* @since 1.6.0
*
* @param array|string $atts Shortcode attributes. Empty string if no attributes.
* @return string|boolean Shortcode output or false on failure to retrieve terms
*/
function america_publication_type_shortcode( $atts ) {
   
   $defaults = array(
    'after'    => '',
    'before'   => __( 'Type: ', 'genesis' ),
    'sep'      => ', ',
    'taxonomy' => 'publication-type',
  );

  $atts = shortcode_atts( $defaults, $atts, 'publication_type' );

  $types = get_the_term_list( get_the_ID(), $atts['taxonomy'], $atts['before'], trim( $atts['sep'] ) . ' ', $atts['after'] );

  if ( is_wp_error( $types ) )
    return;

  if ( empty( $types ) )
    return;

  if ( genesis_html5() )   
    $output = sprintf( '<span %s>', genesis_attr( 'entry-publication-type' ) ) . $types . '</span>';
  else
    $output = '<span class="entry-publication-type">' . $terms . '</span>';

  return apply_filters( 'genesis_post_terms_shortcode', $output, $types, $atts );

}
