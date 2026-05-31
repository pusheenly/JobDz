-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 29, 2026 at 10:08 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `jobdz`
--

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `job_id` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `interview_date` date DEFAULT NULL,
  `interview_time` time DEFAULT NULL,
  `company_message` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`id`, `user_id`, `job_id`, `status`, `created_at`, `interview_date`, `interview_time`, `company_message`) VALUES
(1, 25, 1, 'Accepted', '2026-05-27 11:43:41', '2026-06-05', '10:00:00', 'We liked your frontend skills and would like to move forward with your application.'),
(2, 27, 2, 'Pending', '2026-05-27 11:43:41', NULL, NULL, 'Your backend profile is currently under review by our technical team.'),
(3, 29, 3, 'Interview', '2026-05-27 11:43:41', '2026-06-07', '14:30:00', 'We would like to schedule an interview regarding the UI UX Designer position.'),
(4, 30, 4, 'Accepted', '2026-05-27 11:43:41', '2026-06-08', '09:00:00', 'Your networking experience matches our requirements perfectly.'),
(5, 32, 5, 'Pending', '2026-05-27 11:43:41', NULL, NULL, 'Your application has been received successfully.'),
(6, 34, 6, 'Interview', '2026-05-27 11:43:41', '2026-06-10', '11:00:00', 'We are impressed with your cloud infrastructure experience.'),
(7, 31, 7, 'Rejected', '2026-05-27 11:43:41', NULL, NULL, 'We appreciate your interest but selected another candidate.'),
(8, 26, 8, 'Pending', '2026-05-27 11:43:41', NULL, NULL, 'Your marketing profile is being reviewed.'),
(10, 33, 10, 'Accepted', '2026-05-27 11:43:41', '2026-06-12', '13:00:00', 'Your product design portfolio is excellent.'),
(11, 25, 3, 'accepted', '2026-05-27 16:40:28', '2026-06-04', '09:59:00', 'message to candidate'),
(13, 43, 17, 'accepted', '2026-05-29 19:19:06', '2026-06-04', '08:00:00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `candidates_profiles`
--

CREATE TABLE `candidates_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `job_title` varchar(255) DEFAULT NULL,
  `summary` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `specialty` varchar(150) DEFAULT NULL,
  `availability` varchar(50) DEFAULT NULL,
  `experience_level` varchar(50) DEFAULT NULL,
  `cv_file` varchar(255) DEFAULT NULL,
  `linkedin_url` varchar(255) DEFAULT NULL,
  `github_url` varchar(255) DEFAULT NULL,
  `portfolio_url` varchar(255) DEFAULT NULL,
  `profile_views` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `candidates_profiles`
--

INSERT INTO `candidates_profiles` (`id`, `user_id`, `full_name`, `phone`, `city`, `image_path`, `updated_at`, `job_title`, `summary`, `category`, `specialty`, `availability`, `experience_level`, `cv_file`, `linkedin_url`, `github_url`, `portfolio_url`, `profile_views`) VALUES
(1, 25, 'Amine Belhadj', '0550123001', '13 - Tlemcen', 'assets/candidates/amine.jpg', '2026-05-28 16:22:28', 'Frontend Developer', 'Passionate frontend developer building responsive modern interfaces.', 'IT & Software', 'Frontend Development', 'Part Time', 'Senior', '', 'https://linkedin.com/in/aminebelhadj', 'https://github.com/aminebelhadj', 'https://amine-portfolio.com', 345),
(2, 26, 'Sarah Meziane', '0550123002', 'Oran', 'assets/candidates/sarah.jpg', '2026-05-29 00:26:02', 'Digital Marketing Specialist', 'Creative marketer focused on digital growth strategies.', 'Marketing', 'Digital Marketing', 'Available', 'Senior', 'assets/cv/sarah_cv.pdf', 'https://linkedin.com/in/sarahmeziane', NULL, 'https://sarah-marketing.com', 273),
(3, 27, 'Yacine Dev', '0550123003', 'Constantine', 'assets/candidates/yacine.jpg', '2026-05-28 10:23:55', 'Backend Developer', 'Backend engineer specialized in scalable web systems.', 'IT & Software', 'Backend Development', '', '', '', 'https://linkedin.com/in/yacinedev', 'https://github.com/yacinedev', 'https://yacinedev.com', 410),
(4, 28, 'Nour IT', '0550123004', 'Annaba', 'assets/candidates/nour.jpg', '2026-05-27 11:23:46', 'IT Support Technician', 'Motivated IT technician with networking knowledge.', 'IT', 'Technical Support', 'Available', 'Junior', 'assets/cv/nour_cv.pdf', 'https://linkedin.com/in/nourit', NULL, NULL, 150),
(5, 29, 'Ilyes Design', '0550123005', '01 - Adrar', 'assets/candidates/ilyes.jpg', '2026-05-28 23:56:51', 'UI UX Designer', 'Creative designer focused on user centered interfaces.', 'Design', 'UI UX Design', 'Part Time', 'Mid-Level', '', 'https://linkedin.com/in/ilyesdesign', NULL, 'https://ilyesdesign.com', 320),
(6, 30, 'Rayane Network', '0550123006', 'Setif', 'assets/candidates/rayane.jpg', '2026-05-27 11:23:46', 'Network Engineer', 'Experienced network engineer with CCNA certification.', 'Networking', 'Network Engineering', 'Available', 'Senior', 'assets/cv/rayane_cv.pdf', 'https://linkedin.com/in/rayanenetwork', NULL, NULL, 280),
(7, 31, 'Lina Marketing', '0550123007', 'Blida', 'assets/candidates/lina.jpg', '2026-05-27 11:23:46', 'Social Media Manager', 'Social media specialist building engaging campaigns.', 'Marketing', 'Social Media', 'Available', 'Mid Level', 'assets/cv/lina_cv.pdf', 'https://linkedin.com/in/linamarketing', NULL, NULL, 230),
(8, 32, 'Anis Backend', '0550123008', '03 - Laghouat', 'uploads/profile_6a1867c901723.jpg', '2026-05-28 16:05:29', 'Backend Engineer', 'Backend engineer focused on APIs and scalability.', 'IT & Software', 'Backend Development', 'Available', 'Senior', '', 'https://linkedin.com/in/anisbackend', 'https://github.com/anisbackend', 'https://anisbackend.dev', 520),
(9, 33, 'Meriem UIUX', '0550123009', 'Tlemcen', 'assets/candidates/meriem.jpg', '2026-05-29 11:53:20', 'Product Designer', 'Passionate about building beautiful digital products.', 'Design', 'Product Design', 'Available', 'Mid Level', 'assets/cv/meriem_cv.pdf', 'https://linkedin.com/in/meriemuiux', NULL, 'https://meriemuiux.com', 361),
(10, 34, 'Zakaria Cloud', '0550123010', 'Bejaia', 'assets/candidates/zakaria.jpg', '2026-05-27 11:23:46', 'Cloud Engineer', 'Cloud engineer specialized in scalable infrastructure.', 'Cloud Computing', 'DevOps Engineering', 'Available', 'Senior', 'assets/cv/zakaria_cv.pdf', 'https://linkedin.com/in/zakariacloud', 'https://github.com/zakariacloud', 'https://zakariacloud.dev', 610),
(11, 43, 'Chebili Bouchra', '059999999', '23 - Annaba', 'uploads/profile_6a19ee3421fa1.jpeg', '2026-05-29 19:51:16', 'Junior Front-End Developer', 'Passionate Junior Front-End Developer skilled in creating responsive and user-friendly web interfaces using HTML, CSS, JavaScript, PHP, and MySQL. Motivated to grow professionally, improve user experience, and contribute to modern web development projects.', 'IT', 'PHP & JavaScript Developer', 'Part Time', 'Junior', '', NULL, NULL, NULL, 3);

-- --------------------------------------------------------

--
-- Table structure for table `candidate_educations`
--

CREATE TABLE `candidate_educations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `degree` varchar(255) NOT NULL,
  `school` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_current` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `candidate_educations`
--

INSERT INTO `candidate_educations` (`id`, `user_id`, `degree`, `school`, `description`, `start_date`, `end_date`, `is_current`, `created_at`) VALUES
(2, 26, 'Bachelor Degree in Marketing', 'Oran University', 'Specialized in digital marketing and branding.', '2018-09-01', '2021-06-30', 0, '2026-05-27 11:26:22'),
(3, 27, 'Engineering Degree in Software Engineering', 'Constantine University', 'Backend systems and scalable architectures.', '2017-09-01', '2022-06-30', 0, '2026-05-27 11:26:22'),
(4, 28, 'Bachelor Degree in Information Systems', 'Annaba University', 'IT systems and networking fundamentals.', '2020-09-01', '2023-06-30', 0, '2026-05-27 11:26:22'),
(6, 30, 'Engineering Degree in Networks', 'Setif University', 'Enterprise networking and security.', '2016-09-01', '2021-06-30', 0, '2026-05-27 11:26:22'),
(7, 31, 'Master Degree in Communication', 'Blida University', 'Digital communication and social media.', '2019-09-01', '2023-06-30', 0, '2026-05-27 11:26:22'),
(8, 32, 'Computer Science Degree', 'ESI Algiers', 'Software engineering and APIs.', '2015-09-01', '2020-06-30', 0, '2026-05-27 11:26:22'),
(9, 33, 'Graphic Design Degree', 'Tlemcen Arts School', 'Product design and design systems.', '2018-09-01', '2021-06-30', 0, '2026-05-27 11:26:22'),
(10, 34, 'Engineering Degree in Cloud Computing', 'Bejaia University', 'Cloud infrastructure and DevOps.', '2016-09-01', '2021-06-30', 0, '2026-05-27 11:26:22'),
(11, 25, 'Master Degree in Computer Science', 'USTHB Algiers', 'Focused on web development and software engineering.', '2019-09-01', '2024-06-30', 0, '2026-05-27 11:34:46'),
(12, 29, 'Design Degree', 'Oran Design School', 'UI UX and visual communication.', '2018-09-01', '2021-06-30', 0, '2026-05-28 23:56:47'),
(16, 43, 'Bachelor’s Degree in Computer Science', 'Badji Mokhtar University – Annaba', 'Studying software development, databases, web technologies, and object-oriented programming.', '2025-12-30', '2023-06-22', 0, '2026-05-29 17:50:21');

-- --------------------------------------------------------

--
-- Table structure for table `candidate_experiences`
--

CREATE TABLE `candidate_experiences` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `job_title` varchar(255) NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_current` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `candidate_experiences`
--

INSERT INTO `candidate_experiences` (`id`, `user_id`, `job_title`, `company_name`, `description`, `start_date`, `end_date`, `is_current`, `created_at`) VALUES
(2, 26, 'Digital Marketing Specialist', 'BrandUp Agency', 'Managed SEO campaigns and social media ads.', '2021-02-01', '2024-01-01', 0, '2026-05-27 11:27:51'),
(3, 27, 'Backend Developer', 'TechNova', 'Developed APIs and backend services using Laravel.', '2020-01-01', '2024-01-01', 0, '2026-05-27 11:27:51'),
(4, 28, 'IT Support Technician', 'Smart IT Services', 'Handled hardware maintenance and network troubleshooting.', '2023-01-01', '2024-01-01', 0, '2026-05-27 11:27:51'),
(6, 30, 'Network Engineer', 'NetSecure Algeria', 'Configured enterprise networks and Cisco equipment.', '2021-01-01', '2024-01-01', 0, '2026-05-27 11:27:51'),
(7, 31, 'Social Media Manager', 'MediaBoost', 'Created content strategies and managed campaigns.', '2022-01-01', '2024-01-01', 0, '2026-05-27 11:27:51'),
(8, 32, 'Backend Engineer', 'CloudApps', 'Built scalable REST APIs with Node.js and MongoDB.', '2019-01-01', '2024-01-01', 0, '2026-05-27 11:27:51'),
(9, 33, 'Product Designer', 'Designify', 'Worked on SaaS products and design systems.', '2022-02-01', '2024-01-01', 0, '2026-05-27 11:27:51'),
(10, 34, 'Cloud Engineer', 'SkyCloud', 'Managed AWS infrastructure and Kubernetes clusters.', '2020-01-01', '2024-01-01', 0, '2026-05-27 11:27:51'),
(11, 25, 'Frontend Developer', 'WebSolutions DZ', 'Built responsive interfaces using React and Tailwind CSS.', '2022-01-01', '2024-01-01', 0, '2026-05-27 11:34:46'),
(12, 29, 'UI UX Designer', 'Creative Studio', 'Designed dashboards and mobile interfaces using Figma.', '2022-03-01', '2024-01-01', 0, '2026-05-28 23:56:47'),
(18, 43, 'Frontend Developer Intern', 'Dz Digital Solutions', 'Developed responsive web interfaces using HTML, CSS, JavaScript, and improved user experience for modern web applications.', '2024-12-29', '2022-01-13', 0, '2026-05-29 18:06:52');

-- --------------------------------------------------------

--
-- Table structure for table `candidate_interests`
--

CREATE TABLE `candidate_interests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `interest_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `candidate_interests`
--

INSERT INTO `candidate_interests` (`id`, `user_id`, `interest_name`, `created_at`) VALUES
(4, 26, 'Marketing', '2026-05-27 11:29:04'),
(5, 26, 'Branding', '2026-05-27 11:29:04'),
(6, 26, 'Content Creation', '2026-05-27 11:29:04'),
(7, 27, 'Backend Development', '2026-05-27 11:29:04'),
(8, 27, 'APIs', '2026-05-27 11:29:04'),
(9, 27, 'Databases', '2026-05-27 11:29:04'),
(10, 28, 'Networking', '2026-05-27 11:29:04'),
(11, 28, 'Cyber Security', '2026-05-27 11:29:04'),
(12, 28, 'IT Support', '2026-05-27 11:29:04'),
(16, 30, 'Network Engineering', '2026-05-27 11:29:04'),
(17, 30, 'Infrastructure', '2026-05-27 11:29:04'),
(18, 30, 'Cisco', '2026-05-27 11:29:04'),
(19, 31, 'Social Media', '2026-05-27 11:29:04'),
(20, 31, 'Digital Marketing', '2026-05-27 11:29:04'),
(21, 31, 'Content Strategy', '2026-05-27 11:29:04'),
(22, 32, 'NodeJS', '2026-05-27 11:29:04'),
(23, 32, 'Cloud', '2026-05-27 11:29:04'),
(24, 32, 'Scalable APIs', '2026-05-27 11:29:04'),
(25, 33, 'Product Design', '2026-05-27 11:29:04'),
(26, 33, 'Design Systems', '2026-05-27 11:29:04'),
(27, 33, 'User Experience', '2026-05-27 11:29:04'),
(28, 34, 'Cloud Computing', '2026-05-27 11:29:04'),
(29, 34, 'DevOps', '2026-05-27 11:29:04'),
(30, 34, 'Kubernetes', '2026-05-27 11:29:04'),
(31, 25, 'React JS', '2026-05-27 11:38:49'),
(32, 25, 'UI Design', '2026-05-27 11:38:49'),
(33, 25, 'Web Development', '2026-05-27 11:38:49'),
(34, 29, 'Creativity', '2026-05-28 23:56:51'),
(35, 29, 'Mobile Design', '2026-05-28 23:56:51'),
(36, 29, 'UI UX', '2026-05-28 23:56:51'),
(49, 43, 'Web Development', '2026-05-29 15:53:38'),
(50, 43, 'UI/UX Design', '2026-05-29 15:53:38'),
(51, 43, 'Frontend Development', '2026-05-29 15:53:38'),
(52, 43, 'Backend Systems', '2026-05-29 15:53:38'),
(53, 43, 'Artificial Intelligence', '2026-05-29 15:53:38'),
(54, 43, 'Tech Startups', '2026-05-29 15:53:38');

-- --------------------------------------------------------

--
-- Table structure for table `candidate_languages`
--

CREATE TABLE `candidate_languages` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `language_name` varchar(100) NOT NULL,
  `level` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `candidate_languages`
--

INSERT INTO `candidate_languages` (`id`, `user_id`, `language_name`, `level`, `created_at`) VALUES
(4, 26, 'Arabic', '100', '2026-05-27 11:31:15'),
(5, 26, 'French', '90', '2026-05-27 11:31:15'),
(6, 26, 'English', '85', '2026-05-27 11:31:15'),
(7, 27, 'Arabic', '100', '2026-05-27 11:31:15'),
(8, 27, 'French', '90', '2026-05-27 11:31:15'),
(9, 27, 'English', '90', '2026-05-27 11:31:15'),
(10, 28, 'Arabic', '100', '2026-05-27 11:31:15'),
(11, 28, 'French', '70', '2026-05-27 11:31:15'),
(15, 30, 'Arabic', '100', '2026-05-27 11:31:15'),
(16, 30, 'French', '90', '2026-05-27 11:31:15'),
(17, 30, 'English', '85', '2026-05-27 11:31:15'),
(18, 31, 'Arabic', '100', '2026-05-27 11:31:15'),
(19, 31, 'French', '85', '2026-05-27 11:31:15'),
(20, 31, 'English', '70', '2026-05-27 11:31:15'),
(21, 32, 'Arabic', '100', '2026-05-27 11:31:15'),
(22, 32, 'French', '90', '2026-05-27 11:31:15'),
(23, 32, 'English', '90', '2026-05-27 11:31:15'),
(24, 33, 'Arabic', '100', '2026-05-27 11:31:15'),
(25, 33, 'French', '85', '2026-05-27 11:31:15'),
(26, 33, 'English', '75', '2026-05-27 11:31:15'),
(27, 34, 'Arabic', '100', '2026-05-27 11:31:15'),
(28, 34, 'French', '90', '2026-05-27 11:31:15'),
(29, 34, 'English', '95', '2026-05-27 11:31:15'),
(30, 25, 'English', '70', '2026-05-27 11:38:49'),
(31, 25, 'French', '85', '2026-05-27 11:38:49'),
(32, 25, 'Arabic', '100', '2026-05-27 11:38:49'),
(33, 29, 'English', '75', '2026-05-28 23:56:51'),
(34, 29, 'French', '85', '2026-05-28 23:56:51'),
(35, 29, 'Arabic', '100', '2026-05-28 23:56:51'),
(45, 43, 'French', '85', '2026-05-29 17:47:17'),
(46, 43, 'English', '100', '2026-05-29 17:47:17');

-- --------------------------------------------------------

--
-- Table structure for table `candidate_projects`
--

CREATE TABLE `candidate_projects` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `demo_link` varchar(255) DEFAULT NULL,
  `github_link` varchar(255) DEFAULT NULL,
  `technologies` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `candidate_projects`
--

INSERT INTO `candidate_projects` (`id`, `user_id`, `title`, `description`, `image_path`, `demo_link`, `github_link`, `technologies`, `created_at`) VALUES
(2, 27, 'ERP Management System', 'Backend ERP system for company management and reporting.', 'assets/projects/erp.jpg', 'https://erp-demo.com', 'https://github.com/yacinedev/erp-system', 'PHP,Laravel,MySQL,REST API', '2026-05-27 11:32:30'),
(4, 30, 'Enterprise Network Infrastructure', 'Secure enterprise network architecture with Cisco devices.', 'assets/projects/network.jpg', NULL, NULL, 'Cisco,CCNA,Networking,Security', '2026-05-27 11:32:30'),
(5, 32, 'Realtime Chat Application', 'Realtime messaging platform with authentication and sockets.', 'assets/projects/chatapp.jpg', 'https://chat-demo.com', 'https://github.com/anisbackend/chat-app', 'NodeJS,Express,MongoDB,Socket.io', '2026-05-27 11:32:30'),
(6, 33, 'SaaS Dashboard Design', 'Modern SaaS dashboard interface with analytics pages.', 'assets/projects/dashboard-ui.jpg', 'https://dribbble.com/meriemuiux', NULL, 'Figma,Dashboard UI,Design Systems', '2026-05-27 11:32:30'),
(7, 34, 'Cloud Deployment Pipeline', 'Automated cloud deployment pipeline using Docker and Kubernetes.', 'assets/projects/cloud-devops.jpg', 'https://cloud-demo.com', 'https://github.com/zakariacloud/devops-pipeline', 'AWS,Docker,Kubernetes,CI/CD', '2026-05-27 11:32:30'),
(8, 25, 'E-Commerce Platform', 'Modern ecommerce website with responsive UI and payment integration.', NULL, 'https://amine-shop.com', '', NULL, '2026-05-27 11:38:49'),
(9, 29, 'Mobile Banking UI', 'Clean mobile banking interface designed in Figma.', NULL, 'https://behance.net/ilyes-ui', '', NULL, '2026-05-28 23:56:51'),
(12, 43, 'DzHouse Platform', 'Modern housing and job platform built with PHP, MySQL, HTML, CSS, and JavaScript featuring authentication, profiles, search system, and responsive UI.', NULL, 'https://github.com/username/dzhouse', '', NULL, '2026-05-29 15:53:38');

-- --------------------------------------------------------

--
-- Table structure for table `candidate_skills`
--

CREATE TABLE `candidate_skills` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `skill_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `candidate_skills`
--

INSERT INTO `candidate_skills` (`id`, `user_id`, `skill_name`, `created_at`) VALUES
(6, 26, 'SEO', '2026-05-27 11:35:40'),
(7, 26, 'Meta Ads', '2026-05-27 11:35:40'),
(8, 26, 'Content Marketing', '2026-05-27 11:35:40'),
(9, 26, 'Google Analytics', '2026-05-27 11:35:40'),
(10, 27, 'PHP', '2026-05-27 11:35:40'),
(11, 27, 'Laravel', '2026-05-27 11:35:40'),
(12, 27, 'MySQL', '2026-05-27 11:35:40'),
(13, 27, 'REST API', '2026-05-27 11:35:40'),
(14, 28, 'Networking', '2026-05-27 11:35:40'),
(15, 28, 'Windows Server', '2026-05-27 11:35:40'),
(16, 28, 'Hardware Maintenance', '2026-05-27 11:35:40'),
(21, 30, 'Cisco', '2026-05-27 11:35:40'),
(22, 30, 'CCNA', '2026-05-27 11:35:40'),
(23, 30, 'Network Security', '2026-05-27 11:35:40'),
(24, 31, 'Social Media Marketing', '2026-05-27 11:35:40'),
(25, 31, 'Content Creation', '2026-05-27 11:35:40'),
(26, 31, 'Instagram Marketing', '2026-05-27 11:35:40'),
(27, 32, 'NodeJS', '2026-05-27 11:35:40'),
(28, 32, 'ExpressJS', '2026-05-27 11:35:40'),
(29, 32, 'MongoDB', '2026-05-27 11:35:40'),
(30, 32, 'API Development', '2026-05-27 11:35:40'),
(31, 33, 'Figma', '2026-05-27 11:35:40'),
(32, 33, 'Design Systems', '2026-05-27 11:35:40'),
(33, 33, 'Product Design', '2026-05-27 11:35:40'),
(34, 34, 'AWS', '2026-05-27 11:35:40'),
(35, 34, 'Docker', '2026-05-27 11:35:40'),
(36, 34, 'Kubernetes', '2026-05-27 11:35:40'),
(37, 34, 'CI/CD', '2026-05-27 11:35:40'),
(38, 25, 'Tailwind CSS', '2026-05-27 11:38:49'),
(39, 25, 'React', '2026-05-27 11:38:49'),
(40, 25, 'JavaScript', '2026-05-27 11:38:49'),
(41, 25, 'CSS', '2026-05-27 11:38:49'),
(42, 25, 'HTML', '2026-05-27 11:38:49'),
(43, 29, 'UX Research', '2026-05-28 23:56:51'),
(44, 29, 'UI Design', '2026-05-28 23:56:51'),
(45, 29, 'Adobe XD', '2026-05-28 23:56:51'),
(46, 29, 'Figma', '2026-05-28 23:56:51'),
(67, 43, 'HTML', '2026-05-29 15:53:38'),
(68, 43, 'CSS', '2026-05-29 15:53:38'),
(69, 43, 'JavaScript', '2026-05-29 15:53:38'),
(70, 43, 'PHP', '2026-05-29 15:53:38'),
(71, 43, 'MySQL', '2026-05-29 15:53:38'),
(72, 43, 'React', '2026-05-29 15:53:38'),
(73, 43, 'Git', '2026-05-29 15:53:38'),
(74, 43, 'Responsive Design', '2026-05-29 15:53:38'),
(75, 43, 'UI/UX Basics', '2026-05-29 15:53:38'),
(76, 43, 'REST APIs', '2026-05-29 15:53:38');

-- --------------------------------------------------------

--
-- Table structure for table `candidate_social_links`
--

CREATE TABLE `candidate_social_links` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `platform` varchar(50) NOT NULL,
  `url` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `candidate_social_links`
--

INSERT INTO `candidate_social_links` (`id`, `user_id`, `platform`, `url`, `created_at`) VALUES
(1, 25, 'LinkedIn', 'https://linkedin.com/in/aminebelhadj', '2026-05-27 11:38:41'),
(2, 25, 'GitHub', 'https://github.com/aminebelhadj', '2026-05-27 11:38:41'),
(3, 25, 'Portfolio', 'https://amine-portfolio.com', '2026-05-27 11:38:41'),
(4, 26, 'LinkedIn', 'https://linkedin.com/in/sarahmeziane', '2026-05-27 11:38:41'),
(5, 26, 'Portfolio', 'https://sarah-marketing.com', '2026-05-27 11:38:41'),
(6, 27, 'LinkedIn', 'https://linkedin.com/in/yacinedev', '2026-05-27 11:38:41'),
(7, 27, 'GitHub', 'https://github.com/yacinedev', '2026-05-27 11:38:41'),
(8, 27, 'Portfolio', 'https://yacinedev.com', '2026-05-27 11:38:41'),
(9, 28, 'LinkedIn', 'https://linkedin.com/in/nourit', '2026-05-27 11:38:41'),
(10, 29, 'LinkedIn', 'https://linkedin.com/in/ilyesdesign', '2026-05-27 11:38:41'),
(11, 29, 'Portfolio', 'https://ilyesdesign.com', '2026-05-27 11:38:41'),
(12, 30, 'LinkedIn', 'https://linkedin.com/in/rayanenetwork', '2026-05-27 11:38:41'),
(13, 31, 'LinkedIn', 'https://linkedin.com/in/linamarketing', '2026-05-27 11:38:41'),
(14, 32, 'LinkedIn', 'https://linkedin.com/in/anisbackend', '2026-05-27 11:38:41'),
(15, 32, 'GitHub', 'https://github.com/anisbackend', '2026-05-27 11:38:41'),
(16, 32, 'Portfolio', 'https://anisbackend.dev', '2026-05-27 11:38:41'),
(17, 33, 'LinkedIn', 'https://linkedin.com/in/meriemuiux', '2026-05-27 11:38:41'),
(18, 33, 'Portfolio', 'https://meriemuiux.com', '2026-05-27 11:38:41'),
(19, 34, 'LinkedIn', 'https://linkedin.com/in/zakariacloud', '2026-05-27 11:38:41'),
(20, 34, 'GitHub', 'https://github.com/zakariacloud', '2026-05-27 11:38:41'),
(21, 34, 'Portfolio', 'https://zakariacloud.dev', '2026-05-27 11:38:41'),
(22, 43, 'github', 'https://github.com/username/dzhouse', '2026-05-29 15:53:38');

-- --------------------------------------------------------

--
-- Table structure for table `companies_profiles`
--

CREATE TABLE `companies_profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `industry` varchar(100) DEFAULT NULL,
  `size` varchar(50) DEFAULT NULL,
  `founded_year` year(4) DEFAULT NULL,
  `employees_count` int(11) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `specialties` text DEFAULT NULL,
  `benefits` text DEFAULT NULL,
  `mission` text DEFAULT NULL,
  `vision` text DEFAULT NULL,
  `working_hours` varchar(100) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `logo_url` varchar(255) DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `linkedin` varchar(255) DEFAULT NULL,
  `facebook` varchar(255) DEFAULT NULL,
  `twitter` varchar(255) DEFAULT NULL,
  `instagram` varchar(255) DEFAULT NULL,
  `github` varchar(255) DEFAULT NULL,
  `rating` decimal(2,1) DEFAULT 0.0,
  `total_reviews` int(11) DEFAULT 0,
  `reviews_count` int(11) DEFAULT 0,
  `profile_views` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `companies_profiles`
--

INSERT INTO `companies_profiles` (`id`, `user_id`, `company_name`, `industry`, `size`, `founded_year`, `employees_count`, `phone`, `email`, `city`, `address`, `website`, `description`, `specialties`, `benefits`, `mission`, `vision`, `working_hours`, `is_verified`, `updated_at`, `logo_url`, `cover_image`, `linkedin`, `facebook`, `twitter`, `instagram`, `github`, `rating`, `total_reviews`, `reviews_count`, `profile_views`) VALUES
(1, 11, 'Yassir', 'Technology & Mobility', '500-1000', '2017', 850, '0550123456', 'contact@yassir.dz', 'Alger', 'Hydra, Alger', 'https://yassir.com', 'Yassir is one of the leading super apps in Algeria offering ride hailing and delivery services.', 'Mobile Apps, Ride Hailing, Delivery, Fintech', 'Remote Work, Bonuses, Insurance', 'Make everyday life easier through technology.', 'Become the leading mobility platform in Africa.', '08:00 - 17:00', 1, '2026-05-29 18:32:37', 'assets/logos/yassir.png', NULL, NULL, NULL, NULL, NULL, NULL, 4.9, 320, 320, 5),
(2, 12, 'Condor Electronics', 'Electronics', '1000+', '2002', 3200, '0560234567', 'hr@condor.dz', 'Bordj Bou Arreridj', 'BBA Industrial Zone', 'https://condor.dz', 'Condor Electronics is a major Algerian electronics company.', 'Smartphones, Electronics, Home Appliances', 'Health Insurance, Training', 'Deliver quality electronics products.', 'Innovate electronics in Algeria.', '08:00 - 17:00', 1, '2026-05-29 12:09:23', 'assets/logos/condor.png', NULL, NULL, NULL, NULL, NULL, NULL, 4.6, 210, 210, 1),
(3, 13, 'Ooredoo Algeria', 'Telecommunications', '1000+', '2004', 2400, '0555345678', 'careers@ooredoo.dz', 'Alger', 'Ouled Fayet, Alger', 'https://ooredoo.dz', 'Ooredoo Algeria provides mobile internet and enterprise services.', 'Telecom, Internet, Enterprise Services', 'Bonuses, Flexible Hours', 'Connect people through technology.', 'Build the future of telecom.', '08:00 - 17:00', 1, '2026-05-29 19:30:14', 'assets/logos/ooredoo.png', NULL, NULL, NULL, NULL, NULL, NULL, 4.8, 280, 280, 2),
(4, 14, 'Cevital', 'Industry & Food', '1000+', '1998', 5000, '0555456789', 'jobs@cevital.com', 'Bejaia', 'Ihaddaden, Bejaia', 'https://cevital.com', 'Cevital is one of the largest private companies in Algeria.', 'Food Industry, Manufacturing, Logistics', 'Career Growth, Bonuses', 'Support Algerian industry.', 'Expand globally with innovation.', '08:00 - 17:00', 1, '2026-05-27 11:15:24', 'assets/logos/cevital.png', NULL, NULL, NULL, NULL, NULL, NULL, 4.7, 260, 260, 0),
(5, 15, 'Djezzy', 'Telecommunications', '1000+', '2001', 2100, '0668123456', 'careers@djezzy.dz', 'Alger', 'Cheraga, Alger', 'https://djezzy.dz', 'Djezzy is one of the biggest telecom operators in Algeria.', 'Telecom, Mobile Services, 4G/5G', 'Bonuses, Insurance', 'Improve digital communication.', 'Lead telecom innovation.', '08:00 - 17:00', 1, '2026-05-29 18:33:03', 'assets/logos/djezzy.png', NULL, NULL, NULL, NULL, NULL, NULL, 4.5, 190, 190, 2),
(6, 16, 'Temtem', 'Transportation', '100-200', '2018', 180, '0777123456', 'contact@temtem.one', 'Oran', 'Akid Lotfi, Oran', 'https://temtem.one', 'Temtem is an Algerian ride hailing platform.', 'Transportation, Mobile Apps, Ride Sharing', 'Flexible Work, Bonuses', 'Provide smart transportation.', 'Modernize urban mobility.', '08:00 - 17:00', 1, '2026-05-29 19:27:02', 'assets/logos/temtem.png', NULL, NULL, NULL, NULL, NULL, NULL, 4.4, 120, 120, 4),
(7, 17, 'SkyTech', 'Cloud Computing', '100-200', '2015', 140, '0555000005', 'contact@skytech.dz', 'Setif', 'El Eulma, Setif', 'https://skytech.dz', 'SkyTech provides cloud and infrastructure solutions.', 'Cloud Computing, DevOps, Infrastructure', 'Remote Work, Training', 'Help companies scale digitally.', 'Lead cloud transformation.', '08:00 - 17:00', 1, '2026-05-29 19:22:16', 'assets/logos/skytech.png', NULL, '', '', '', '', '', 4.3, 90, 90, 2),
(8, 24, 'NovaTech Solutions', 'Information Technology', '50-100', '2020', 75, '0555998877', 'contact@novatech.dz', 'Djelfa', 'Centre Ville', 'https://novatech.dz', 'NovaTech delivers innovative software solutions.\r\nNovaTech delivers innovative software solutions.\r\nNovaTech delivers innovative software solutions.\r\nNovaTech delivers innovative software solutions.', 'Software Development, AI, Web Apps,Software Development, AI, Web Apps', 'Remote Work, Modern Offices\r\nRemote Work, Modern Offices\r\nRemote Work, Modern Offices', 'Create impactful digital products.\r\nCreate impactful digital products.\r\nCreate impactful digital products.', 'Become a top Algerian tech company.\r\nBecome a top Algerian tech company.\r\nBecome a top Algerian tech company.', '08:00 - 17:00', 1, '2026-05-29 19:22:08', 'assets/logos/novatech.png', NULL, '', '', '', '', '', 4.2, 60, 60, 10),
(9, 45, 'GreenBuild Algeria', 'Construction & Architecture', '50-100', '2018', 72, '0550000009', 'contact@greenbuild-dz.com', 'Oran', 'El Eulma, Setif', 'https://greenbuild-dz.com', 'GreenBuild Algeria is a modern construction and architecture company focused on sustainable buildings, smart urban projects, and innovative engineering solutions. We work with both public and private sectors to deliver high-quality infrastructure across Algeria.', 'Construction, Architecture, Urban Planning, Smart Buildings, Engineering, Infrastructure', 'Health insurance, Paid leave, Career growth opportunities, Training programs, Flexible work environment, Team events', 'To create sustainable and modern construction solutions that improve everyday life and support the future of smart cities in Algeria.', 'To become one of the leading construction and engineering companies in North Africa through innovation, quality, and environmental responsibility.', 'sunday - Thursday | 8:00 AM - 5:00 PM', 0, '2026-05-29 20:07:51', '', NULL, 'https://linkedin.com/company/greenbuild-algeria', 'https://facebook.com/greenbuild.algeria', 'https://twitter.com/greenbuild_dz', 'https://instagram.com/greenbuild.algeria', 'https://github.com/greenbuild-dz', 0.0, 0, 0, 6);

-- --------------------------------------------------------

--
-- Table structure for table `company_reviews`
--

CREATE TABLE `company_reviews` (
  `id` int(11) NOT NULL,
  `company_user_id` int(11) NOT NULL,
  `candidate_user_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL,
  `review` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `company_reviews`
--

INSERT INTO `company_reviews` (`id`, `company_user_id`, `candidate_user_id`, `rating`, `review`, `created_at`) VALUES
(7, 17, 25, 5, '', '2026-05-27 16:54:07'),
(8, 24, 43, 2, '', '2026-05-29 18:09:23'),
(9, 45, 43, 1, '', '2026-05-29 19:19:55'),
(10, 16, 43, 1, '', '2026-05-29 19:22:27'),
(11, 13, 43, 4, '', '2026-05-29 19:30:14');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(200) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `replied_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `full_name`, `email`, `subject`, `message`, `is_read`, `replied_at`, `created_at`) VALUES
(1, 'Ahmed Dendani', 'bouchrachebili81@gmail.com', 'blog', 'yes', 1, '2026-05-28 03:18:17', '2026-05-28 03:04:15');

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `requirements` text DEFAULT NULL,
  `responsibilities` text DEFAULT NULL,
  `specialty` varchar(100) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `contract_type` varchar(50) DEFAULT NULL,
  `salary` varchar(100) DEFAULT NULL,
  `skills` text DEFAULT NULL,
  `benefits` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime DEFAULT NULL,
  `work_mode` varchar(50) DEFAULT NULL,
  `experience` varchar(50) DEFAULT NULL,
  `status` enum('open','closed') DEFAULT 'open',
  `views_count` int(11) DEFAULT 0,
  `applications_count` int(11) DEFAULT 0,
  `experience_level` varchar(100) NOT NULL DEFAULT '',
  `education_level` varchar(100) DEFAULT NULL,
  `language_required` varchar(255) DEFAULT NULL,
  `duration_days` int(11) NOT NULL DEFAULT 3
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jobs`
--

INSERT INTO `jobs` (`id`, `user_id`, `title`, `description`, `requirements`, `responsibilities`, `specialty`, `category`, `city`, `contract_type`, `salary`, `skills`, `benefits`, `created_at`, `expires_at`, `work_mode`, `experience`, `status`, `views_count`, `applications_count`, `experience_level`, `education_level`, `language_required`, `duration_days`) VALUES
(1, 11, 'Frontend Developer', 'Join Yassir to build modern web applications and improve user experience.', 'Strong knowledge of HTML CSS JavaScript and React.', 'Develop responsive interfaces and collaborate with backend teams.', 'Web Development', 'IT & Software', 'Djelfa', '', '120000 DZD', 'HTML,CSS,JavaScript,React', '123\r\n456\r\n789', '2026-05-27 11:17:05', '2026-06-27 16:36:13', 'Hybrid', '2 Years', 'open', 550, 89, 'Mid Level', 'Bachelor Degree', 'English, French', 30),
(2, 11, 'Mobile App Developer', 'Develop scalable mobile applications for Yassir platform.', 'Experience with Flutter or React Native.', 'Build and maintain mobile features.', 'Mobile Development', 'IT & Software', 'Alger', 'Full Time', '140000 DZD', 'Flutter,React Native,Firebase', 'Health Insurance\r\nRemote Work\r\nFlexible Hours\r\nPaid Vacation', '2026-05-27 11:17:05', NULL, 'Remote', '3 Years', 'open', 628, 120, 'Senior', 'Bachelor Degree', 'English', 30),
(3, 12, 'IT Support Technician', 'Provide technical support for Condor internal systems.', 'Knowledge in networking and troubleshooting.', 'Maintain computers and support staff.', 'Technical Support', 'IT', 'Bordj Bou Arreridj', 'Full Time', '70000 DZD', 'Networking,Windows,Hardware', NULL, '2026-05-27 11:17:05', NULL, 'On Site', '1 Year', 'closed', 212, 35, 'Junior', 'TS Degree', 'French', 20),
(4, 13, 'Network Engineer', 'Manage telecom infrastructure and optimize network systems.', 'CCNA knowledge required.', 'Monitor and maintain network performance.', 'Networking', 'Telecommunications', 'Alger', 'Full Time', '150000 DZD', 'CCNA,Cisco,Networking', NULL, '2026-05-27 11:17:05', NULL, 'On Site', '3 Years', 'open', 781, 145, 'Senior', 'Engineering Degree', 'English, French', 30),
(5, 13, 'Customer Support Agent', 'Assist Ooredoo customers and solve technical issues.', 'Communication skills required.', 'Answer customer requests and incidents.', 'Customer Service', 'Support', 'Alger', 'Full Time', '65000 DZD', 'Communication,CRM', NULL, '2026-05-27 11:17:05', NULL, 'On Site', 'No Experience', 'open', 312, 55, 'Entry Level', 'Bachelor Degree', 'Arabic, French', 15),
(6, 14, 'Production Manager', 'Supervise manufacturing operations at Cevital.', 'Leadership and industrial management experience.', 'Manage teams and production quality.', 'Management', 'Industry', 'Bejaia', 'Full Time', '180000 DZD', 'Management,Production,Leadership', NULL, '2026-05-27 11:17:05', NULL, 'On Site', '5 Years', 'open', 490, 77, 'Manager', 'Master Degree', 'French', 30),
(7, 15, 'Digital Marketing Specialist', 'Create marketing campaigns for Djezzy services.', 'Experience with Meta Ads and Google Ads.', 'Manage social media and advertising campaigns.', 'Marketing', 'Marketing', 'Alger', 'Full Time', '95000 DZD', 'Marketing,SEO,Meta Ads', NULL, '2026-05-27 11:17:05', NULL, 'Hybrid', '2 Years', 'open', 432, 62, 'Mid Level', 'Bachelor Degree', 'French, English', 25),
(8, 15, 'Cyber Security Analyst', 'Protect Djezzy systems against cyber threats.', 'Knowledge of cyber security principles.', 'Monitor threats and secure infrastructure.', 'Cyber Security', 'Security', 'Alger', 'Full Time', '170000 DZD', 'Security,SIEM,Networking', NULL, '2026-05-27 11:17:05', NULL, 'On Site', '4 Years', 'open', 691, 133, 'Senior', 'Engineering Degree', 'English', 30),
(9, 16, 'UI UX Designer', 'Design user friendly interfaces for Temtem applications.', 'Portfolio required.', 'Create wireframes and design systems.', 'Design', 'Design', 'Oran', 'Contract', '90000 DZD', 'Figma,UI,UX', NULL, '2026-05-27 11:17:05', NULL, 'Remote', '2 Years', 'open', 282, 48, 'Mid Level', 'Bachelor Degree', 'English', 20),
(10, 17, 'Cloud Engineer', 'Deploy and manage cloud infrastructure solutions.', 'AWS or Azure experience required.', 'Maintain scalable cloud environments.', 'Cloud Computing', 'IT Infrastructure', 'Setif', 'Full Time', '200000 DZD', 'AWS,Azure,Docker,Kubernetes', NULL, '2026-05-27 11:17:05', NULL, 'Hybrid', '4 Years', 'open', 852, 160, 'Senior', 'Engineering Degree', 'English', 30),
(11, 24, 'AI Engineer', 'Build intelligent AI powered applications at NovaTech.', 'Python and Machine Learning skills required.', 'Develop AI models and APIs.', 'Artificial Intelligence', 'IT & Software', 'Djelfa', 'Full Time', '160000 DZD', 'Python,Machine Learning,TensorFlow', NULL, '2026-05-27 11:17:05', NULL, 'Remote', '3 Years', 'open', 514, 96, 'Senior', 'Master Degree', 'English', 30),
(12, 24, 'Backend PHP Developer', 'Develop backend systems and REST APIs.', 'Laravel and MySQL experience required.', 'Maintain scalable backend architecture.', 'Backend Development', 'IT & Software', 'Djelfa', 'Full Time', '110000 DZD', 'PHP,Laravel,MySQL', NULL, '2026-05-27 11:17:05', NULL, 'Remote', '2 Years', 'open', 471, 81, 'Mid Level', 'Bachelor Degree', 'English, French', 30),
(17, 45, 'Civil Engineer', 'GreenBuild Algeria is looking for a motivated Civil Engineer to join our growing engineering department in Oran. The selected candidate will work on modern residential and commercial construction projects, collaborating with architects, project managers, and technical teams to deliver high-quality infrastructure solutions. You will participate in planning, structural analysis, site supervision, and project execution while ensuring safety and quality standards are respected. This is a great opportunity to grow within a modern and innovative company focused on sustainable construction.', 'Bachelor degree in Civil Engineering or related field. Good knowledge of construction materials and structural analysis. Experience with AutoCAD and engineering software is required. Ability to work in teams, communicate effectively, and manage deadlines. Previous site experience is considered an advantage.', 'Supervise construction activities on-site. Prepare technical documents and engineering reports. Coordinate with contractors and project teams. Ensure safety regulations and quality standards are followed. Monitor project progress and provide solutions for technical issues.', 'Civil Engineering', 'Construction', 'Oran', 'Full-time', '120000 - 150000 DZD', 'AutoCAD, Structural Analysis, Site Supervision, Project Management, Engineering Drawings, Communication Skills, Construction Planning', 'Health insurance, Paid leave, Professional training, Career growth opportunities, Transportation support, Modern work environment', '2026-05-29 19:15:00', '2026-06-28 20:15:00', 'On-site', '2+ years', 'open', 2, 1, 'Mid Level', 'Bachelor Degree', 'French, English', 30),
(18, 45, 'Architect Designer', 'GreenBuild Algeria is hiring a creative Architect Designer to join our architecture and urban design team. The candidate will contribute to designing modern sustainable buildings and smart urban projects across Algeria. You will work closely with engineers and clients to transform ideas into functional and visually appealing spaces. This role is ideal for someone passionate about innovation, design aesthetics, and modern architecture.', 'Degree in Architecture or Urban Design. Strong creativity and attention to detail. Experience using AutoCAD, SketchUp, Revit, or 3D visualization tools. Ability to create modern architectural concepts and communicate ideas effectively. Portfolio required.', 'Create architectural plans and 3D concepts. Collaborate with engineering teams and project managers. Participate in client meetings and presentations. Prepare design documents and technical drawings. Ensure projects meet quality and sustainability standards.', 'Architecture', 'Architecture', 'Algiers', 'Full-time', '140000 - 170000 DZD', 'AutoCAD, SketchUp, Revit, 3D Design, Creativity, Interior Design, Urban Planning, Teamwork', 'Flexible schedule, Bonuses, Career development, Training programs, Creative workspace, Hybrid work model', '2026-05-29 19:15:00', '2026-06-23 20:15:00', 'Hybrid', '1+ years', 'open', 1, 0, 'Junior', 'Master Degree', 'English, French', 25),
(19, 45, 'Project Manager', 'GreenBuild Algeria is seeking an experienced Project Manager to oversee large-scale infrastructure and construction projects. The ideal candidate will coordinate teams, manage budgets, supervise timelines, and ensure successful project delivery from planning to completion. You will play a key role in maintaining communication between departments and ensuring operational efficiency across multiple ongoing projects.', 'Bachelor degree in Management, Engineering, or related field. Strong leadership and organizational skills. Experience managing construction or infrastructure projects. Ability to handle budgets, schedules, and multidisciplinary teams. Excellent communication and decision-making abilities.', 'Plan and supervise project execution. Coordinate engineers, contractors, and suppliers. Monitor project budgets and deadlines. Prepare progress reports and manage risks. Ensure compliance with company standards and client expectations.', 'Project Management', 'Management', 'Constantine', 'Full-time', '180000 - 230000 DZD', 'Leadership, Project Planning, Budget Management, Communication, Risk Management, Team Coordination, Reporting', 'Company car, Paid vacations, Health insurance, Performance bonuses, Leadership training, Career advancement', '2026-05-29 19:15:00', '2026-07-08 20:15:00', 'On-site', '4+ years', 'open', 0, 0, 'Senior', 'Bachelor Degree', 'French', 40),
(20, 45, 'Interior Designer', 'GreenBuild Algeria is looking for a talented Interior Designer passionate about modern interiors and innovative living spaces. The selected candidate will work on residential and commercial projects, helping clients transform their ideas into elegant and functional environments. You will collaborate with architects, engineers, and customers to create inspiring interior concepts aligned with current trends and company standards.', 'Degree or certification in Interior Design or related field. Creativity and artistic sense are essential. Experience with design software such as Photoshop, Illustrator, or 3D tools is appreciated. Strong communication skills and ability to understand client needs.', 'Design interior concepts and mood boards. Select furniture, colors, and materials. Meet with clients to understand project requirements. Coordinate with suppliers and contractors. Ensure projects are delivered according to design standards.', 'Interior Design', 'Design', 'Annaba', 'Part-time', '90000 - 120000 DZD', 'Interior Planning, Creativity, Adobe Photoshop, Space Planning, Customer Communication, Decoration, 3D Visualization', 'Remote flexibility, Friendly environment, Creative projects, Training opportunities, Flexible schedule, Team events', '2026-05-29 19:15:00', '2026-06-18 20:15:00', 'Hybrid', '1 year', 'open', 0, 0, 'Entry Level', 'Bachelor Degree', 'Arabic, French', 20);

-- --------------------------------------------------------

--
-- Table structure for table `job_alerts`
--

CREATE TABLE `job_alerts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `alert_name` varchar(255) NOT NULL,
  `keywords` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `specialty` varchar(100) DEFAULT NULL,
  `contract_type` varchar(100) DEFAULT NULL,
  `experience_level` varchar(100) DEFAULT NULL,
  `work_type` varchar(100) DEFAULT NULL,
  `frequency` enum('instant','daily','weekly') DEFAULT 'daily',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_alerts`
--

INSERT INTO `job_alerts` (`id`, `user_id`, `alert_name`, `keywords`, `city`, `category`, `specialty`, `contract_type`, `experience_level`, `work_type`, `frequency`, `created_at`) VALUES
(2, 26, 'Marketing Opportunities', 'SEO,Marketing,Social Media', 'Oran', 'Marketing', 'Digital Marketing', 'Full Time', 'Senior', 'Hybrid', 'weekly', '2026-05-27 11:48:17'),
(3, 27, 'Backend Developer Jobs', 'PHP,Laravel,API', 'Constantine', 'IT & Software', 'Backend Development', 'Full Time', 'Senior', 'Remote', 'daily', '2026-05-27 11:48:17'),
(4, 28, 'IT Support Positions', 'Networking,IT Support,Hardware', 'Annaba', 'IT', 'Technical Support', 'Contract', 'Junior', 'On Site', 'weekly', '2026-05-27 11:48:17'),
(5, 29, 'UI UX Designer Alert', 'Figma,UI,UX', 'Oran', 'Design', 'UI UX Design', 'Freelance', 'Mid Level', 'Remote', 'instant', '2026-05-27 11:48:17'),
(6, 30, 'Network Engineer Jobs', 'Cisco,CCNA,Security', 'Setif', 'Networking', 'Network Engineering', 'Full Time', 'Senior', 'On Site', 'daily', '2026-05-27 11:48:17'),
(7, 31, 'Social Media Jobs', 'Instagram,Content,Branding', 'Blida', 'Marketing', 'Social Media', 'Part Time', 'Mid Level', 'Hybrid', 'weekly', '2026-05-27 11:48:17'),
(8, 32, 'NodeJS Backend Roles', 'NodeJS,Express,MongoDB', 'Alger', 'IT & Software', 'Backend Development', 'Full Time', 'Senior', 'Remote', 'instant', '2026-05-27 11:48:17'),
(9, 33, 'Product Design Alert', 'Product Design,Figma,UX', 'Tlemcen', 'Design', 'Product Design', 'Freelance', 'Mid Level', 'Remote', 'daily', '2026-05-27 11:48:17'),
(10, 34, 'Cloud DevOps Jobs', 'AWS,Docker,Kubernetes', 'Bejaia', 'Cloud Computing', 'DevOps Engineering', 'Full Time', 'Senior', 'Remote', 'instant', '2026-05-27 11:48:17'),
(11, 27, 'Frontend Developer', 'Frontend Developer', 'Tlemcen', 'IT & Technology', 'Full Stack', '', '', '', 'weekly', '2026-05-28 01:27:47'),
(12, 43, 'developer', 'developer', 'Laghouat', 'Design', 'Backend Development', 'CDD', 'Junior Level', 'On_Site', 'instant', '2026-05-29 18:37:45');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `receiver_id` int(11) DEFAULT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `related_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `is_read` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `receiver_id`, `sender_id`, `title`, `type`, `related_id`, `message`, `is_read`, `created_at`) VALUES
(1, 25, 25, 11, 'Application Accepted', 'application', 1, 'Yassir accepted your application for Frontend Developer.', 1, '2026-05-27 11:46:08'),
(2, 27, 27, 12, 'Application Under Review', 'application', 2, 'Condor Electronics is reviewing your Backend Developer application.', 1, '2026-05-27 11:46:08'),
(3, 29, 29, 13, 'Interview Invitation', 'interview', 3, 'Ooredoo Algeria invited you for an interview for the UI UX Designer position.', 0, '2026-05-27 11:46:08'),
(4, 30, 30, 14, 'Application Accepted', 'application', 4, 'Cevital accepted your application for Network Engineer.', 1, '2026-05-27 11:46:08'),
(5, 32, 32, 15, 'New Job Match', 'match', 5, 'Djezzy found a new Backend Engineer job matching your profile.', 0, '2026-05-27 11:46:08'),
(6, 34, 34, 16, 'Interview Scheduled', 'interview', 6, 'Temtem scheduled an interview for the Cloud Engineer position.', 0, '2026-05-27 11:46:08'),
(7, 31, 31, 17, 'Application Rejected', 'application', 7, 'SkyTech decided not to continue with your application.', 1, '2026-05-27 11:46:08'),
(8, 26, 26, 24, 'New Job Match', 'match', 8, 'NovaTech Solutions posted a new marketing opportunity for you.', 0, '2026-05-27 11:46:08'),
(9, 25, 25, 11, 'Interview Reminder', 'interview', 9, 'Reminder: your interview with Yassir is tomorrow at 15:00.', 0, '2026-05-27 11:46:08'),
(10, 33, 33, 13, 'Portfolio Viewed', 'profile', 10, 'Ooredoo Algeria viewed your design portfolio.', 1, '2026-05-27 11:46:08'),
(11, 12, 12, 25, '', 'application', 11, 'Amine Belhadj applied for IT Support Technician', 0, '2026-05-27 16:40:28'),
(13, 17, NULL, NULL, '', 'job_status_changed', 10, 'Job \"Cloud Engineer\" has been closed.', 0, '2026-05-28 09:42:59'),
(14, 17, NULL, NULL, '', 'job_status_changed', 10, 'Job \"Cloud Engineer\" has been reopened.', 0, '2026-05-28 09:43:04'),
(15, 17, NULL, NULL, '', 'job_status_changed', 10, 'Job \"Cloud Engineer\" has been closed.', 0, '2026-05-28 09:43:06'),
(16, 17, NULL, NULL, '', 'job_status_changed', 10, 'Job \"Cloud Engineer\" has been reopened.', 0, '2026-05-28 09:43:10'),
(17, 24, NULL, NULL, '', 'job_status_changed', 11, 'Job \"AI Engineer\" has been closed.', 0, '2026-05-28 12:56:59'),
(18, 24, NULL, NULL, '', 'job_status_changed', 11, 'Job \"AI Engineer\" has been reopened.', 0, '2026-05-28 12:57:03'),
(19, 17, 17, 43, '', 'application', 12, 'Chebili Bouchra applied for Cloud Engineer', 0, '2026-05-29 18:31:48'),
(20, 45, 45, 43, '', 'application', 13, 'Chebili Bouchra applied for Civil Engineer', 0, '2026-05-29 19:19:06'),
(22, 43, NULL, 45, '', 'accepted', 17, 'Congratulations! Your application for \"Civil Engineer\" at GreenBuild Algeria has been accepted. Interview date: June 04, 2026 at 8:00 AM', 0, '2026-05-29 19:46:07');

-- --------------------------------------------------------

--
-- Table structure for table `saved_jobs`
--

CREATE TABLE `saved_jobs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `job_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `saved_jobs`
--

INSERT INTO `saved_jobs` (`id`, `user_id`, `job_id`, `created_at`) VALUES
(47, 25, 9, '2026-05-27 16:20:12'),
(52, 25, 3, '2026-05-27 16:53:36'),
(53, 32, 5, '2026-05-29 10:12:02'),
(62, 43, 8, '2026-05-29 18:32:55'),
(63, 43, 7, '2026-05-29 18:33:07'),
(64, 43, 12, '2026-05-29 18:33:24'),
(65, 43, 11, '2026-05-29 18:33:49'),
(66, 43, 17, '2026-05-29 19:19:07');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `role` enum('candidate','company','admin') NOT NULL,
  `email_verified` tinyint(1) DEFAULT 0,
  `verification_token` varchar(255) DEFAULT NULL,
  `token_expires` datetime DEFAULT NULL,
  `reset_token` varchar(6) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password_hash`, `is_verified`, `created_at`, `role`, `email_verified`, `verification_token`, `token_expires`, `reset_token`, `reset_expires`) VALUES
(11, 'contact@yassir.dz', '$2y$10$EoQHKHQcJdoDl663aVIemeC2DY55gAUHe/61myauAs0p.FOO8JxzO', 1, '2026-05-27 11:10:00', 'company', 1, NULL, NULL, NULL, NULL),
(12, 'hr@condor.dz', '$2y$10$EoQHKHQcJdoDl663aVIemeC2DY55gAUHe/61myauAs0p.FOO8JxzO', 1, '2026-05-27 11:10:00', 'company', 1, NULL, NULL, NULL, NULL),
(13, 'careers@ooredoo.dz', '$2y$10$EoQHKHQcJdoDl663aVIemeC2DY55gAUHe/61myauAs0p.FOO8JxzO', 1, '2026-05-27 11:10:00', 'company', 1, NULL, NULL, NULL, NULL),
(14, 'jobs@cevital.com', '$2y$10$EoQHKHQcJdoDl663aVIemeC2DY55gAUHe/61myauAs0p.FOO8JxzO', 1, '2026-05-27 11:10:00', 'company', 1, NULL, NULL, NULL, NULL),
(15, 'careers@djezzy.dz', '$2y$10$EoQHKHQcJdoDl663aVIemeC2DY55gAUHe/61myauAs0p.FOO8JxzO', 1, '2026-05-27 11:10:00', 'company', 1, NULL, NULL, NULL, NULL),
(16, 'contact@temtem.one', '$2y$10$EoQHKHQcJdoDl663aVIemeC2DY55gAUHe/61myauAs0p.FOO8JxzO', 1, '2026-05-27 11:10:00', 'company', 1, NULL, NULL, NULL, NULL),
(17, 'contact@skytech.dz', '$2y$10$EoQHKHQcJdoDl663aVIemeC2DY55gAUHe/61myauAs0p.FOO8JxzO', 1, '2026-05-27 11:10:00', 'company', 1, NULL, NULL, NULL, NULL),
(24, 'contact@novatech.dz', '$2y$10$EoQHKHQcJdoDl663aVIemeC2DY55gAUHe/61myauAs0p.FOO8JxzO', 1, '2026-05-27 11:10:00', 'company', 1, NULL, NULL, NULL, NULL),
(25, 'amine.belhadj@gmail.com', '$2y$10$EoQHKHQcJdoDl663aVIemeC2DY55gAUHe/61myauAs0p.FOO8JxzO', 1, '2026-05-27 11:10:00', 'candidate', 1, NULL, NULL, NULL, NULL),
(26, 'sarah.meziane@gmail.com', '$2y$10$EoQHKHQcJdoDl663aVIemeC2DY55gAUHe/61myauAs0p.FOO8JxzO', 1, '2026-05-27 11:10:00', 'candidate', 1, NULL, NULL, NULL, NULL),
(27, 'yacine.dev@gmail.com', '$2y$10$EoQHKHQcJdoDl663aVIemeC2DY55gAUHe/61myauAs0p.FOO8JxzO', 1, '2026-05-27 11:10:00', 'candidate', 1, NULL, NULL, NULL, NULL),
(28, 'nour.it@gmail.com', '$2y$10$EoQHKHQcJdoDl663aVIemeC2DY55gAUHe/61myauAs0p.FOO8JxzO', 1, '2026-05-27 11:10:00', 'candidate', 1, NULL, NULL, NULL, NULL),
(29, 'ilyes.design@gmail.com', '$2y$10$EoQHKHQcJdoDl663aVIemeC2DY55gAUHe/61myauAs0p.FOO8JxzO', 1, '2026-05-27 11:10:00', 'candidate', 1, NULL, NULL, NULL, NULL),
(30, 'rayane.network@gmail.com', '$2y$10$EoQHKHQcJdoDl663aVIemeC2DY55gAUHe/61myauAs0p.FOO8JxzO', 1, '2026-05-27 11:10:00', 'candidate', 1, NULL, NULL, NULL, NULL),
(31, 'lina.marketing@gmail.com', '$2y$10$EoQHKHQcJdoDl663aVIemeC2DY55gAUHe/61myauAs0p.FOO8JxzO', 1, '2026-05-27 11:10:00', 'candidate', 1, NULL, NULL, NULL, NULL),
(32, 'anis.backend@gmail.com', '$2y$10$EoQHKHQcJdoDl663aVIemeC2DY55gAUHe/61myauAs0p.FOO8JxzO', 1, '2026-05-27 11:10:00', 'candidate', 1, NULL, NULL, NULL, NULL),
(33, 'meriem.uiux@gmail.com', '$2y$10$EoQHKHQcJdoDl663aVIemeC2DY55gAUHe/61myauAs0p.FOO8JxzO', 1, '2026-05-27 11:10:00', 'candidate', 1, NULL, NULL, NULL, NULL),
(34, 'zakaria.cloud@gmail.com', '$2y$10$EoQHKHQcJdoDl663aVIemeC2DY55gAUHe/61myauAs0p.FOO8JxzO', 1, '2026-05-27 11:10:00', 'candidate', 1, NULL, NULL, NULL, NULL),
(35, 'admin@jobdz.dz', '$2y$10$EoQHKHQcJdoDl663aVIemeC2DY55gAUHe/61myauAs0p.FOO8JxzO', 1, '2026-05-27 11:10:00', 'admin', 1, NULL, NULL, NULL, NULL),
(43, 'bouchrachebili81@gmail.com', '$argon2id$v=19$m=65536,t=4,p=3$RURJMjRJNlFVTzRwcFFVTA$IcmJ7AkxW2dGq/mdf4r7iG0xeFNVctfekJHCL0LdLZE', 1, '2026-05-29 14:50:53', 'candidate', 0, NULL, NULL, NULL, NULL),
(45, 'contact@greenbuild-dz.com', '$argon2id$v=19$m=65536,t=4,p=3$R2pkaHdWakw1RGVxUldJOA$Kq++noUtsw/xvfGa3r3zk0kGIn6/DmAcFucKG+hH+lc', 1, '2026-05-29 18:45:27', 'company', 0, NULL, NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_applications_user` (`user_id`);

--
-- Indexes for table `candidates_profiles`
--
ALTER TABLE `candidates_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `candidate_educations`
--
ALTER TABLE `candidate_educations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_education_user` (`user_id`);

--
-- Indexes for table `candidate_experiences`
--
ALTER TABLE `candidate_experiences`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_experience_user` (`user_id`);

--
-- Indexes for table `candidate_interests`
--
ALTER TABLE `candidate_interests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_interest_user` (`user_id`);

--
-- Indexes for table `candidate_languages`
--
ALTER TABLE `candidate_languages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_language_user` (`user_id`);

--
-- Indexes for table `candidate_projects`
--
ALTER TABLE `candidate_projects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_project_user` (`user_id`);

--
-- Indexes for table `candidate_skills`
--
ALTER TABLE `candidate_skills`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_skill_user` (`user_id`);

--
-- Indexes for table `candidate_social_links`
--
ALTER TABLE `candidate_social_links`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_social_user` (`user_id`);

--
-- Indexes for table `companies_profiles`
--
ALTER TABLE `companies_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `company_reviews`
--
ALTER TABLE `company_reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_review` (`company_user_id`,`candidate_user_id`),
  ADD KEY `fk_candidate_review` (`candidate_user_id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `job_alerts`
--
ALTER TABLE `job_alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_job_alerts_user` (`user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_notifications_receiver` (`receiver_id`),
  ADD KEY `fk_notifications_sender` (`sender_id`);

--
-- Indexes for table `saved_jobs`
--
ALTER TABLE `saved_jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_saved_jobs_user` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_verification_token` (`verification_token`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `candidates_profiles`
--
ALTER TABLE `candidates_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `candidate_educations`
--
ALTER TABLE `candidate_educations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `candidate_experiences`
--
ALTER TABLE `candidate_experiences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `candidate_interests`
--
ALTER TABLE `candidate_interests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `candidate_languages`
--
ALTER TABLE `candidate_languages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `candidate_projects`
--
ALTER TABLE `candidate_projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `candidate_skills`
--
ALTER TABLE `candidate_skills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `candidate_social_links`
--
ALTER TABLE `candidate_social_links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `companies_profiles`
--
ALTER TABLE `companies_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `company_reviews`
--
ALTER TABLE `company_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `job_alerts`
--
ALTER TABLE `job_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `saved_jobs`
--
ALTER TABLE `saved_jobs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `fk_applications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `candidates_profiles`
--
ALTER TABLE `candidates_profiles`
  ADD CONSTRAINT `candidates_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `candidate_educations`
--
ALTER TABLE `candidate_educations`
  ADD CONSTRAINT `fk_education_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `candidate_experiences`
--
ALTER TABLE `candidate_experiences`
  ADD CONSTRAINT `fk_experience_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `candidate_interests`
--
ALTER TABLE `candidate_interests`
  ADD CONSTRAINT `fk_interest_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `candidate_languages`
--
ALTER TABLE `candidate_languages`
  ADD CONSTRAINT `fk_language_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `candidate_projects`
--
ALTER TABLE `candidate_projects`
  ADD CONSTRAINT `fk_project_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `candidate_skills`
--
ALTER TABLE `candidate_skills`
  ADD CONSTRAINT `fk_skill_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `candidate_social_links`
--
ALTER TABLE `candidate_social_links`
  ADD CONSTRAINT `fk_social_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `companies_profiles`
--
ALTER TABLE `companies_profiles`
  ADD CONSTRAINT `companies_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `company_reviews`
--
ALTER TABLE `company_reviews`
  ADD CONSTRAINT `fk_candidate_review` FOREIGN KEY (`candidate_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_company_review` FOREIGN KEY (`company_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `jobs`
--
ALTER TABLE `jobs`
  ADD CONSTRAINT `jobs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `job_alerts`
--
ALTER TABLE `job_alerts`
  ADD CONSTRAINT `fk_job_alerts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_notifications_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `saved_jobs`
--
ALTER TABLE `saved_jobs`
  ADD CONSTRAINT `fk_saved_jobs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
