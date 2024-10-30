<?php

/**
 * Class Hrappka_Offer_List_Admin
 */

global $post;

class Hrappka_Offer_List_Admin
{
     const OPTIONS_GROUP = 'hrappka-offer-list-settings';
     const COMPANY_HASH = 'hrappka_company_hash';
     const OFFERS_PER_PAGE = 'offer_per_page';
     const CUSTOM_CSS = 'hrappka_custom_css';
     const WIDGET_NAME = 'hrappka_widget_name';
     const OFFER_PAGE = 'hrappka_widget_offer_page';
     const DATE_FORMAT = 'Y-m-d';

     const VISIBLE_SETTINGS = 'hrappka_visible_settings';
     const VIEW_SETTINGS_CLIENT = 'hrappka_view_settings_client';
     const VIEW_SETTINGS_BRUTTO = 'hrappka_view_settings_brutto';
     const VIEW_SETTINGS_CONTRACT_TYPE = 'hrappka_view_settings_contract_type';
     const VIEW_SETTINGS_PUBLICATION_DATE = 'hrappka_view_settings_publication_date';
     const VIEW_SETTINGS_DESCRIPTION = 'hrappka_view_settings_description';
     const VIEW_SETTINGS_REQUIREMENTS = 'hrappka_view_settings_requirements';
     const VIEW_SETTINGS_IMG = 'hrappka_view_settings_img';
     const VIEW_SETTINGS_OFFER = 'hrappka_view_settings_offer';
     const VIEW_SETTINGS_DATE_START_RECRUITMENT = 'hrappka_view_settings_date_start_recruitment';
     const VIEW_SETTINGS_DATE_END_RECRUITMENT = 'hrappka_view_settings_date_end_recruitment';
     const VIEW_SETTINGS_DATE_START_EMPLOY = 'hrappka_view_settings_date_start_employ';
     const VIEW_SETTINGS_DATE_END_EMPLOY = 'hrappka_view_settings_date_end_employ';
     const VIEW_SETTINGS_QUANTITY = 'hrappka_view_settings_quantity';
     const VIEW_SETTINGS_WORK_HOURS = 'hrappka_view_settings_work_hours';

     const PL = 'pl';
     const EN = 'en';
     const UK = 'uk';
     const RU = 'ru';
     const DE = 'de';

     public static $langs = [
          'pl_PL' => 'pl',
          'en_US' => 'en',
          'en_GB' => 'en',
          'ru_RU' => 'ru',
          'de_DE' => 'de',
          'uk' => 'uk',
     ];

     const RECRUITMENT_STATE_OPEN = 'OPEN';
     const RECRUITMENT_STATE_PLANNED = 'PLANNED';
     const RECRUITMENT_STATE_CONST = 'CONST';
     const RECRUITMENT_STATE_COMPLETED = 'COMPLETED';

     public static $recruitmentActiveStatuses = [
          self::RECRUITMENT_STATE_CONST,
          self::RECRUITMENT_STATE_OPEN,
     ];

     const DEFAULT_HOST = 'https://app.hrappka.pl';
     const HOST = 'hrappka_host';

     public static $offerSidebarSettings = [
          self::VIEW_SETTINGS_CLIENT,
          self::VIEW_SETTINGS_BRUTTO,
          self::VIEW_SETTINGS_CONTRACT_TYPE,
          self::VIEW_SETTINGS_PUBLICATION_DATE,

     ];

     public static $mainOfferData = [
          'rec_description' => Hrappka_Offer_List_Admin::VIEW_SETTINGS_DESCRIPTION,
          'rec_requirements' => Hrappka_Offer_List_Admin::VIEW_SETTINGS_REQUIREMENTS,
          'rec_offer' => Hrappka_Offer_List_Admin::VIEW_SETTINGS_OFFER,
     ];

     public static $mainOfferTitles = [
          'rec_description' => 'Opis stanowiska',
          'rec_requirements' => 'Wymagania',
          'rec_offer' => 'Oferujemy',
     ];

     public static $defaultOrder = [
          'rec_description' => 0,
          'rec_offer' => 2,
          'rec_requirements' => 1,
     ];


     public function __construct()
     {
          // init actions
          add_action('admin_menu', [$this, 'add_plugin_options_page']);
          add_action('admin_enqueue_scripts', [$this, 'load_admin_styles']);
          add_action('admin_init', [$this, 'update_hrappka_settings']);
     }

     /*
      * register plugin options
      */
     public function update_hrappka_settings()
     {
          register_setting(static::OPTIONS_GROUP, static::COMPANY_HASH);
          register_setting(static::OPTIONS_GROUP, static::OFFER_PAGE);
          register_setting(static::OPTIONS_GROUP, static::HOST);
          register_setting(static::OPTIONS_GROUP, static::VISIBLE_SETTINGS);
          register_setting(static::OPTIONS_GROUP, static::CUSTOM_CSS);
          add_action('admin_post_update_hrappka_offers_and_widget_settings', [$this, 'update_hrappka_offers_and_widget_settings']);
     }

     public function update_hrappka_offers_and_widget_settings()
     {
          $modelOffers = new Hrappka_Offer_List_Cron();
          $modelOffers->get_offer_list_by_company_hash();
          $modelWidget = new Hrappka_Offer_List_Cron();
          $modelWidget->get_widget_settings_by_hash();
          wp_redirect(wp_get_referer());
     }

     public function add_plugin_options_page()
     {
          add_menu_page(
               __('HRappka.pl', 'hrappka-offer-list'),
               __('HRappka.pl', 'hrappka-offer-list'),
               'manage_options',
               'HrappkaOfferList',
               [$this, 'options_page'],
               plugin_dir_url(__DIR__) . 'admin/icons/logo-hrappka.png'
          );
     }

     public function options_page()
     {

          $visibleSettings = get_option(static::VISIBLE_SETTINGS);
          $modelWidget = new Hrappka_ModelsWidget();
          $widgets = $modelWidget->getWidgetsNameAndHash();

          ?>
          <?php include(__DIR__ . '/hrappka-offer-list-admin-template.phtml'); ?>
          <?php
     }

     public static function host()
     {
          $host = get_option(static::HOST);
          if (!empty($host)) {
               return $host;
          }

          return static::DEFAULT_HOST;
     }

     function load_admin_styles()
     {
          // JS
          wp_register_script('hrappak_admin_bootstrap', plugin_dir_url(__DIR__) . '/public/js/bootstrap-js/bootstrap.js');
          // CSS
          wp_register_style('hrappak_admin_bootstrap', plugin_dir_url(__DIR__) . '/public/css/bootstrap-css/bootstrap.css');
          wp_register_style('hrappak_admin_style', plugin_dir_url(__DIR__) . '/admin/css/admin-css.css');

          //JS);
          wp_enqueue_script('hrappak_admin_bootstrap');
          //CSS
          wp_enqueue_style('hrappak_admin_bootstrap');
          wp_enqueue_style('hrappak_admin_style');


     }


     public static function define_distance_function()
     {

          return
          "CREATE FUNCTION hrappka_distance_function(lat1 float,lon1 float,lat2 float,lon2 float ) 
          RETURNS FLOAT 
          BEGIN 
          RETURN ACOS(SIN(RADIANS(lat1))*SIN(RADIANS(lat2))+COS(RADIANS(lat1))*COS(RADIANS(lat2))*COS(RADIANS(lon2-lon1)))*6371;
          END;";
     }

}
