/**
 * All of the code for your admin-facing JavaScript source
 * should reside in this file.
 *
 * Note: It has been assumed you will write jQuery code here, so the
 * $ function reference has been prepared for usage within the scope
 * of this function.
 *
 * jQuery(document).ready( function($){
 *  // Add jQuery or vanilla javascript code here
 * });
 * 
 */

jQuery(document).ready(function ($) {
  // -------------------------------------------------------------
  //   Toast Notification (cloned from Review Stack)
  // -------------------------------------------------------------
  
  // Toast hide function - remove show class first for transition, then color class after
  function hideToast() {
    var $toast = $('#hwcf-toast');
    // Remove inline styles and show class to trigger fade out
    $toast.removeAttr('style').removeClass('show');
    // Wait for transition to complete (300ms) before removing color classes
    setTimeout(function() {
      $toast.removeClass('toast-error success');
    }, 350);
  }
  
  // Show toast notification with custom title and message
  function showToast(title, message, type) {
    var $toast = $('#hwcf-toast');
    
    // Fall back to alert if toast element doesn't exist
    if (!$toast.length) {
      if (type === 'error') {
        alert(title + ': ' + message);
      }
      return;
    }
    
    $toast.find('.hwcf-toast-title').text(title);
    $toast.find('.hwcf-toast-message').text(message);
    
    // Remove all state classes and inline styles
    $toast.removeClass('show success toast-error').removeAttr('style');
    
    // Set error or success class
    if (type === 'error') {
      $toast.addClass('toast-error');
    } else {
      $toast.addClass('success');
    }
    
    // Reset progress bar
    var $progress = $toast.find('.hwcf-toast-progress');
    if ($progress.length) {
      $progress.css('animation', 'none');
      $progress[0].offsetHeight; // Trigger reflow
      $progress.css('animation', '');
    }
    
    // Show toast
    $toast.addClass('show');
    
    // Auto-hide after 2.5 seconds for errors (longer to read)
    var hideDelay = type === 'error' ? 2500 : 1500;
    setTimeout(function() {
      hideToast();
    }, hideDelay);
  }
  
  // Toast close button
  $('#hwcf-toast .hwcf-toast-close').on('click', function(e) {
    e.preventDefault();
    hideToast();
  });
  
  // Check for success/delete message on page load via PHP flag
  setTimeout(function() {
    if (typeof hwcf !== 'undefined' && hwcf.show_toast) {
      if (hwcf.show_toast === 'success') {
        showToast('Success!', 'Settings saved.');
      } else if (hwcf.show_toast === 'deleted') {
        showToast('Deleted!', 'Settings deleted.', 'error');
      }
    }
  }, 100);

  // -------------------------------------------------------------
  //   Initialize multi select
  // -------------------------------------------------------------

  if ($('.hwcf_categories').length > 0) {
    var multiSelect = new IconicMultiSelect({
      select: ".hwcf_categories",
      placeholder: hwcf.search_text,
      noResults: hwcf.search_none,
    });
    multiSelect.init();
    multiSelect.subscribe(function (e) {
      var selected_options = e.selection;
      var selected_option = [];
      $(".hwcf_categories option:selected").removeAttr("selected");
      selected_options.forEach(function (item) {
        selected_option.push(item.value);
      });
      $("#hwcf_categories").val(selected_option);

    });
  }
  $(document).on("click", "#hwcf_notice button.notice-dismiss", function (e) {
    e.preventDefault();
    hwcf_dismiss_notice(0);
  });

  $(document).on("click", "a.hwcf-feedback", function (e) {
    e.preventDefault();
    hwcf_dismiss_notice(1);
    $("#hwcf_notice").slideUp();
    window.open($(this).attr("href"), "_blank");
  });

  // Disable User Role selection if rules applied to guest only
  $(document).on("change", "#hwcf_loggedinUsers", function (e) {

    if (this.checked) {
      $(document).find("#hwcf_userType").removeAttr("disabled");
    } else {
      $(document).find("#hwcf_userType").attr("disabled", "disabled");
    }

  });

  $(document).on("change", "#hwcf_hide_price", function (e) {

    if (!this.checked) {
      $(document).find("#hwcf_overridePriceTag").removeAttr("disabled");
    } else {
      $(document).find("#hwcf_overridePriceTag").attr("disabled", "disabled");
    }

  });

  function hwcf_dismiss_notice(is_final) {

    $.ajax({
      url: ajaxurl,
      data: { action: "hwcf_dismiss_notice", "dismissed_final": is_final, nonce: hwcf.nonce },
      type: "POST",
      dataType: "json",
      success: function (response) {
        // Notice dismissed successfully
      }
    });
  }

  // Support button click handler - sends notification email
  $(document).on('click', '.hwcf-support-btn', function(e) {
    e.preventDefault();
    var url = $(this).attr('href');
    window.open(url, '_blank');
    $.post(hwcf.ajaxurl, {
      action: 'hwcf_support_notification',
      nonce: hwcf.nonce
    });
  });

  // Cripple bots checkbox handler
  $(document).on('change', '#hwcf_cripple_bots', function() {
    var isChecked = $(this).is(':checked') ? 1 : 0;
    $.post(hwcf.ajaxurl, {
      action: 'hwcf_cripple_bots',
      settings_action: isChecked,
      nonce: hwcf.nonce
    });
  });

  // Disable purchases checkbox handler
  $(document).on('change', '#hwcf_disable_purchases', function() {
    var isChecked = $(this).is(':checked') ? 1 : 0;
    $.post(hwcf.ajaxurl, {
      action: 'hwcf_disable_purchases',
      settings_action: isChecked,
      nonce: hwcf.nonce
    });
  });

  // Delete data on uninstall checkbox handler
  $(document).on('change', '#hwcf_delete_on_uninstall', function() {
    var isChecked = $(this).is(':checked') ? 1 : 0;
    $.post(hwcf.ajaxurl, {
      action: 'hwcf_delete_on_uninstall',
      settings_action: isChecked,
      nonce: hwcf.nonce
    });
  });

  // Save Store Settings button handler
  $(document).on('click', '.hwcf-save-store-settings', function() {
    var $btn = $(this);
    $btn.prop('disabled', true);
    
    // Save all three settings
    var crippleBots = $('#hwcf_cripple_bots').is(':checked') ? 1 : 0;
    var disablePurchases = $('#hwcf_disable_purchases').is(':checked') ? 1 : 0;
    var deleteOnUninstall = $('#hwcf_delete_on_uninstall').is(':checked') ? 1 : 0;
    
    $.when(
      $.post(hwcf.ajaxurl, { action: 'hwcf_cripple_bots', settings_action: crippleBots, nonce: hwcf.nonce }),
      $.post(hwcf.ajaxurl, { action: 'hwcf_disable_purchases', settings_action: disablePurchases, nonce: hwcf.nonce }),
      $.post(hwcf.ajaxurl, { action: 'hwcf_delete_on_uninstall', settings_action: deleteOnUninstall, nonce: hwcf.nonce })
    ).done(function() {
      showToast('Success!', 'Settings saved.');
      $btn.prop('disabled', false);
    }).fail(function() {
      showToast('Error', 'Failed to save settings.', 'error');
      $btn.prop('disabled', false);
    });
  });

});

// woocommerce search product query

jQuery(document).ready(function ($) {

  
  // stop selecting both checkboxes at same time starts here 
  jQuery('.guest-checkbox').on('change', function(){
    if (jQuery(this).is(":checked")) {
      jQuery('.logged-in-checkbox').prop('checked', false);
    }
  });
  jQuery('.logged-in-checkbox').on('change', function(){
    if (jQuery(this).is(":checked")) {
      jQuery('.guest-checkbox').prop('checked', false);
    }
  });
  // stop selecting both checkboxes at same time ends here 



  jQuery('#custom-product-search-field').select2({
    placeholder: hwcf.search_product,
    minimumInputLength: 3,
    allowClear: true,
    ajax: {
      delay: 250,
      type: 'post',
      dataType: 'json',
      url: ajaxurl,
      data: function (params) {
        return {
          action: 'custom_product_search',
          product_name: params.term,
          nonce: hwcf.nonce
        };
      },
      processResults: function (data) {
        return {
          results: $.map(data, function (item) {
            return {
              text: item.post_name,
              id: item.ID,
            };
          })
        };
      },
      cache: true
    }
  }).on("select2:unselect", function (e) { 
    var product_id = e.params.data.id;
    var pro_elem = jQuery("#hwcf_products");
    var product_ids = pro_elem.val();
    product_ids = product_ids.split(",");
    var find_ID = product_ids.indexOf(product_id);
    if (find_ID > -1) {
      product_ids.splice(find_ID, 1);
    }

    product_ids = product_ids.length > 1 ? product_ids.join(",") : product_ids;
    
    pro_elem.val(product_ids);

  }).on("select2:select", function (e) { 
    var product_id = e.params.data.id;
    var pro_elem = jQuery("#hwcf_products");
    var product_ids = pro_elem.val();
    product_ids = product_ids.split(",");
    product_ids.push(product_id);
    var final_ids = product_ids.filter(function(a) { return a; });
    final_ids = final_ids.length > 1 ? final_ids.join(",") : final_ids;
    pro_elem.val(final_ids);
  });

});
