<?php

/**
 * Class Hrappka_Offer_Model
 */
class Hrappka_ModelsOffers
{
     const OFFERS_TABLE_NAME = 'hrappka_offer_list';

     public $table_name;

     public function __construct()
     {
          global $wpdb;
          $this->table_name = $wpdb->prefix . static::OFFERS_TABLE_NAME;

     }

     public function getOfferByRecId($rec_id)
     {
          global $wpdb;

          $offer = $wpdb->get_results(
               $wpdb->prepare(
                    "SELECT * FROM {$this->table_name} WHERE rec_id = %s", $rec_id
               )
          );

          if (isset($offer[0])) {
               $offer = $offer[0];
               if (property_exists($offer, 'additional')) {
                    $offer->additional = json_decode($offer->additional);
               }

               if ((property_exists($offer, 'additional'))) {
                    $offer->rec_json_settings = json_decode($offer->rec_json_settings);
               }

               if (isset($offer->rec_fulltext_search)) {
                    $offer->rec_fulltext_search = json_decode($offer->rec_fulltext_search);
               }
          }

          return $offer;
     }

     public function getOffers($limit, $filters, $tags)
     {
          global $wpdb;

          $query = "SELECT * FROM  $this->table_name ";

          if (!empty($filters)) {

               $where = '';

               if (!empty($filters['search'])) {
                    $where .= !empty($where) ? ' AND ' : '';
                    $where .= $wpdb->prepare("  rec_title LIKE %s ", '%' . $wpdb->esc_like($filters['search']) . '%');
               }

               if (!empty($filters['locality']['lat']) && !empty($filters['locality']['lng'])) {
                    $where .= !empty($where) ? ' AND ' : '';
                    $where .= $wpdb->prepare(" hrappka_distance_function(rec_coordinate_lat, rec_coordinate_long, %s, %s) <= 50 ", $filters['locality']['lat'], $filters['locality']['lng']);
               }

               if (!empty($tags)) {
                    $where .= !empty($where) ? ' AND ( ' : '(';
                    $tagsForDB = explode('+', $tags);
                    foreach ($tagsForDB as $key => $value) {
                         if ($key !== 0) {
                              $where .= ' OR ';
                         }
                         $where .= $wpdb->prepare(" rec_tags_search_data like %s ", '%' . $wpdb->esc_like($value) . '%');
                    }

                    $where .= ')';
               }

               if (!empty($where)) {
                    $filters['page'] = 1;
                    $query .= ' WHERE ' . $where . ' ';
               }

          }

          $count_after_filter = $wpdb->get_results($query);

          $total_after = ceil(count($count_after_filter) / $limit);

          $page = (0 < $filters['page'] && $filters['page'] < $count_after_filter) ? $filters['page'] : 1;
          $offset = $limit * (--$page);

          $query .= " ORDER BY rec_recently_featured_active DESC, rec_recently_featured DESC, rec_employ_date_from ASC, rec_title ASC ";
          $query .= " LIMIT {$limit} OFFSET {$offset}";
          $offers = $wpdb->get_results($query);

          if (!empty($offers)) {
               foreach ($offers as $offer) {
                    if (property_exists($offer, 'additional')) {
                         $offer->additional = json_decode($offer->additional);
                    }
               }
          }

          return compact('offers', 'total_after');
     }

     public function save($offers)
     {

          global $wpdb;

          // columns name
          $columns_array = $wpdb->get_col("DESC {$this->table_name}", 0);
          // columns types
          $column_types = $wpdb->get_col("DESC {$this->table_name}", 1);
          // remove pk
          array_shift($columns_array);
          array_shift($column_types);
          // string with columns
          $columns_string = implode(', ', $columns_array);

          //prepare types chain
          $type_chain = [];
          foreach ($column_types as $value) {
               $type_chain[] = Hrappka_Services_Utility::checkType($value);
          }

          $type_chain = implode(',', $type_chain);

          $offers_form_db = $wpdb->get_results("SELECT rec_id,rec_last_update_time FROM  {$this->table_name}");

          $query_insert = "INSERT into  {$this->table_name} ({$columns_string}) VALUES ";
          $values_insert = [];

          foreach ($offers as $offer) {
               if (isset($offer['rec_id']) && 0 < $offer['rec_id']) {
                    $is_insert = true;

                    if (!empty ($offers_form_db)) {
                         foreach ($offers_form_db as $offer_db) {
                              if ($offer_db->rec_id == $offer['rec_id']) {
                                   if (strtotime($offer_db->rec_last_update_time) < strtotime($offer['rec_last_update_time'])) {
                                        $new_data = Hrappka_Services_Utility::prepareRowForDbUpdate($columns_array, $offer);
                                        $wpdb->update($this->table_name, $new_data, ['rec_id' => $offer['rec_id']]);
                                   }

                                   $is_insert = false;
                                   break;
                              }
                         }
                    }

                    if ($is_insert) {
                         $values_insert[] = Hrappka_Services_Utility::prepareRowForDbInsert($columns_array, $type_chain, $offer);
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

          // remove old offers
          $rec_ids_db = [];
          $rec_ids = [];
          $delete_ids = [];
          foreach ($offers as $offer) {
               $rec_ids[] = $offer['rec_id'];
          }

          foreach ($offers_form_db as $offer) {
               $rec_ids_db[] = (int)$offer->rec_id;
          }

          $delete_ids = array_diff($rec_ids_db, $rec_ids);

          if (!empty($delete_ids)) {
               $ids = implode(',', $delete_ids);
               $wpdb->query("DELETE FROM {$this->table_name} WHERE rec_id IN($ids)");
          }

     }


     public function table_definition()
     {

          return "CREATE TABLE IF NOT EXISTS  {$this->table_name} (
                    id bigint(20) NOT NULL AUTO_INCREMENT, 
                    rec_id  bigint(20) NOT NULL,   
                    rec_creation_time DATETIME,
                    rec_last_update_time DATETIME,
                    rec_employment_form VARCHAR(100),
                    rec_end_date DATETIME,
                    rec_start_date DATETIME,
                    rec_size_employment VARCHAR(100),
                    rec_work_time VARCHAR(50),
                    rec_employ_date_from DATETIME,
                    rec_employ_date_to DATETIME,
                    rec_salary_type  VARCHAR(50),
                    rec_salary_amount   FLOAT(10,2),
                    rec_salary_currency  VARCHAR(50),
                    rec_salary_print  VARCHAR(100),
                    rec_coordinate_lat  FLOAT(11,8),
                    rec_coordinate_long  FLOAT(11,8),
                    rec_quantity  INTEGER,
                    rec_reference_number VARCHAR(30),
                    rec_state  VARCHAR(150),
                    rec_state_system  VARCHAR(150),
                    rec_locality  VARCHAR(60),
                    rec_title  VARCHAR(100),
                    rec_description  TEXT,
                    rec_offer TEXT,
                    rec_working_hours  VARCHAR(255),
                    rec_recruitment_internal BOOLEAN,
                    rec_locality_additional TEXT,
                    rec_work_type VARCHAR(255),
                    rec_note TEXT,
                    rec_is_active_status BOOLEAN,
                    cc_name VARCHAR(100),
                    cps_name VARCHAR(100),
                    show_options TEXT,
                    rec_visibility_options TEXT,
                    link  VARCHAR(255),
                    rec_geo_tags  TEXT,
                    rec_geo_geometry TEXT,
                    rec_geo_name  VARCHAR(255),
                    rec_geo_formatted_address  VARCHAR(255),
                    rec_geo_country  VARCHAR(255),
                    rec_geo_country_code  VARCHAR(255),
                    additional TEXT,
                    rec_requirements TEXT,
                    rec_json_settings TEXT,
                    rec_fulltext_search TEXT,
                    rec_tags_search_data TEXT,
                    rec_recently_featured_active BOOLEAN,
                    rec_recently_featured BOOLEAN,
                    PRIMARY KEY (id)   
               ) CHARACTER SET utf8mb4";

     }

}
