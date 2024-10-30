var HrWidget = (function () {

     // Localize jQuery variable
     var jQuery;

     var lang = null;

     var languageQueryParam = 'l';

     var googleApiKey = 'AIzaSyDW6NOoihExl6NvpiM5ktH8dcj6JY6reZA';

     jQuery = window.jQuery;

     main();

     /**
      * On jQuery loaded
      */
     function scriptLoadHandler() {
          // Restore $ and window.jQuery to their previous values and store the
          // new jQuery in our local jQuery variable
          jQuery = window.jQuery.noConflict(true);
          // Call our main function
          main();
     }

     /**
      * Start widget
      */
     function main() {

          jQuery(function ($) {
               var script_tag = document.createElement('script');
               script_tag.setAttribute("type", "text/javascript");
               script_tag.setAttribute("src", "https://maps.googleapis.com/maps/api/js?key=" + (googleApiKey || 'AIzaSyBuu8GDowMJpGSTsfFrAsIPLr4mc2MKhcw') + "&language=" + (lang || 'pl') + "&libraries=places&callback=hrInitLocalityAutocomplete");
               // Try to find the head, otherwise default to the documentElement
               (document.getElementsByTagName("head")[0] || document.documentElement).appendChild(script_tag);

          });
     }


     /**
      * Start loader
      *
      * @param element
      */
     function startLoader(element) {
          element = element || jQuery(containerElement);

          jQuery(element).css('position', 'relative');
          jQuery(element).append("<div class='hr-mask' style='opacity: 0.3; position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 99999; background-color: rgba(0, 0, 0, .7);'>&nbsp;</div>");
          jQuery(element).append("<div class='hr-loader' style='font-size: 8px; width: 1em; height: 1em; border-radius: 50%; position: absolute; top: 50%; left: 50%; text-indent: -9999em; animation: load4 1.3s infinite linear; z-index: 99999;'>Loading &nbsp;</div>");
     }

     /**
      * Stop loader
      *
      * @param element
      */
     function stopLoader(element) {
          element = element || jQuery(containerElement);

          jQuery(element).find('.hr-mask').remove();
          jQuery(element).find('.hr-loader').remove();
     }


     /**
      * Parse google response
      *
      * @param components
      * @returns {{country: *}}
      */
     var parseGoogleAddressComponents = function (components) {
          var country = null;

          for (var key in components) {
               if (components.hasOwnProperty(key)) {
                    if (-1 !== jQuery.inArray('country', components[key].types)) {
                         country = components[key].short_name;
                    }
               }
          }

          return {
               country,
          };
     };

     return {
          parseGoogleAddressComponents,
          main,
     };

})();

window.hrInitLocalityAutocomplete = function () {
     if (typeof google !== 'undefined') {
          var gmapsInput = document.getElementsByClassName('hr-search-locality');
          if (gmapsInput.length > 0) {
               var gmapsAutocomplete = new google.maps.places.Autocomplete(gmapsInput[0], {types: ['geocode']});

               // On place change - get coordinates and set them to filters
               gmapsAutocomplete.addListener('place_changed', function () {

                    jQuery('#filter-locality-lat').val('');
                    jQuery('#filter-locality-lng').val('');
                    jQuery('#filter-locality-viewport').val('');
                    jQuery('#filter-locality-country').val('');

                    var place = gmapsAutocomplete.getPlace();

                    if (!place.geometry) {
                         // User entered the name of a Place that was not suggested and
                         // pressed the Enter key, or the Place Details request failed.
                         // window.alert("No details available for input: '" + place.name + "'");
                         return;
                    }

                    var location = place.geometry.location;
                    var parsed = HrWidget.parseGoogleAddressComponents(place.address_components);

                    jQuery('#filter-locality-lat').val(location.lat());
                    jQuery('#filter-locality-lng').val(location.lng());
                    jQuery('#filter-locality-viewport').val(JSON.stringify(place.geometry.viewport));
                    jQuery('#filter-locality-country').val(parsed.country);
               });

               // Clear lat,lng filters o clear input
               jQuery(gmapsInput).on('change', function () {
                    if (this.value.trim() === '') {
                         jQuery('#filter-locality-lat').val('');
                         jQuery('#filter-locality-lng').val('');
                         jQuery('#filter-locality-viewport').val('');
                         jQuery('#filter-locality-country').val('');
                    }
               });

          }
     }
};

jQuery(document).ready(function ($) {

     function set_page_and_submit($page) {
          $('#hrappka-page').val($page);
          $form.submit();
     }

     let $document = $(document);
     let $form = $('.hrappka-widget-form');
     let current_number = $('.hrappka-pagination-page.active').data('hrappka-page-number');
     let total_pages = $('.hrappka-pagination').data('hrappka-total-pages');

     $('[name=_wp_http_referer]').remove();

     $document.on('click', '.hrappka-pagination-page', function () {
          let page_number = $(this).data('hrappka-page-number');
          set_page_and_submit(page_number);
     });

     $document.on('click', '.hrappka-pagination-page-prev', function () {
          if (1 < current_number) {
               set_page_and_submit(--current_number)
          }
     });

     $document.on('click', '.hrappka-pagination-page-next', function () {
          if (current_number < total_pages) {
               set_page_and_submit(++current_number)
          }
     });

});


jQuery(document).ready(function ($) {

     function hrappka_clear_bootstrap_col_classes($elem) {

          $elem.find('[class^="col-lg-"] , [class^="col-md-"] , [class^="col-sm-"] ').each(function (index, value) {
               let classes = $(value)[0].classList;
               for (let i = 0; i < classes.length; ++i) {
                    if (/^col-lg-/.test(classes[i]) || /^col-md-/.test(classes[i]) || /^col-sm-/.test(classes[i])) {
                         $(value).removeClass(classes[i])
                    }
               }

          });

     }

     $('.hrappka-offer-list ').each(function (index, value) {
          let windowWidth = window.innerWidth;
          let containerWidth = $(value).parent().width();

          if (768 < windowWidth && containerWidth < 768) {
               let $widget = $(this).find('.hrappka-offer-one-offer');

               $widget.each(function (index, value) {
                    hrappka_clear_bootstrap_col_classes($(value));
               });

               let $content = $('.hrappka-offer-search-name');
               let $locality = $('.hrappka-offer-search-locality');
               let $button = $('.hrappka-offer-search-button');

               $content.parent().removeClass('col-md-5 p-l-0');
               $locality.parent().removeClass('col-md-4 p-l-0 p-r-0');
               $locality.parent().addClass(' m-t-5');
               $button.removeClass('col-md-3');
               $button.addClass('text-center m-t-5');
          }

     });

});
