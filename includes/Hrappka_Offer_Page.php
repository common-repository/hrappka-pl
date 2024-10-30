<?php

/**
 * Class HrappkaOfferPage
 */
class Hrappka_Offer_Page
{
     protected $plugin_slug;

     protected static $instance;

     protected $templates;

     public static function get_instance()
     {
          if (null == self::$instance) {
               self::$instance = new Hrappka_Offer_Page();
          }

          return self::$instance;
     }

     private function __construct()
     {
          $this->templates = [];

          if (version_compare(floatval(get_bloginfo('version')), '4.7', '<')) {
               add_filter(
                    'page_attributes_dropdown_pages_args',
                    [$this, 'register_project_templates']
               );
          } else {
               add_filter(
                    'theme_page_templates', [$this, 'add_new_template']
               );
          }

          add_filter(
               'wp_insert_post_data',
               [$this, 'register_project_templates']
          );

          add_filter(
               'template_include',
               [$this, 'view_project_template']
          );

          $this->templates = [
               'hrappka-offer-view.php' => 'HRappka.pl',
          ];

     }

     public function add_new_template($posts_templates)
     {
          $posts_templates = array_merge($posts_templates, $this->templates);
          return $posts_templates;
     }

     public function register_project_templates($atts)
     {


          $cache_key = 'page_templates-' . md5(get_theme_root() . '/' . get_stylesheet());

          $templates = wp_get_theme()->get_page_templates();
          if (empty($templates)) {
               $templates = [];
          }

          wp_cache_delete($cache_key, 'themes');

          $templates = array_merge($templates, $this->templates);

          wp_cache_add($cache_key, $templates, 'themes', 1800);

          return $atts;

     }

     public function view_project_template($template)
     {

          // Get global post
          global $post;

          // Return template if post is empty
          if (!$post) {
               return $template;
          }

          // Return default template if we don't have a custom one defined
          if (!isset($this->templates[get_post_meta(
                    $post->ID, '_wp_page_template', true
               )])) {
               return $template;
          }

          $file = plugin_dir_path(__FILE__) . get_post_meta(
                    $post->ID, '_wp_page_template', true
               );

          // Just to be safe, we check if the file exist first
          if (file_exists($file)) {
               return $file;
          } else {
               echo $file;
          }

          // Return template
          return $template;

     }

     public static function get_offer_by_rec_id($rec_id)
     {
          global $wpdb;
          $offer_table_name = $wpdb->prefix . Hrappka_Offer_List::OFFERS_TABLE_NAME;

          return $wpdb->get_results(
               $wpdb->prepare(
                    "SELECT * FROM {$offer_table_name} WHERE rec_id = %s", $rec_id
               )
          );

     }

     public static function getTranslatedOfferInfo($lang, $key, $text)
     {
          $result = '';

          switch (Hrappka_Offer_List_Admin::$langs[$lang]) {
               case Hrappka_Offer_List_Admin::PL:
                    $result = isset($text->{$key}->{Hrappka_Offer_List_Admin::PL}) ? $text->{$key}->{Hrappka_Offer_List_Admin::PL} : '';
                    break;
               case Hrappka_Offer_List_Admin::EN:
                    $result = isset($text->{$key}->{Hrappka_Offer_List_Admin::EN}) ? $text->{$key}->{Hrappka_Offer_List_Admin::EN} : '';
                    break;
               case Hrappka_Offer_List_Admin::DE:
                    $result = isset($text->{$key}->{Hrappka_Offer_List_Admin::DE}) ? $text->{$key}->{Hrappka_Offer_List_Admin::DE} : '';
                    break;
               case Hrappka_Offer_List_Admin::RU:
                    $result = isset($text->{$key}->{Hrappka_Offer_List_Admin::RU}) ? $text->{$key}->{Hrappka_Offer_List_Admin::RU} : '';
                    break;
               case Hrappka_Offer_List_Admin::UK:
                    $result = isset($text->{$key}->{Hrappka_Offer_List_Admin::UK}) ? $text->{$key}->{Hrappka_Offer_List_Admin::UK} : '';
                    break;
               default:
                    $result = '';
                    break;
          }

          return $result;

     }

}
