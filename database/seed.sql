-- Seed data for Karnataka Trekkers

USE `karnataka_trekkers`;

-- Seed Admins (password: admin123)
INSERT INTO `admins` (`name`, `username`, `email`, `password_hash`, `role`) VALUES
('System Administrator', 'admin', 'admin@karnatakatrekkers.com', '$2y$10$iZkFm2K779kXG9M787GkNu9vG3yA9B8oD668p8aM8jE40u6o1o7uC', 'Super Admin');

-- Seed Users (password: user123)
INSERT INTO `users` (`name`, `email`, `password_hash`, `phone`, `address`, `status`, `email_verified`) VALUES
('Rahul Sharma', 'rahul@example.com', '$2y$10$0k3G3Q/k9E345lK2p7v4DeE4.Qe04b4q8jE40u6o1o7uC1zG789Y.', '9876543210', 'Bengaluru, Karnataka', 'Active', 1);

-- Seed Categories
INSERT INTO `trek_categories` (`name`, `slug`, `description`, `image`, `status`) VALUES
('Western Ghats', 'western-ghats', 'Explore the lush green rainforests, foggy peaks, and beautiful grasslands of Western Ghats in Karnataka.', 'western-ghats.jpg', 'Active'),
('One Day Treks', 'one-day-treks', 'Perfect weekend getaways near Bengaluru for quick adventure seekers.', 'one-day-treks.jpg', 'Active'),
('Night Treks', 'night-treks', 'Experience stargazing, night bonfires, and sunrise views above the clouds.', 'night-treks.jpg', 'Active');

-- Seed Treks
INSERT INTO `treks` (`category_id`, `title`, `slug`, `duration`, `difficulty`, `trek_distance`, `altitude`, `price`, `offer_price`, `description`, `itinerary`, `inclusions`, `exclusions`, `things_to_carry`, `pickup_points_txt`, `status`, `featured`, `meta_title`, `meta_description`) VALUES
(1, 'Kudremukh Trek', 'kudremukh-trek', '2 Days / 1 Night', 'Moderate', 22.00, 6207, 3499.00, 2999.00, 
'Kudremukh is a mountain range and name of a peak located in Chikkamagaluru district, in Karnataka, India. It is shaped like a horse\'s face and is the second-highest peak in Karnataka. The trek takes you through lush green shola forests, bamboo shrubs, and multiple water streams.',
'[{"day": 1, "title": "Departure & Homestay Arrival", "desc": "Depart from Bengaluru at 9:00 PM. Overnight journey in a pushback bus. Arrive at the homestay in Chikkamagaluru by 6:00 AM, freshen up, and enjoy a traditional breakfast."}, {"day": 2, "title": "The Trek & Return", "desc": "Reach the forest office by 8:00 AM. Start trekking through misty paths. Reach the peak by 12:30 PM, enjoy packed lunch. Descend back to the base, return to homestay, have dinner, and depart to Bengaluru."}]',
'Transport (To & Fro Bengaluru), Homestay accommodation (shared), 2 Breakfasts, 1 Lunch, 1 Dinner, Forest Permitting Fees, Guide Charges, Campfire',
'Personal expenses, Meals during transit, Anything not mentioned in inclusions',
'Backpack, Hiking shoes with good grip, Raincoat/Poncho, 2 liters of water bottle, Personal medication, Warm clothes',
'Majestic Metro Station (9:00 PM), Goraguntepalya Metro Station (9:30 PM)',
'Active', 1, 'Kudremukh Trek Booking | Karnataka Trekkers', 'Book Kudremukh trek online at best prices. Experience the horse-faced peak in Chikkamagaluru with homestay, guide, and meals included.'),

(1, 'Kumara Parvatha Trek', 'kumara-parvatha-trek', '2 Days / 2 Nights', 'Difficult', 28.00, 5617, 3999.00, 3699.00,
'Kumara Parvatha, also known as Pushpagiri, is the most challenging trek in Karnataka. Located in Coorg district, it offers steep climbs, dense forests, and high winds, but the view from the top makes every drop of sweat worth it.',
'[{"day": 1, "title": "Bengaluru to Kukke Subramanya", "desc": "Overnight journey starting at 10:00 PM from Bengaluru towards Kukke Subramanya."}, {"day": 2, "title": "The Ascent to Bhatt\'s House & Peak", "desc": "Reach Kukke, freshen up. Start trekking by 6:00 AM. Stop at Bhatt\'s house for breakfast. Continue towards the peak. Trek down back to Bhatt\'s house for night stay/camping."}, {"day": 3, "title": "Descent & Departure", "desc": "Trek down to base, visit Kukke temple, have lunch, and start return journey to Bengaluru."}]',
'Transport, Tents/Homestay, Forest Permissions, 2 Breakfasts, 1 Lunch, 1 Dinner, Experienced Trek Lead',
'Portage charges, Personal items, Dinner on Day 3',
'Trekking pole, Flashlight with extra batteries, Energy bars, Toiletries, Insect repellent',
'Keny Metro Station (9:30 PM), Yeshwanthpur (10:00 PM)',
'Active', 1, 'Kumara Parvatha Trek | Pushpagiri Trekking Guide', 'Challenge yourself with Kumara Parvatha trekking. Get complete itinerary, inclusions, exclusion, and trek details.'),

(2, 'Skandagiri Sunrise Trek', 'skandagiri-sunrise-trek', '1 Day', 'Easy', 8.00, 4757, 1499.00, 1199.00,
'Skandagiri, also known as Kalavara Durga, is an ancient mountain fortress located near Chikballapur. Famous for its spectacular sunrise views above a blanket of clouds, it is a perfect trek for beginners.',
'[{"day": 1, "title": "Midnight Journey & Sunrise Ascent", "desc": "Depart from Bengaluru at 11:00 PM. Reach base by 1:30 AM. Start trekking with forest guides. Reach peak by 5:00 AM, wait for sunrise. Descend at 7:30 AM, have breakfast on the way back, and reach Bengaluru by 12:00 PM."}]',
'To and Fro transportation, Forest entry permission, Guide fees, Breakfast',
'Any personal expense, water bottles',
'Warm jacket, Torch light (compulsory), ID proof, Water, Comfortable shoes',
'Domlur (11:00 PM), MG Road (11:15 PM), Hebbal (11:45 PM)',
'Active', 0, 'Skandagiri Sunrise Trek Booking | Near Bangalore', 'Book Skandagiri sunrise trek online. Enjoy walking in clouds and sunrise at the historic Kalavara Durga hill.');

-- Seed Trek Dates
INSERT INTO `trek_dates` (`trek_id`, `start_date`, `end_date`, `available_slots`, `status`) VALUES
(1, '2026-06-06', '2026-06-07', 20, 'Active'),
(1, '2026-06-13', '2026-06-14', 25, 'Active'),
(2, '2026-06-06', '2026-06-08', 15, 'Active'),
(2, '2026-06-20', '2026-06-22', 20, 'Active'),
(3, '2026-06-07', '2026-06-07', 40, 'Active'),
(3, '2026-06-14', '2026-06-14', 50, 'Active');

-- Seed Pickup Points
INSERT INTO `pickup_points` (`trek_id`, `time`, `location`, `landmark`) VALUES
(1, '21:00:00', 'Majestic Metro Station', 'Near KSRTC Bus Stand'),
(1, '21:30:00', 'Goraguntepalya Metro Station', 'Opposite People\'s Tree Hospital'),
(2, '21:30:00', 'Keny Metro Station', 'Next to Main Gate'),
(2, '22:00:00', 'Yeshwanthpur Metro Station', 'Near Platform 1 Exit'),
(3, '23:00:00', 'Domlur petrol bunk', 'Near flyover'),
(3, '23:30:00', 'Hebbal Outer Ring Road', 'Near Hebbal Flyover');

-- Seed Coupons
INSERT INTO `coupons` (`code`, `discount_type`, `discount_value`, `min_booking_amount`, `expiry_date`, `max_uses`, `current_uses`, `status`) VALUES
('TREK10', 'Percentage', 10.00, 2000.00, '2026-12-31', 100, 0, 'Active'),
('WELCOME500', 'Fixed', 500.00, 3000.00, '2026-12-31', 50, 0, 'Active');

-- Seed Blogs
INSERT INTO `blogs` (`title`, `slug`, `content`, `image`, `author`, `status`, `meta_title`, `meta_description`) VALUES
('Top 5 Monsoon Treks in Karnataka', 'top-5-monsoon-treks-karnataka', 
'<p>Monsoon in Karnataka transforms the Western Ghats into a lush green paradise. Here are the top 5 treks you should not miss this rainy season:</p><ul><li><strong>Kudremukh:</strong> The rolling green hills are a sight to behold.</li><li><strong>Kumara Parvatha:</strong> Adventurous and misty pathways.</li><li><strong>Netravati Peak:</strong> Known for its gentle flow of waterfalls and ridge walk.</li><li><strong>Kodachadri:</strong> Historical peak with the beautiful Hidlumane waterfalls.</li><li><strong>Tadiandamol:</strong> Coorg\'s highest peak offering spectacular views.</li></ul><p>Always ensure you trek with a certified organizer, wear high-grip shoes, carry rain gear, and check forest department rules.</p>', 
'monsoon-treks.jpg', 'Admin', 'Active', 'Top 5 Monsoon Treks in Karnataka | Monsoon Trekking Guide', 'Read our curated list of top 5 monsoon treks in Karnataka. Find tips, guidelines, and packing recommendations.'),
('Trekking Safety: Essential Tips for Beginners', 'trekking-safety-beginners-tips',
'<p>If you are planning your first trek, safety should be your top priority. Trekking can be an incredible experience, but lack of preparation can lead to accidents or fatigue.</p><p>Here are some key safety tips for absolute beginners:</p><ol><li><strong>Start small:</strong> Don\'t select a hard trek like Kumara Parvatha as your first choice. Go for Skandagiri or Savandurga.</li><li><strong>Stay hydrated:</strong> Drink water regularly, even if you do not feel thirsty. Carry electrolytes.</li><li><strong>Wear appropriate shoes:</strong> Normal gym shoes might slip on muddy or wet rocks. Invest in trekking shoes.</li><li><strong>Listen to your guide:</strong> Forest paths can be confusing. Never wander away from the group.</li></ol>',
'trekking-safety.jpg', 'Trek Lead Suresh', 'Active', 'Trekking Safety Tips for Beginners | Karnataka Trekkers', 'Learn essential safety tips for trekking in Karnataka. Get insights on footwear, hydration, and preparation.');

-- Seed Reviews
INSERT INTO `reviews` (`user_id`, `trek_id`, `name`, `rating`, `comment`, `status`) VALUES
(1, 1, 'Rahul Sharma', 5, 'Kudremukh trek with Karnataka Trekkers was an amazing experience! The homestay was clean and the food was delicious. Guide Suresh was very knowledgeable.', 'Approved'),
(NULL, 1, 'Amit Patel', 4, 'Awesome views and well organized. Only issue was a slight delay in pickup, but overall very good.', 'Approved');
