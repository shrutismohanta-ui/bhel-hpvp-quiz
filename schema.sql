-- BHEL-HPVP Vizag Quiz Application Database Schema
-- Database Name: hpvpbhelweb
-- Table Prefix: quiz_

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `quiz_attempt_responses`;
DROP TABLE IF EXISTS `quiz_attempts`;
DROP TABLE IF EXISTS `quiz_questions`;
DROP TABLE IF EXISTS `quiz_quizzes`;
DROP TABLE IF EXISTS `quiz_users`;

SET FOREIGN_KEY_CHECKS = 1;

-- Users Table
CREATE TABLE `quiz_users` (
  `user_id` INT AUTO_INCREMENT PRIMARY KEY,
  `staff_no` VARCHAR(50) NOT NULL UNIQUE,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `department` VARCHAR(100) NOT NULL DEFAULT 'Operations',
  `role` ENUM('admin', 'employee') NOT NULL DEFAULT 'employee',
  `password` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Quizzes Table
CREATE TABLE `quiz_quizzes` (
  `quiz_id` INT AUTO_INCREMENT PRIMARY KEY,
  `title_en` VARCHAR(255) NOT NULL,
  `title_hi` VARCHAR(255) DEFAULT '',
  `title_te` VARCHAR(255) DEFAULT '',
  `description_en` TEXT DEFAULT NULL,
  `description_hi` TEXT DEFAULT NULL,
  `description_te` TEXT DEFAULT NULL,
  `start_time` DATETIME NOT NULL,
  `end_time` DATETIME NOT NULL,
  `duration_minutes` INT NOT NULL DEFAULT 15,
  `marks_per_question` DECIMAL(5,2) NOT NULL DEFAULT 2.00,
  `negative_marks` DECIMAL(5,2) NOT NULL DEFAULT 0.50,
  `pass_percentage` DECIMAL(5,2) NOT NULL DEFAULT 40.00,
  `is_published` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Questions Table (Trilingual)
CREATE TABLE `quiz_questions` (
  `question_id` INT AUTO_INCREMENT PRIMARY KEY,
  `quiz_id` INT NOT NULL,
  `question_num` INT NOT NULL DEFAULT 1,
  `question_en` TEXT NOT NULL,
  `question_hi` TEXT DEFAULT NULL,
  `question_te` TEXT DEFAULT NULL,
  `option_1_en` TEXT NOT NULL,
  `option_1_hi` TEXT DEFAULT NULL,
  `option_1_te` TEXT DEFAULT NULL,
  `option_2_en` TEXT NOT NULL,
  `option_2_hi` TEXT DEFAULT NULL,
  `option_2_te` TEXT DEFAULT NULL,
  `option_3_en` TEXT NOT NULL,
  `option_3_hi` TEXT DEFAULT NULL,
  `option_3_te` TEXT DEFAULT NULL,
  `option_4_en` TEXT NOT NULL,
  `option_4_hi` TEXT DEFAULT NULL,
  `option_4_te` TEXT DEFAULT NULL,
  `correct_option` TINYINT NOT NULL COMMENT '1, 2, 3, or 4',
  `explanation_en` TEXT DEFAULT NULL,
  `explanation_hi` TEXT DEFAULT NULL,
  `explanation_te` TEXT DEFAULT NULL,
  FOREIGN KEY (`quiz_id`) REFERENCES `quiz_quizzes`(`quiz_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Quiz Attempts Table
CREATE TABLE `quiz_attempts` (
  `attempt_id` INT AUTO_INCREMENT PRIMARY KEY,
  `quiz_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `start_time` DATETIME NOT NULL,
  `submit_time` DATETIME DEFAULT NULL,
  `score_achieved` DECIMAL(6,2) DEFAULT 0.00,
  `total_marks` DECIMAL(6,2) DEFAULT 0.00,
  `total_questions` INT DEFAULT 0,
  `correct_answers` INT DEFAULT 0,
  `wrong_answers` INT DEFAULT 0,
  `unattempted` INT DEFAULT 0,
  `status` ENUM('in_progress', 'completed', 'timed_out') NOT NULL DEFAULT 'in_progress',
  FOREIGN KEY (`quiz_id`) REFERENCES `quiz_quizzes`(`quiz_id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `quiz_users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Quiz Attempt Individual Responses Table
CREATE TABLE `quiz_attempt_responses` (
  `response_id` INT AUTO_INCREMENT PRIMARY KEY,
  `attempt_id` INT NOT NULL,
  `question_id` INT NOT NULL,
  `selected_option` TINYINT DEFAULT NULL COMMENT '1, 2, 3, 4 or NULL',
  `is_marked_review` TINYINT(1) NOT NULL DEFAULT 0,
  `is_correct` TINYINT(1) DEFAULT NULL,
  `marks_awarded` DECIMAL(5,2) DEFAULT 0.00,
  FOREIGN KEY (`attempt_id`) REFERENCES `quiz_attempts`(`attempt_id`) ON DELETE CASCADE,
  FOREIGN KEY (`question_id`) REFERENCES `quiz_questions`(`question_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Seed Data: Sample Users (Password is 'bhelpassword')
-- --------------------------------------------------------

INSERT INTO `quiz_users` (`staff_no`, `full_name`, `email`, `department`, `role`, `password`) VALUES
('ADMIN001', 'BHEL Vizag Administrator', 'admin.vizag@bhel.in', 'IT & Safety Admin', 'admin', '$2y$10$ISldjfyuBkHl4vja0IwINepl3xQFREOzJsOZAv5HGaetoqewqwikm'),
('EMP1001', 'Rajesh Kumar Verma', 'rajesh.k@bhel.in', 'Quality Assurance', 'employee', '$2y$10$ISldjfyuBkHl4vja0IwINepl3xQFREOzJsOZAv5HGaetoqewqwikm'),
('EMP1002', 'Srinivas Rao K', 'srinivas.r@bhel.in', 'Heavy Machining Shop', 'employee', '$2y$10$ISldjfyuBkHl4vja0IwINepl3xQFREOzJsOZAv5HGaetoqewqwikm'),
('EMP1003', 'Priya Sharma', 'priya.s@bhel.in', 'Safety & Environment', 'employee', '$2y$10$ISldjfyuBkHl4vja0IwINepl3xQFREOzJsOZAv5HGaetoqewqwikm');

-- Note: The hash above will be properly updated by install.php using password_hash('bhel123', PASSWORD_DEFAULT).

-- --------------------------------------------------------
-- Seed Data: Sample Quizzes & Trilingual Questions
-- --------------------------------------------------------

INSERT INTO `quiz_quizzes` 
(`quiz_id`, `title_en`, `title_hi`, `title_te`, `description_en`, `description_hi`, `description_te`, `start_time`, `end_time`, `duration_minutes`, `marks_per_question`, `negative_marks`, `pass_percentage`, `is_published`, `created_by`)
VALUES 
(1, 
'BHEL-HPVP Safety & Quality Protocol Quiz 2026', 
'बीएचईएल-एचपीवीपी सुरक्षा और गुणवत्ता प्रोटोकॉल क्विज़ 2026', 
'BHEL-HPVP భద్రత మరియు నాణ్యత ప్రోటోకాల్ క్విజ్ 2026', 
'Mandatory safety, industrial hazards, ISO standards, and operational quality guidelines quiz for BHEL-HPVP Vizag employees.', 
'बीएचईएल-एचपीवीपी विशाखापट्टनम कर्मचारियों के लिए अनिवार्य सुरक्षा, औद्योगिक खतरों, आईएसओ मानकों और परिचालन गुणवत्ता दिशानिर्देशों का क्विज़।', 
'BHEL-HPVP విశాఖపట్నం ఉద్యోగుల కోసం నిర్బంధ భద్రత, పారిశ్రామిక ప్రమాదాలు, ISO ప్రమాణాలు మరియు కార్యాచరణ నాణ్యత మార్గదర్శకాల క్విజ్.', 
DATE_SUB(NOW(), INTERVAL 1 DAY), 
DATE_ADD(NOW(), INTERVAL 30 DAY), 
15, 2.00, 0.50, 40.00, 1, 1),

(2, 
'HPVP Electrical & Mechanical Safety Awareness', 
'एचपीवीपी विद्युत और यांत्रिक सुरक्षा जागरूकता', 
'HPVP ఎలక్ట్రికల్ మరియు మెకానికల్ సేఫ్టీ అవగాహన', 
'Assessment on shop floor emergency procedures, PPE protocols, and electrical safety standards at Heavy Power Equipment Plant Vizag.', 
'हेवी पावर इक्विपमेंट प्लांट विशाखापट्टनम में शॉप फ्लोर आपातकालीन प्रक्रियाओं, पीपीई प्रोटोकॉल और विद्युत सुरक्षा मानकों पर मूल्यांकन।', 
'హెవీ పవర్ ఎక్విప్‌మెంట్ ప్లాంట్ విశాఖపట్నంలో షాప్ ఫ్లోర్ ఎమర్జెన్సీ విధానాలు, పిపిఇ ప్రోటోకాల్‌లు మరియు ఎలక్ట్రికల్ భద్రతా ప్రమాణాలపై అంచనా.', 
DATE_SUB(NOW(), INTERVAL 1 DAY), 
DATE_ADD(NOW(), INTERVAL 15 DAY), 
20, 1.00, 0.25, 50.00, 1, 1);

-- Insert Questions for Quiz 1
INSERT INTO `quiz_questions` 
(`quiz_id`, `question_num`, `question_en`, `question_hi`, `question_te`, `option_1_en`, `option_1_hi`, `option_1_te`, `option_2_en`, `option_2_hi`, `option_2_te`, `option_3_en`, `option_3_hi`, `option_3_te`, `option_4_en`, `option_4_hi`, `option_4_te`, `correct_option`, `explanation_en`, `explanation_hi`, `explanation_te`)
VALUES
(1, 1,
'What color safety helmet is mandatory for general visitors and contractors at BHEL plant premises?',
'बीएचईएल संयंत्र परिसर में सामान्य आगंतुकों और ठेकेदारों के लिए किस रंग का सुरक्षा हेलमेट अनिवार्य है?',
'BHEL ప్లాంట్ ప్రాంగణంలో సాధారణ సందర్శకులు మరియు కాంట్రాక్టర్లకు ఏ రంగు సేఫ్టీ హెల్మెట్ తప్పనిసరి?',
'Yellow', 'पीला', 'పసుపు',
'White', 'सफेद', 'తెలుపు',
'Green', 'हरा', 'ఆకుపచ్చ',
'Blue', 'नीला', 'నీలం',
1,
'Yellow helmets are standard for visitors and contractor workers, while White is designated for Engineers/Managers.',
'पीले हेलमेट आगंतुकों और ठेकेदारों के लिए मानक हैं, जबकि सफेद इंजीनियरों/प्रबंधकों के लिए हैं।',
'పసుపు హెల్మెట్లు సందర్శకులు మరియు కాంట్రాక్టర్లకు ప్రామాణికం, అయితే తెల్లటి హెల్మెట్ ఇంజనీర్లు/మేనేజర్లకు కేటాయించబడుతుంది.'),

(1, 2,
'Under ISO 9001 Quality Standards followed at HPVP Vizag, what does PDCA cycle stand for?',
'एचपीवीपी विशाखापट्टनम में ISO 9001 गुणवत्ता मानकों के तहत PDCA चक्र का क्या अर्थ है?',
'HPVP విశాఖపట్నంలో ISO 9001 క్వాలిటీ ప్రమాణాల ప్రకారం PDCA సైకిల్ అంటే ఏమిటి?',
'Plan-Do-Check-Act', 'योजना-करें-जांचें-कार्रवाई (Plan-Do-Check-Act)', 'ప్లాన్-డూ-చెక్-యాక్ట్ (Plan-Do-Check-Act)',
'Prepare-Develop-Control-Assess', 'तैयार करें-विकसित करें-नियंत्रित करें-मूल्यांकन करें', 'సన్నాహకం-అభివృద్ధి-నియంత్రణ-అంచనా',
'Process-Deliver-Calculate-Analyze', 'प्रक्रिया-वितरित करें-गणना करें-विश्लेषण करें', 'ప్రక్రియ-పంపిణీ-లెక్కించు-విశ్లేషణ',
'Prevent-Detect-Correct-Audit', 'रोकें-पहचानें-सही करें-अंकुश लगाएं', 'నిరోధించు-గుర్తించు-సరిచేయి-ఆడిట్',
1,
'PDCA stands for Plan-Do-Check-Act, an iterative management method used in quality management.',
'PDCA का अर्थ है प्लान-डू-चेक-एक्ट, जो गुणवत्ता प्रबंधन में उपयोग की जाने वाली एक पुनरावृत्ति प्रबंधन विधि है।',
'PDCA అంటే ప్లాన్-డూ-చెక్-యాక్ట్, ఇది క్వాలిటీ మేనేజ్‌మెంట్‌లో ఉపయోగించే ఒక నిర్వహణ పద్ధతి.'),

(1, 3,
'What is the primary action to be taken immediately in case of a chemical fire emergency in the workshop?',
'कार्यशाला में रासायनिक आग की आपात स्थिति में तुरंत की जाने वाली प्राथमिक कार्रवाई क्या है?',
'వర్క్‌షాప్‌లో కెమికల్ ఫైర్ ఎమర్జెన్సీ సంభవించినప్పుడు వెంటనే తీసుకోవాల్సిన ప్రాథమిక చర్య ఏమిటి?',
'Throw water on the chemical fire', 'रासायनिक आग पर पानी फेंकें', 'రసాయన మంటలపై నీరు చల్లండి',
'Sound the emergency fire alarm & use Foam/CO2 extinguisher', 'आपातकालीन अग्नि अलार्म बजाएं और फोम/CO2 अग्निशामक का उपयोग करें', 'ఎమర్జెన్సీ ఫైర్ అలారం మోగించి ఫోమ్/CO2 అగ్నిమాపక యంత్రాన్ని ఉపయోగించండి',
'Ignore and continue working', 'अनदेखा करें और काम जारी रखें', 'అలక్ష్యం చేసి పని కొనసాగించండి',
'Open all gas supply valves', 'सभी गैस आपूर्ति वाल्व खोलें', 'అన్ని గ్యాస్ సరఫరా వాల్వ్‌లను తెరవండి',
2,
'Water can react violently with chemical fires. Emergency alarm must be raised and Foam or CO2 extinguishers used.',
'पानी रासायनिक आग के साथ हिंसक प्रतिक्रिया कर सकता है। आपातकालीन अलार्म बजाया जाना चाहिए और फोम या CO2 अग्निशामक का उपयोग किया जाना चाहिए।',
'నీరు రసాయన మంటలతో తీవ్రంగా ప్రతిస్పందిస్తుంది. ఎమర్జెన్సీ అలారం మోగించి ఫోమ్ లేదా CO2 అగ్निమాపక యంత్రాన్ని ఉపయోగించాలి.'),

(1, 4,
'What is the maximum permissible continuous noise level exposure for an 8-hour shift without ear protection?',
'कान की सुरक्षा के बिना 8 घंटे की पाली के लिए अधिकतम स्वीकार्य निरंतर शोर स्तर का जोखिम क्या है?',
'చెవి రక్షణ లేకుండా 8 గంటల షిఫ్ట్ కోసం అనుమతించదగిన గరిష్ట నిరంతర శబ్దం ఎంత?',
'70 dB', '70 डीबी', '70 dB',
'85 dB', '85 डीबी', '85 dB',
'110 dB', '110 डीबी', '110 dB',
'130 dB', '130 डीबी', '130 dB',
2,
'85 dB is the maximum safe exposure limit for an 8-hour work shift as per factory safety regulations.',
'कारखाना सुरक्षा नियमों के अनुसार 8 घंटे की कार्य पाली के लिए 85 dB अधिकतम सुरक्षित एक्सपोजर सीमा है।',
'ఫ్యాక్టరీ భద్రతా నిబంధనల ప్రకారం 8 గంటల పని షిఫ్ట్‌కు 85 dB అనేది గరిష్ట సురక్షితమైన పరిమితి.'),

(1, 5,
'What does PPE stand for in workplace safety guidelines at BHEL?',
'बीएचईएल में कार्यस्थल सुरक्षा दिशानिर्देशों में PPE का क्या अर्थ है?',
'BHEL లో వర్క్‌ప్లేస్ సేఫ్టీ మార్గదర్శకాలలో PPE అంటే ఏమిటి?',
'Personal Protective Equipment', 'पर्सनल प्रोटेक्टिव इक्विपमेंट (व्यक्तिगत सुरक्षा उपकरण)', 'పర్సనల్ ప్రొటెక్టివ్ ఎక్విప్‌మెంట్ (వ్యక్తిగత రక్షణ పరికరాలు)',
'Public Plant Engine', 'पब्लिक प्लांट इंजन', 'పబ్లిక్ ప్లాంట్ ఇంజిన్',
'Power Plant Enterprise', 'पावर प्लांट एंटरप्राइज', 'పవర్ ప్లాంట్ ఎంటర్ప్రైజ్',
'Proper Process Execution', 'प्रॉपर प्रोसेस एग्जीक्यूशन', 'ప్రాపర్ ప్రాసెస్ ఎగ్జిక్యూషన్',
1,
'PPE stands for Personal Protective Equipment including helmets, goggles, safety shoes, and gloves.',
'PPE का अर्थ है व्यक्तिगत सुरक्षा उपकरण जिसमें हेलमेट, चश्मे, सुरक्षा जूते और दस्ताने शामिल हैं।',
'PPE అంటే హెల్మెట్లు, గాగుల్స్, సేఫ్టీ షూస్ మరియు గ్లోవ్స్‌తో కూడిన పర్సనల్ ప్రొటెక్టివ్ ఎక్విప్‌మెంట్.');
