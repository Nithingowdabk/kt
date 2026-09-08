<?php
/**
 * Reusable Auto-Scrolling Experience Highlights Strip
 */

// Determine highlights (Force static default highlights only)
$highlights_data = [
    ['title' => 'Trek Leader', 'icon' => 'fas fa-user-shield'],
    ['title' => 'Campfire', 'icon' => 'fas fa-fire'],
    ['title' => 'Waterfall Visit', 'icon' => 'fas fa-tint'],
    ['title' => 'Sunrise Point', 'icon' => 'fas fa-sun'],
    ['title' => 'Sunset Point', 'icon' => 'fas fa-mountain'],
    ['title' => 'Tea & Snacks', 'icon' => 'fas fa-coffee'],
    ['title' => 'Zipline', 'icon' => 'fas fa-wind'],
    ['title' => 'Rock Climbing', 'icon' => 'fas fa-hiking'],
    ['title' => 'Camping', 'icon' => 'fas fa-campground'],
    ['title' => 'Meals Included', 'icon' => 'fas fa-utensils'],
    ['title' => 'Transportation', 'icon' => 'fas fa-bus'],
    ['title' => 'Forest Permit', 'icon' => 'fas fa-file-contract'],
    ['title' => 'Photography', 'icon' => 'fas fa-camera'],
    ['title' => 'Homestay', 'icon' => 'fas fa-home'],
    ['title' => 'Guide', 'icon' => 'fas fa-route']
];

// Duplicate items once to ensure a seamless infinite scrolling track
$marquee_items = array_merge($highlights_data, $highlights_data);
?>
<section class="highlights-marquee-section border-bottom shadow-sm" aria-label="Trekking highlights and amenities">
    <div class="highlights-marquee-container">
        <div class="highlights-marquee-track">
            <?php foreach ($marquee_items as $item): ?>
                <div class="highlight-marquee-card">
                    <div class="highlight-marquee-icon">
                        <i class="<?php echo htmlspecialchars($item['icon']); ?>"></i>
                    </div>
                    <span class="highlight-marquee-title"><?php echo htmlspecialchars($item['title']); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
