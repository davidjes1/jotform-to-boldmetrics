/**
 * Bold Metrics Integration - Form Handling
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

        // Handle sex radio button change to show/hide conditional fields
        $('input[name="sex"]').on('change', function() {
            const selectedSex = $(this).val();

            if (selectedSex === 'male') {
                $maleFields.slideDown();
                $femaleFields.slideUp();
                // Make male fields required, female fields optional
                $('#bm-waist').attr('required', true);
                $('#bm-strap, #bm-cup').attr('required', false);
            } else if (selectedSex === 'female') {
                $femaleFields.slideDown();
                $maleFields.slideUp();
                // Make female fields required, male fields optional
                $('#bm-strap, #bm-cup').attr('required', true);
                $('#bm-waist').attr('required', false);
            }
        });

        // Handle form submission
        $form.on('submit', function(e) {
            e.preventDefault();

            // Clear previous messages and results
            $message.hide().removeClass('bm-error bm-success');
            $resultsContainer.hide();

            // Get form data
            const formData = {
                weight: $('#bm-weight').val(),
                height: $('#bm-height').val(),
                age: $('#bm-age').val(),
                sex: $('input[name="sex"]:checked').val()
            };

            // Add sex-specific fields
            if (formData.sex === 'male') {
                formData.waist = $('#bm-waist').val();
            } else if (formData.sex === 'female') {
                formData.strap_size = $('#bm-strap').val();
                formData.cup_size = $('#bm-cup').val();
            }

            // Disable submit button
            const $submitBtn = $form.find('.bm-submit-btn');
            $submitBtn.prop('disabled', true).text('Processing...');

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
                    $message.addClass('bm-success').text('Size recommendations calculated successfully!').show();
                    // Scroll to results
                    $('html, body').animate({
                        scrollTop: $resultsContainer.offset().top - 20
                    }, 500);
                } else {
                    $message.addClass('bm-error').text('An error occurred while processing your request.').show();
                }
            })
            .fail(function(xhr) {
                let errorMessage = 'An error occurred. Please try again.';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    errorMessage = xhr.responseJSON.error;
                }
                $message.addClass('bm-error').text(errorMessage).show();
            })
            .always(function() {
                // Re-enable submit button
                $submitBtn.prop('disabled', false).text('Get My Size Recommendations');
            });
        });

        /**
         * Display API results in the results container
         */
        function displayResults(result) {
            let html = '';

            // Display good matches
            if (result.good_matches && result.good_matches.length > 0) {
                html += '<div class="bm-good-matches">';
                html += '<h4>Recommended Sizes</h4>';
                html += '<ul class="bm-size-list">';
                result.good_matches.forEach(function(match) {
                    const displayTitle = match.brand_size || match.size || 'Unknown Size';
                    const fitScore = match.fit_score ? ' (Fit Score: ' + match.fit_score + ')' : '';
                    html += '<li>' + escapeHtml(displayTitle + fitScore) + '</li>';
                });
                html += '</ul>';
                html += '</div>';
            } else {
                html += '<p class="bm-no-matches">No size recommendations available.</p>';
            }

            // Display predicted measurements
            if (result.predictions && Object.keys(result.predictions).length > 0) {
                html += '<div class="bm-predictions">';
                html += '<h4>Predicted Measurements</h4>';
                html += '<table class="bm-measurements"><tbody>';
                for (const key in result.predictions) {
                    if (result.predictions.hasOwnProperty(key)) {
                        html += '<tr>';
                        html += '<td>' + escapeHtml(formatMeasurementName(key)) + '</td>';
                        html += '<td>' + escapeHtml(result.predictions[key]) + '</td>';
                        html += '</tr>';
                    }
                }
                html += '</tbody></table>';
                html += '</div>';
            }

            $resultsContent.html(html);
            $resultsContainer.slideDown();
        }

        /**
         * Format measurement name for display
         */
        function formatMeasurementName(name) {
            return name.replace(/_/g, ' ').replace(/\b\w/g, function(l) {
                return l.toUpperCase();
            });
        }

        /**
         * Escape HTML to prevent XSS
         */
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
        }
    });

})(jQuery);
