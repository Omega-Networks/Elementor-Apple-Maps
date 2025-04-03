/**
 * Elementor Apple Maps - Admin Settings JavaScript
 */
(function($) {
    'use strict';

    // When the document is ready
    $(document).ready(function() {
        const privateKeyField = $('#token-gen-authkey');
        const keyIdField = $('#token-gen-kid');
        const teamIdField = $('#token-gen-iss');
        const statusElement = $('#mapkit-credentials-status');
        const submitButton = $('input[type="submit"]');

        // Function to test MapKit credentials
        function testMapKitCredentials() {
            const privateKey = privateKeyField.val();
            const keyId = keyIdField.val();
            const teamId = teamIdField.val();

            // Skip if any field is empty
            if (!privateKey || !keyId || !teamId) {
                return;
            }

            // Show loading state
            statusElement.removeClass('mapkit-valid mapkit-error');
            statusElement.addClass('mapkit-loading');
            statusElement.html('<span class="spinner is-active"></span> ' + 
                               '<span class="txt">' + ElementorAppleMapsAdmin.testing + '</span>');

            // Try to get a JWT token from the server
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'test_mapkit_credentials',
                    nonce: ElementorAppleMapsAdmin.nonce,
                    private_key: privateKey,
                    key_id: keyId,
                    team_id: teamId
                },
                success: function(response) {
                    if (response.success) {
                        // Successfully generated token
                        statusElement.removeClass('mapkit-loading mapkit-error');
                        statusElement.addClass('mapkit-valid');
                        statusElement.text(ElementorAppleMapsAdmin.authorized);
                        
                        // Try to initialize MapKit, but handle case where it's already initialized
                        if (typeof mapkit !== 'undefined') {
                            try {
                                // Check if already initialized
                                if (mapkit.authorized) {
                                    console.log('MapKit already authorized, skipping initialization');
                                } else {
                                    mapkit.init({
                                        authorizationCallback: function(done) {
                                            done(response.data);
                                        }
                                    });
                                }
                            } catch (e) {
                                console.log('Note about MapKit initialization:', e.message);
                                // Not a critical error, the token is still valid
                            }
                        }
                    } else {
                        // Error generating token
                        statusElement.removeClass('mapkit-loading mapkit-valid');
                        statusElement.addClass('mapkit-error');
                        statusElement.text(response.data || ElementorAppleMapsAdmin.invalid);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    statusElement.removeClass('mapkit-loading mapkit-valid');
                    statusElement.addClass('mapkit-error');
                    statusElement.text(errorThrown || ElementorAppleMapsAdmin.connectionError);
                }
            });
        }

        // Test credentials when all 3 fields have values and user stops typing for a moment
        let debounceTimer;
        $('.apple-maps-credential-field').on('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function() {
                if (privateKeyField.val() && keyIdField.val() && teamIdField.val()) {
                    testMapKitCredentials();
                }
            }, 1000);
        });

        // Add class to credential fields for input tracking
        privateKeyField.addClass('apple-maps-credential-field');
        keyIdField.addClass('apple-maps-credential-field');
        teamIdField.addClass('apple-maps-credential-field');

        // Check credentials status on page load
        if (privateKeyField.val() && keyIdField.val() && teamIdField.val()) {
            // Test credentials with a short delay to ensure page is fully loaded
            setTimeout(testMapKitCredentials, 500);
        }
        
        // Visual feedback when editing credentials
        $('.apple-maps-credential-field').on('focus', function() {
            if (statusElement.hasClass('mapkit-valid')) {
                statusElement.removeClass('mapkit-valid').addClass('mapkit-pending');
                statusElement.text(ElementorAppleMapsAdmin.pendingChanges);
            }
        });

        // Update form submission to show processing state
        $('form').on('submit', function() {
            submitButton.attr('disabled', true);
            submitButton.val(ElementorAppleMapsAdmin.saving);
        });
    });

})(jQuery);