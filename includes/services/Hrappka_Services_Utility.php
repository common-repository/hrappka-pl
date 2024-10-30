<?php

/**
 * Class Hrappka_Utilty
 */
class Hrappka_Services_Utility
{

     const EMPTY_DATE = '0000-00-00 00:00:00';

     public static function checkType($type)
     {
          if (strpos($type, 'float') !== false) {
               return '%f';
          } elseif (strpos($type, 'bigint') !== false || strpos($type, 'int') !== false || strpos($type, 'tinyint') !== false) {
               return '%d';
          } else {
               return '%s';
          }
     }

     public static function prepareRowForDbUpdate($cols, $offer)
     {
          $result = [];
          $json_fields = static::jsonFields();

          foreach ($cols as $val) {
               if (in_array($val, $json_fields)) {
                    if (isset($offer[$val])) {
                         $result[$val] = json_encode($offer[$val]);
                    }
                    continue;
               }

               $result[$val] = $offer[$val];
          }

          return $result;
     }

     public static function prepareRowForDbInsert($cols, $type_chain, $offer)
     {
          global $wpdb;

          $values = [];
          $json_fields = static::jsonFields();

          $values[] = $type_chain;

          //prepare values
          foreach ($cols as $val) {
               if (in_array($val, $json_fields)) {
                    $values[] = json_encode($offer[$val]);
                    continue;
               }

               $values[] = $offer[$val];
          }

          return call_user_func_array([$wpdb, 'prepare'], $values);
     }

     public static function jsonFields()
     {
          return [
               'show_options',
               'crw_view_configs',
               'additional',
               'rec_settings_json',
          ];
     }

     public static function is_field_visible($key, $view_settings)
     {
          if (is_object($view_settings)) {
               if (!property_exists($view_settings, $key)) {
                    return false;
               }

               if ($view_settings->{$key} != 1) {
                    return false;
               }

          } else {

               if (isset($view_settings[$key])) {
                    if ($view_settings[$key] == -1) {

                         return false;
                    }
               }

          }

          return true;

     }

     public static function checkNotEmptyDate($date)
     {
          if ($date == static::EMPTY_DATE) {
               return false;
          }

          return true;
     }

     public static function imgExist($img,$cmpHash) {

          if (isset($img->rec_image->reca_data->f_id) && $img->rec_image->reca_data->f_id > 0) {

               $imgResponse = wp_remote_get(Hrappka_Offer_List_Admin::host() . '/files/get/f_id/' . $img->rec_image->reca_data->f_id . '/h/' . $cmpHash);

               if (is_wp_error($imgResponse)) {
                    return false;
               }

               return true;
          }
     }

}
