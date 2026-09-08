/**
 * Karnataka Trekkers - Booking Flow JS
 * Enhanced with validation scroll, mobile step progress, and smooth UX
 */

$(document).ready(function() {
    // =========================================================================
    // 1. Dynamic Traveler Details Generator
    // =========================================================================
    $('#num_trekkers').on('change input', function() {
        var numTrekkers = parseInt($(this).val()) || 1;
        var trekPrice = parseFloat($('#trek_price').val()) || 0;
        
        // Update basic prices
        var subTotal = numTrekkers * trekPrice;
        $('#summary_subtotal').text('₹' + subTotal.toFixed(2));
        updateBookingSummary();

        // Generate input fields for other travelers (excluding primary user)
        var container = $('#other_travelers_container');
        container.empty();

        if (numTrekkers > 1) {
            container.append('<h5 class="mt-4 mb-3 text-success border-bottom pb-2"><i class="fas fa-users me-2"></i>Co-Trekkers Details</h5>');
            for (var i = 2; i <= numTrekkers; i++) {
                var rowHtml = `
                    <div class="card p-3 mb-3 border-light shadow-sm bg-light">
                        <div class="row g-3">
                            <div class="col-12">
                                <strong class="text-dark">Trekker #${i}</strong>
                            </div>
                            <div class="col-md-5 col-12">
                                <label class="form-label">Full Name *</label>
                                <input type="text" name="traveler_name[]" class="form-control" required placeholder="Trekker Name">
                            </div>
                            <div class="col-md-3 col-6">
                                <label class="form-label">Age *</label>
                                <input type="number" name="traveler_age[]" class="form-control" required min="5" max="100" placeholder="Age" inputmode="numeric">
                            </div>
                            <div class="col-md-4 col-6">
                                <label class="form-label">Gender *</label>
                                <select name="traveler_gender[]" class="form-select" required>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                    </div>
                `;
                container.append(rowHtml);
            }

            // Smooth scroll to co-trekker section on mobile
            if ($(window).width() < 768) {
                $('html, body').animate({
                    scrollTop: container.offset().top - 80
                }, 400);
            }
        }
    });

    // =========================================================================
    // 2. Apply Coupon Code (AJAX)
    // =========================================================================
    $('#btn_apply_coupon').on('click', function(e) {
        e.preventDefault();
        var couponCode = $('#coupon_code_input').val().trim();
        var subtotal = parseFloat($('#summary_subtotal').text().replace('₹', '').replace(',', '')) || 0;

        if (couponCode === '') {
            showCouponError('Please enter a coupon code.');
            return;
        }

        // Disable button while checking
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Applying...');

        // AJAX request to verify coupon code
        $.ajax({
            url: '../booking/checkout.php?action=apply_coupon',
            type: 'POST',
            data: { 
                coupon_code: couponCode,
                subtotal: subtotal
            },
            dataType: 'json',
            success: function(response) {
                btn.prop('disabled', false).text('Apply');
                
                if (response.success) {
                    $('#coupon_success_msg').text(response.message).removeClass('d-none');
                    $('#coupon_error_msg').addClass('d-none');
                    
                    // Set inputs
                    $('#discount_amount_val').val(response.discount_amount);
                    $('#applied_coupon_code').val(couponCode);
                    
                    // Display discount in summary
                    $('#summary_discount_row').removeClass('d-none');
                    $('#summary_discount').text('-₹' + response.discount_amount.toFixed(2));
                    
                    updateBookingSummary();

                    // Smooth scroll to payment summary on mobile
                    if ($(window).width() < 768) {
                        $('html, body').animate({
                            scrollTop: $('#summary_payable').offset().top - 100
                        }, 500);
                    }
                } else {
                    showCouponError(response.message);
                    
                    // Clear inputs
                    $('#discount_amount_val').val(0);
                    $('#applied_coupon_code').val('');
                    
                    $('#summary_discount_row').addClass('d-none');
                    updateBookingSummary();
                }
            },
            error: function() {
                btn.prop('disabled', false).text('Apply');
                showCouponError('Error applying coupon. Please try again.');
            }
        });
    });

    function showCouponError(msg) {
        $('#coupon_error_msg').text(msg).removeClass('d-none');
        $('#coupon_success_msg').addClass('d-none');

        // Shake animation for error feedback
        var $input = $('#coupon_code_input');
        $input.addClass('border-danger');
        setTimeout(function() { $input.removeClass('border-danger'); }, 2000);
    }

    // =========================================================================
    // 3. Update Overall Booking Summary
    // =========================================================================
    function updateBookingSummary() {
        var subtotalText = $('#summary_subtotal').text().replace('₹', '').replace(/,/g, '');
        var subtotal = parseFloat(subtotalText) || 0;
        var discount = parseFloat($('#discount_amount_val').val()) || 0;
        var payable = subtotal - discount;
        
        if (payable < 0) payable = 0;
        
        $('#summary_payable').text('₹' + payable.toFixed(2));
        $('#payable_amount_val').val(payable.toFixed(2));
    }

    // =========================================================================
    // 4. Filter Pickup Points based on Date Batch
    // =========================================================================
    if ($('#trek_date_id').length > 0 && $('#pickup_point_id').length > 0) {
        var originalPickupOptions = $('#pickup_point_id option').clone();
        
        $('#trek_date_id').on('change', function() {
            var selectedDateId = $(this).val();
            var pickupSelect = $('#pickup_point_id');
            
            pickupSelect.empty();
            
            originalPickupOptions.each(function() {
                var optionDateId = $(this).attr('data-date-id');
                if ($(this).val() === '' || optionDateId === undefined || optionDateId === '' || optionDateId === selectedDateId) {
                    pickupSelect.append($(this).clone());
                }
            });
            
            pickupSelect.val('');

            // Dynamic Price Updater based on batch price and package type
            var packageType = $('input[name="package_type"]:checked').val() || $('#package_type_hidden').val() || 'with_transport';
            var selectedOption = $(this).find('option:selected');
            var price = 0;

            if (packageType === 'without_transport') {
                price = parseFloat($('#without_transport_base').val()) || 0;
            } else {
                var datePrice = parseFloat(selectedOption.data('price'));
                if (!isNaN(datePrice) && datePrice > 0) {
                    price = datePrice;
                } else {
                    price = parseFloat($('#with_transport_base').val()) || 0;
                }
            }

            if (price > 0) {
                $('#trek_price').val(price);
                var formattedPrice = '₹' + price.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                $('#widget_active_price, #mobile_active_price').text(formattedPrice);
            }
            
            // Recalculate subtotal and payable
            $('#num_trekkers').trigger('change');
        });

        // Listen for package card option clicks to support mobile/touch devices reliably
        $(document).on('click', '.package-card-option', function(e) {
            if ($(e.target).is('input[type="radio"]')) {
                return;
            }
            e.preventDefault();
            var radio = $(this).find('input[name="package_type"]');
            if (radio.length > 0 && !radio.prop('checked')) {
                radio.prop('checked', true).trigger('change');
            }
        });

        // Listen for package type changes
        $(document).on('change', 'input[name="package_type"]', function() {
            var packageType = $(this).val();
            
            // Update pickup location requirement and display
            if (packageType === 'without_transport') {
                $('#pickup_location_wrapper').addClass('d-none');
                $('#pickup_point_id').removeAttr('required').val('');
                $('#own_transport_info_wrapper').removeClass('d-none');
                $('#with_transport_info_wrapper').addClass('d-none');
                
                // Highlight selected option card visually
                $('#pkg_card_without_transport').addClass('active').css({'border': '2px solid #28a745', 'background-color': '#f4fff6'});
                $('#pkg_card_with_transport').removeClass('active').css({'border': '1px solid #ced4da', 'background-color': ''});
            } else {
                $('#pickup_location_wrapper').removeClass('d-none');
                $('#pickup_point_id').attr('required', true);
                $('#own_transport_info_wrapper').addClass('d-none');
                $('#with_transport_info_wrapper').removeClass('d-none');
                
                // Highlight selected option card visually
                $('#pkg_card_with_transport').addClass('active').css({'border': '2px solid #28a745', 'background-color': '#f4fff6'});
                $('#pkg_card_without_transport').removeClass('active').css({'border': '1px solid #ced4da', 'background-color': ''});
            }
            
            // Trigger date change to update price and recalculate
            $('#trek_date_id').trigger('change');
        });

        setTimeout(function() {
            var checkedPkg = $('input[name="package_type"]:checked');
            if (checkedPkg.length > 0) {
                checkedPkg.trigger('change');
            }
        }, 100);

        // Quick batch select button handler
        $(document).on('click', '.select-batch-btn', function(e) {
            e.preventDefault();
            var dateId = $(this).data('date-id');
            $('#trek_date_id').val(dateId).trigger('change');
            
            $('html, body').animate({
                scrollTop: $('#booking-form-anchor').offset().top - 100
            }, 400);
        });
    }

    // =========================================================================
    // 5. Auto-scroll to First Validation Error on Form Submit
    // =========================================================================
    $('form').on('submit', function(e) {
        var $form = $(this);
        var firstInvalid = $form.find(':invalid').not('[type="hidden"]').first();
        
        if (firstInvalid.length > 0 && !firstInvalid[0].checkValidity()) {
            e.preventDefault();
            
            $('html, body').stop().animate({
                scrollTop: firstInvalid.offset().top - 120
            }, 400, function() {
                firstInvalid.focus();
                // Add visual feedback
                firstInvalid.addClass('border-danger');
                setTimeout(function() { firstInvalid.removeClass('border-danger'); }, 3000);
            });
        }
    });

    // =========================================================================
    // 6. Mobile-Friendly Number Input (for num_trekkers)
    // =========================================================================
    if ($('#num_trekkers').length > 0) {
        $('#num_trekkers').attr('inputmode', 'numeric');
    }
});
