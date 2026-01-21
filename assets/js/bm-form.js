/**
 * Bold Metrics Integration - Form Handling
 * Version: 0.3.0
 * Handles form submission, conditional fields, and results display
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        const $form = $('#bm-measurement-form');
        const $maleFields = $('#bm-male-fields');
        const $femaleFields = $('#bm-female-fields');
        const $message = $('#bm-form-message');
        const $resultsContainer = $('#bm-results-container');
        const $resultsContent = $('#bm-results-content');
        const $submitBtn = $form.find('.bm-submit-btn');
        const submitBtnText = $submitBtn.text();

        // Handle sex radio button change to show/hide conditional fields
        $('input[name="sex"]').on('change', function() {
            const selectedSex = $(this).val();

            if (selectedSex === 'male') {
                $maleFields.slideDown(200);
                $femaleFields.slideUp(200);
                // Make male fields required, female fields optional
                $('#bm-waist').prop('required', true);
                $('#bm-strap, #bm-cup').prop('required', false);
            } else if (selectedSex === 'female') {
                $femaleFields.slideDown(200);
                $maleFields.slideUp(200);
                // Make female fields required, male fields optional
                $('#bm-strap, #bm-cup').prop('required', true);
                $('#bm-waist').prop('required', false);
            }
        });

        // Handle form submission
        $form.on('submit', function(e) {
            e.preventDefault();

            // Clear previous messages and results
            $message.hide().removeClass('bm-error bm-success').text('');

            // Validate required fields
            const sex = $('input[name="sex"]:checked').val();
            if (!sex) {
                showError('Please select your sex.');
                return;
            }

            // Get and validate height
            const heightFt = parseInt($('#bm-height-ft').val(), 10);
            const heightIn = parseInt($('#bm-height-in').val(), 10);

            if (isNaN(heightFt) || heightFt < 3 || heightFt > 8) {
                showError('Please enter a valid height (feet).');
                $('#bm-height-ft').focus();
                return;
            }

            if (isNaN(heightIn) || heightIn < 0 || heightIn > 11) {
                showError('Please enter a valid height (inches must be 0-11).');
                $('#bm-height-in').focus();
                return;
            }

            // Convert feet + inches to total inches
            const totalHeightInches = (heightFt * 12) + heightIn;

            // Validate weight
            const weight = parseFloat($('#bm-weight').val());
            if (isNaN(weight) || weight < 50 || weight > 500) {
                showError('Please enter a valid weight (50-500 lbs).');
                $('#bm-weight').focus();
                return;
            }

            // Validate age
            const age = parseInt($('#bm-age').val(), 10);
            if (isNaN(age) || age < 18 || age > 100) {
                showError('Please enter a valid age (18-100).');
                $('#bm-age').focus();
                return;
            }

            // Build form data
            const formData = {
                weight: weight,
                height: totalHeightInches,
                age: age,
                sex: sex
            };

            // Add sex-specific fields
            if (sex === 'male') {
                const waist = parseFloat($('#bm-waist').val());
                if (isNaN(waist) || waist < 20 || waist > 60) {
                    showError('Please enter a valid waist size (20-60 inches).');
                    $('#bm-waist').focus();
                    return;
                }
                formData.waist = waist;
            } else if (sex === 'female') {
                const strap = $('#bm-strap').val();
                const cup = $('#bm-cup').val();
                if (!strap || !cup) {
                    showError('Please select both band and cup size.');
                    return;
                }
                formData.strap_size = strap;
                formData.cup_size = cup;
            }

            // Show loading state
            setLoading(true);

            // Make AJAX request
            $.ajax({
                url: bmData.restUrl,
                method: 'POST',
                data: formData,
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', bmData.nonce);
                }
            })
            .done(function(response) {
                if (response.success && response.result) {
                    displayResults(response.result);
                    $message.addClass('bm-success').text('Body measurements calculated successfully!').show();
                    // Smooth scroll to results
                    setTimeout(function() {
                        $('html, body').animate({
                            scrollTop: $resultsContainer.offset().top - 30
                        }, 400);
                    }, 100);
                } else {
                    showError('Unable to calculate body measurements. Please try again.');
                }
            })
            .fail(function(xhr) {
                let errorMessage = 'An error occurred. Please try again.';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    errorMessage = xhr.responseJSON.error;
                }
                showError(errorMessage);
            })
            .always(function() {
                setLoading(false);
            });
        });

        /**
         * Show error message
         */
        function showError(message) {
            $message.removeClass('bm-success').addClass('bm-error').text(message).show();
            // Scroll to message
            $('html, body').animate({
                scrollTop: $message.offset().top - 100
            }, 300);
        }

        /**
         * Set loading state on submit button
         */
        function setLoading(isLoading) {
            if (isLoading) {
                $submitBtn.prop('disabled', true).addClass('bm-loading').text('');
            } else {
                $submitBtn.prop('disabled', false).removeClass('bm-loading').text(submitBtnText);
            }
        }

        /**
         * Display Virtual Tailor API results in the results container
         */
        function displayResults(result) {
            let html = '';

            // Display outlier warning if present
            if (result.outlier === true) {
                html += '<div class="bm-outlier-warning">';
                html += '<p><strong>Warning:</strong> Some measurements may be inconsistent.</p>';
                if (result.outlier_messages && result.outlier_messages.specifics && result.outlier_messages.specifics.length > 0) {
                    html += '<ul>';
                    result.outlier_messages.specifics.forEach(function(msg) {
                        html += '<li>' + escapeHtml(msg.message || '') + '</li>';
                    });
                    html += '</ul>';
                }
                html += '</div>';
            }

            // Display accuracy message if present
            if (result.message && typeof result.message === 'string') {
                html += '<p class="bm-accuracy-note"><em>' + escapeHtml(result.message) + '</em></p>';
            }

            // Display body dimensions from Virtual Tailor
            if (result.dimensions && Object.keys(result.dimensions).length > 0) {
                html += '<div class="bm-predictions">';
                html += '<h4>Predicted Body Measurements</h4>';
                html += '<table class="bm-measurements"><tbody>';
                for (const key in result.dimensions) {
                    if (result.dimensions.hasOwnProperty(key)) {
                        const value = result.dimensions[key];
                        const numValue = parseFloat(value);
                        const formattedValue = !isNaN(numValue) ? numValue.toFixed(2) + ' in' : escapeHtml(value);
                        html += '<tr>';
                        html += '<td>' + escapeHtml(formatMeasurementName(key)) + '</td>';
                        html += '<td>' + formattedValue + '</td>';
                        html += '</tr>';
                    }
                }
                html += '</tbody></table>';
                html += '</div>';
            } else {
                html += '<p class="bm-no-matches">No measurements returned. Please check your inputs.</p>';
            }

            // Display customer input summary
            if (result.customer && Object.keys(result.customer).length > 0) {
                html += '<div class="bm-input-summary-section">';
                html += '<h4>Your Input Summary</h4>';
                html += '<table class="bm-measurements bm-input-summary"><tbody>';
                for (const key in result.customer) {
                    if (result.customer.hasOwnProperty(key)) {
                        const value = result.customer[key];
                        html += '<tr>';
                        html += '<td>' + escapeHtml(formatMeasurementName(key)) + '</td>';
                        html += '<td>' + escapeHtml(value) + '</td>';
                        html += '</tr>';
                    }
                }
                html += '</tbody></table>';
                html += '</div>';
            }

            $resultsContent.html(html);
            $resultsContainer.slideDown(300);
        }

        /**
         * Format measurement name for display
         */
        function formatMeasurementName(name) {
            return name
                .replace(/_/g, ' ')
                .replace(/\b\w/g, function(l) {
                    return l.toUpperCase();
                });
        }

        /**
         * Escape HTML to prevent XSS
         */
        function escapeHtml(text) {
            if (text === null || text === undefined) {
                return '';
            }
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
        }

        // Auto-advance from feet to inches field
        $('#bm-height-ft').on('input', function() {
            const val = $(this).val();
            if (val.length >= 1 && parseInt(val, 10) >= 3) {
                $('#bm-height-in').focus();
            }
        });

    });

})(jQuery);
