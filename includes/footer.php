<?php
/**
 * Shared Footer Layout Component
 */
require_once __DIR__ . '/config.php';
?>
</main>
<footer class="footer-section">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4 col-md-6 text-center text-md-start">
                <a href="<?php echo SITE_URL; ?>/index.php" class="d-inline-flex align-items-center gap-2 text-decoration-none text-white mb-2" aria-label="Karnataka Trekkers Home">
                    <picture>
                        <source srcset="<?php echo SITE_URL; ?>/assets/images/LOGO.webp" type="image/webp">
                        <img src="<?php echo SITE_URL; ?>/assets/images/LOGO.png" alt="Karnataka Trekkers Logo" width="46" height="46" style="width: 46px; height: 46px; object-fit: contain; aspect-ratio: 1/1; background-color: #ffffff; padding: 4px 8px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
                    </picture>
                    <span class="fs-4 fw-bold"><?php echo SITE_NAME; ?></span>
                </a>
                <p class="mt-3">We are Karnataka's premium trekking and adventure team. We specialize in safety-first eco-treks across the Western Ghats and sunrise day trips around Bengaluru.</p>
                <div class="footer-social-icons justify-content-center justify-content-md-start">
                    <a href="https://www.facebook.com/share/1c8UtnZW2C/?mibextid=wwXIfr" target="_blank" rel="noopener noreferrer" aria-label="Follow Karnataka Trekkers on Facebook"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg></a>
                    <a href="https://www.instagram.com/karnataka__trekkers?igsh=ejN1YTA1bTJneDJ5&utm_source=qr" target="_blank" rel="noopener noreferrer" aria-label="Follow Karnataka Trekkers on Instagram"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg></a>
                    <a href="#" aria-label="Follow Karnataka Trekkers on Twitter"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg></a>
                    <a href="https://youtube.com/@karnatakatrekkers.official?si=jdkkC6PwwKYdV_7M" target="_blank" rel="noopener noreferrer" aria-label="Subscribe to Karnataka Trekkers on YouTube"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg></a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-2 col-md-6 text-center text-md-start">
                <h4>Quick Links</h4>
                <ul class="footer-links">
                    <li><a href="<?php echo SITE_URL; ?>/">Home</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/about">About Us</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/treks">All Treks</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/blogs">Blogs</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/gallery">Photo Gallery</a></li>
                </ul>
            </div>

            <!-- Helpful Information -->
            <div class="col-lg-2 col-md-6 text-center text-md-start">
                <h4>Information</h4>
                <ul class="footer-links">
                    <li><a href="<?php echo SITE_URL; ?>/faq">FAQs</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/contact">Contact Support</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/terms">Terms & Conditions</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/privacy">Privacy Policy</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/cancellation">Refund & Cancellation</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/sitemap.xml" target="_blank">Sitemap</a></li>
                </ul>
            </div>

            <!-- Contact Support Address -->
            <div class="col-lg-4 col-md-6 text-center text-md-start">
                <h4>Contact Us</h4>
                <ul class="footer-links mt-3 text-white-50">
                    <li class="d-flex align-items-start justify-content-center justify-content-md-start gap-2 mb-3">
                        <i class="fas fa-map-marker-alt text-success mt-1"></i>
                        <span><?php echo CONTACT_ADDRESS; ?></span>
                    </li>
                    <li class="d-flex align-items-center justify-content-center justify-content-md-start gap-2 mb-3">
                        <i class="fas fa-phone-alt text-success"></i>
                        <span><?php echo CONTACT_PHONE; ?></span>
                    </li>
                    <li class="d-flex align-items-center justify-content-center justify-content-md-start gap-2 mb-3">
                        <i class="fas fa-envelope text-success"></i>
                        <span><?php echo CONTACT_EMAIL; ?></span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- SEO Popular Trek Destinations Links -->
        <?php
        try {
            $seo_db = Database::connect();
            $seo_treks = $seo_db->query("SELECT slug, title FROM treks WHERE status = 'Active' ORDER BY id DESC LIMIT 10")->fetchAll();
        } catch (Exception $e) {
            $seo_treks = [];
        }
        if (!empty($seo_treks)):
        ?>
        <div class="row pt-4 mt-4 border-top border-secondary border-opacity-25">
            <div class="col-12 text-center text-md-start">
                <span class="text-light fw-bold text-uppercase d-block mb-2" style="letter-spacing: 0.5px; font-size: 0.78rem; opacity: 0.85;">Popular Treks in Karnataka:</span>
                <div class="d-flex flex-wrap justify-content-center justify-content-md-start gap-2">
                    <?php foreach ($seo_treks as $st): ?>
                        <a href="<?php echo SITE_URL; ?>/treks/<?php echo $st['slug']; ?>" class="text-light text-decoration-none small py-1 px-2.5 rounded bg-white bg-opacity-10 hover-white" style="font-size: 0.82rem; transition: background 0.2s, color 0.2s;">
                            <?php echo htmlspecialchars($st['title']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Copyright -->
        <div class="row">
            <div class="col-12 text-center footer-bottom">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved. Developed with passion for adventure.</p>
            </div>
        </div>
    </div>
</footer>

<!-- JS dependencies: jQuery and Bootstrap Bundle (Deferred for Zero Main-Thread Blocking) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js" defer></script>
<script src="<?php echo SITE_URL; ?>/assets/js/bootstrap.bundle.min.js?v=5.3.3" defer></script>
<?php if (!empty($use_glightbox)): ?>
<script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js" defer></script>
<?php endif; ?>

<!-- Global App JS (Minified for Production) -->
<script src="<?php echo SITE_URL; ?>/assets/js/app.min.js?v=2.2.0" defer></script>

<?php
// Fetch active treks for callback modal preferred trek list
$footer_treks = [];
try {
    $db = Database::connect();
    $ft_stmt = $db->query("SELECT id, title FROM treks WHERE status = 'Active' ORDER BY title ASC");
    if ($ft_stmt) {
        $footer_treks = $ft_stmt->fetchAll();
    }
} catch (Exception $e) {
    // Ignored
}
?>

<!-- Floating WhatsApp Direct Chat Button -->
<?php 
$wa_phone = get_setting('contact_whatsapp', CONTACT_PHONE);
$clean_wa_phone = preg_replace('/[^0-9]/', '', $wa_phone);
if (strlen($clean_wa_phone) === 10) {
    $clean_wa_phone = '91' . $clean_wa_phone;
}
$wa_url = "https://wa.me/{$clean_wa_phone}?text=" . urlencode("Hi Karnataka Trekkers! I am interested in trekking packages and need more information.");
?>
<a href="<?php echo $wa_url; ?>" target="_blank" rel="noopener noreferrer" class="whatsapp-float-btn shadow-lg" aria-label="Chat on WhatsApp" title="Chat on WhatsApp">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2m.01 1.67c2.2 0 4.26.86 5.82 2.42a8.225 8.225 0 0 1 2.41 5.83c0 4.54-3.7 8.24-8.24 8.24-1.48 0-2.93-.4-4.2-1.15l-.3-.18-3.12.82.83-3.04-.2-.31a8.196 8.196 0 0 1-1.26-4.38c0-4.54 3.7-8.24 8.25-8.24m4.52 11.66c-.25-.13-1.47-.72-1.7-.81-.23-.08-.39-.13-.56.13-.17.25-.64.81-.79.97-.14.17-.29.19-.54.06-.25-.13-1.06-.39-2.02-1.25-.75-.67-1.26-1.5-1.41-1.75-.15-.25-.02-.39.11-.51.11-.11.25-.29.38-.44.13-.14.17-.25.25-.42.08-.17.04-.31-.02-.44s-.56-1.35-.77-1.85c-.2-.48-.41-.42-.56-.43h-.48c-.17 0-.44.06-.67.31-.23.25-.88.86-.88 2.1 0 1.24.9 2.44 1.03 2.61.13.17 1.78 2.72 4.31 3.81.6.26 1.07.42 1.44.54.61.19 1.16.17 1.6.1.49-.07 1.47-.6 1.68-1.18.21-.58.21-1.07.15-1.18-.07-.11-.22-.17-.47-.3"/></svg>
    <span class="whatsapp-text d-none d-md-inline">WhatsApp</span>
</a>

<!-- Quick Enquiry & Callback Request Modal (Auto-pops after 20 seconds) -->
<div class="modal fade callback-modal" id="callbackModal" tabindex="-1" aria-labelledby="callbackModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
            <div class="modal-header text-white" style="background: linear-gradient(135deg, #2D5A27 0%, #1e3d1a 100%);">
                <h5 class="modal-title fw-bold" id="callbackModalLabel"><i class="fas fa-hiking me-2"></i>Need Help Planning Your Trek?</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="p-3 pb-0">
                <!-- Instant WhatsApp Option -->
                <a href="<?php echo $wa_url; ?>" target="_blank" rel="noopener noreferrer" class="btn btn-success w-100 py-2.5 fw-bold d-flex align-items-center justify-content-center gap-2 shadow-sm" style="background-color: #25D366; border: none; border-radius: 10px; font-size: 1rem;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2m.01 1.67c2.2 0 4.26.86 5.82 2.42a8.225 8.225 0 0 1 2.41 5.83c0 4.54-3.7 8.24-8.24 8.24-1.48 0-2.93-.4-4.2-1.15l-.3-.18-3.12.82.83-3.04-.2-.31a8.196 8.196 0 0 1-1.26-4.38c0-4.54 3.7-8.24 8.25-8.24m4.52 11.66c-.25-.13-1.47-.72-1.7-.81-.23-.08-.39-.13-.56.13-.17.25-.64.81-.79.97-.14.17-.29.19-.54.06-.25-.13-1.06-.39-2.02-1.25-.75-.67-1.26-1.5-1.41-1.75-.15-.25-.02-.39.11-.51.11-.11.25-.29.38-.44.13-.14.17-.25.25-.42.08-.17.04-.31-.02-.44s-.56-1.35-.77-1.85c-.2-.48-.41-.42-.56-.43h-.48c-.17 0-.44.06-.67.31-.23.25-.88.86-.88 2.1 0 1.24.9 2.44 1.03 2.61.13.17 1.78 2.72 4.31 3.81.6.26 1.07.42 1.44.54.61.19 1.16.17 1.6.1.49-.07 1.47-.6 1.68-1.18.21-.58.21-1.07.15-1.18-.07-.11-.22-.17-.47-.3"/></svg>
                    <span>Chat Directly on WhatsApp</span>
                </a>
                
                <div class="d-flex align-items-center my-3">
                    <hr class="flex-grow-1 my-0 text-muted">
                    <span class="px-2 text-muted fw-bold" style="font-size: 0.75rem; letter-spacing: 0.5px;">OR REQUEST A CALL BACK</span>
                    <hr class="flex-grow-1 my-0 text-muted">
                </div>
            </div>

            <form id="callbackRequestForm" action="<?php echo SITE_URL; ?>/booking/submit-callback.php" method="POST">
                <div class="modal-body text-start pt-0">
                    <div class="alert alert-success d-none" id="callback_success_alert" role="alert"></div>
                    <div class="alert alert-danger d-none" id="callback_error_alert" role="alert"></div>
                    
                    <div class="mb-2.5">
                        <label class="form-label small text-muted fw-bold mb-1">Your Name *</label>
                        <input type="text" name="name" id="callback_name" class="form-control" placeholder="Enter your full name" required>
                    </div>
                    
                    <div class="mb-2.5">
                        <label class="form-label small text-muted fw-bold mb-1">Mobile Number *</label>
                        <input type="tel" name="phone" id="callback_phone" class="form-control" placeholder="10-digit mobile number" required inputmode="tel">
                        <small class="text-muted" style="font-size: 0.72rem;">Our trek expert will call or message you on WhatsApp</small>
                    </div>
                    
                    <div class="mb-2.5">
                        <label class="form-label small text-muted fw-bold mb-1">Interested Trek</label>
                        <select name="trek_id" id="callback_trek_id" class="form-select">
                            <option value="">-- Select Trek (Optional) --</option>
                            <?php foreach ($footer_treks as $ft): ?>
                                <option value="<?php echo $ft['id']; ?>" <?php echo (isset($trek_id) && $trek_id == $ft['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($ft['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="row g-2 mb-2.5">
                        <div class="col-6">
                            <label class="form-label small text-muted fw-bold mb-1">Planned Date</label>
                            <input type="date" name="preferred_date" id="callback_pref_date" class="form-control" min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label small text-muted fw-bold mb-1">No. of Persons</label>
                            <input type="number" name="participants" id="callback_participants" class="form-control" min="1" placeholder="e.g. 2">
                        </div>
                    </div>
                    
                    <div class="mb-1">
                        <label class="form-label small text-muted fw-bold mb-1">Questions / Message</label>
                        <textarea name="message" id="callback_message" class="form-control" rows="2" placeholder="Any special requests or doubts..."></textarea>
                    </div>
                </div>
                <div class="modal-footer pt-0 border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-warning px-4 fw-bold text-white shadow-sm" id="btn_submit_callback" style="background: linear-gradient(135deg, #FF6B00 0%, #E65100 100%); border: none; border-radius: 8px;">
                        <i class="fas fa-paper-plane me-1"></i>Request Call Back
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
window.addEventListener('DOMContentLoaded', function() {
    function initCallbackModal() {
        if (typeof jQuery === 'undefined' || typeof bootstrap === 'undefined') {
            setTimeout(initCallbackModal, 100);
            return;
        }
        var $ = jQuery;
        // Auto-show Callback / Enquiry Popup 20 seconds after user visits
        setTimeout(function() {
            if (!sessionStorage.getItem('kt_callback_dismissed') && !sessionStorage.getItem('kt_callback_submitted')) {
                var callbackModalEl = document.getElementById('callbackModal');
                if (callbackModalEl && typeof bootstrap !== 'undefined') {
                    var callbackModal = bootstrap.Modal.getOrCreateInstance(callbackModalEl);
                    callbackModal.show();
                }
            }
        }, 20000);

        // Save dismissal flag when modal is closed
        $('#callbackModal').on('hidden.bs.modal', function () {
            sessionStorage.setItem('kt_callback_dismissed', '1');
        });

        $('#callbackRequestForm').on('submit', function(e) {
            e.preventDefault();
            
            var form = $(this);
            var submitBtn = $('#btn_submit_callback');
            var successAlert = $('#callback_success_alert');
            var errorAlert = $('#callback_error_alert');
            
            // Client side phone validation
            var phoneInput = $('#callback_phone').val().replace(/[^0-9]/g, '');
            if (phoneInput.length === 11 && phoneInput.startsWith('0')) {
                phoneInput = phoneInput.substring(1);
            } else if (phoneInput.length === 12 && phoneInput.startsWith('91')) {
                phoneInput = phoneInput.substring(2);
            }
            if (phoneInput.length !== 10) {
                errorAlert.text('Please enter a valid 10-digit mobile number.').removeClass('d-none');
                successAlert.addClass('d-none');
                return;
            }
            
            submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Submitting...');
            
            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: form.serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        sessionStorage.setItem('kt_callback_submitted', '1');
                        successAlert.text(response.message).removeClass('d-none');
                        errorAlert.addClass('d-none');
                        form.find('input, select, textarea').not('[type="hidden"]').val('');
                        
                        // Reset submit button
                        submitBtn.html('<i class="fas fa-check me-1"></i>Submitted!').prop('disabled', true);
                        
                        // Close modal after a delay
                        setTimeout(function() {
                            var modalEl = document.getElementById('callbackModal');
                            var modalInstance = bootstrap.Modal.getInstance(modalEl);
                            if (modalInstance) {
                                modalInstance.hide();
                            }
                            successAlert.addClass('d-none');
                        }, 2500);
                    } else {
                        errorAlert.text(response.message).removeClass('d-none');
                        successAlert.addClass('d-none');
                        submitBtn.html('<i class="fas fa-paper-plane me-1"></i>Request Call Back').prop('disabled', false);
                    }
                },
                error: function() {
                    errorAlert.text('Error submitting request. Please try again.').removeClass('d-none');
                    successAlert.addClass('d-none');
                    submitBtn.html('<i class="fas fa-paper-plane me-1"></i>Request Call Back').prop('disabled', false);
                }
            });
        });
    }
    initCallbackModal();
});
</script>

<?php
// Inject page-specific JS scripts if defined
if (isset($extra_js) && is_array($extra_js)) {
    foreach ($extra_js as $js_file) {
        echo '<script src="' . SITE_URL . '/' . $js_file . '"></script>' . "\n";
    }
}
?>
</body>
</html>

