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
                        <img src="<?php echo SITE_URL; ?>/assets/images/LOGO.png" alt="Karnataka Trekkers Logo" width="46" height="46" style="max-height: 46px; width: auto; background-color: #ffffff; padding: 4px 8px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
                    </picture>
                    <span class="fs-4 fw-bold"><?php echo SITE_NAME; ?></span>
                </a>
                <p class="mt-3">We are Karnataka's premium trekking and adventure team. We specialize in safety-first eco-treks across the Western Ghats and sunrise day trips around Bengaluru.</p>
                <div class="footer-social-icons justify-content-center justify-content-md-start">
                    <a href="https://www.facebook.com/share/1c8UtnZW2C/?mibextid=wwXIfr" target="_blank" rel="noopener noreferrer" aria-label="Follow Karnataka Trekkers on Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://www.instagram.com/karnataka__trekkers?igsh=ejN1YTA1bTJneDJ5&utm_source=qr" target="_blank" rel="noopener noreferrer" aria-label="Follow Karnataka Trekkers on Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" aria-label="Follow Karnataka Trekkers on Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="https://youtube.com/@karnatakatrekkers.official?si=jdkkC6PwwKYdV_7M" target="_blank" rel="noopener noreferrer" aria-label="Subscribe to Karnataka Trekkers on YouTube"><i class="fab fa-youtube"></i></a>
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

<!-- Secondary Stylesheets for Below-the-Fold Content (Asynchronous Non-Blocking Load) -->
<link rel="preload" href="<?php echo SITE_URL; ?>/assets/css/bootstrap.min.css?v=5.3.3" as="style" onload="this.onload=null;this.rel='stylesheet'">
<link rel="preload" href="<?php echo SITE_URL; ?>/assets/css/style.css?v=2.1.0" as="style" onload="this.onload=null;this.rel='stylesheet'">
<link rel="preload" href="<?php echo SITE_URL; ?>/assets/css/responsive.css?v=2.1.0" as="style" onload="this.onload=null;this.rel='stylesheet'">
<link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
<link rel="preload" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/bootstrap.min.css?v=5.3.3">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css?v=2.1.0">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/responsive.css?v=2.1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css">
</noscript>

<!-- JS dependencies: jQuery and Bootstrap Bundle -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js" defer></script>
<script src="<?php echo SITE_URL; ?>/assets/js/bootstrap.bundle.min.js?v=5.3.3" defer></script>
<script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js" defer></script>

<!-- Global App JS -->
<script src="<?php echo SITE_URL; ?>/assets/js/app.js?v=2.1.0" defer></script>

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
    <i class="fab fa-whatsapp"></i>
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
                    <i class="fab fa-whatsapp fs-5"></i>
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

