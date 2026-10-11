-- MySQL dump 10.13  Distrib 8.0.46, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: threads_city_blog
-- ------------------------------------------------------
-- Server version	8.0.46

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `bookmarks`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bookmarks` (
  `user_id` bigint unsigned NOT NULL,
  `post_id` bigint unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`,`post_id`),
  KEY `bookmarks_post_id_foreign` (`post_id`),
  CONSTRAINT `bookmarks_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bookmarks_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bookmarks`
--

LOCK TABLES `bookmarks` WRITE;
/*!40000 ALTER TABLE `bookmarks` DISABLE KEYS */;
INSERT INTO `bookmarks` VALUES (2,1,'2026-10-11 11:11:08');
/*!40000 ALTER TABLE `bookmarks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icon` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'book',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_name_unique` (`name`),
  UNIQUE KEY `categories_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Lập trình','lap-trinh','Ghi chép từ những dòng code và các dự án nhỏ.','code',1,'2026-10-11 11:11:08','2026-10-11 11:11:08'),(2,'Công nghệ','cong-nghe','Công cụ, mã nguồn mở và những điều đáng khám phá.','layers',1,'2026-10-11 11:11:08','2026-10-11 11:11:08'),(3,'Đời sống','doi-song','Chậm lại một chút, để nhìn rõ hơn những điều thường ngày.','coffee',1,'2026-10-11 11:11:08','2026-10-11 11:11:08');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comments`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `comments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `post_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `comments_user_id_foreign` (`user_id`),
  KEY `comments_post_id_status_index` (`post_id`,`status`),
  CONSTRAINT `comments_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `comments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comments`
--

LOCK TABLES `comments` WRITE;
/*!40000 ALTER TABLE `comments` DISABLE KEYS */;
INSERT INTO `comments` VALUES (1,1,2,'Cảm ơn bạn đã chia sẻ hành trình học Laravel!','approved','2026-10-11 11:11:08','2026-10-11 11:11:08'),(2,1,2,'Mình đang chờ bài viết tiếp theo.','pending','2026-10-11 11:11:08','2026-10-11 11:11:08');
/*!40000 ALTER TABLE `comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2026_10_11_000001_create_personal_blog_tables',1),(2,'2026_10_11_000002_add_post_trash_and_daily_views',2);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `post_daily_views`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `post_daily_views` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `post_id` bigint unsigned NOT NULL,
  `view_date` date NOT NULL,
  `views` bigint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `post_daily_views_post_id_view_date_unique` (`post_id`,`view_date`),
  KEY `post_daily_views_view_date_index` (`view_date`),
  CONSTRAINT `post_daily_views_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `post_daily_views`
--

LOCK TABLES `post_daily_views` WRITE;
/*!40000 ALTER TABLE `post_daily_views` DISABLE KEYS */;
/*!40000 ALTER TABLE `post_daily_views` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `post_tag`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `post_tag` (
  `post_id` bigint unsigned NOT NULL,
  `tag_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`post_id`,`tag_id`),
  KEY `post_tag_tag_id_foreign` (`tag_id`),
  CONSTRAINT `post_tag_post_id_foreign` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `post_tag_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `post_tag`
--

LOCK TABLES `post_tag` WRITE;
/*!40000 ALTER TABLE `post_tag` DISABLE KEYS */;
INSERT INTO `post_tag` VALUES (1,1),(1,2),(2,2),(1,3),(2,4),(3,5),(3,6),(4,7),(4,8);
/*!40000 ALTER TABLE `post_tag` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `posts`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `posts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `category_id` bigint unsigned NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(240) COLLATE utf8mb4_unicode_ci NOT NULL,
  `excerpt` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `cover_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cover_alt` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('draft','published','archived') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `is_featured` tinyint(1) NOT NULL DEFAULT '0',
  `views_count` int unsigned NOT NULL DEFAULT '0',
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `posts_slug_unique` (`slug`),
  KEY `posts_user_id_foreign` (`user_id`),
  KEY `posts_category_id_foreign` (`category_id`),
  KEY `posts_status_published_at_index` (`status`,`published_at`),
  KEY `posts_deleted_at_index` (`deleted_at`),
  CONSTRAINT `posts_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `posts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `posts`
--

LOCK TABLES `posts` WRITE;
/*!40000 ALTER TABLE `posts` DISABLE KEYS */;
INSERT INTO `posts` VALUES (1,1,1,'Đi từ PHP thuần đến Laravel: những điều mình học được','tu-php-thuan-den-laravel','Không chỉ là đổi framework. Đó là lúc mình học cách tổ chức code, tách trách nhiệm và nghĩ xa hơn một trang web chạy được.','## Bắt đầu từ những tệp PHP nhỏ\n\nDự án đầu tiên của mình chỉ có vài tệp PHP. Một tệp nhận request, một tệp kết nối dữ liệu, phần còn lại dựng HTML. Cách đó giúp mình hiểu rất rõ một trang web được tạo ra như thế nào.\n\nNhưng khi số trang tăng lên, mình bắt đầu thấy những đoạn code lặp lại: kiểm tra dữ liệu, xử lý đường dẫn, rồi truyền biến vào giao diện. Mình cần một cách tổ chức nhất quán hơn.\n\n## Học cách phân chia trách nhiệm\n\nĐiều mình thích ở Laravel là ranh giới rõ ràng giữa route, controller và view. Route mô tả địa chỉ, controller điều phối request, còn Blade tập trung vào việc hiển thị.\n\nMình không cố tạo thật nhiều lớp ngay từ đầu. Một repository nhỏ cho nguồn dữ liệu và một controller dễ đọc đã đủ để dự án tiến thêm một bước. Khi có MySQL, nguồn dữ liệu có thể thay đổi mà phần giao diện vẫn giữ nguyên.\n\n## Một bài học về việc làm chậm lại\n\nChuyển framework không có nghĩa là bỏ qua nền tảng. HTTP, SQL, kiểm tra đầu vào và cách quản lý phiên vẫn là những điều mình cần hiểu.\n\nMình dành thời gian kiểm tra từng trang, thử đường dẫn không tồn tại và đọc lại các thông báo lỗi. Những việc nhỏ đó khiến mình tự tin hơn vào code mình đang viết.\n\n## Điều mình muốn làm tiếp\n\nBlog này là một nơi để ghi lại hành trình đó. Không cần bảng tin, không cần chạy theo những con số tương tác; chỉ cần một bài viết đủ rõ ràng để người đọc mang về điều gì đó.\n\nBước tiếp theo sẽ là hoàn thiện mô hình dữ liệu, quản lý bản nháp và xuất bản bài viết. Mỗi phần đều sẽ được xây dựng dựa trên nhu cầu thật của một blog cá nhân.','images/code-night.jpg','Màn hình code trong một góc làm việc yên tĩnh','published',1,0,'2026-10-10 11:11:08','2026-10-11 11:11:08','2026-10-11 11:11:08',NULL),(2,1,1,'Một controller dễ đọc bắt đầu từ đâu?','mot-controller-de-doc','Một vài thay đổi nhỏ giúp mình bớt lạc trong chính code của mình: tên rõ ràng, hàm ngắn và trách nhiệm vừa đủ.','## Đặt tên để người khác hiểu\n\nMình thường đọc code nhiều hơn viết code. Vì vậy, mình chọn tên biến mô tả dữ liệu và tên hàm mô tả hành động, thay vì cố rút ngắn mọi thứ.\n\nMột controller tốt không cần kể toàn bộ câu chuyện của hệ thống. Nó chỉ cần cho thấy request đi vào đâu, dữ liệu đến từ đâu và response được trả ra như thế nào.\n\n## Tách nguồn dữ liệu khỏi giao diện\n\nỞ giai đoạn làm giao diện, dữ liệu mẫu là đủ. Mình đặt nó trong repository để không trộn nội dung vào Blade.\n\nKhi cấu trúc MySQL được thống nhất, mình có thể thay nguồn này bằng Eloquent. Cách chia nhỏ đó giúp mỗi lần thay đổi dễ kiểm tra hơn.','images/code-desk.jpg','Bàn làm việc với máy tính và dụng cụ ghi chép','published',0,0,'2026-10-09 11:11:08','2026-10-11 11:11:08','2026-10-11 11:11:08',NULL),(3,1,3,'Một góc làm việc ít đồ hơn, một ngày nhẹ đầu hơn','goc-lam-viec-it-hon','Mình dọn lại chiếc bàn, tắt vài thông báo và nhận ra sự tập trung đôi khi đến từ những điều rất đơn giản.','## Bắt đầu bằng việc bỏ bớt\n\nMình cất những vật dụng không dùng hằng ngày khỏi mặt bàn. Không gian trống hơn một chút, và mình cũng thấy dễ bắt đầu công việc hơn.\n\nĐây không phải một công thức cho tất cả mọi người. Chỉ là một thay đổi nhỏ đã phù hợp với cách mình làm việc.\n\n## Để lại chỗ cho một khoảng nghỉ\n\nMình đặt cuốn sổ bên cạnh máy tính để ghi lại ý tưởng, thay vì mở thêm một tab. Một tách cà phê và vài phút nhìn ra cửa sổ cũng là một phần của buổi sáng.\n\nCó những ngày không làm được nhiều. Nhưng mình đang học cách tạo ra một nhịp làm việc có thể duy trì, thay vì cố gắng hết sức rồi kiệt sức.',NULL,'','published',0,0,'2026-10-08 11:11:08','2026-10-11 11:11:08','2026-10-11 11:11:08',NULL),(4,1,2,'Mã nguồn mở và thói quen đọc code của người khác','ghi-chep-ve-ma-nguon-mo','Có những bài học mình không tìm thấy trong một tutorial, nhưng lại tìm thấy trong một commit rất nhỏ.','## Không cần hiểu cả dự án ngay\n\nMình bắt đầu từ một issue nhỏ hoặc một hàm mình đang sử dụng. Đọc test đi kèm giúp mình hiểu tác giả mong đợi điều gì từ đoạn code đó.\n\nThay vì tải về rồi bỏ quên, mình dành một khoảng thời gian ngắn để theo dõi một thay đổi cụ thể trong dự án.\n\n## Đóng góp cũng là một cách học\n\nMột bản sửa tài liệu hoặc một báo cáo lỗi rõ ràng đều có ích. Mình học cách mô tả vấn đề, cung cấp bước tái hiện và lắng nghe phản hồi.\n\nMã nguồn mở với mình không chỉ là công cụ miễn phí, mà còn là cơ hội để nhìn thấy cách nhiều người cùng chăm sóc một sản phẩm.',NULL,'','published',0,0,'2026-10-07 11:11:08','2026-10-11 11:11:08','2026-10-11 11:11:08',NULL),(5,1,1,'Một bản nháp chưa xuất bản','mot-ban-nhap-chua-xuat-ban','Bài này chỉ chủ blog nhìn thấy.','## Ý tưởng ban đầu\n\nĐây là nội dung đang được hoàn thiện.',NULL,NULL,'draft',0,0,NULL,'2026-10-11 11:11:08','2026-10-11 11:11:08',NULL);
/*!40000 ALTER TABLE `posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tags`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tags` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tags_name_unique` (`name`),
  UNIQUE KEY `tags_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tags`
--

LOCK TABLES `tags` WRITE;
/*!40000 ALTER TABLE `tags` DISABLE KEYS */;
INSERT INTO `tags` VALUES (1,'PHP','php','2026-10-11 11:11:08','2026-10-11 11:11:08'),(2,'Laravel','laravel','2026-10-11 11:11:08','2026-10-11 11:11:08'),(3,'Ghi chép','ghi-chep','2026-10-11 11:11:08','2026-10-11 11:11:08'),(4,'Clean code','clean-code','2026-10-11 11:11:08','2026-10-11 11:11:08'),(5,'Đời sống','doi-song','2026-10-11 11:11:08','2026-10-11 11:11:08'),(6,'Không gian làm việc','khong-gian-lam-viec','2026-10-11 11:11:08','2026-10-11 11:11:08'),(7,'Mã nguồn mở','ma-nguon-mo','2026-10-11 11:11:08','2026-10-11 11:11:08'),(8,'Git','git','2026-10-11 11:11:08','2026-10-11 11:11:08');
/*!40000 ALTER TABLE `tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','reader') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'reader',
  `is_locked` tinyint(1) NOT NULL DEFAULT '0',
  `headline` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bio` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `about` text COLLATE utf8mb4_unicode_ci,
  `blog_title` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Duy Khang','admin@blog.local','$2y$12$hcDX7bD90b.0IOtLr0.mmOqAhpti5879EiHTHy5Ua.4YBfDflk4DC','admin',0,'Lập trình viên & người viết','Mình viết về lập trình, những trải nghiệm nhỏ và hành trình làm mọi thứ tốt hơn mỗi ngày.','Mình tạo blog này như một cuốn sổ mở. Có những bài là ghi chép sau khi giải quyết một vấn đề trong code. Có những bài chỉ là trải nghiệm nhỏ khiến mình muốn ngồi xuống và viết.\n\nMình thích sự đơn giản: một trang trắng, vài dòng chữ rõ ràng và đủ khoảng trống để suy nghĩ. Blog này lưu lại hành trình học lập trình, tìm hiểu mã nguồn mở và cuộc sống thường ngày.','Khang\'s Journal',NULL,NULL,NULL,'2026-10-11 11:11:08','2026-10-11 11:11:08'),(2,'Người đọc demo','reader@blog.local','$2y$12$xENIOXvnDb67AgfCYW3qMugjOlbmpT/xp7TpXhOA8vFRqk9bOA1uS','reader',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-10-11 11:11:08','2026-10-11 11:11:08');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed
