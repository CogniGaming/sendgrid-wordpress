<?php

require_once plugin_dir_path( __FILE__ ) . 'class-sendgrid-tools.php';

class Sendgrid_NLVX
{
  const NLVX_API_URL = 'https://api.sendgrid.com/v3/marketing';

  /**
   * Returns the appropriate header value of authorization depending on the available credentials.
   *
   * @return  mixed   string of the header value if successful, false otherwise.
   */
  protected static function get_auth_header_value()
  {
    if ( 'false' == Sendgrid_Tools::get_mc_opt_use_transactional() ) {
      $mc_api_key = Sendgrid_Tools::get_mc_api_key();

      if ( false != $mc_api_key ) {
        return 'Bearer ' . $mc_api_key;
      }
    }

    $api_key = Sendgrid_Tools::get_api_key();
    if ( false == $api_key ) {
      return false;
    }

    return 'Bearer ' . $api_key;
  }

  /**
   * Returns the contact lists from SendGrid
   *
   * @return  mixed   an array of lists if the request is successful, false otherwise.
   */
  public static function get_all_lists()
  {
    $auth = Sendgrid_NLVX::get_auth_header_value();

    if ( false == $auth ) {
      return false;
    }

    $args = array(
        'headers' => array(
          'Authorization' => $auth
        ),
        'decompress' => false,
        'timeout' => Sendgrid_Tools::get_request_timeout()
    );

    $url = Sendgrid_NLVX::NLVX_API_URL . '/lists';

    $response = wp_remote_get( $url, $args );

	  
    if ( ! is_array( $response ) or ! isset( $response['body'] ) ) {
      return false;
    }

    $lists_response = json_decode($response['body'], true);
	  		
    if ( isset( $lists_response['result'] ) ) {
      return $lists_response['result'];
    }

    return false;
  }

  /**
   * Adds a recipient in the SendGrid MC contact db
   *
   * @param   string $email          The email of the recipient
   * @param   string $first_name     The first name of the recipient
   * @param   string $last_name      The last name of the recipient
   * @param   array  $custom_fields  Custom field data
   *
   * @return  mixed   The recipient ID if successful, false otherwise.
   */
  public static function add_recipient($email, $list_id, $first_name = '', $last_name = '', array $custom_fields = array())
  {
    $auth = Sendgrid_NLVX::get_auth_header_value();

    if ( false == $auth ) {
      return false;
    }

    $args = array(
        'headers' => array(
          'Authorization' => $auth,
    	  'Content-Type'   => 'application/json'
        ),
        'decompress' => false,
        'timeout' => Sendgrid_Tools::get_request_timeout()
    );

    $url = Sendgrid_NLVX::NLVX_API_URL . '/contacts';

    $contact = array('email' => $email);

    if ( '' != $first_name ) {
      $contact['first_name'] = $first_name;
    }

    if ( '' != $last_name ) {
      $contact['last_name'] = $last_name;
    }

    foreach ( $custom_fields as $field => $value ) {
      $contact[$field] = $value;
    }
    
	$data = array("list_ids" => array($list_id), "contacts" => array($contact));
	  
    $args['body'] = json_encode($data);
	$args['method'] = 'PUT';

    $response = wp_remote_post( $url, $args );

    if ( ! is_array( $response ) or ! isset( $response['body'] ) ) {
      return false;
    }

    $recipient_response = json_decode($response['body'], true);
    if ( isset( $recipient_response['error_count'] ) and 0 != $recipient_response['error_count'] ) {
      return false;
    }

    if ( ! isset( $recipient_response['job_id'] ) ) {
      return false;
    }

    return $recipient_response['job_id'];
  }

  /**
   * Adds a recipient in the SendGrid MC contact db and adds it to the list
   *
   * @param   string $email          The email of the recipient
   * @param   string $first_name     The first name of the recipient
   * @param   string $last_name      The last name of the recipient
   *
   * @return  bool   True if successful, false otherwise.
   */
  public static function create_and_add_recipient_to_list($email, $first_name = '', $last_name = '')
  {
    $list_id = Sendgrid_Tools::get_mc_list_id();
	  
    if ( false == $list_id ) {
      return false;
    }

    $recipient_id = Sendgrid_NLVX::add_recipient($email, $list_id, $first_name, $last_name);
	  	  
    if ( false == $recipient_id ) {
      return false;
    }

    return true;
  }

/**
   * Removes a recipient from the specified list
   *
   * @param   string $recipient_id      the ID of the recipient.
   * @param   string $list_id           the ID of the list, defaults to the default list
   *
   * @return  bool   True if successful, false otherwise.
   */
  public static function delete_recipient_from_list($recipient_id, $list_id = null)
  {
    $auth = self::get_auth_header_value();

    if ( false == $auth ) {
      return false;
    }

    if ( ! $list_id ) {
      $list_id = Sendgrid_Tools::get_mc_list_id();
    }
    if ( false == $list_id ) {
      return false;
    }

    $args = array(
      'method' => 'DELETE',
      'headers' => array(
        'Authorization' => $auth
      ),
      'decompress' => false,
      'timeout' => Sendgrid_Tools::get_request_timeout()
    );

    $url = Sendgrid_NLVX::NLVX_API_URL . '/lists/'. $list_id . '/recipients/' . $recipient_id;

    $response = wp_remote_post( $url, $args );

    if ( isset( $response['response']['code'] ) && 204 == $response['response']['code'] ) {
      return true;
    }

    return false;
  }

  /**
   * Removes a recipient from the SendGrid MC contact DB
   *
   * @param   string $recipient_id      the ID of the recipient.
   *
   * @return  bool   True if successful, false otherwise.
   */
  public static function delete_recipient($recipient_id)
  {
    $auth = self::get_auth_header_value();

    if ( false == $auth ) {
      return false;
    }

    $args = array(
      'method' => 'DELETE',
      'headers' => array(
        'Authorization' => $auth
      ),
      'decompress' => false,
      'timeout' => Sendgrid_Tools::get_request_timeout()
    );

    $url = Sendgrid_NLVX::NLVX_API_URL . '/contacts?ids='.trim($recipient_id,'"');

    $response = wp_remote_post( $url, $args );

    if ( isset( $response['response']['code'] ) && 204 == $response['response']['code'] ) {
      return true;
    }

    return false;
  }
}