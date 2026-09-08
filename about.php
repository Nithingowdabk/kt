<?php
/**
 * Karnataka Trekkers - About Page
 */
$page_title = "About Us";
require_once __DIR__ . '/includes/header.php';
?>

<!-- Header Banner -->
<section class="trek-detail-header-bg" style="background-image: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.7)), url('<?php echo SITE_URL; ?>/assets/images/hero-bg.jpg');">
    <div class="container text-center">
        <h1 class="text-white fw-bold mb-3">About Karnataka Trekkers</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb justify-content-center bg-transparent p-0 mb-0">
                <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/index.php" class="text-white-50 text-decoration-none">Home</a></li>
                <li class="breadcrumb-item text-white active" aria-current="page">About Us</li>
            </ol>
        </nav>
    </div>
</section>

<!-- Main Details -->
<section class="section-padding">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="text-success fw-bold text-uppercase" style="letter-spacing: 1px;">Who We Are</span>
                <h2 class="fw-bold mt-2 mb-4">Pioneers of Safe Eco-Trekking in Southern India</h2>
                <p>Founded in 2018, Karnataka Trekkers was born out of a deep love for the Western Ghats range and a mission to make remote trails accessible, safe, and clean. We provide fully-guided, end-to-end trekking experiences, bringing nature lovers closer to Karnataka's rich flora, fauna, and mountain peaks.</p>
                <p>Our trips are carefully planned with the local forest department inputs, ensuring we comply with all legal permits, entry passes, and conservation guidelines. We operate with a strict "Leave No Trace" mandate to preserve the delicate mountain ecology for future generations.</p>
                
                <div class="row g-3 mt-3">
                    <div class="col-6">
                        <div class="border-start border-success border-4 ps-3">
                            <h4 class="fw-bold text-success mb-1">50+</h4>
                            <p class="text-muted mb-0">Eco-conscious Guides</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="border-start border-success border-4 ps-3">
                            <h4 class="fw-bold text-success mb-1">100%</h4>
                            <p class="text-muted mb-0">Local Homestay Partnering</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <img src="<?php echo SITE_URL; ?>/assets/images/placeholder.jpg" class="img-fluid rounded-4 shadow" alt="Trekking Community Karnataka">
            </div>
        </div>
    </div>
</section>

<!-- Values Section -->
<section class="section-padding bg-light">
    <div class="container">
        <div class="section-title">
            <span>Our Core Values</span>
            <h2>What Drives Us Forward</h2>
        </div>
        <div class="row g-4 text-center">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm p-4 h-100">
                    <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 60px; height: 60px; font-size: 1.5rem;">
                        <i class="fas fa-hand-holding-heart"></i>
                    </div>
                    <h4 class="fw-bold">Safety First</h4>
                    <p class="text-muted mb-0">We never compromise on safety. From carrying satellite communication devices to maintaining medical kits and keeping emergency standby vehicles, your safety is our top concern.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm p-4 h-100">
                    <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 60px; height: 60px; font-size: 1.5rem;">
                        <i class="fas fa-globe-asia"></i>
                    </div>
                    <h4 class="fw-bold">Eco Conservation</h4>
                    <p class="text-muted mb-0">We respect the wilderness. We educate our trekkers on local fauna, follow zero-litter practices, and ban single-use water plastic bottles during high-slope climbs.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm p-4 h-100">
                    <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 60px; height: 60px; font-size: 1.5rem;">
                        <i class="fas fa-hands-helping"></i>
                    </div>
                    <h4 class="fw-bold">Community Support</h4>
                    <p class="text-muted mb-0">We work directly with local families, village committees, and homestays. This boosts rural livelihoods and ensures our trekkers experience authentic local hospitality and food.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
