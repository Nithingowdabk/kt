# Karnataka Trekkers - Trekking Website Management System

A complete Trekking Website Management System built using PHP 8+ and MySQL, styled with Bootstrap 5, FontAwesome, Google Fonts, jQuery, and custom CSS. It includes user and admin portals, online booking calculations, coupons validation, automated WhatsApp confirmations logs, invoice generators, reviews approvals, and simulated payment gateway loaders.

---

## Features

1. **Responsive Frontend Catalog**: Immersive green/earth forest themed homepage, listings, search filter bars, and interactive specifications grids.
2. **Dynamic Itinerary & Tabs**: Multi-day itinerary accordion loops, inclusions/exclusions tabs, and star reviews.
3. **Checkout Calculations & Coupons**: Real-time pricing aggregation, co-trekker repeating panels, and AJAX coupon code validator (TREK10, WELCOME500).
4. **Simulated Payment Integrations**: Integrates with a mock Razorpay secure checkout dialog which updates database parameters and decrements available slots on successful transactions.
5. **PDF/Print Invoices**: Clean ticket template formatting for printing or saving receipts.
6. **WhatsApp Notifications**: Automatically logs outbound notifications to `whatsapp/log.txt` on booking confirmation.
7. **Professional Admin Console**: Complete stats metrics dashboards (Revenues, bookings count, registrations, and destination totals) and full CRUD tables for treks, reviews, coupons, blogs, media gallery, and general settings.
8. **SEO-Friendly URLs**: Preconfigured `.htaccess` rules for routing clear path endpoints.

---

## Directory Structure

```text
karnatakatrekkers/
├── index.php                # Homepage
├── about.php                # About page
├── contact.php              # Contact form & database entries
├── gallery.php              # Photo filters gallery
├── blogs.php                # Blogs list & details routing
├── faq.php                  # Accordions question guides
├── login.php                # User login page
├── register.php             # User registration
├── logout.php               # Ends sessions
│
├── treks/
│   ├── index.php            # Catalog page with filter sidebars
│   ├── details.php          # Dynamic details, itineraries, specs & widget booking
│   ├── category.php         # Treks by category slug
│   └── search.php           # Search query matched grid
│
├── booking/
│   ├── create.php           # traveler details forms
│   ├── checkout.php         # review page & coupon AJAX checks
│   ├── payment.php          # Razorpay order creator & simulated modal popup
│   ├── success.php          # success captures, slot updates, WhatsApp alert dispatches
│   ├── failed.php           # failure alerts & guides
│   └── invoice.php          # printable ticket template
│
├── user/
│   ├── dashboard.php        # profile summary dashboard
│   ├── bookings.php         # user bookings table
│   ├── profile.php          # update contact info
│   ├── wishlist.php         # saved treks list
│   └── settings.php         # password update settings
│
├── admin/
│   ├── login.php            # secure portal admin auth
│   ├── dashboard.php        # admin overview stats & bookings
│   ├── treks/               # treks CRUD (add, edit, manage, delete)
│   ├── bookings/            # bookings status filters inline
│   ├── users/               # registered customer listings
│   ├── blogs/               # write/edit guides
│   ├── gallery/             # media files uploads
│   ├── coupons/             # discount coupons creations
│   ├── reviews/             # approve/reject reviews moderation
│   └── settings/            # general, payment & SEO dashboards
│
├── includes/
│   ├── config.php           # global definitions
│   ├── database.php         # PDO helper connection
│   ├── functions.php        # sanitizers, formatters, and dispatchers
│   ├── auth.php             # sessions validators
│   ├── header.php           # HTML opening elements, CDNs imports
│   ├── footer.php           # JS scripts imports, copyrights
│   ├── navbar.php           # website navigation header
│   └── sidebar.php          # admin console sidebar
│
├── assets/
│   ├── css/                 # style.css, admin.css, responsive.css
│   ├── js/                  # app.js, booking.js, admin.js
│   └── images/              # photo assets folder
│
├── payment/
│   ├── razorpay.php         # SDK orders generator placeholder
│   ├── callback.php         # redirect verify
│   └── webhook.php          # server-to-server captures listener
│
├── whatsapp/
│   └── send.php             # CLI & API trigger controller
│
├── database/
│   ├── schema.sql           # MySQL tables structure definitions
│   └── seed.sql             # mock base seed records
│
├── .htaccess                # URL rewriting directives
└── README.md                # this file
```

---

## Installation & Setup

### 1. Database Configuration
1. Open your local MySQL database panel (e.g. PHPMyAdmin).
2. Create a database named `karnataka_trekkers`.
3. Import the file `database/schema.sql` first, followed by `database/seed.sql` to populate default categories, treks, coupons, and administrative credentials.
4. Verify database configurations inside `includes/config.php` constants. If you are using a different MySQL username/password, update `DB_USER` and `DB_PASS`.

### 2. Launch Local Server
1. Move the `KarnatakaTrekkers` directory directly under your local web server docroot (e.g. `C:\xampp\htdocs\karnatakatrekkers`).
2. Start Apache and MySQL modules.
3. Access the website at: `http://localhost/karnatakatrekkers/index.php`.

### 3. Portal Credentials
- **Admin Portal**: Access via `http://localhost/karnatakatrekkers/admin/login.php`
  - **Username**: `admin`
  - **Password**: `admin123`
- **User Portal**: Access via `http://localhost/karnatakatrekkers/login.php`
  - **Email**: `rahul@example.com`
  - **Password**: `user123`

---

## How to Test Booking Flow

1. Access the catalog page at `http://localhost/karnatakatrekkers/treks/index.php`.
2. Click on details of **Kudremukh Trek**.
3. Select a date batch from the interactive booking widget, choose your pick-up point, and click **Proceed Booking**.
4. Log in using the User credentials (if not already logged in).
5. Enter co-trekkers details, then apply coupon `TREK10` or `WELCOME500` to verify discounts.
6. Click **Pay Now with Razorpay**. The secure simulated checkout modal will open automatically.
7. Click **Simulate SUCCESSFUL Payment** to review the ticket receipt page, download the printable invoice, and inspect the logged WhatsApp message dispatch in `whatsapp/log.txt`.
