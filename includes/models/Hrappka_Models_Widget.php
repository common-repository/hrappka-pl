<?php

/**
 * Class Hrappka_Models_Widget
 */
class Hrappka_ModelsWidget
{
     const WIDGET_TABLE_NAME = 'hrappka_widget_settings';

     public $table_name;

     public function __construct()
     {
          global $wpdb;
          $this->table_name = $wpdb->prefix . static::WIDGET_TABLE_NAME;

     }

     public function getSettingsForWidgetByHash($hash)
     {
          global $wpdb;
          $query = "SELECT * FROM {$this->table_name} WHERE crw_hash = '{$hash}'";
          $settings = $wpdb->get_results($query);

          if (empty($settings)) {

               return [];
          }

          foreach ($settings as $setting) {
               $setting->crw_view_configs = json_decode($setting->crw_view_configs);
               $setting->crw_search = json_decode($setting->crw_search);

          }

          return $settings[0];
     }

     public function getWidgetsNameAndHash()
     {
          global $wpdb;
          $query = "SELECT crw_name, crw_hash FROM {$this->table_name}";
          return $wpdb->get_results($query);
     }

     public function save($new_settings)
     {
          global $wpdb;

          $widget_columns_array = $wpdb->get_col("DESC {$this->table_name}", 0);
          // columns types
          $column_types = $wpdb->get_col("DESC {$this->table_name}", 1);
          // remove pk
          array_shift($widget_columns_array);
          array_shift($column_types);
          // string with columns
          $columns_string = implode(', ', $widget_columns_array);

          //prepare types chain
          $type_chain = [];
          foreach ($column_types as $value) {
               $type_chain[] = Hrappka_Services_Utility::checkType($value);
          }

          $type_chain = implode(',', $type_chain);

          $old_settings = $wpdb->get_results("SELECT * FROM  {$this->table_name}");

          $query_insert = "INSERT into  {$this->table_name} ({$columns_string}) VALUES ";
          $values_insert = [];

          foreach ($new_settings as $settings) {
               if (isset($settings['crw_hash']) && !empty($settings['crw_hash'])) {
                    $is_insert = true;

                    if (!empty ($old_settings)) {
                         foreach ($old_settings as $settings_db) {
                              if ($settings_db->crw_hash == $settings['crw_hash']) {
                                   if (strtotime($settings_db->crw_last_update_time) < strtotime($settings['crw_last_update_time'])) {
                                        $new_data = Hrappka_Services_Utility::prepareRowForDbUpdate($widget_columns_array, $settings);
                                        $wpdb->update($this->table_name, $new_data, ['crw_hash' => $settings['crw_hash']]);
                                   }

                                   $is_insert = false;
                                   break;
                              }
                         }
                    }

                    if ($is_insert) {
                         $values_insert[] = Hrappka_Services_Utility::prepareRowForDbInsert($widget_columns_array, $type_chain, $settings);
                    }
               }
          }

          if (!empty($values_insert)) {
               foreach ($values_insert as $key => $value) {
                    $values_insert[$key] = '(' . $value . ')';
               }
               $data = implode(",\n", $values_insert);
               $query_insert .= $data;
               $wpdb->query($query_insert);
          }

//           remove old widget settings
          $crw_hash_db = [];
          $crw_hash = [];
          $delete_ids = [];
          foreach ($new_settings as $settings) {
               $crw_hash[] = $settings['crw_hash'];
          }

          foreach ($old_settings as $settings) {
               $crw_hash_db[] = $settings->crw_hash;
          }

          $delete_ids = array_diff($crw_hash_db, $crw_hash);

          if (!empty($delete_ids)) {
               $hash = implode("','", $delete_ids);
               $hash = "'" . $hash . "'";
               $wpdb->query("DELETE FROM {$this->table_name} WHERE crw_hash IN ($hash)");
          }

     }

     public function table_definition()
     {
          return "CREATE TABLE IF NOT EXISTS  {$this->table_name} (
                    id bigint(20) NOT NULL AUTO_INCREMENT,
                    crw_name VARCHAR(255),
                    crw_pagination_per_page INTEGER,
                    crw_search TEXT,
                    crw_hash TEXT,
                    crw_last_update_time DATETIME, 
                    crw_view_configs TEXT,
                    crw_styles TEXT,
                    PRIMARY KEY (id)   
               )";

     }

}
