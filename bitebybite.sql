 phpMyAdmin SQL Dump
 version 5.2.1
 https://www.phpmyadmin.net/

 Host: 127.0.0.1
 Generation Time: Aug 19, 2026 at 04:15 PM
 Server version: 10.4.32-MariaDB
 PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;


 Database: 'bitebybite'


 


 Table structure for table 'checkins'


CREATE TABLE 'checkins' (
  'id' int(11) NOT NULL,
  'user_id' int(11) NOT NULL,
  'spot_id' int(11) NOT NULL,
  'created_at' timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


 Dumping data for table 'checkins'


INSERT INTO 'checkins' ('id', 'user_id', 'spot_id', 'created_at') VALUES
(2, 5, 1, '2026-04-21 08:52:40'),
(3, 6, 1, '2026-04-21 18:38:46'),
(4, 3, 6, '2026-08-12 14:11:49');

 


 Table structure for table 'dishes'


CREATE TABLE 'dishes' (
  'id' int(11) NOT NULL,
  'spot_id' int(11) NOT NULL,
  'dish_name' varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


 Dumping data for table 'dishes'


INSERT INTO 'dishes' ('id', 'spot_id', 'dish_name') VALUES
(1, 1, 'Pani Puri'),
(2, 1, 'Sev Puri'),
(3, 1, 'Dahi Puri'),
(4, 1, 'Bhel Puri'),
(5, 2, 'Misal Pav'),
(6, 2, 'Sabudana Khichdi'),
(7, 2, 'Puran Poli'),
(8, 2, 'Ukdiche Modak'),
(9, 3, 'Chicken Schnitzel'),
(10, 3, 'Mumbai Sandwich'),
(11, 3, 'Cold Coffee'),
(12, 3, 'Fish & Chips'),
(13, 4, 'Bhel Puri'),
(14, 4, 'Ragda Pattice'),
(15, 4, 'Pav Bhaji'),
(16, 4, 'Corn on the Cob'),
(19, 5, 'Matcha-Misu'),
(20, 5, 'Chili Garlic Noodles'),
(21, 5, 'Tornado Egg Fried rice'),
(22, 6, 'Banana Pudding'),
(23, 6, 'Tres leches'),
(24, 6, 'Chocolate Cupcake'),
(25, 7, 'Lobster Roll'),
(26, 7, 'Wasabi Prawns'),
(27, 7, 'Avocado Salad'),
(28, 7, 'Truffle Fries'),
(29, 8, 'Chicken Drums of Heaven'),
(30, 8, 'Black Pepper Paneer'),
(31, 8, 'Kuai Special Roll');

 


 Table structure for table 'favourites'


CREATE TABLE 'favourites' (
  'id' int(11) NOT NULL,
  'user_id' int(11) NOT NULL,
  'spot_id' int(11) DEFAULT NULL,
  'trail_id' int(11) DEFAULT NULL,
  'created_at' timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

 


 Table structure for table 'food_spots'


CREATE TABLE 'food_spots' (
  'id' int(11) NOT NULL,
  'slug' varchar(150) NOT NULL,
  'trail_id' int(11) NOT NULL,
  'name' varchar(150) NOT NULL,
  'area' varchar(100) NOT NULL,
  'description' text DEFAULT NULL,
  'type' enum('veg','nonveg','both') DEFAULT 'both',
  'price_range' varchar(50) DEFAULT NULL,
  'image_url' varchar(500) DEFAULT NULL,
  'open_time' varchar(20) DEFAULT NULL,
  'close_time' varchar(20) DEFAULT NULL,
  'days_open' varchar(50) DEFAULT NULL,
  'map_src' text DEFAULT NULL,
  'created_at' timestamp NOT NULL DEFAULT current_timestamp(),
  'latitude' decimal(10,7) DEFAULT NULL,
  'longitude' decimal(10,7) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


 Dumping data for table 'food_spots'


INSERT INTO 'food_spots' ('id', 'slug', 'trail_id', 'name', 'area', 'description', 'type', 'price_range', 'image_url', 'open_time', 'close_time', 'days_open', 'map_src', 'created_at', 'latitude', 'longitude') VALUES
(1, 'elco', 1, 'Elco Pani Puri Centre', 'Bandra', 'Mumbai\'s most iconic pani puri stall on Hill Road.', 'both', '₹50–₹150', 'https://images.unsplash.com/photo-1601050690597-df0568f70950?w=600&q=80', '11:00 AM', '11:00 PM', 'Mon – Sun', NULL, '2026-04-19 17:02:51', 19.0543000, 72.8361000),
(2, 'aaswad', 2, 'Aaswad Restaurant', 'Dadar', 'Legendary Maharashtrian restaurant serving since 1971.', 'veg', '₹80–₹300', 'https://images.unsplash.com/photo-1567337710282-00832b415979?w=600&q=80', '7:00 AM', '10:30 PM', 'Mon – Sun', NULL, '2026-04-19 17:02:51', 19.0176000, 72.8436000),
(3, 'leopold', 3, 'Leopold Cafe', 'Colaba', 'One of Mumbai\'s oldest and most iconic cafés (est. 1871).', 'both', '₹300–₹800', 'https://images.unsplash.com/photo-1554118811-1e0d58224f24?w=600&q=80', '8:00 AM', '12:00 AM', 'Mon – Sun', NULL, '2026-04-19 17:02:51', 18.9229000, 72.8311000),
(4, 'juhu-beach-chaat', 4, 'Juhu Beach Chaat Stalls', 'Juhu', 'Famous chaat stalls lining Juhu Beach — best at sunset.', 'both', '₹30–₹100', 'https://images.unsplash.com/photo-1606491956689-2ea866880c84?w=600&q=80', '10:00 AM', '11:00 PM', 'Mon – Sun', NULL, '2026-04-19 17:02:51', 19.0948000, 72.8267000),
(5, 'mokai-1776667908', 1, 'Mokai', 'Pali Hill', 'Mokai Café is a trendy Asian-fusion café located in Bandra, Mumbai. It is known for its creative all-day breakfast, bold Thai-French-Singaporean flavors, and specialty coffees.', 'both', '₹1400-₹1600', 'https://tse1.mm.bing.net/th/id/OIP.sh2SAAH2u5uJvijLABMVHQHaE9?rs=1&pid=ImgDetMain&o=7&rm=3', '7 am', '11:30 pm', '', 'https://maps.app.goo.gl/RnP2TwUxsMFLwK6f9', '2026-04-20 06:51:48', -38.5288000, 175.9038000),
(6, 'magnolia-bakery-1776669861', 1, 'Magnolia Bakery', 'Pali Rd', 'Bakery ,Desserts , Beverages', 'both', '800-1000', 'https://thumbs.6sqft.com/wp-content/uploads/2022/01/02101706/Magnolia-Bakery-Hudson-Yards.jpeg', '10:00 AM', '12:00 AM', 'Mon-Sun', '', '2026-04-20 07:24:21', NULL, NULL),
(7, 'mag-st-cafe-1776670123', 3, 'Mag St. Cafe', 'Mandlik Rd', 'Fine Dining restaurant serving authentic Italian and an experience.', 'both', '1800-2000', 'https://curlytales.com/wp-content/uploads/2024/01/Mag-St..jpg', '8:00 AM', '11:30 PM', 'Mon-Sun', '', '2026-04-20 07:28:43', NULL, NULL),
(8, 'kuai-kitchen-1776760317', 3, 'Kuai Kitchen', 'Shahid Bhagat Singh Rd', 'A Pan-Asian menu with bold Sichuan heat, delicate sushi rolls, dim sum, and wok-tossed mains, balanced with rich sauces, fresh seafood, and modern Asian flavours in a refined Colaba setting.', 'both', '₹1600-₹1800', 'https://tourismquest.com/wp-content/uploads/2022/11/Kuai-Kitchen-Khar.jpg', '12:00AM', '11:30 PM', 'Mon-Sun', '', '2026-04-21 08:31:57', NULL, NULL);

 


 Table structure for table 'reviews'


CREATE TABLE 'reviews' (
  'id' int(11) NOT NULL,
  'spot_id' int(11) NOT NULL,
  'user_id' int(11) NOT NULL,
  'rating' tinyint(4) NOT NULL CHECK ('rating' between 1 and 5),
  'review_text' text DEFAULT NULL,
  'created_at' timestamp NOT NULL DEFAULT current_timestamp(),
  'photo_url' text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


 Dumping data for table 'reviews'


INSERT INTO 'reviews' ('id', 'spot_id', 'user_id', 'rating', 'review_text', 'created_at', 'photo_url') VALUES
(1, 1, 3, 4, 'Great Place, aesthetically pleasing.', '2026-08-14 12:27:22', NULL),
(2, 4, 3, 5, 'Amazing place!', '2026-08-14 12:32:28', NULL);

 


 Table structure for table 'seasonal_specials'


CREATE TABLE 'seasonal_specials' (
  'id' int(11) NOT NULL,
  'spot_id' int(11) NOT NULL,
  'dish_name' varchar(150) NOT NULL,
  'season_name' varchar(100) NOT NULL,
  'start_month' tinyint(4) NOT NULL COMMENT '1=Jan ... 12=Dec',
  'end_month' tinyint(4) NOT NULL,
  'created_at' timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


 Dumping data for table 'seasonal_specials'


INSERT INTO 'seasonal_specials' ('id', 'spot_id', 'dish_name', 'season_name', 'start_month', 'end_month', 'created_at') VALUES
(1, 2, 'Ukdiche Modak', 'Ganesh Chaturthi', 8, 9, '2026-04-19 17:04:24'),
(2, 2, 'Puran Poli', 'Holi / Gudhi Padwa', 3, 4, '2026-04-19 17:04:24'),
(3, 1, 'Special Pani Puri', 'Monsoon Special', 6, 9, '2026-04-19 17:04:24'),
(4, 3, 'Christmas Cake', 'Christmas', 12, 12, '2026-04-19 17:04:24'),
(5, 4, 'Kite Festival Chaat', 'Makar Sankranti', 1, 1, '2026-04-19 17:04:24');

 


 Table structure for table 'trails'


CREATE TABLE 'trails' (
  'id' int(11) NOT NULL,
  'slug' varchar(100) NOT NULL,
  'name' varchar(150) NOT NULL,
  'area' varchar(100) NOT NULL,
  'description' text DEFAULT NULL,
  'cost' varchar(50) DEFAULT NULL,
  'duration' varchar(50) DEFAULT NULL,
  'created_at' timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


 Dumping data for table 'trails'


INSERT INTO 'trails' ('id', 'slug', 'name', 'area', 'description', 'cost', 'duration', 'created_at') VALUES
(1, 'bandra', 'Bandra Trail', 'Bandra', 'Trendy cafés, street shawarmas and dessert hotspots in the Queen of Suburbs.', '₹100–₹500', '2–3 hrs', '2026-04-19 17:02:50'),
(2, 'dadar', 'Dadar Trail', 'Dadar', 'Authentic Maharashtrian snacks and iconic street food stops.', '₹80–₹300', '2 hrs', '2026-04-19 17:02:50'),
(3, 'colaba', 'Colaba Trail', 'Colaba', 'Heritage cafés and legendary Mumbai eateries in the city\'s historic south.', '₹200–₹800', '2–3 hrs', '2026-04-19 17:02:50'),
(4, 'juhu', 'Juhu Trail', 'Juhu', 'Beachside bites and classic Mumbai chaat experiences by the sea.', '₹50–₹200', '1–2 hrs', '2026-04-19 17:02:50'),
(5, 'thane', 'Thane Trail', 'Thane', 'Street food and lakeside eateries in the City of Lakes.', '₹60–₹250', '2 hrs', '2026-04-19 17:02:50'),
(6, 'morning', 'Morning Breakfast Trail', 'Various', 'Start your day with iconic breakfast spots across Mumbai.', '₹60–₹200', '1–2 hrs', '2026-04-19 17:02:50'),
(7, 'night', 'Night Street Food Trail', 'Various', 'Explore Mumbai\'s vibrant late-night food culture.', '₹50–₹200', '2–3 hrs', '2026-04-19 17:02:50'),
(8, 'varsova-trail', 'Varsova Trail', 'Varsova', 'A mix of coastal seafood dishes, Mumbai street classics, and creative café-style bites, featuring everything from spicy chaats and kebabs to fresh fried fish, Indo-fusion snacks, Ramen and local sweet treats along a lively neighbourhood stretch.', '₹400-₹600', '2-3hrs', '2026-04-21 08:23:42');

 


 Table structure for table 'trail_ratings'


CREATE TABLE 'trail_ratings' (
  'id' int(11) NOT NULL,
  'trail_id' int(11) NOT NULL,
  'user_id' int(11) NOT NULL,
  'rating' tinyint(4) NOT NULL CHECK ('rating' between 1 and 5),
  'created_at' timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


 Dumping data for table 'trail_ratings'


INSERT INTO 'trail_ratings' ('id', 'trail_id', 'user_id', 'rating', 'created_at') VALUES
(1, 1, 5, 4, '2026-04-21 08:50:24'),
(2, 1, 3, 4, '2026-08-14 12:25:37');

 


 Table structure for table 'users'


CREATE TABLE 'users' (
  'id' int(11) NOT NULL,
  'name' varchar(100) NOT NULL,
  'username' varchar(30) DEFAULT NULL,
  'email' varchar(150) NOT NULL,
  'phone' varchar(15) NOT NULL,
  'password' varchar(255) NOT NULL,
  'role' enum('user','admin') DEFAULT 'user',
  'created_at' timestamp NOT NULL DEFAULT current_timestamp(),
  'is_banned' tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


 Dumping data for table 'users'


INSERT INTO 'users' ('id', 'name', 'username', 'email', 'phone', 'password', 'role', 'created_at', 'is_banned') VALUES
(1, 'Admin', 'admin', 'admin@bbb.com', '0000000000', '$2y$10$KxovOoIw6VgKA0cQ9uGyiuBDbqGoFZMjBy2HhCw3xgTGR/k5lIjW2', 'admin', '2026-04-19 15:44:36', 0),
(3, 'Palak Sanklecha', 'cool_as_lava', 'palaksanklecha13@gmail.com', '8983644683', '$2y$10$J7w1mkvVI2C.viWriVEpY.F4jiPnLBPZE5zvdnVEPlMnW9aja2J0q', 'user', '2026-04-19 17:07:02', 0),
(4, 'Sushant Samant', 'foodie_sushant', 'sushant.samant@somaiya.edu', '1234567890', '$2y$10$f.bQDwc35TKJbLku2T/BDexFSi6/xEaDEKPvMdpqfVvNDB2UmG8/S', 'user', '2026-04-21 08:37:53', 0),
(5, 'Shreya Sakala', 'foodie_shreya', 'shreya.sakala@somaiya.edu', '1236541234', '$2y$10$NiYh5woOS06zztKGLD5kOuk3TBC4rBKMBw4KbBntIinH9MuoXidyq', 'user', '2026-04-21 08:48:27', 0),
(6, 'Princy Patel', 'her_highness', 'princy.patel@somaiya.edu', '1234567890', '$2y$10$M0nYfWyIGQGJCWHRy6Leu.MOmKgZrOQvVqRfUW1OgkIPZ9V8ttaPu', 'user', '2026-04-21 18:35:37', 0);


 Indexes for dumped tables



 Indexes for table 'checkins'

ALTER TABLE 'checkins'
  ADD PRIMARY KEY ('id'),
  ADD UNIQUE KEY 'unique_checkin' ('user_id','spot_id'),
  ADD KEY 'spot_id' ('spot_id');


 Indexes for table 'dishes'

ALTER TABLE 'dishes'
  ADD PRIMARY KEY ('id'),
  ADD KEY 'spot_id' ('spot_id');


 Indexes for table 'favourites'

ALTER TABLE 'favourites'
  ADD PRIMARY KEY ('id'),
  ADD UNIQUE KEY 'unique_fav_spot' ('user_id','spot_id'),
  ADD UNIQUE KEY 'unique_fav_trail' ('user_id','trail_id'),
  ADD KEY 'spot_id' ('spot_id'),
  ADD KEY 'trail_id' ('trail_id');


 Indexes for table 'food_spots'

ALTER TABLE 'food_spots'
  ADD PRIMARY KEY ('id'),
  ADD UNIQUE KEY 'slug' ('slug'),
  ADD KEY 'trail_id' ('trail_id');


 Indexes for table 'reviews'

ALTER TABLE 'reviews'
  ADD PRIMARY KEY ('id'),
  ADD KEY 'spot_id' ('spot_id'),
  ADD KEY 'user_id' ('user_id');


 Indexes for table 'seasonal_specials'

ALTER TABLE 'seasonal_specials'
  ADD PRIMARY KEY ('id'),
  ADD KEY 'spot_id' ('spot_id');


 Indexes for table 'trails'

ALTER TABLE 'trails'
  ADD PRIMARY KEY ('id'),
  ADD UNIQUE KEY 'slug' ('slug');


 Indexes for table 'trail_ratings'

ALTER TABLE 'trail_ratings'
  ADD PRIMARY KEY ('id'),
  ADD UNIQUE KEY 'unique_trail_user' ('trail_id','user_id'),
  ADD KEY 'user_id' ('user_id');


 Indexes for table 'users'

ALTER TABLE 'users'
  ADD PRIMARY KEY ('id'),
  ADD UNIQUE KEY 'email' ('email'),
  ADD UNIQUE KEY 'username' ('username');


 AUTO_INCREMENT for dumped tables



 AUTO_INCREMENT for table 'checkins'

ALTER TABLE 'checkins'
  MODIFY 'id' int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;


 AUTO_INCREMENT for table 'dishes'

ALTER TABLE 'dishes'
  MODIFY 'id' int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;


 AUTO_INCREMENT for table 'favourites'

ALTER TABLE 'favourites'
  MODIFY 'id' int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;


 AUTO_INCREMENT for table 'food_spots'

ALTER TABLE 'food_spots'
  MODIFY 'id' int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;


 AUTO_INCREMENT for table 'reviews'

ALTER TABLE 'reviews'
  MODIFY 'id' int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;


 AUTO_INCREMENT for table 'seasonal_specials'

ALTER TABLE 'seasonal_specials'
  MODIFY 'id' int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;


 AUTO_INCREMENT for table 'trails'

ALTER TABLE 'trails'
  MODIFY 'id' int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;


 AUTO_INCREMENT for table 'trail_ratings'

ALTER TABLE 'trail_ratings'
  MODIFY 'id' int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;


 AUTO_INCREMENT for table 'users'

ALTER TABLE 'users'
  MODIFY 'id' int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;


 Constraints for dumped tables



 Constraints for table 'checkins'

ALTER TABLE 'checkins'
  ADD CONSTRAINT 'checkins_ibfk_1' FOREIGN KEY ('user_id') REFERENCES 'users' ('id') ON DELETE CASCADE,
  ADD CONSTRAINT 'checkins_ibfk_2' FOREIGN KEY ('spot_id') REFERENCES 'food_spots' ('id') ON DELETE CASCADE;


 Constraints for table 'dishes'

ALTER TABLE 'dishes'
  ADD CONSTRAINT 'dishes_ibfk_1' FOREIGN KEY ('spot_id') REFERENCES 'food_spots' ('id') ON DELETE CASCADE;


 Constraints for table 'favourites'

ALTER TABLE 'favourites'
  ADD CONSTRAINT 'favourites_ibfk_1' FOREIGN KEY ('user_id') REFERENCES 'users' ('id') ON DELETE CASCADE,
  ADD CONSTRAINT 'favourites_ibfk_2' FOREIGN KEY ('spot_id') REFERENCES 'food_spots' ('id') ON DELETE CASCADE,
  ADD CONSTRAINT 'favourites_ibfk_3' FOREIGN KEY ('trail_id') REFERENCES 'trails' ('id') ON DELETE CASCADE;


 Constraints for table 'food_spots'

ALTER TABLE 'food_spots'
  ADD CONSTRAINT 'food_spots_ibfk_1' FOREIGN KEY ('trail_id') REFERENCES 'trails' ('id') ON DELETE CASCADE;


 Constraints for table 'reviews'

ALTER TABLE 'reviews'
  ADD CONSTRAINT 'reviews_ibfk_1' FOREIGN KEY ('spot_id') REFERENCES 'food_spots' ('id') ON DELETE CASCADE,
  ADD CONSTRAINT 'reviews_ibfk_2' FOREIGN KEY ('user_id') REFERENCES 'users' ('id') ON DELETE CASCADE;


 Constraints for table 'seasonal_specials'

ALTER TABLE 'seasonal_specials'
  ADD CONSTRAINT 'seasonal_specials_ibfk_1' FOREIGN KEY ('spot_id') REFERENCES 'food_spots' ('id') ON DELETE CASCADE;


 Constraints for table 'trail_ratings'

ALTER TABLE 'trail_ratings'
  ADD CONSTRAINT 'trail_ratings_ibfk_1' FOREIGN KEY ('trail_id') REFERENCES 'trails' ('id') ON DELETE CASCADE,
  ADD CONSTRAINT 'trail_ratings_ibfk_2' FOREIGN KEY ('user_id') REFERENCES 'users' ('id') ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
