<?php
/*
*
* @author 	Hrappka
* @version     1.0.0
 *
 */

$rec_id = isset($_GET['rec_id']) ? (int)$_GET['rec_id'] : null;

if (!empty($rec_id) && is_numeric($rec_id)) {

     // add custom css to site
     $styles = get_option(Hrappka_Offer_List_Admin::CUSTOM_CSS);
     add_action('wp_head', function () use ($styles) {
          echo "<style>{$styles}</style>";
     });

     $modelOffers = new Hrappka_ModelsOffers();
     $offer = $modelOffers->getOfferByRecId($rec_id);

     if (!empty($offer)) {

          $cmpHash = get_option(Hrappka_Offer_List_Admin::COMPANY_HASH);

          //load all options
          $visible_settings = get_option(Hrappka_Offer_List_Admin::VISIBLE_SETTINGS);

          $show_offer_site_bar = false;

          //check sidebar

          foreach (Hrappka_Offer_List_Admin::$offerSidebarSettings as $value) {
               if (!isset($visible_settings[$value])) {
                    $show_offer_site_bar = true;
                    break;
               }
          }

          $is_image = false;
          if (property_exists($offer, 'additional')) {
               $img = $offer->additional;
               $is_image = Hrappka_Services_Utility::imgExist($img, $cmpHash);

          }

          //create order array
          if (isset($offer->rec_json_settings->fields_order)) {
               $order = $offer->rec_json_settings->fields_order;
          } else {
               $order = Hrappka_Offer_List_Admin::$defaultOrder;
          }

          $ordered = [];
          $locale = get_locale();
          foreach ($order as $key => $value) {
               $ordered[$value] = [
                    'key' => $key,
                    'visible' => Hrappka_Offer_List_Admin::$mainOfferData[$key],
                    'title' => Hrappka_Offer_List_Admin::$mainOfferTitles[$key],
               ];
          }

     }
}

get_header(); ?>

     <div class="container">
          <?php if (!empty($offer)): ?>
               <div class="offer-view m-b-10 m-t-10">
                    <div class="row">
                         <div class="col-xl-<?php echo $show_offer_site_bar ? 8 : 12 ?> col-lg-<?php echo $show_offer_site_bar ? 8 : 12 ?> col-xs-12">
                              <div class="offer-wrapper">
                                   <?php if (Hrappka_Services_Utility::is_field_visible(Hrappka_Offer_List_Admin::VIEW_SETTINGS_IMG, $visible_settings)): ?>
                                        <?php if ($is_image): ?>
                                             <img src="<?php echo esc_url(Hrappka_Offer_List_Admin::host() . '/files/get/f_id/' . $img->rec_image->reca_data->f_id  . '/h/' . $cmpHash) ?>">
                                        <?php endif; ?>
                                   <?php endif; ?>
                                   <h1 class="hrappa-rec-title"><?php echo $offer->rec_title ?></h1>
                              </div>

                              <?php foreach ($ordered as $value): ?>
                                   <?php if (Hrappka_Services_Utility::is_field_visible($value['visible'], $visible_settings)): ?>
                                        <?php $translatedInfo = Hrappka_Offer_Page::getTranslatedOfferInfo($locale, $value['key'], $offer->rec_fulltext_search);
                                        $info = empty($translatedInfo) ? $offer->{$value['key']} : $translatedInfo ?>
                                        <?php if (!empty($info)): ?>
                                             <div class="offer-general">
                                                  <h4>
                                                       <?php _e($value['title'], 'hrappka-offer-list') ?>
                                                  </h4>
                                                  <?php _e($info) ?>
                                             </div>
                                        <?php endif; ?>
                                   <?php endif; ?>
                              <?php endforeach; ?>


                              <div class="row">
                                   <div class="col-md-12 col-xs-12 text-center m-b-10">
                                        <a href="<?php echo esc_url($offer->link . '?recruitment_info=0') ?>" class="btn btn-default btn-lg hrappka-apply-link" target="_blank"><?php _e('Aplikuj', 'hrappka-offer-list') ?></a>
                                   </div>
                              </div>

                         </div>
                         <?php if ($show_offer_site_bar): ?>
                              <div class="col-xl-4 col-lg-4 col-xs-12">
                                   <div class="sidebar-widget">
                                        <div class="hrappa-job-overview">
                                             <div class="hrappa-job-overview-headline"><?php _e('Oferta', 'hrappka-offer-list') ?></div>
                                             <div class="hrappa-job-overview-inner">
                                                  <ul>
                                                       <?php if (Hrappka_Services_Utility::is_field_visible(Hrappka_Offer_List_Admin::VIEW_SETTINGS_BRUTTO, $visible_settings)): ?>
                                                            <?php if (!empty($offer->rec_salary_print)): ?>
                                                                 <li>
                                                                      <i class="job-info-icon fa fa-dollar"></i>
                                                                      <span class="hrappa-job-info-title"><?php _e('Wynagrodzenie brutto', 'hrappka-offer-list') ?></span>
                                                                      <h5><?php echo $offer->rec_salary_print ?></h5>
                                                                 </li>
                                                            <?php endif; ?>
                                                       <?php endif; ?>
                                                       <?php if (Hrappka_Services_Utility::is_field_visible(Hrappka_Offer_List_Admin::VIEW_SETTINGS_CONTRACT_TYPE, $visible_settings)): ?>
                                                            <?php if (!empty($offer->rec_employment_form)): ?>
                                                                 <li>
                                                                      <i class="job-info-icon fa fa-briefcase"></i>
                                                                      <span class="hrappa-job-info-title"><?php _e('Typ umowy', 'hrappka-offer-list') ?></span>
                                                                      <h5>
                                                                           <?php _e($offer->rec_employment_form, 'hrappka-offer-list') ?>
                                                                      </h5>

                                                                 </li>
                                                            <?php endif; ?>
                                                       <?php endif; ?>
                                                       <?php if (Hrappka_Services_Utility::is_field_visible(Hrappka_Offer_List_Admin::VIEW_SETTINGS_CLIENT, $visible_settings)): ?>
                                                            <?php if (!empty($offer->cc_name)): ?>
                                                                 <li>
                                                                      <i class="job-info-icon fa fa-briefcase"></i>
                                                                      <span class="hrappa-job-info-title"><?php _e('Klient', 'hrappka-offer-list') ?></span>
                                                                      <h5>
                                                                           <?php echo $offer->cc_name ?>
                                                                      </h5>
                                                                 </li>
                                                            <?php endif; ?>
                                                       <?php endif; ?>

                                                       <?php if (Hrappka_Services_Utility::is_field_visible(Hrappka_Offer_List_Admin::VIEW_SETTINGS_PUBLICATION_DATE, $visible_settings)): ?>
                                                            <?php if (!empty($offer->rec_creation_time) && Hrappka_Services_Utility::checkNotEmptyDate($offer->rec_creation_time)): ?>
                                                                 <li>
                                                                      <i class="job-info-icon fa fa-clock-o"></i>
                                                                      <span class="hrappa-job-info-title"><?php _e('Data publikacji', 'hrappka-offer-list') ?></span>
                                                                      <h5><?php echo date(Hrappka_Offer_List_Admin::DATE_FORMAT, strtotime($offer->rec_creation_time)); ?></h5>
                                                                 </li>
                                                            <?php endif; ?>
                                                       <?php endif; ?>

                                                       <?php if (Hrappka_Services_Utility::is_field_visible(Hrappka_Offer_List_Admin::VIEW_SETTINGS_WORK_HOURS, $visible_settings)): ?>
                                                            <?php if (!empty($offer->rec_working_hours)): ?>
                                                                 <li>
                                                                      <i class="job-info-icon fa fa-clock-o"></i>
                                                                      <span class="hrappa-job-info-title"><?php _e('Godziny pracy', 'hrappka-offer-list') ?></span>
                                                                      <?php $t = json_decode($offer->rec_working_hours, true) ?>
                                                                      <h5><?php echo $t['rec_working_hours_from'] . ' - ' . $t['rec_working_hours_to'] ?></h5>
                                                                 </li>
                                                            <?php endif; ?>
                                                       <?php endif; ?>

                                                       <?php if (Hrappka_Services_Utility::is_field_visible(Hrappka_Offer_List_Admin::VIEW_SETTINGS_QUANTITY, $visible_settings)): ?>
                                                            <?php if (!empty($offer->rec_quantity)): ?>
                                                                 <li>
                                                                      <i class="job-info-icon fa fa-users"></i>
                                                                      <span class="hrappa-job-info-title"><?php _e('Ilość miejsc', 'hrappka-offer-list') ?></span>
                                                                      <h5><?php echo $offer->rec_quantity ?></h5>
                                                                 </li>
                                                            <?php endif; ?>
                                                       <?php endif; ?>

                                                       <?php if (Hrappka_Services_Utility::is_field_visible(Hrappka_Offer_List_Admin::VIEW_SETTINGS_DATE_START_EMPLOY, $visible_settings)): ?>
                                                            <?php if (!empty($offer->rec_employ_date_from) && Hrappka_Services_Utility::checkNotEmptyDate($offer->rec_employ_date_from)): ?>
                                                                 <li>
                                                                      <i class="job-info-icon fa fa-calendar-o"></i>
                                                                      <span class="hrappa-job-info-title"><?php _e('Termin zatrudnienia od', 'hrappka-offer-list') ?></span>
                                                                      <h5><?php echo date(Hrappka_Offer_List_Admin::DATE_FORMAT, strtotime($offer->rec_employ_date_from)); ?></h5>
                                                                 </li>
                                                            <?php endif; ?>
                                                       <?php endif; ?>

                                                       <?php if (Hrappka_Services_Utility::is_field_visible(Hrappka_Offer_List_Admin::VIEW_SETTINGS_DATE_END_EMPLOY, $visible_settings)): ?>
                                                            <?php if (!empty($offer->rec_employ_date_to) && Hrappka_Services_Utility::checkNotEmptyDate($offer->rec_employ_date_to)): ?>
                                                                 <li>
                                                                      <i class="job-info-icon fa fa-calendar-o"></i>
                                                                      <span class="hrappa-job-info-title"><?php _e('Termin zatrudnienia do', 'hrappka-offer-list') ?></span>
                                                                      <h5><?php echo date(Hrappka_Offer_List_Admin::DATE_FORMAT, strtotime($offer->rec_employ_date_to)); ?></h5>
                                                                 </li>
                                                            <?php endif; ?>
                                                       <?php endif; ?>

                                                       <?php if (Hrappka_Services_Utility::is_field_visible(Hrappka_Offer_List_Admin::VIEW_SETTINGS_DATE_END_RECRUITMENT, $visible_settings)): ?>
                                                            <?php if (!empty($offer->rec_end_date) && Hrappka_Services_Utility::checkNotEmptyDate($offer->rec_end_date)): ?>
                                                                 <li>
                                                                      <i class="job-info-icon fa fa-clock-o"></i>
                                                                      <span class="hrappa-job-info-title"><?php _e('Data zakończenia rekrutacji', 'hrappka-offer-list') ?></span>
                                                                      <h5><?php echo date(Hrappka_Offer_List_Admin::DATE_FORMAT, strtotime($offer->rec_end_date)); ?></h5>
                                                                 </li>
                                                            <?php endif; ?>
                                                       <?php endif; ?>


                                                       <?php if (Hrappka_Services_Utility::is_field_visible(Hrappka_Offer_List_Admin::VIEW_SETTINGS_DATE_START_RECRUITMENT, $visible_settings)): ?>
                                                            <?php if (!empty($offer->rec_start_date) && Hrappka_Services_Utility::checkNotEmptyDate($offer->rec_start_date)): ?>
                                                                 <li>
                                                                      <i class="job-info-icon fa fa-clock-o"></i>
                                                                      <span class="hrappa-job-info-title"><?php _e('Data zakończenia rekrutacji', 'hrappka-offer-list') ?></span>
                                                                      <h5><?php echo date(Hrappka_Offer_List_Admin::DATE_FORMAT, strtotime($offer->rec_start_date)); ?></h5>
                                                                 </li>
                                                            <?php endif; ?>
                                                       <?php endif; ?>

                                                  </ul>
                                             </div>
                                        </div>
                                   </div>
                              </div>
                         <?php endif; ?>
                    </div>
               </div>

          <?php else: ?>
               <div class="row">
                    <div class="col-md-12 col-xs-12 text-center hrappka-no-offer-found-info">
                         <h3 style="margin-top: 150px"><?php _e('Nie znaleziono oferty', 'hrappka-offer-list') ?></h3>
                    </div>

               </div>
          <?php endif; ?>
     </div>

<?php
get_footer();
?>