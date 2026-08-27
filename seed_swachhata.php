<?php
/**
 * BHEL-HPVP Vizag Quiz Application
 * Seed Script: Swachhata Pakhwada Quiz & Trilingual Questions
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDBConnection();

if (!$pdo) {
    die("Error: Could not connect to database hpvpbhelweb.\n");
}

echo "Connected to database hpvpbhelweb successfully.\n";

// Check if Swachhata Pakhwada quiz already exists
$checkStmt = $pdo->prepare("SELECT quiz_id FROM " . tbl('quizzes') . " WHERE title_en LIKE '%Swachhata Pakhwada%' LIMIT 1");
$checkStmt->execute();
$existingQuizId = $checkStmt->fetchColumn();

if ($existingQuizId) {
    echo "Swachhata Pakhwada Quiz already exists with ID: {$existingQuizId}. Refreshing questions...\n";
    // Delete existing questions for this quiz to replace cleanly
    $delStmt = $pdo->prepare("DELETE FROM " . tbl('questions') . " WHERE quiz_id = ?");
    $delStmt->execute([$existingQuizId]);
    $quizId = $existingQuizId;
} else {
    // Insert new Swachhata Pakhwada Quiz
    $quizStmt = $pdo->prepare("
        INSERT INTO " . tbl('quizzes') . " 
        (title_en, title_hi, title_te, description_en, description_hi, description_te, languages, target_categories, start_time, end_time, duration_minutes, marks_per_question, negative_marks, pass_percentage, is_published, created_by)
        VALUES 
        (?, ?, ?, ?, ?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_ADD(NOW(), INTERVAL 30 DAY), ?, ?, ?, ?, 1, 1)
    ");

    $quizStmt->execute([
        'Swachhata Pakhwada Cleanliness & Sustainability Quiz 2026',
        'स्वच्छता पखवाड़ा स्वच्छता एवं पर्यावरण संधारणीयता क्विज़ 2026',
        'స్వచ్ఛతా పఖ్వాడా పరిశుభ్రత మరియు పర్యావరణ సుస్థిరత క్విజ్ 2026',
        'Official BHEL Swachhata Pakhwada quiz focusing on waste segregation, single-use plastic reduction, industrial hygiene, 5S workplace cleanliness, and environmental protection.',
        'बीएचईएल स्वच्छता पखवाड़ा क्विज़ - अपशिष्ट पृथक्करण, एकल-उपयोग प्लास्टिक में कमी, औद्योगिक स्वच्छता, 5S कार्यस्थल स्वच्छता और पर्यावरण संरक्षण पर केंद्रित।',
        'BHEL స్వచ్ఛతా పఖ్వాడా క్విజ్ - వ్యర్థాల వేరుచేత, సింగిల్-యూజ్ ప్లాస్టిక్ తగ్గింపు, పారిశ్రామిక పారిశుధ్యం, 5S పని ప్రదేశ పరిశుభ్రత మరియు పర్యావరణ పరిరక్షణపై దృష్టి సారించింది.',
        'en,hi,te',
        'executive,supervisor,workman',
        15,
        2.00,
        0.50,
        40.00
    ]);

    $quizId = $pdo->lastInsertId();
    echo "Created new Swachhata Pakhwada Quiz with ID: {$quizId}\n";
}

// 10 Trilingual Questions
$questions = [
    [
        'question_num' => 1,
        'question_en' => 'What is the duration of "Swachhata Pakhwada" observed across Government of India ministries and public sector enterprises like BHEL?',
        'question_hi' => 'बीएचईएल जैसे सार्वजनिक उपक्रमों और भारत सरकार के मंत्रालयों द्वारा आयोजित "स्वच्छता पखवाड़ा" की अवधि कितनी होती है?',
        'question_te' => 'BHEL వంటి ప్రభుత్వ రంగ సంస్థలు మరియు కేంద్ర మంత్రిత్వ శాఖలు నిర్వహించే "స్వచ్ఛతా పఖ్వాడా" వ్యవధి ఎంత?',
        'option_1_en' => '15 Days (Fortnight)',
        'option_1_hi' => '15 दिन (एक पखवाड़ा)',
        'option_1_te' => '15 రోజులు (ఒక పక్షం)',
        'option_2_en' => '7 Days (One Week)',
        'option_2_hi' => '7 दिन (एक सप्ताह)',
        'option_2_te' => '7 రోజులు (ఒక వారం)',
        'option_3_en' => '30 Days (One Month)',
        'option_3_hi' => '30 दिन (एक महीना)',
        'option_3_te' => '30 రోజులు (ఒక నెల)',
        'option_4_en' => '100 Days',
        'option_4_hi' => '100 दिन',
        'option_4_te' => '100 రోజులు',
        'correct_option' => 1,
        'explanation_en' => '"Pakhwada" signifies a fortnight (15 days) dedicated to intensive cleanliness drives, environmental shramdaan, and sanitation awareness.',
        'explanation_hi' => '"पखवाड़ा" का तात्पर्य 15 दिनों की अवधि से है जो गहन स्वच्छता अभियानों, पर्यावरण श्रमदान और स्वच्छता जागरूकता के लिए समर्पित है।',
        'explanation_te' => '"పఖ్వాడా" అంటే 15 రోజుల పక్షం రోజులు, ఇది తీవ్రీకృత పరిశుభ్రత డ్రైవ్‌లు, శ్రమదానం మరియు పారిశుధ్య అవగాహనకు అంకితం చేయబడింది.'
    ],
    [
        'question_num' => 2,
        'question_en' => 'On which historic occasion was the nationwide Swachh Bharat Abhiyan (Clean India Mission) officially launched by PM Narendra Modi?',
        'question_hi' => 'स्वच्छ भारत अभियान (Clean India Mission) आधिकारिक तौर पर देश भर में किस ऐतिहासिक अवसर पर शुरू किया गया था?',
        'question_te' => 'దేశవ్యాప్త స్వచ్ఛ భారత్ అభియాన్ అధికారికంగా ఏ చారిత్రాత్మక సందర్భంలో ప్రారంభించబడింది?',
        'option_1_en' => '2nd October 2014 (Mahatma Gandhi Jayanti)',
        'option_1_hi' => '2 अक्टूबर 2014 (महात्मा गांधी जयंती)',
        'option_1_te' => '2 అక్టోబర్ 2014 (మహాత్మా గాంధీ జయంతి)',
        'option_2_en' => '15th August 2015 (Independence Day)',
        'option_2_hi' => '15 अगस्त 2015 (स्वतंत्रता दिवस)',
        'option_2_te' => '15 ఆగస్టు 2015 (స్వాతంత్య్ర దినోత్సవం)',
        'option_3_en' => '26th January 2014 (Republic Day)',
        'option_3_hi' => '26 जनवरी 2014 (गणतंत्र दिवस)',
        'option_3_te' => '26 జనవరి 2014 (రిపబ్లిక్ డే)',
        'option_4_en' => '5th June 2016 (World Environment Day)',
        'option_4_hi' => '5 जून 2016 (विश्व पर्यावरण दिवस)',
        'option_4_te' => '5 జూన్ 2016 (ప్రపంచ పర్యావరణ దినోత్సవం)',
        'correct_option' => 1,
        'explanation_en' => 'Swachh Bharat Abhiyan was launched on 2nd October 2014 at Rajghat, New Delhi, to realize Mahatma Gandhi\'s dream of a clean and hygienic India.',
        'explanation_hi' => 'महात्मा गांधी के स्वच्छ और सुंदर भारत के सपने को साकार करने के लिए 2 अक्टूबर 2014 को राजघाट, नई दिल्ली से स्वच्छ भारत अभियान शुरू किया गया था।',
        'explanation_te' => 'మహాత్మా గాంధీ స్వచ్ఛమైన భారతదేశం స్వప్నాన్ని సాకారం చేసుకోవడానికి 2014 అక్టోబర్ 2న న్యూఢిల్లీలోని రాజ్‌ఘాట్‌లో స్వచ్ఛ భారత్ అభియాన్ ప్రారంభించబడింది.'
    ],
    [
        'question_num' => 3,
        'question_en' => 'Under standard waste segregation guidelines in workplaces and municipalities, which color dustbin is designated for Organic/Wet Waste?',
        'question_hi' => 'कार्यस्थलों और नगर निगमों में मानक कचरा पृथक्करण दिशानिर्देशों के तहत, जैविक/गीले कचरे के लिए कौन सा रंगीन डस्टबिन निर्धारित है?',
        'question_te' => 'పని ప్రదేశాలు మరియు మున్సిపాలిటీలలో ప్రామాణిక వ్యర్థాల వేరుచేసే మార్గదర్శకాల ప్రకారం, సేంద్రీయ/తడి వ్యర్థాల కోసం ఏ రంగు డస్ట్ బిన్ కేటాయించబడింది?',
        'option_1_en' => 'Green Bin',
        'option_1_hi' => 'हरा डिब्बा (Green Bin)',
        'option_1_te' => 'ఆకుపచ్చ బిన్ (Green Bin)',
        'option_2_en' => 'Blue Bin',
        'option_2_hi' => 'नीला डिब्बा (Blue Bin)',
        'option_2_te' => 'నీలం బిన్ (Blue Bin)',
        'option_3_en' => 'Red Bin',
        'option_3_hi' => 'लाल डिब्बा (Red Bin)',
        'option_3_te' => 'ఎరుపు బిన్ (Red Bin)',
        'option_4_en' => 'Yellow Bin',
        'option_4_hi' => 'पीला डिब्बा (Yellow Bin)',
        'option_4_te' => 'పసుపు బిన్ (Yellow Bin)',
        'correct_option' => 1,
        'explanation_en' => 'Green bins are designated for organic/wet waste (food scraps, bio-degradables) and Blue bins are designated for dry recyclable waste (paper, plastic, metal).',
        'explanation_hi' => 'हरे डिब्बे जैविक/गीले कचरे (भोजन के टुकड़े, बायोडिग्रेडेबल) के लिए हैं, और नीले डिब्बे सूखे पुनर्चक्रण योग्य कचरे के लिए हैं।',
        'explanation_te' => 'ఆకుపచ్చ బిన్లు సేంద్రీయ/తడి వ్యర్థాల కోసం, నీలి బిన్లు పొడి పునరుత్పత్తి వ్యర్థాల కోసం ఉపయోగపడతాయి.'
    ],
    [
        'question_num' => 4,
        'question_en' => 'What is the voluntary shramdaan commitment encouraged for every citizen under the Swachhata Pledge (Swachhata Shapath)?',
        'question_hi' => 'राष्ट्रीय स्वच्छता प्रतिज्ञा (स्वच्छता शपथ) के तहत प्रत्येक नागरिक के लिए प्रोत्साहित किया जाने वाला स्वैच्छिक श्रमदान संकल्प कितना है?',
        'question_te' => 'జాతీయ స్వచ్ఛతా ప్రతిజ్ఞ (స్వచ్ఛతా శపథం) ప్రకారం ప్రతి పౌరుడికి ప్రోత్సహించబడే స్వచ్ఛంద శ్రమదాన సమయం ఎంత?',
        'option_1_en' => '100 Hours per Year (~2 hours per week)',
        'option_1_hi' => 'प्रति वर्ष 100 घंटे (लगभग 2 घंटे प्रति सप्ताह)',
        'option_1_te' => 'సంవత్సరానికి 100 గంటలు (వారానికి దాదాపు 2 గంటలు)',
        'option_2_en' => '500 Hours per Year',
        'option_2_hi' => 'प्रति वर्ष 500 घंटे',
        'option_2_te' => 'సంవత్సరానికి 500 గంటలు',
        'option_3_en' => '10 Hours per Year',
        'option_3_hi' => 'प्रति वर्ष 10 घंटे',
        'option_3_te' => 'సంవత్సరానికి 10 గంటలు',
        'option_4_en' => '365 Hours per Year',
        'option_4_hi' => 'प्रति वर्ष 365 घंटे',
        'option_4_te' => 'సంవత్సరానికి 365 గంటలు',
        'correct_option' => 1,
        'explanation_en' => 'The Swachhata Pledge asks citizens to commit 100 hours per year (approx 2 hours weekly) towards shramdaan for community cleanliness.',
        'explanation_hi' => 'स्वच्छता प्रतिज्ञा नागरिकों से सामुदायिक स्वच्छता के लिए प्रति वर्ष 100 घंटे (लगभग 2 घंटे साप्ताहिक) श्रमदान करने का संकल्प लेती है।',
        'explanation_te' => 'స్వచ్ఛతా ప్రతిజ్ఞ పౌరులు సంఘ పారిశుధ్యం కోసం సంవత్సరానికి 100 గంటలు (వారానికి దాదాపు 2 గంటలు) కేటాయించాలని కోరుతుంది.'
    ],
    [
        'question_num' => 5,
        'question_en' => 'What is the primary environmental objective of banning Single-Use Plastics (SUP) in plant premises, offices, and canteens?',
        'question_hi' => 'संयंत्र परिसर, कार्यालयों और कैंटीनों में सिंगल-यूज प्लास्टिक (SUP) पर प्रतिबंध लगाने का प्राथमिक पर्यावरण उद्देश्य क्या है?',
        'question_te' => 'ప్లాంట్ ప్రాంగణం, ఆఫీసులు మరియు క్యాంటీన్లలో సింగిల్-యూజ్ ప్లాస్టిక్ (SUP) నిషేధించడానికి ప్రధాన పర్యావరణ లక్ష్యం ఏమిటి?',
        'option_1_en' => 'Preventing long-term non-biodegradable pollution & toxic microplastics contamination in soil and water',
        'option_1_hi' => 'मिट्टी और पानी में लंबे समय तक रहने वाले गैर-बायोडिग्रेडेबल प्रदूषण और विषैले माइक्रोप्लास्टिक को रोकना',
        'option_1_te' => 'నేల మరియు నీటిలో దీర్ఘకాలిక నాన్-బయోడిగ్రేడబుల్ కాలుష్యం మరియు విషపూరిత మైక్రోప్లాస్టిక్స్‌ను నిరోధించడం',
        'option_2_en' => 'Reducing electricity consumption on the shop floor',
        'option_2_hi' => 'शॉप फ्लोर पर बिजली की खपत कम करना',
        'option_2_te' => 'షాప్ ఫ్లోర్‌లో విద్యుత్ వినియోగాన్ని తగ్గించడం',
        'option_3_en' => 'Eliminating shop floor noise pollution',
        'option_3_hi' => 'शॉप फ्लोर के ध्वनि प्रदूषण को समाप्त करना',
        'option_3_te' => 'షాప్ ఫ్లోర్ శబ్దం కాలుష్యాన్ని నిర్మూలించడం',
        'option_4_en' => 'Decreasing raw steel procurement costs',
        'option_4_hi' => 'कच्चे इस्पात की खरीद लागत को घटाना',
        'option_4_te' => 'రా స్టీల్ కొనుగోలు ఖర్చులను తగ్గించడం',
        'correct_option' => 1,
        'explanation_en' => 'Single-use plastics persist for hundreds of years, clogging drains, choking wildlife, polluting marine systems, and generating toxic microplastics.',
        'explanation_hi' => 'सिंगल-यूज प्लास्टिक सैकड़ों वर्षों तक नष्ट नहीं होता, जिससे नाले अवरुद्ध होते हैं, समुद्री पारिस्थितिकी तंत्र प्रदूषित होता है और माइक्रोप्लास्टिक बनते हैं।',
        'explanation_te' => 'సింగిల్-యూజ్ ప్లాస్టిక్ వందల సంవత్సరాలు నాశనం కాకుండా ఉండి కాలువలను మూసివేస్తుంది మరియు విషపూరిత మైక్రోప్లాస్టిక్స్ సృష్టిస్తుంది.'
    ],
    [
        'question_num' => 6,
        'question_en' => 'In industrial workplace organization and cleanliness, what does the 5S methodology stand for?',
        'question_hi' => 'औद्योगिक कार्यस्थल संगठन और स्वच्छता में, 5S पद्धति का क्या अर्थ है?',
        'question_te' => 'పారిశ్రామిక వర్క్‌ప్లేస్ నిర్మాణం మరియు పరిశుభ్రతలో, 5S పద్ధతి అంటే ఏమిటి?',
        'option_1_en' => 'Sort, Set in Order, Shine, Standardize, Sustain',
        'option_1_hi' => 'छांटना (Sort), क्रम में रखना (Set in order), चमकाना (Shine), मानकीकरण (Standardize), बनाए रखना (Sustain)',
        'option_1_te' => 'వర్గీకరించు (Sort), క్రమబద్ధీకరించు (Set in order), మెరిపించు (Shine), ప్రామాణీకరించు (Standardize), నిలబెట్టుకో (Sustain)',
        'option_2_en' => 'Safety, Speed, Service, Storage, Security',
        'option_2_hi' => 'सुरक्षा, गति, सेवा, भंडारण, सुरक्षा',
        'option_2_te' => 'భద్రత, వేగం, సేవ, నిల్వ, భద్రత',
        'option_3_en' => 'Stop, Start, Search, Solve, Save',
        'option_3_hi' => 'रोकें, शुरू करें, खोजें, हल करें, बचाएं',
        'option_3_te' => 'ఆపు, ప్రారంభించు, శోధించు, పరిష్కరించు, దాచు',
        'option_4_en' => 'Sweep, Scrub, Sanitize, Seal, Systematize',
        'option_4_hi' => 'झाड़ू लगाना, रगड़ना, सैनेटाइज करना, सील करना, व्यवस्थित करना',
        'option_4_te' => 'తుడవడం, రుద్దడం, శానిటైజ్ చేయడం, సీల్ చేయడం, వ్యవస్థీకరించడం',
        'correct_option' => 1,
        'explanation_en' => '5S comprises Seiri (Sort), Seiton (Set in order), Seiso (Shine), Seiketsu (Standardize), and Shitsuke (Sustain) to maintain world-class shop floor cleanliness.',
        'explanation_hi' => '5S में सेइरी (छांटना), सेइटन (क्रमबद्ध करना), सेइसो (सफाई/चमकाना), सेइकेत्सु (मानकीकरण) और शित्सुके (बनाए रखना) शामिल हैं।',
        'explanation_te' => '5S లో సీరి (వర్గీకరించు), సీటన్ (క్రమబద్ధీకరించు), సీసో (సఫాయి), సీకెట్సు (ప్రామాణీకరించు) మరియు షిట్సుకే (నిలబెట్టుకో) ఉంటాయి.'
    ],
    [
        'question_num' => 7,
        'question_en' => 'How must hazardous Industrial E-Waste (discarded electronic cards, circuit boards, IT scrap, batteries) at BHEL plant premises be disposed of?',
        'question_hi' => 'बीएचईएल संयंत्र परिसर में खतरनाक औद्योगिक ई-कचरे (पुराने इलेक्ट्रॉनिक कार्ड, सर्किट बोर्ड, आईटी स्क्रैप, बैटरियों) का निपटान कैसे किया जाना चाहिए?',
        'question_te' => 'BHEL ప్లాంట్ ప్రాంగణంలో ప్రమాదకర పారిశ్రామిక ఇ-వ్యర్థాలను (పాత ఎలక్ట్రానిక్ కార్డులు, సర్క్యూట్ బోర్డులు, IT స్క్రాప్, బ్యాటరీలు) ఎలా నిర్మూలించాలి?',
        'option_1_en' => 'Handed over to CPCB/SPCB authorized and certified E-Waste recyclers',
        'option_1_hi' => 'CPCB/SPCB द्वारा प्रमाणित अधिकृत ई-कचरा पुनर्चक्रणकर्ताओं को सौंपा जाना चाहिए',
        'option_1_te' => 'CPCB/SPCB ధృవీకరించిన అధీకృత ఇ-వ్యర్థాల రీసైక్లర్లకు అప్పగించాలి',
        'option_2_en' => 'Mixed with general domestic shop scrap bins',
        'option_2_hi' => 'सामान्य घरेलू स्क्रैप डिब्बों में मिलाया जाना चाहिए',
        'option_2_te' => 'సాధారణ ఇంజినీరింగ్ వ్యర్థాల బిన్లలో కలపాలి',
        'option_3_en' => 'Burned in open shop floor yard',
        'option_3_hi' => 'खुले शॉप फ्लोर यार्ड में जलाया जाना चाहिए',
        'option_3_te' => 'ఓపెన్ యార్డ్‌లో బహిరంగంగా కాల్చాలి',
        'option_4_en' => 'Dumped into plant storm water channels',
        'option_4_hi' => 'संयंत्र के वर्षा जल नालों में बहाया जाना चाहिए',
        'option_4_te' => 'వర్షపు నీటి కాలువలలోకి పారవేయాలి',
        'correct_option' => 1,
        'explanation_en' => 'E-waste contains hazardous heavy metals (lead, mercury, cadmium). Under E-Waste Management Rules, disposal is strictly restricted to certified CPCB/SPCB recyclers.',
        'explanation_hi' => 'ई-कचरे में सीसा, पारा जैसी खतरनाक धातुएं होती हैं। ई-कचरा प्रबंधन नियमों के तहत निपटान केवल प्रमाणित CPCB/SPCB रीसायकलर्स द्वारा ही किया जाना चाहिए।',
        'explanation_te' => 'ఇ-వ్యర్థాలలో సీసం, పాదరసం వంటి ప్రమాదకర లోహాలు ఉంటాయి. ఇ-వ్యర్థాల నిబంధనల ప్రకారం వీటిని సిపిసిబి/ఎస్పిసిబి ధృవీకరించిన రీసైక్లర్ల ద్వారా మాత్రమే నిర్మూలించాలి.'
    ],
    [
        'question_num' => 8,
        'question_en' => 'What natural aerobic biological process converts canteen food leftovers, fallen leaves, and plant organic waste into rich soil fertilizer?',
        'question_hi' => 'कैंटीन के बचे हुए भोजन, सूखे पत्तों और जैविक कचरे को पोषक तत्वों से भरपूर मृदा उर्वरक में बदलने वाली प्राकृतिक जैविक प्रक्रिया क्या है?',
        'question_te' => 'క్యాంటీన్ మిగిలిపోయిన ఆహారం, రాలిన ఆకులు మరియు సేంద్రీయ వ్యర్థాలను పోషకాలు కలిగిన ఎరువుగా మార్చే సహజ ప్రక్రియ ఏమిటి?',
        'option_1_en' => 'Composting',
        'option_1_hi' => 'कंपोस्टिंग (खाद बनाना)',
        'option_1_te' => 'కంపోస్టింగ్ (ఎరువు తయారుచేయడం)',
        'option_2_en' => 'Incineration',
        'option_2_hi' => 'भस्मीकरण (Incineration)',
        'option_2_te' => 'భస్మీకరణం (Incineration)',
        'option_3_en' => 'Gasification',
        'option_3_hi' => 'गैसीकरण (Gasification)',
        'option_3_te' => 'గ్యాసిఫికేషన్ (Gasification)',
        'option_4_en' => 'Pyrolysis',
        'option_4_hi' => 'पायरोलिसिस (Pyrolysis)',
        'option_4_te' => 'పైరోలిసిస్ (Pyrolysis)',
        'correct_option' => 1,
        'explanation_en' => 'Composting breaks down wet bio-degradable matter using beneficial micro-organisms, producing organic fertilizer for township greenery and gardens.',
        'explanation_hi' => 'कंपोस्टिंग सूक्ष्मजीवों का उपयोग करके जैविक कचरे को विघटित करती है, जिससे पौधों के लिए जैविक खाद बनती है।',
        'explanation_te' => 'కంపోస్టింగ్ సూక్ష్మజీవులను ఉపయోగించి సేంద్రీయ వ్యర్థాలను విచ్ఛిన్నం చేసి, మొక్కల పెంపకానికి ఉపయోగపడే జైవిక ఎరువును ఉత్పత్తి చేస్తుంది.'
    ],
    [
        'question_num' => 9,
        'question_en' => 'What is the name of the annual urban cleanliness assessment and ranking survey conducted across Indian cities under Swachh Bharat Abhiyan?',
        'question_hi' => 'स्वच्छ भारत अभियान के तहत भारतीय शहरों में आयोजित किए जाने वाले वार्षिक शहरी स्वच्छता मूल्यांकन और रैंकिंग सर्वेक्षण का क्या नाम है?',
        'question_te' => 'స్వచ్ఛ భారత్ అభియాన్ కింద భారతీయ నగరాలలో నిర్వహించే వార్షిక పట్టణ పారిశుధ్య అంచనా మరియు ర్యాంకింగ్ సర్వే పేరు ఏమిటి?',
        'option_1_en' => 'Swachh Survekshan',
        'option_1_hi' => 'स्वच्छ सर्वेक्षण (Swachh Survekshan)',
        'option_1_te' => 'స్వచ్ఛ సర్వేక్షన్ (Swachh Survekshan)',
        'option_2_en' => 'Nirmal Bharat Rating',
        'option_2_hi' => 'निर्मल भारत रेटिंग',
        'option_2_te' => 'నిర్మల్ భారత్ రేటింగ్',
        'option_3_en' => 'Green City Audit',
        'option_3_hi' => 'ग्रीन सिटी ऑडिट',
        'option_3_te' => 'గ్రీన్ సిటీ ఆడిట్',
        'option_4_en' => 'Paryavaran Suraksha Census',
        'option_4_hi' => 'पर्यावरण सुरक्षा जनगणना',
        'option_4_te' => 'పర్యావరణ సురక్ష జనాభా సర్వే',
        'correct_option' => 1,
        'explanation_en' => 'Swachh Survekshan is the world\'s largest urban cleanliness survey, promoting healthy competition among cities for solid waste management and hygiene.',
        'explanation_hi' => 'स्वच्छ सर्वेक्षण दुनिया का सबसे बड़ा शहरी स्वच्छता सर्वेक्षण है, जो ठोस अपशिष्ट प्रबंधन और स्वच्छता के लिए शहरों के बीच प्रतिस्पर्धा को बढ़ावा देता है।',
        'explanation_te' => 'స్వచ్ఛ సర్వేక్షన్ అనేది ప్రపంచంలోనే అతిపెద్ద పట్టణ పారిశుధ్య సర్వే, ఇది వ్యర్థాల నిర్వహణ మరియు పారిశుధ్యంలో నగరాల మధ్య ఆరోగ్యకరమైన పోటీని పెంపొందిస్తుంది.'
    ],
    [
        'question_num' => 10,
        'question_en' => 'Which of the following is a primary core objective of observing "Swachhata Pakhwada" in industrial organizations like BHEL?',
        'question_hi' => 'बीएचईएल जैसे औद्योगिक संगठनों में "स्वच्छता पखवाड़ा" मनाने का प्राथमिक मुख्य उद्देश्य निम्नलिखित में से कौन सा है?',
        'question_te' => 'BHEL వంటి పారిశ్రామిక సంస్థలలో "స్వచ్ఛతా పఖ్వాడా" నిర్వహించడం యొక్క ప్రాథమిక ప్రధాన లక్ష్యం ఏమిటి?',
        'option_1_en' => 'Fostering sustained cleanliness practices, zero-plastic pledges, tree plantation, and waste minimization among workforce',
        'option_1_hi' => 'कर्मचारियों के बीच निरंतर स्वच्छता प्रथाओं, शून्य-प्लास्टिक संकल्पों, वृक्षारोपण और कचरा न्यूनीकरण को बढ़ावा देना',
        'option_1_te' => 'సిబ్బందిలో నిరంతర పరిశుభ్రత పద్ధతులు, జీరో-ప్లాస్టిక్ శపథాలు, మొక్కలు నాటడం మరియు వ్యర్థాల తగ్గింపును ప్రోత్సహించడం',
        'option_2_en' => 'Closing shop floor operations for 15 days',
        'option_2_hi' => '15 दिनों के लिए शॉप फ्लोर संचालन बंद करना',
        'option_2_te' => '15 రోజుల పాటు షాప్ ఫ్లోర్ కార్యకలాపాలను నిలిపివేయడం',
        'option_3_en' => 'Encouraging single-use plastic packaging on the shop floor',
        'option_3_hi' => 'शॉप फ्लोर पर एकल-उपयोग प्लास्टिक पैकेजिंग को बढ़ावा देना',
        'option_3_te' => 'షాప్ ఫ్లోర్‌లో సింగిల్-యూజ్ ప్లాస్టిక్ ప్యాకేజింగ్‌ను ప్రోత్సహించడం',
        'option_4_en' => 'Replacing plant greenery with concrete pavements',
        'option_4_hi' => 'संयंत्र की हरियाली को कंक्रीट के फुटपाथ से बदलना',
        'option_4_te' => 'ప్లాంట్ పచ్చదనం స్థానంలో కాంక్రీట్ కాలిబాటలు ఏర్పాటు చేయడం',
        'correct_option' => 1,
        'explanation_en' => 'Swachhata Pakhwada encourages long-term behavioural changes, clean shop floor environments, eco-friendly practices, and active community participation.',
        'explanation_hi' => 'स्वच्छता पखवाड़ा दीर्घकालिक व्यावहारिक परिवर्तनों, स्वच्छ शॉप फ्लोर वातावरण, पर्यावरण-अनुकूल प्रथाओं और सक्रिय सामुदायिक भागीदारी को प्रोत्साहित करता है।',
        'explanation_te' => 'స్వచ్ఛతా పఖ్వాడా దీర్ఘకాలిక ప్రవర్తనా మార్పులు, పరిశుభ్రమైన షాప్ ఫ్లోర్ వాతావరణం, పర్యావరణ అనుకూల పద్ధతులు మరియు సమాజ భాగస్వామ్యాన్ని ప్రోత్సహిస్తుంది.'
    ]
];

$insQuestion = $pdo->prepare("
    INSERT INTO " . tbl('questions') . " 
    (quiz_id, question_num, question_en, question_hi, question_te, option_1_en, option_1_hi, option_1_te, option_2_en, option_2_hi, option_2_te, option_3_en, option_3_hi, option_3_te, option_4_en, option_4_hi, option_4_te, correct_option, explanation_en, explanation_hi, explanation_te)
    VALUES
    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$qCount = 0;
foreach ($questions as $q) {
    $insQuestion->execute([
        $quizId,
        $q['question_num'],
        $q['question_en'],
        $q['question_hi'],
        $q['question_te'],
        $q['option_1_en'],
        $q['option_1_hi'],
        $q['option_1_te'],
        $q['option_2_en'],
        $q['option_2_hi'],
        $q['option_2_te'],
        $q['option_3_en'],
        $q['option_3_hi'],
        $q['option_3_te'],
        $q['option_4_en'],
        $q['option_4_hi'],
        $q['option_4_te'],
        $q['correct_option'],
        $q['explanation_en'],
        $q['explanation_hi'],
        $q['explanation_te']
    ]);
    $qCount++;
}

echo "Successfully inserted {$qCount} trilingual questions for Quiz ID: {$quizId}!\n";
