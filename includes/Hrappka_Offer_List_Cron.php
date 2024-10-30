<?php

/**
 * Class Hrappka_Offer_List_Cron
 */


class Hrappka_Offer_List_Cron
{

     public function __construct()
     {

          // cron callbacks
          add_action('hrappka_offer_list_cron_hook', [$this, 'get_offer_list_by_company_hash']);
          add_action('hrappka_widget_settings_cron_hook', [$this, 'get_widget_settings_by_hash']);

     }

     public function activate()
     {
          if (wp_next_scheduled('hrappka_offer_list_cron_hook') === false) {
               wp_schedule_event(
                    current_time('timestamp'),
                    'daily',
                    'hrappka_offer_list_cron_hook'
               );
          }


          if (wp_next_scheduled('hrappka_widget_settings_cron_hook') === false) {
               wp_schedule_event(
                    current_time('timestamp'),
                    'daily',
                    'hrappka_widget_settings_cron_hook'
               );
          }
     }

     public function deactivate()
     {
          wp_clear_scheduled_hook('hrappka_offer_list_cron_hook');
     }

     public function get_widget_settings_by_hash()
     {
          $hash = get_option(Hrappka_Offer_List_Admin::COMPANY_HASH);

          if (empty($hash)) {
               return;
          }

          $host = Hrappka_Offer_List_Admin::host();

          $response = wp_remote_get("$host/widget/widget-show-settings/?hash[]=$hash");

          if (is_wp_error($response)) {
               return;
          }

          $new_settings = json_decode($response['body'], true);

          if (empty($new_settings)) {
               return;
          }

          $modelWidget = new Hrappka_ModelsWidget();
          $modelWidget->save($new_settings);
     }

     public function get_offer_list_by_company_hash()
     {

          $company_hash = get_option(Hrappka_Offer_List_Admin::COMPANY_HASH);

          if (empty($company_hash)) {
               return;
          }

          $host = Hrappka_Offer_List_Admin::host();
          $response = wp_remote_get("$host/offer/list/$company_hash?return_json=1&less_fields=1");

          if (is_wp_error($response)) {
               return;
          }

          $offers = json_decode($response['body'], true);

          if (empty($offers)) {
               return;
          }

          $modelOffers = new Hrappka_ModelsOffers();
          $modelOffers->save($offers);

     }

}
