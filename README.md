╔══════════════════════════════════════════════════════════════╗
║          LifeFlow - Smart Blood Donation Platform            ║
║          Frontend: HTML + CSS + JavaScript                   ║
║          Backend:  PHP 8.0+                                  ║
║          Database: MySQL 5.7+ / MariaDB 10.4+               ║
╚══════════════════════════════════════════════════════════════╝

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 QUICK SETUP (XAMPP / WAMP / MAMP)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

1. COPY FILES
   Place the entire `lifeflow-php/` folder inside:
   - XAMPP: C:\xampp\htdocs\lifeflow\
   - WAMP:  C:\wamp64\www\lifeflow\
   - MAMP:  /Applications/MAMP/htdocs/lifeflow/
   - Linux: /var/www/html/lifeflow/

2. CREATE DATABASE
   Open phpMyAdmin → New Database → name it "lifeflow"
   OR run from terminal:
     mysql -u root -p -e "CREATE DATABASE lifeflow CHARACTER SET utf8mb4;"

3. IMPORT SCHEMA
   In phpMyAdmin: select "lifeflow" → Import → Choose database.sql
   OR from terminal:
     mysql -u root -p lifeflow < database.sql

4. CONFIGURE DB CONNECTION
   Edit db_connect.php and set your credentials:
     $db_host = '127.0.0.1';    (usually 127.0.0.1 or localhost)
     $db_name = 'lifeflow';
     $db_user = 'root';         (your MySQL username)
     $db_pass = '';             (your MySQL password)
     $db_port = 3306;           (default MySQL port)

   TIP: You can also set these as environment variables:
     DB_HOST, DB_NAME, DB_USER, DB_PASS, DB_PORT

5. START SERVERS
   - Start Apache and MySQL in XAMPP Control Panel
   - Open: http://localhost/lifeflow/php/index.php

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 DEMO ACCOUNTS (from database.sql seed data)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  Role       Email                    Password
  ────────   ─────────────────────    ──────────
  Admin      admin@lifeflow.com       password
  Donor      donor@lifeflow.com       password
  Patient    patient@lifeflow.com     password
  Hospital   hospital@lifeflow.com    password

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 FILE STRUCTURE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

lifeflow-php/
├── database.sql              ← Import this first!
├── db_connect.php            ← Database config (edit this)
├── README.txt                ← This file
│
├── css/
│   └── style.css             ← All styles (crimson theme)
│
├── js/
│   └── app.js                ← All JavaScript interactions
│
└── php/
    ├── index.php             ← Home / Landing page
    ├── login.php             ← Login page
    ├── register.php          ← Registration (4 roles)
    ├── logout.php            ← Logout handler
    ├── dashboard.php         ← Role-based redirect
    ├── donor_dashboard.php   ← Donor dashboard
    ├── patient_dashboard.php ← Patient dashboard
    ├── hospital_dashboard.php← Hospital dashboard + inventory
    ├── admin_dashboard.php   ← Admin control panel
    ├── blood_request.php     ← Post a blood request
    ├── view_requests.php     ← Browse all requests (public)
    ├── about.php             ← About page
    ├── contact.php           ← Contact form
    └── edit_profile.php      ← Edit user profile

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 FEATURES
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

✅ Multi-role authentication (Donor / Patient / Hospital / Admin)
✅ PHP sessions for login state
✅ bcrypt password hashing
✅ Blood request CRUD (create, filter, status updates)
✅ Blood inventory management (per hospital, all 8 blood groups)
✅ Donor availability toggle
✅ Donor search (filter by blood group + city)
✅ Admin user management (suspend / reactivate)
✅ Contact form with admin review
✅ Newsletter subscription
✅ Responsive design (mobile-friendly)
✅ Crimson/red theme matching the LifeFlow brand
✅ Auto-dismiss flash alerts
✅ FAQ accordion
✅ Animated stat counters

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 REQUIREMENTS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

• PHP 8.0 or higher (uses PHP 8.x features)
• MySQL 5.7+ or MariaDB 10.4+
• Apache or Nginx with mod_rewrite (for XAMPP/WAMP, Apache works out of the box)
• Internet connection (CSS loads Google Fonts + Font Awesome from CDN)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 PRODUCTION DEPLOYMENT NOTES
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

• Set DB credentials via environment variables (not hardcoded)
• Enable HTTPS (SSL certificate required for production)
• Set session cookie: secure = true in php.ini for HTTPS
• Set PHP error display off: display_errors = 0
• Consider adding CSRF tokens to all POST forms
• Rate limit login attempts with a middleware or .htaccess

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 © 2024 LifeFlow. Every Drop Counts.
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━