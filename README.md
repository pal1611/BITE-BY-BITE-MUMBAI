🍴 Bite by Bite — Mumbai  
A web-based food itinerary platform for exploring Mumbai's iconic street food, cafés,
and hidden culinary gems through curated food trails.  
Built as a Web Programming Lab project by Shreya Sakala, Gayatri Bedekar & Palak
Sanklecha (Third-year Computer Engineering). 

Contributions — Initial Version:  
• Gayatri Bedekar — HTML and CSS development  
• Palak Sanklecha — PHP and MySQL development  
• Shreya Sakala — Database data entry

Further Research & Advancements:  
Palak Sanklecha — Further research, development, and advancements to the project.

📸 Overview  
Bite by Bite lets users explore Mumbai's food culture through area-wise and time-based
food trails. Users can discover spots, check in, leave reviews, save favourites, track their
journey on a visual Food Passport, and find spots near them — all backed by a PHP +
MySQL backend.

✨ Features 

For Users  
• Food Trails— curated area-wise (Bandra, Dadar, Colaba, Juhu, Thane) and time-
based (Morning, Night) trails.  
• Spot Pages — dishes, timings, open/closed status, photo reviews, seasonal specials,
and live route from your location.  
• Surprise Me — randomly picks a trail or spot for indecisive explorers.  
• Check-ins & Badges — check into spots, complete trails, earn badges.  
• Food Passport — visual stamp grid showing visited and unvisited spots, rank tiers starting 
from Fresh off the Plate (first badge) to Mumbai Food Legend (final badge).  
• Favourites — save spots and trails, synced to the database.  
• Dish Search — find which spots serve a particular dish.  
• Budget Planner — filter trails and spots by your budget and food type.  
• Nearby Spots — geolocation-based discovery with adjustable radius.  
• Reviews — star ratings, text reviews, and photo uploads.  
• Seasonal Specials — festival tagged dishes (e.g. Modak during Ganesh Chaturthi).

For Admins  
• Admin Panel — 7-tab dashboard (Dashboard, Analytics, Trails, Food Spots, Users,
Reviews, Seasonal)   
• Edit & Delete trails and spots with pre-filled modals.  
• User Management — ban, unban, promote to admin, delete users.  
• Analytics — most reviewed spot, highest rated trail, most active user, bar charts.  
• Review Moderation — view and delete any review.  

🛠 Tech Stack  
• Frontend - HTML CSS, Javascipt  
• Backend - PHP (no framework)  
• Database - MySQL via MySQLi  
• Server - XAMPP (Apache + MySQL)  
• Maps - OpenStreetMap embed  
• Distance - Haversine formula

📁 File Structure  
bite-by-bite/  
│  
├── db.php                    # Database connection  
├── header.php                # Shared nav/dropdown (include on all pages)  
├── header_styles.php         # Shared header CSS (include inside <style>)  
├── cookie-banner.php         # Cookie consent banner include  
├── cookie-preferences.php    # Cookie settings page  
│   
├── index.php                 # Homepage — hero, time banner, popular trails, stats  
├── login.php                 # Login (email or username)    
├── register.php              # Registration with username  
├── logout.php                # Session destroy + redirect  
│  
├── trails.php                # All trails with search  
├── trail-details.php         # Trail page — spots, rating, favourites  
├── spot-details.php          # Spot page — dishes, map, route, reviews, check-in  
│  
├── favourites.php            # Saved spots and trails  
├── toggle_favourite.php      # AJAX endpoint — add/remove favourites  
├── checkin.php               # AJAX endpoint — check-in + trail completion  
│  
├── profile.php               # User profile — reviews, badges, progress  
├── food-passport.php         # Visual stamp passport  
├── dish-search.php           # Search by dish name   
├── budget-planner.php        # Budget + type filter for trails and spots  
├── nearby.php                # Geolocation-based nearby spots    
├── surprise.php              # Random trail/spot redirect  
│  
└── admin.php                 # Full admin panel  

🗄 Database Setup  
• bitebybite.sql - All tables + sample data.  
• add_favourites.sql - Favourites table.  
• add_medium_features - Check-ins table + photo URL column.  
• add_admin_fun_features - admin's features like banning malicious user. accounts, adding seasonal specials, etc.  
• add_username.sql - Username column for users table.

Schema at a glance  
• users — id, name, username, email, phone, password (bcrypt), role, is_banned.  
• trails — id, slug, name, area, description, cost, duration.  
• food_spots — id, slug, trail_id, name, area, type, price_range, image_url, timings, rating.  
• dishes — id, spot_id, dish_name.  
• reviews — id, spot_id, user_id, rating, review_text, photo_ url.  
• trail_ratings — id, trail_id, user_id, rating.  
• favourites— id, user_id, spot_id (nullable), trail_id (nullable).  
• checkins— id, user_id, spot_id.  
seasonal_specials — id, spot_id, dish_name, season_name, start_month, end_month. 

🚀 Getting Started (XAMPP)  

Prerequisites  
• XAMPP with Apache and MySQL enabled.  
• A modern browser.  

Steps  
1. Clone this repository:  
bash  
   git clone https://github.com/<your-username>/bite-by-bite.git  
2. Copy to XAMPP  
   Copy the folder to: C:\xampp\htdocs\bite-by-bite\  
3. Set up the database  
• Open phpMyAdmin at http://localhost/phpmyadmin  
• Create a database called bitebybite  
• Import all 5 SQL files in the order listed above
4. Configure the connection (if needed) Open db.php and confirm the credentials match your XAMPP setup:  
php  
   $conn = new mysqli("localhost", "root", "", "bitebybite");  
5. Run the site Visit: http://localhost/bite-by-bite/index.php  
Default Admin Account:  
Field	                        Value  
Email	                    admin@bbb.com  
Password            	    admin123  

⚠️ Change the admin password after first login.    

📍 Sample Spots & Coordinates  

The following spots have coordinates pre-loaded for the Nearby and Route features:  
 
Spot                             Area	               Lat	     Lng  
Elco Pani Puri Centre         	Bandra	           19.0543	 72.8361  
Aaswad Restaurant              	Dadar	             19.0176	 72.8436  
Leopold Cafe                   	Colaba	           18.9229	 72.8311  
Juhu Beach Chaat Stalls        	Juhu	             19.0948	 72.8267  

Additional spots can have coordinates added through the Admin Panel → Food Spots → Edit.  
  
🎯 User Rank Tiers  
Progress through the Food Passport to unlock ranks:  
 
 Rank	                                     Requirement
🌱 Fresh off the Plate	                      Just starting out  
🍴 Hungry Adventurer	                        25% of all spots visited  
⭐ Seasoned Explorer	                        50% of all spots visited  
🏅 Trail Connoisseur	                        75% of all spots visited  
👑 Mumbai Food Legend                        Every single spot visited    

👥 Authors  
Name          	                           Role
Shreya Sakala                          	Developer
Gayatri Bedekar                        	Developer
Palak Sanklecha                        	Developer

Third-year Computer Engineering — Web Programming Lab Project, 2026.  

📄 License  
This project was built for academic purposes. All food spot data is illustrative and for demonstration only.  
