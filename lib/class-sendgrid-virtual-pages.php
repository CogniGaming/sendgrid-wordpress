<?php

require_once plugin_dir_path( __FILE__ ) . 'class-sendgrid-tools.php';

if ( ! class_exists( 'SGVirtualPage' ) )
{
  class SGVirtualPage
  {
    private $slug = NULL;
    private $title = NULL;
    private $content = NULL;
    private $author = NULL;
    private $date = NULL;
    private $type = NULL;

    public function __construct( $args )
    {
      if ( ! isset( $args['slug'] ) ) {
        throw new Exception( 'No slug given for virtual page' );
      }

      $this->slug     = $args['slug'];
      $this->title    = isset( $args['title'] ) ? $args['title'] : '';
      $this->content  = isset( $args['content'] ) ? $args['content'] : '';
      $this->author   = isset( $args['author'] ) ? $args['author'] : 1;
      $this->date     = isset( $args['date'] ) ? $args['date'] : current_time( 'mysql' );
      $this->dategmt  = isset( $args['date'] ) ? $args['date'] : current_time( 'mysql', 1 );
      $this->type     = isset( $args['type'] ) ? $args['type'] : 'page';

      add_filter( 'the_posts', array( $this, 'virtualPage' ) );
    }

    /**
     * Filter to create virtual page content
     *
     * @param   mixed  $posts  posts saved in wp
     * @return  mixed  $posts  posts saved in wp
     */
    public function virtualPage( $posts )
    {
		
      global $wp, $wp_query;

      $post = new stdClass;

      $post->ID                     = -1;
      $post->post_author            = $this->author;
      $post->post_date              = $this->date;
      $post->post_date_gmt          = $this->dategmt;
      $post->post_content           = $this->content;
      $post->post_title             = $this->title;
      $post->post_excerpt           = '';
      $post->post_status            = 'publish';
      $post->comment_status         = 'closed';
      $post->ping_status            = 'closed';
      $post->post_password          = '';
      $post->post_name              = $this->slug;
      $post->to_ping                = '';
      $post->pinged                 = '';
      $post->modified               = $post->post_date;
      $post->modified_gmt           = $post->post_date_gmt;
      $post->post_content_filtered  = '';
      $post->post_parent            = 0;
      $post->guid                   = get_home_url('/' . $this->slug);
      $post->menu_order             = 0;
      $post->post_type              = $this->type;
      $post->post_mime_type         = '';
      $post->comment_count          = 0;

      $posts = array( $post );
		
      // reset wp_query properties to simulate a found page
      $wp_query->is_page     = true;
      $wp_query->is_singular = true;
      $wp_query->is_home     = false;
      $wp_query->is_archive  = false;
      $wp_query->is_category = false;
      unset( $wp_query->query['error'] );
      $wp_query->query_vars['error'] = '';
      $wp_query->is_404              = false;
		
      return ( $posts );
    }
  }
}

/**
 * Filter to create general error page
 *
 * @return  void
 */
function sg_create_subscribe_general_error_page()
{
  $url = basename( $_SERVER['REQUEST_URI'] );

  if ( $url == 'emails-error' )
  {
    $args = array('slug' => 'emails-error',
              'title' => 'Subscription Error',
              'content' => '<h1>Subscription Error</h1><p>Something went wrong while trying to send information.</p>' );
    $pg = new SGVirtualPage( $args );
  }
}

/**
 * Filter to create invalid token error page
 *
 * @return  void
 */
function sg_create_subscribe_invalid_token_error_page()
{
  $url = basename( $_SERVER['REQUEST_URI'] );

  if ( $url == 'emails-invalid-token' )
  {
    $args = array( 'slug' => 'emails-invalid-token',
              'title' => 'Subscription Error - Invalid Token',
              'content' => '<h1>Subscription Error - Invalid Token</h1><p>Token is invalid, you are not subscribed to our newsletter.</p>' );
    $pg = new SGVirtualPage( $args );
  }
}

/**
 * Filter to create missing token error page
 *
 * @return  void
 */
function sg_create_subscribe_missing_token_error_page()
{
  $url = basename( $_SERVER['REQUEST_URI'] );

  if ( $url == 'emails-missing-token' )
  {
    $args = array( 'slug' => 'emails-missing-token',
              'title' => 'Subscription Error',
              'content' => '<h1>Token Missing</h1><p>Token is missing, you are not subscribed to our newsletter.</p>' );
    $pg = new SGVirtualPage( $args );
  }
}

/**
 * Filter to create subscribe success page
 *
 * @return  void
 */
function sg_create_subscribe_success_page()
{
  $url = basename( $_SERVER['REQUEST_URI'] );

  if ( $url == 'emails-subscribed' )
  {
    $args = array( 'slug' => 'emails-subscribed',
          'title' => 'Subscribed to ' . get_option( 'blogname' ),
          'content' => '<h1>Success</h1><p>You have been successfully subscribed to our newsletter.</p>' );
    $pg = new SGVirtualPage( $args );
  }
}

/**
 * Filter to create subscribe success page
 *
 * @return  void
 */
function sg_create_validation_required_page()
{
  $url = basename( $_SERVER['REQUEST_URI'] );

  if ( $url == 'email-validation-required' )
  {
	$content = stripslashes( Sendgrid_Tools::get_mc_form_subscribe_message() );
    $args = array( 'slug' => 'email-validation-required',
          'title' => 'Subscribed to ' . get_option( 'blogname' ),
          'content' => '<h1>Success</h1><p>' . $content . '</p>' );
    $pg = new SGVirtualPage( $args );
  }
}