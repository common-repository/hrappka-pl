<?php

/*
Plugin Name: HRappka.pl
Plugin URI: http://hrappka.pl/landing/wordpress-plugin
Description: HRappka.pl umożliwia automatyczne tworzenie listy ofert pracy oraz podstron ze szczegółami oferty i linkiem aplikacyjnym. Do poprawnego działania wtyczki wymagane jest posiadanie konta w systemie HRappka.pl.
Version: 1.1
Author: HRappka.pl
Text Domain: hrappka-offer-list
Domain Path: /languages
*/

require_once(__DIR__ . '/includes/Hrappka_Offer_List_Admin.php');
require_once(__DIR__ . '/includes/Hrappka_Offer_List_Cron.php');
require_once(__DIR__ . '/includes/Hrappka_Offer_Page.php');
require_once(__DIR__ . '/includes/models/Hrappka_Models_Offers.php');
require_once(__DIR__ . '/includes/models/Hrappka_Models_Widget.php');
require_once(__DIR__ . '/includes/services/Hrappka_Services_Utility.php');
global $wpdb;


if (!class_exists('Hrappka_Offer_List')) {
     class Hrappka_Offer_List extends WP_Widget
     {
          protected $filters = [];
          protected $table_name;

          // init widget and register action/hooks/filters
          public function __construct()
          {

               parent::__construct(
                    'hrappka_offer_list',
                    __('HRappka.pl', 'text_domain'),
                    [
                         'customize_selective_refresh' => true,
                    ]
               );

               add_shortcode('hrappka_offers_list', [$this, 'widget']);

               $this->register_actions_filters_hooks();

               global $wpdb;
               $this->table_name = $wpdb->prefix . Hrappka_ModelsOffers::OFFERS_TABLE_NAME;

          }


          // register to wordpress all my action
          public function register_actions_filters_hooks()
          {

               add_action('wp_enqueue_scripts', [$this, 'load_styles']);

               add_action('plugins_loaded', function () {
                    load_plugin_textdomain('hrappka-offer-list', false, basename(dirname(__FILE__)) . '/languages/');
               });

               add_action('widgets_init', function () {
                    register_widget('Hrappka_Offer_List');
               });


               add_action('plugins_loaded', ['Hrappka_Offer_Page', 'get_instance']);

               register_activation_hook(__FILE__, [$this, 'when_plugin_activate']);
               register_deactivation_hook(__FILE__, [$this, 'when_plugin_deactivate']);
               register_uninstall_hook(__FILE__, [static::class, 'when_plugin_uninstall']);


          }

          // form for widget instance settings
          public function form($instance)
          {
               $widget_hash = !empty($instance['hrappka_offer_list_widget_hash']) ? $instance['hrappka_offer_list_widget_hash'] : '';
               $modelWidget = new Hrappka_ModelsWidget();
               $widgets = $modelWidget->getWidgetsNameAndHash();

               ?>
               <div class="form-group row" style="margin-top: 10px">
                    <label for="<?php echo esc_attr($this->get_field_id('hrappka_offer_list_widget_hash')); ?>" class="control-label col-md-12 col-xs-12">
                         <?php esc_attr_e('Widget:', 'hrappka-offer-list'); ?>
                    </label>

                    <div class="col-md-12 col-xs-12">
                         <select id="<?php echo esc_attr($this->get_field_id('hrappka_offer_list_widget_hash')); ?>" class="form-control" name="<?php echo esc_attr($this->get_field_name('hrappka_offer_list_widget_hash')); ?>">
                              <option value="" selected disabled><?php _e('Wybierz ustawienia widget-u') ?></option>
                              <?php foreach ($widgets as $value): ?>

                                   <option value="<?php echo $value->crw_hash ?>" <?php echo $value->crw_hash == $widget_hash ? 'selected' : '' ?>>
                                        <?php echo $value->crw_name ?>
                                   </option>

                              <?php endforeach; ?>
                         </select>
                    </div>
               </div>
               <?php
          }

          // handle update widget
          public function update($new_instance, $old_instance)
          {
               $instance = [];
               $instance['hrappka_offer_list_widget_hash'] = (!empty($new_instance['hrappka_offer_list_widget_hash'])) ? sanitize_text_field($new_instance['hrappka_offer_list_widget_hash']) : '';

               return $instance;
          }

          // define widget
          public function widget($args, $instance)
          {

               extract($args);

               if (empty(get_option(Hrappka_Offer_List_Admin::COMPANY_HASH))) {
                    _e("Nie podano hash-u firmy", 'hrappka-offer-list');
                    return;
               }

               $widgetHash = null;

               if (isset($instance['hrappka_offer_list_widget_hash'])) {
                    $widgetHash = $instance['hrappka_offer_list_widget_hash'];
               }

               if (empty($widgetHash)) {
                    if (isset($hash)) {
                         $widgetHash = $hash;
                    } else {
                         _e("Nie podano hash-u plugina", 'hrappka-offer-list');
                         return;
                    }
               }

               $modelWidget = new Hrappka_ModelsWidget();
               $modelOffers = new Hrappka_ModelsOffers();

               $cmpHash = get_option(Hrappka_Offer_List_Admin::COMPANY_HASH);

               $offerPage = get_option(Hrappka_Offer_List_Admin::OFFER_PAGE);

               // settings for widget
               $widget_settings = $modelWidget->getSettingsForWidgetByHash($widgetHash);

               if (empty($widget_settings)) {
                    _e("Brak ustawień dla widget-u", 'hrappka-offer-list');
                    return;
               }


               $view_settings = $widget_settings->crw_view_configs->fields;

               //handle post from widget search
               $this->handle_post();

               // get offers and number of page
               $data = $modelOffers->getOffers($widget_settings->crw_pagination_per_page, $this->filters, $widget_settings->crw_search->tags);
               $offers = $data['offers'];
               $total_pages = $data['total_after'];

               if (isset($is_sc)) {
                    ob_start();
               }

               ?>

               <?php $template = include(__DIR__ . '/includes/hrappka-offer-list-template.phtml'); ?>

               <?php

               if (isset($is_sc)) {
                    return ob_get_clean();
               }


          }

          public function handle_post()
          {
               $filters = [];
               if (isset($_GET['filters'])) {
                    $filters = $_GET['filters'];
               }

               $this->filters = [
                    'search' => isset($filters['search']) ? sanitize_text_field($filters['search']) : '',
                    'locality' => [
                         'value' => isset($filters['locality']['value']) ? sanitize_text_field($filters['locality']['value']) : '',
                         'lat' => isset($filters['locality']['lat']) && is_numeric($filters['locality']['lat']) ? (float)$filters['locality']['lat'] : '',
                         'lng' => isset($filters['locality']['lng']) && is_numeric($filters['locality']['lng']) ? (float)$filters['locality']['lng'] : '',
                         'viewport' => isset($filters['locality']['viewport']) ? sanitize_text_field($filters['locality']['viewport']) : '',
                         'country' => isset($filters['locality']['country']) ? sanitize_text_field($filters['locality']['country']) : '',
                    ],
                    'page' => isset($filters['page']) && is_numeric($filters['page']) ? (int)$filters['page'] : 1,
               ];

               remove_query_arg('filters');
          }

          public function when_plugin_activate()
          {

               global $wpdb;

               $modelWidget = new Hrappka_ModelsWidget();
               $modelOffers = new Hrappka_ModelsOffers();

               $sql = $modelOffers->table_definition();
               require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
               dbDelta($sql);

               $sql = $modelWidget->table_definition();
               dbDelta($sql);

               $sql = Hrappka_Offer_List_Admin::define_distance_function();
               $wpdb->query("DROP FUNCTION IF EXISTS hrappka_distance_function;");
               mysqli_multi_query($wpdb->dbh, $sql);


               $hrappka_cron = new Hrappka_Offer_List_Cron();
               $hrappka_cron->get_offer_list_by_company_hash();
               $hrappka_cron->activate();

          }

          public function when_plugin_deactivate()
          {
               $hrappka_cron = new Hrappka_Offer_List_Cron();
               $hrappka_cron->deactivate();
          }

          public static function when_plugin_uninstall()
          {
               global $wpdb;

               $modelWidget = new Hrappka_ModelsWidget();
               $modelOffers = new Hrappka_ModelsOffers();

               $sql = "DROP TABLE IF EXISTS {$modelOffers->table_name}";
               $wpdb->query($sql);

               $sql = "DROP TABLE IF EXISTS {$modelWidget->table_name}";
               $wpdb->query($sql);

          }

          public function load_styles()
          {

               // JS
               wp_enqueue_script('jquery');
               wp_register_script('hrappka_widget_bootstrap', plugin_dir_url(__FILE__) . '/public/js/bootstrap-js/bootstrap.js');
               wp_register_script('hrappka_widget_application', plugin_dir_url(__FILE__) . '/public/js/application.js');
               // CSS
               wp_register_style('hrappka_widget_bootstrap', plugin_dir_url(__FILE__) . '/public/css/bootstrap-css/bootstrap.css');
               wp_register_style('hrappka_widget_application', plugin_dir_url(__FILE__) . '/public/css/application.css');
               wp_register_style('hrappka_widget_font_awesome', plugin_dir_url(__FILE__) . '/public/font-awesome/css/font-awesome.css');

               //JS
               wp_enqueue_script('hrappka_widget_jquery');
               wp_enqueue_script('hrappka_widget_bootstrap');
               wp_enqueue_script('hrappka_widget_application');


               //CSS
               wp_enqueue_style('hrappka_widget_bootstrap');
               wp_enqueue_style('hrappka_widget_application');
               wp_enqueue_style('hrappka_widget_font_awesome');

          }

          public function removeDB()
          {
               global $wpdb;
               $modelOffers = new Hrappka_ModelsOffers();
               $modelWidget = new Hrappka_ModelsWidget();

               $sql = "DROP TABLE IF EXISTS {$modelOffers->table_name}";
               $wpdb->query($sql);

               $sql = "DROP TABLE IF EXISTS {$modelWidget->table_name}";
               $wpdb->query($sql);

               $sql = $modelOffers->table_definition();
               require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
               dbDelta($sql);

               $sql = $modelWidget->table_definition();
               require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
               dbDelta($sql);
          }


     }

     new Hrappka_Offer_List();
}

$hrappka_offer_list_admin_class = new Hrappka_Offer_List_Admin();
//$hrappka_offer_list_class = new Hrappka_Offer_List();
