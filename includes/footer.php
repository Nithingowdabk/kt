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
            $seo_treks = $seo_db->query("SELECT slug, title FROM treks WHERE status = 'Active' AND (is_indexed = 1 OR is_indexed IS NULL) ORDER BY id DESC LIMIT 10")->fetchAll();
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

        <!-- Copyright & Powered By Credits -->
        <div class="row">
            <div class="col-12 text-center footer-bottom">
                <div class="d-flex flex-column align-items-center justify-content-center gap-2">
                    <p class="mb-0 text-white-50 small">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved. Developed with passion for adventure.</p>
                    <div class="powered-by-container mt-1">
                        <a href="https://naitrons.in" target="_blank" rel="noopener noreferrer" class="naitrons-badge" aria-label="Powered by Naitrons">
                            <span class="naitrons-badge-prefix">Powered by</span>
                            <span class="naitrons-badge-brand">
                                <svg class="naitrons-icon" width="13" height="13" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path d="M12 2L14.85 9.15L22 12L14.85 14.85L12 22L9.15 14.85L2 12L9.15 9.15L12 2Z"/>
                                </svg>
                                Naitrons
                            </span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>

<style>
/* Footer safe-area padding & aesthetic badge */
.footer-section {
    padding-bottom: calc(25px + env(safe-area-inset-bottom, 0px));
}
.footer-bottom {
    padding-bottom: 15px;
}
.whatsapp-float-btn {
    bottom: calc(20px + env(safe-area-inset-bottom, 0px)) !important;
}
body:has(.fixed-bottom-booking-bar) .whatsapp-float-btn,
body:has(.mobile-bottom-cta) .whatsapp-float-btn {
    bottom: calc(85px + env(safe-area-inset-bottom, 0px)) !important;
}
.powered-by-container {
    display: inline-flex;
    align-items: center;
}
.naitrons-badge {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 6px 16px;
    background: linear-gradient(135deg, #F8F3EC 0%, #EFE5D8 100%);
    border: 1px solid #DFCDBD;
    border-radius: 999px;
    text-decoration: none !important;
    color: #382216 !important;
    font-family: var(--font-heading), -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    font-size: 0.78rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12), inset 0 1px 0 rgba(255, 255, 255, 0.7);
    transition: all 0.28s cubic-bezier(0.16, 1, 0.3, 1);
    line-height: 1;
}
.naitrons-badge:hover {
    background: linear-gradient(135deg, #FFFDF9 0%, #F5ECE0 100%);
    border-color: #D3BCAB;
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(56, 34, 22, 0.22), inset 0 1px 0 rgba(255, 255, 255, 0.9);
    color: #24140B !important;
}
.naitrons-badge-prefix {
    color: #6E4E3A;
    font-weight: 500;
    font-size: 0.72rem;
    letter-spacing: 0.3px;
    opacity: 0.9;
}
.naitrons-badge-brand {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    color: #2E1B10;
    font-weight: 700;
    font-size: 0.82rem;
    letter-spacing: -0.2px;
}
.naitrons-icon {
    color: #8C5B3E;
    flex-shrink: 0;
    transition: transform 0.3s ease;
}
.naitrons-badge:hover .naitrons-icon {
    transform: rotate(45deg) scale(1.15);
    color: #5A3520;
}
@media (max-width: 767.98px) {
    .naitrons-badge {
        font-size: 0.75rem;
        padding: 5px 12px;
    }
}
</style>

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
    <svg width="24" height="24" viewBox="0 0 175.216 175.552" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" style="flex-shrink: 0;">
        <path fill="#ffffff" d="m12.966 161.238 10.439-38.114a73.42 73.42 0 0 1-9.821-36.772c.017-40.556 33.021-73.55 73.578-73.55 19.681.01 38.154 7.669 52.047 21.572s21.537 32.383 21.53 52.037c-.018 40.553-33.027 73.553-73.578 73.553h-.032c-12.313-.005-24.412-3.094-35.159-8.954z"/>
        <path fill="#25D366" d="M87.184 25.227c-33.733 0-61.166 27.423-61.178 61.13a60.98 60.98 0 0 0 9.349 32.535l1.455 2.313-6.179 22.558 23.146-6.069 2.235 1.324c9.387 5.571 20.15 8.517 31.126 8.523h.023c33.707 0 61.14-27.426 61.153-61.135a60.75 60.75 0 0 0-17.895-43.251 60.75 60.75 0 0 0-43.235-17.928z"/>
        <path fill="#ffffff" fill-rule="evenodd" d="M68.772 55.603c-1.378-3.061-2.828-3.123-4.137-3.176l-3.524-.043c-1.226 0-3.218.46-4.902 2.3s-6.435 6.287-6.435 15.332 6.588 17.785 7.506 19.013 12.718 20.381 31.405 27.75c15.529 6.124 18.689 4.906 22.061 4.6s10.877-4.447 12.408-8.74 1.532-7.971 1.073-8.74-1.685-1.226-3.525-2.146-10.877-5.367-12.562-5.981-2.91-.919-4.137.921-4.746 5.979-5.819 7.206-2.144 1.381-3.984.462-7.76-2.861-14.784-9.124c-5.465-4.873-9.154-10.891-10.228-12.73s-.114-2.835.808-3.751c.825-.824 1.838-2.147 2.759-3.22s1.224-1.84 1.836-3.065.307-2.301-.153-3.22-4.032-10.011-5.666-13.647"/>
    </svg>
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
                    <svg width="24" height="24" viewBox="0 0 175.216 175.552" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" style="flex-shrink: 0;">
                        <path fill="#ffffff" d="m12.966 161.238 10.439-38.114a73.42 73.42 0 0 1-9.821-36.772c.017-40.556 33.021-73.55 73.578-73.55 19.681.01 38.154 7.669 52.047 21.572s21.537 32.383 21.53 52.037c-.018 40.553-33.027 73.553-73.578 73.553h-.032c-12.313-.005-24.412-3.094-35.159-8.954z"/>
                        <path fill="#25D366" d="M87.184 25.227c-33.733 0-61.166 27.423-61.178 61.13a60.98 60.98 0 0 0 9.349 32.535l1.455 2.313-6.179 22.558 23.146-6.069 2.235 1.324c9.387 5.571 20.15 8.517 31.126 8.523h.023c33.707 0 61.14-27.426 61.153-61.135a60.75 60.75 0 0 0-17.895-43.251 60.75 60.75 0 0 0-43.235-17.928z"/>
                        <path fill="#ffffff" fill-rule="evenodd" d="M68.772 55.603c-1.378-3.061-2.828-3.123-4.137-3.176l-3.524-.043c-1.226 0-3.218.46-4.902 2.3s-6.435 6.287-6.435 15.332 6.588 17.785 7.506 19.013 12.718 20.381 31.405 27.75c15.529 6.124 18.689 4.906 22.061 4.6s10.877-4.447 12.408-8.74 1.532-7.971 1.073-8.74-1.685-1.226-3.525-2.146-10.877-5.367-12.562-5.981-2.91-.919-4.137.921-4.746 5.979-5.819 7.206-2.144 1.381-3.984.462-7.76-2.861-14.784-9.124c-5.465-4.873-9.154-10.891-10.228-12.73s-.114-2.835.808-3.751c.825-.824 1.838-2.147 2.759-3.22s1.224-1.84 1.836-3.065.307-2.301-.153-3.22-4.032-10.011-5.666-13.647"/>
                    </svg>
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
        if (!window.jQuery || !window.bootstrap) {
            setTimeout(initCallbackModal, 100);
            return;
        }
        var $ = jQuery;
        // Auto-show Callback / Enquiry Popup 20 seconds after user visits
        setTimeout(function() {
            if (!sessionStorage.getItem('kt_callback_dismissed') && !sessionStorage.getItem('kt_callback_submitted')) {
                var callbackModalEl = document.getElementById('callbackModal');
                if (callbackModalEl && window.bootstrap) {
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
        echo '<script src="' . SITE_URL . '/' . $js_file . '" defer></script>' . "\n";
    }
}
?>
</body>
</html>

