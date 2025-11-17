(function( $ ) {
    
  "use strict";

    // Handle activation submission
    $('#acm-license-activation-form').on('submit', function(e) {
        e.preventDefault(); // Prevent default form submission
        $('#acm-license-activation-form .wps-button').addClass('btn-spin');
        // Get form data
        var licenseKey = $('#license-key').val();
        var domain_name = $('#domain-name').val();
        var product_code = $('#product-code').val();

        // Send AJAX request
        $.ajax({
            url: ajaxurl, // WordPress AJAX URL
            type: 'POST',
            data: {
                action: 'wps_verify_envato_purchase', // WordPress action hook
                purchase_code: licenseKey,
                domain_name: domain_name,
                product_code: product_code
            },
            success: function(response) {
                if (response.success) {
                    $('#acm-license-activation-form .wps-button').removeClass('btn-spin');
                    $('#api-response').html(
                        '<span style="color: red;">Output: Success</span>'
                    );
                    window.location.reload();
                } else {
                    $('#acm-license-activation-form .wps-button').removeClass('btn-spin');
                    // Display the error message on the page
                    let message = typeof response.data === 'object'
                    ? JSON.stringify(response.data)
                    : (response.data || 'Unknown error');
                    $('#api-response').html(
                        '<span style="color: red;">Error: ' + message + '</span>'
                    );
                }
            },
            error: function() {
                alert('AJAX request failed.');
            }
        });
    });

    // Handle deactivate submission
    $('#acm-deactivate-form').on('submit', function(e) {
        e.preventDefault(); // Prevent default form submission
        $('#acm-deactivate-form .wps-button').addClass('btn-spin');
        // Get form data
        var licenseKey = $('#license-key').val();
        var domain_name = $('#domain-name').val();

        // Send AJAX request
        $.ajax({
            url: ajaxurl, // WordPress AJAX URL
            type: 'POST',
            data: {
                action: 'wps_detach_license', // WordPress action hook
                purchase_code: licenseKey,
                domain_name: domain_name
            },
            success: function(response) {
                if (response.success) {
                    $('#acm-deactivate-form .wps-button').removeClass('btn-spin');
                    window.location.reload();
                } else {
                    $('#acm-deactivate-form .wps-button').removeClass('btn-spin');
                    // Display the error message on the page
                    let message = typeof response.data === 'object'
                    ? JSON.stringify(response.data)
                    : (response.data || 'Unknown error');
                    $('#api-response').html(
                        '<span style="color: red;">Error: ' + message + '</span>'
                    );
                }
            },
            error: function() {
                alert('AJAX request failed.');
            }
        });
    });


})( jQuery );
