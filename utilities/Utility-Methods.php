<?php

class Solicitar_Utility_Methods
{

  /**
   * The ID of this plugin.
   *
   * @since    1.0.0
   * @access   private
   * @var      string    $plugin_title    The ID of this plugin.
   */
  private $plugin_title;

  /**
   * The version of this plugin.
   *
   * @since    1.0.0
   * @access   private
   * @var      string    $version    The current version of this plugin.
   */
  private $version;

  /**
   * Query memo.
   *
   * @since    1.0.0
   * @access   private
   * @var      string    $non_kyc_products    A memo for a query that gets re-run.
   */

  /**
   * Initialize the class and set its properties.
   *
   * @since    1.0.0
   * @param      string    $plugin_title       The name of the plugin.
   * @param      string    $version    The version of this plugin.
   */
  public function __construct($plugin_title, $version)
  {

    $this->plugin_title = $plugin_title;
    $this->version = $version;
  }

  

}