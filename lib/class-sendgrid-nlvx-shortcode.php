<?php
// disable direct access
if (!defined('ABSPATH')) {
  exit;
}

require_once plugin_dir_path(__FILE__) . 'class-sendgrid-tools.php';
require_once plugin_dir_path(__FILE__) . 'class-sendgrid-nlvx.php';
require_once plugin_dir_path(__FILE__) . '../vendor/punycode/Punycode.php';

use SendGridTrueBV\Punycode;

class SendGrid_NLVX_Shortcode {
  const INVALID_EMAIL_ERROR = 'email_invalid';
  const SUCCESS_EMAIL_SEND = 'email_sent';
  const ERROR_EMAIL_SEND = 'email_error_send';
  const INVALID_FNAME_ERROR = 'firstname_invalid';
  const INVALID_SNAME_ERROR = 'surname_invalid';

  function __construct() {
    add_shortcode('sendgrid-form', array($this, 'sendgrid_shortcode'));
    add_shortcode('sendgrid-form-small', array($this, 'sendgrid_shortcode_small'));
  }

  /**
   * Method called to render the front-end of the shortcode
   *
   * @param   mixed   $sg_atts   the shortcode instance arguements
   *
   * @return  string
   */
  public function sendgrid_shortcode_small($sg_atts) {
    // attributes
    $sg_atts = shortcode_atts(array(
      'title'            => '',
      'error_text'       => '',
      'error_email_text' => '',
      'success_text'     => ''

    ), $sg_atts);

    $form_html = '';

    $title = stripslashes(Sendgrid_Tools::get_mc_form_title());
    if (!empty($sg_atts['title'])) {
      $title = apply_filters('widget_title', $sg_atts['title']);
    }

    $error_text = stripslashes(Sendgrid_Tools::get_mc_form_error_message());
    if (!empty($sg_atts['error_text'])) {
      $error_text = apply_filters('widget_text', $sg_atts['error_text']);
    }

    $error_email_text = stripslashes(Sendgrid_Tools::get_mc_form_email_error_message());
    if (!empty($sg_atts['error_email_text'])) {
      $error_email_text = apply_filters('widget_text', $sg_atts['error_email_text']);
    }

    $success_text = stripslashes(Sendgrid_Tools::get_mc_form_subscribe_message());
    if (!empty($sg_atts['success_text'])) {
      $success_text = apply_filters('widget_text', $sg_atts['success_text']);
    }

    // Form was submitted
    if (isset($_POST['sendgrid_mc_email'])) {
      $process_form_reponse = $this->process_subscription();
      if (self::SUCCESS_EMAIL_SEND == $process_form_reponse) {
        wp_redirect("email-validation-required");
        exit;

        // $form_html .=  '<p class="sendgrid_widget_text sendgrid_widget_success"> ' . $success_text . ' </p>';
      } elseif (self::INVALID_EMAIL_ERROR == $process_form_reponse) {
        $form_html .= $this->display_form_small($title);
        $form_html .= '<p class="sendgrid_widget_text sendgrid_widget_error"> ' . $error_email_text . ' </p>';
      } else {
        $form_html .= $this->display_form_small($title);
        $form_html .= '<p class="sendgrid_widget_text sendgrid_widget_error"> ' . $error_text . ' </p>';
      }
    } else {
      // Display form
      $form_html .= $this->display_form_small($title);
    }

    return $form_html;
  }


  /**
   * Method called to render the front-end of the shortcode
   *
   * @param   mixed   $sg_atts   the shortcode instance arguements
   *
   * @return  string
   */
  public function sendgrid_shortcode($sg_atts) {
    // attributes
    $sg_atts = shortcode_atts(array(
      'class'            => '',
      'title'            => '',
      'message'          => '',
      'error_text'       => '',
      'error_email_text' => '',
      'error_fname_text' => '',
      'error_sname_text' => '',
      'success_text'     => ''

    ), $sg_atts);

    $form_html = '';

    $title = stripslashes(Sendgrid_Tools::get_mc_form_title());
    if (!empty($sg_atts['title'])) {
      $title = apply_filters('widget_title', $sg_atts['title']);
    }

    $text = stripslashes(Sendgrid_Tools::get_mc_form_message());
    if (!empty($sg_atts['message'])) {
      $text = apply_filters('widget_text', $sg_atts['message']);
    }

    $error_text = stripslashes(Sendgrid_Tools::get_mc_form_error_message());
    if (!empty($sg_atts['error_text'])) {
      $error_text = apply_filters('widget_text', $sg_atts['error_text']);
    }

    $error_email_text = stripslashes(Sendgrid_Tools::get_mc_form_email_error_message());
    if (!empty($sg_atts['error_email_text'])) {
      $error_email_text = apply_filters('widget_text', $sg_atts['error_email_text']);
    }

    $error_fname_text = stripslashes(Sendgrid_Tools::get_mc_form_fname_error_message());
    if (!empty($sg_atts['error_fname_text'])) {
      $error_fname_text = apply_filters('widget_text', $sg_atts['error_fname_text']);
    }

    $error_sname_text = stripslashes(Sendgrid_Tools::get_mc_form_sname_error_message());
    if (!empty($sg_atts['error_sname_text'])) {
      $error_sname_text = apply_filters('widget_text', $sg_atts['error_sname_text']);
    }

    $success_text = stripslashes(Sendgrid_Tools::get_mc_form_subscribe_message());
    if (!empty($sg_atts['success_text'])) {
      $success_text = apply_filters('widget_text', $sg_atts['success_text']);
    }

    // Theme style
    $form_html .= '<div id="sendgrid_nlvx_shortcode" class="widget_sendgrid_nlvx_shortcode ' . $sg_atts['class'] . '">';

    if (!empty($title)) {
      $form_html .= '<h3>' . $title . '</h3>';
    }

    // Form was submitted
    if (isset($_POST['sendgrid_mc_email'])) {
      $process_form_reponse = $this->process_subscription();
      if (self::SUCCESS_EMAIL_SEND == $process_form_reponse) {
        $form_html .= '<p class="sendgrid_widget_text sendgrid_widget_success"> ' . $success_text . ' </p>';
      } elseif (self::INVALID_EMAIL_ERROR == $process_form_reponse) {
        if (!empty($text)) {
          $form_html .= '<p>' . $text . '</p>';
        }
        $form_html .= '<p class="sendgrid_widget_text sendgrid_widget_error"> ' . $error_email_text . ' </p>';
        $form_html .= $this->display_form();
      } elseif (self::INVALID_FNAME_ERROR == $process_form_reponse) {
        if (!empty($text)) {
          $form_html .= '<p>' . $text . '</p>';
        }
        $form_html .= '<p class="sendgrid_widget_text sendgrid_widget_error"> ' . $error_fname_text . ' </p>';
        $form_html .= $this->display_form();
      } elseif (self::INVALID_SNAME_ERROR == $process_form_reponse) {
        if (!empty($text)) {
          $form_html .= '<p>' . $text . '</p>';
        }
        $form_html .= '<p class="sendgrid_widget_text sendgrid_widget_error"> ' . $error_sname_text . ' </p>';
        $form_html .= $this->display_form();
      } else {
        if (!empty($text)) {
          $form_html .= '<p>' . $text . '</p>';
        }
        $form_html .= '<p class="sendgrid_widget_text sendgrid_widget_error"> ' . $error_text . ' </p>';
        $form_html .= $this->display_form();
      }
    } else {
      // Display form
      if (!empty($text)) {
        $form_html .= '<p>' . $text . '</p>';
      }

      $form_html .= $this->display_form();
    }
    $form_html .= '</div>';

    return $form_html;
  }

  /**
   * Method that processes the subscription params
   *
   * @return  void
   */
  private function process_subscription() {
    $email_split = explode("@", htmlspecialchars($_POST['sendgrid_mc_email'], ENT_QUOTES, 'UTF-8'));

    if (isset($email_split[1])) {
      $email_domain = $email_split[1];

      try {
        $Punycode = new Punycode();
        $email_domain = $Punycode->decode($email_split[1]);
      } catch (Exception $e) {
      }

      $email = $email_split[0] . '@' . $email_domain;
    } else {
      $email = htmlspecialchars($_POST['sendgrid_mc_email'], ENT_QUOTES, 'UTF-8');
    }

    // Bad call
    if (!isset($email) or !Sendgrid_Tools::is_valid_email($email)) {
      return self::INVALID_EMAIL_ERROR;
    }

    if ('true' == Sendgrid_Tools::get_mc_opt_req_fname_lname() and 'true' == Sendgrid_Tools::get_mc_opt_incl_fname_lname()) {
      if (isset($_POST['sendgrid_mc_first_name']) and empty($_POST['sendgrid_mc_first_name'])) {
        return self::INVALID_FNAME_ERROR;
      }
      if (isset($_POST['sendgrid_mc_last_name']) and empty($_POST['sendgrid_mc_last_name'])) {
        return self::INVALID_SNAME_ERROR;
      }
    }

    if (isset($_POST['sendgrid_mc_first_name']) and isset($_POST['sendgrid_mc_last_name'])) {
      Sendgrid_OptIn_API_Endpoint::send_confirmation_email(
        $email,
        htmlspecialchars($_POST['sendgrid_mc_first_name'], ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($_POST['sendgrid_mc_last_name'], ENT_QUOTES, 'UTF-8')
      );
    } else {
      Sendgrid_OptIn_API_Endpoint::send_confirmation_email($email);
    }

    return self::SUCCESS_EMAIL_SEND;
  }

  /**
   * Method that displays the subscription form
   *
   * @return  void
   */
  private function display_form() {
    $email_label = stripslashes(Sendgrid_Tools::get_mc_email_label());
    if (false == $email_label) {
      $email_label = Sendgrid_Settings::DEFAULT_EMAIL_LABEL;
    }

    $first_name_label = stripslashes(Sendgrid_Tools::get_mc_first_name_label());
    if (false == $first_name_label) {
      $first_name_label = Sendgrid_Settings::DEFAULT_FIRST_NAME_LABEL;
    }

    $last_name_label = stripslashes(Sendgrid_Tools::get_mc_last_name_label());
    if (false == $last_name_label) {
      $last_name_label = Sendgrid_Settings::DEFAULT_LAST_NAME_LABEL;
    }

    $subscribe_label = stripslashes(Sendgrid_Tools::get_mc_subscribe_label());
    if (false == $subscribe_label) {
      $subscribe_label = Sendgrid_Settings::DEFAULT_SUBSCRIBE_LABEL;
    }

    $require_fname_lname = '';

    $form_html = '';

    $form_html .= '<form method="post" id="sendgrid_mc_email_form" class="mc_email_form">';

    if ('true' == Sendgrid_Tools::get_mc_opt_incl_fname_lname()) {
      if ('true' == Sendgrid_Tools::get_mc_opt_req_fname_lname()) {
        $require_fname_lname = " (Required)";
        $first_name_label .= $require_fname_lname;
        $last_name_label .= $require_fname_lname;
      }

      $fname = '';
      $sname = '';

      if (isset($_POST['sendgrid_mc_first_name']) and !empty($_POST['sendgrid_mc_first_name'])) {
        $fname = $_POST['sendgrid_mc_first_name'];
      }

      if (isset($_POST['sendgrid_mc_last_name']) and !empty($_POST['sendgrid_mc_last_name'])) {
        $sname = $_POST['sendgrid_mc_last_name'];
      }

      $form_html .= '<div class="sendgrid_mc_fields">';
      $form_html .= '  <div class="sendgrid_mc_input_div">';
      $form_html .= '    <input class="sendgrid_mc_input sendgrid_mc_input_first_name" id="sendgrid_mc_first_name" name="sendgrid_mc_first_name" type="text" title="' . $first_name_label . '" placeholder="' . $first_name_label . '" value=""' . $fname . ' />';
      $form_html .= '  </div>';
      $form_html .= '</div>';
      $form_html .= '<div class="sendgrid_mc_fields">';
      $form_html .= '  <div class="sendgrid_mc_input_div">';
      $form_html .= '    <input class="sendgrid_mc_input sendgrid_mc_input_last_name" id="sendgrid_mc_last_name" name="sendgrid_mc_last_name" type="text" title="' . $last_name_label . '" placeholder="' . $last_name_label . '" value="" ' . $sname . '/>';
      $form_html .= '  </div>';
      $form_html .= '</div>';
    }

    $email = '';
    if (isset($_POST['sendgrid_mc_email']) and !empty($_POST['sendgrid_mc_email'])) {
      $email = $_POST['sendgrid_mc_email'];
    }

    $form_html .= '<div class="sendgrid_mc_fields">';
    $form_html .= '  <div class="sendgrid_mc_input_div">';
    $form_html .= '    <input class="sendgrid_mc_input sendgrid_mc_input_email" id="sendgrid_mc_email" name="sendgrid_mc_email" type="text" title="' . $email_label . '" placeholder="' . $email_label . '" value="' . $email . '" required/>';
    $form_html .= '  </div>';
    $form_html .= '</div>';

    $form_html .= '<div class="sendgrid_mc_button_div">';
    $form_html .= '  <input class="sendgrid_mc_button" type="submit" id="sendgrid_mc_email_submit" value="' . $subscribe_label . '" />';
    $form_html .= '</div>';
    $form_html .= '</form>';

    return $form_html;
  }


  /**
   * Method that displays the subscription form
   *
   * @return  void
   */
  private function display_form_small($title) {
    $email_label = stripslashes(Sendgrid_Tools::get_mc_email_label());
    if (false == $email_label) {
      $email_label = Sendgrid_Settings::DEFAULT_EMAIL_LABEL;
    }

    $subscribe_label = stripslashes(Sendgrid_Tools::get_mc_subscribe_label());
    if (false == $subscribe_label) {
      $subscribe_label = Sendgrid_Settings::DEFAULT_SUBSCRIBE_LABEL;
    }

    $form_html = '<form method="post" id="sendgrid_mc_email_form" class="newsletter-form">';

    $email = '';
    if (isset($_POST['sendgrid_mc_email']) and !empty($_POST['sendgrid_mc_email'])) {
      $email = $_POST['sendgrid_mc_email'];
    }

    $form_html .= '  <input id="sendgrid_mc_email" name="sendgrid_mc_email" type="text" placeholder="' . $title . '" title="' . $email_label . '" value="' . $email . '" required/>';
    $form_html .= '  <input type="submit" id="sendgrid_mc_email_submit" title="' . $subscribe_label . '" value="' . $subscribe_label . '" />';
    $form_html .= '</form>';

    return $form_html;
  }
}


$sgShortcode = new SendGrid_NLVX_Shortcode();