<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

include "conn.php";

define('GEMINI_API_KEY', 'AIzaSyBiPq50YH9mIAWLrcEhG5f8B4C9qK4nbHc');
define('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $userMessage = isset($input['message']) ? trim($input['message']) : '';
    $conversationHistory = isset($input['history']) ? $input['history'] : [];
    $sessionData = isset($input['session']) ? $input['session'] : [];

    if (empty($userMessage)) {
        echo json_encode(['success' => false, 'message' => 'Message is required']);
        exit;
    }

    // --- STEP 1: Handle teacher login flow (stateful, highest priority) ---
    $loginResult = checkTeacherLogin($userMessage, $sessionData, $conn);
    if ($loginResult !== false) {
        echo json_encode($loginResult);
        exit;
    }

    // --- STEP 2: Handle data requests for logged-in teachers ---
    if (isset($sessionData['step']) && $sessionData['step'] === 'logged_in') {
        $dataResult = handleTeacherDataRequest($userMessage, $sessionData, $conn);
        if ($dataResult !== false) {
            echo json_encode($dataResult);
            exit;
        }
        // Teacher is logged in but asked a general question — fall through to Gemini
        // but keep session so they stay logged in
    }

    // --- STEP 3: General AI response via Gemini ---
    $trainingData = file_get_contents(__DIR__ . '/ai_train.txt');

    $teacherInstructions  = "\n\nTEACHER LOGIN SYSTEM:\n";
    $teacherInstructions .= "If a user says they want to login as teacher, check attendance, or access teacher features:\n";
    $teacherInstructions .= "1. Ask for their username\n";
    $teacherInstructions .= "2. Ask for their password\n";
    $teacherInstructions .= "3. The system will verify credentials automatically\n";
    $teacherInstructions .= "4. After login, they can ask for their classes, attendance records, etc.\n\n";

    $systemPrompt  = $trainingData . $teacherInstructions;
    $systemPrompt .= "\n\nIMPORTANT: Answer in plain text only. Do NOT use markdown formatting like **, *, #, or any other markdown symbols. Just use simple plain text.";

    $response = callGeminiAPI($systemPrompt, $userMessage, $conversationHistory);

    if ($response['success']) {
        // Only generate suggestions when NOT in a teacher session
        $suggestions = [];
        if (!isset($sessionData['step']) || $sessionData['step'] !== 'logged_in') {
            $suggestions = generateSuggestions($userMessage);
        }

        echo json_encode([
            'success'     => true,
            'response'    => $response['text'],
            'suggestions' => $suggestions,
            'session'     => $sessionData   // preserve existing session
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => $response['error']]);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

// ─────────────────────────────────────────────────────────────────────────────
// Teacher login state machine
// ─────────────────────────────────────────────────────────────────────────────
function checkTeacherLogin($message, $sessionData, $conn) {
    $messageLower = strtolower($message); // lowercase ONLY for keyword matching

    // Trigger: user wants to log in as teacher
    if (
        strpos($messageLower, 'teacher login')   !== false ||
        strpos($messageLower, 'login as teacher') !== false ||
        strpos($messageLower, 'i am teacher')     !== false ||
        strpos($messageLower, 'teacher account')  !== false
    ) {
        return [
            'success'     => true,
            'response'    => "Welcome to the Teacher Portal! 👨‍🏫\n\nPlease enter your username to continue.",
            'suggestions' => [],
            'session'     => ['step' => 'waiting_username']
        ];
    }

    // State: waiting for username
    if (isset($sessionData['step']) && $sessionData['step'] === 'waiting_username') {
        return [
            'success'     => true,
            'response'    => "Got it! Now please enter your password.",
            'suggestions' => [],
            'session'     => ['step' => 'waiting_password', 'username' => $message] // keep original case
        ];
    }

    // State: waiting for password — use ORIGINAL $message (not lowercased) for password
    if (isset($sessionData['step']) && $sessionData['step'] === 'waiting_password') {
        $username = $sessionData['username'];
        $password = $message; // preserve original case for password_verify

        try {
            $stmt = $conn->prepare("SELECT * FROM teachers WHERE username = ?");
            $stmt->execute([$username]);
            $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($teacher && password_verify($password, $teacher['password'])) {
                return [
                    'success'     => true,
                    'response'    => "✅ Login successful!\n\nWelcome back, " . $teacher['full_name'] . "! I'm here to help you manage your classes and students. What would you like to do?",
                    'suggestions' => [
                        '📚 Show My Classes',
                        '👥 View My Students',
                        '📅 My Schedule',
                        '📊 Attendance Reports'
                    ],
                    'session'     => [
                        'step'         => 'logged_in',
                        'teacher_id'   => $teacher['id'],
                        'teacher_name' => $teacher['full_name'],
                        'username'     => $teacher['username']
                    ]
                ];
            } else {
                return [
                    'success'     => true,
                    'response'    => "❌ Invalid username or password. Please try again.\n\nWould you like to attempt login again?",
                    'suggestions' => ['👨‍🏫 Teacher Login'],
                    'session'     => []
                ];
            }
        } catch (Exception $e) {
            return [
                'success'     => true,
                'response'    => "⚠️ A system error occurred. Please try again later.",
                'suggestions' => ['👨‍🏫 Teacher Login'],
                'session'     => []
            ];
        }
    }

    return false; // not a login-flow message
}

// ─────────────────────────────────────────────────────────────────────────────
// Logged-in teacher data requests
// ─────────────────────────────────────────────────────────────────────────────
function handleTeacherDataRequest($message, $sessionData, $conn) {
    $msg       = strtolower($message);
    $teacherId = $sessionData['teacher_id'];

    // Default suggestions shown after every teacher action
    $defaultSuggestions = [
        '📚 Show My Classes',
        '👥 View My Students',
        '📅 My Schedule',
        '🚪 Logout'
    ];

    try {
        // ── Classes ──────────────────────────────────────────────────────────
        if (
            strpos($msg, 'classes')      !== false ||
            strpos($msg, 'my classes')   !== false ||
            strpos($msg, 'show my classes') !== false
        ) {
            $stmt = $conn->prepare("
                SELECT
                    c.class_name, c.study_mode,
                    d.department_name,
                    s.subject_name,
                    tsa.start_time, tsa.end_time, tsa.status
                FROM teacher_subject_allocation tsa
                INNER JOIN classes  c ON tsa.class_id   = c.id
                INNER JOIN departments d ON c.department_id = d.id
                INNER JOIN faculty  f ON c.faculty_id   = f.id
                INNER JOIN subjects s ON tsa.subject_id  = s.id
                INNER JOIN teachers t ON tsa.teacher_id  = t.id
                WHERE tsa.teacher_id = ?
                ORDER BY c.class_name ASC, s.subject_name ASC
            ");
            $stmt->execute([$teacherId]);
            $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($classes) > 0) {
                $response     = "📚 Your Classes\n\n";
                $currentClass = '';
                $classCount   = 0;
                $subjectCount = 0;

                foreach ($classes as $row) {
                    if ($currentClass !== $row['class_name']) {
                        if ($currentClass !== '') $response .= "\n";
                        $classCount++;
                        $response     .= "🎓 " . $row['class_name'] . " (" . $row['study_mode'] . ")\n";
                        $response     .= "📍 " . $row['department_name'] . "\n\n";
                        $currentClass  = $row['class_name'];
                    }
                    $subjectCount++;
                    $icon      = $row['status'] === 'approved' ? '✅' : '⏳';
                    $response .= $icon . " " . $row['subject_name'];
                    if ($row['start_time'] && $row['end_time']) {
                        $response .= " • " . date('H:i', strtotime($row['start_time']))
                                   . "–"   . date('H:i', strtotime($row['end_time']));
                    }
                    $response .= "\n";
                }
                $response .= "\n📊 Total: $subjectCount subject" . ($subjectCount > 1 ? 's' : '')
                           . " across $classCount class" . ($classCount > 1 ? 'es' : '');
            } else {
                $response = "📚 No classes assigned yet.\n\nPlease contact administration.";
            }

            return ['success' => true, 'response' => $response, 'suggestions' => $defaultSuggestions, 'session' => $sessionData];
        }

        // ── Schedule ─────────────────────────────────────────────────────────
        if (strpos($msg, 'schedule') !== false || strpos($msg, 'timetable') !== false) {
            $stmt = $conn->prepare("
                SELECT c.class_name, s.subject_name, tsa.start_time, tsa.end_time, tsa.status
                FROM teacher_subject_allocation tsa
                JOIN classes  c ON tsa.class_id  = c.id
                JOIN subjects s ON tsa.subject_id = s.id
                WHERE tsa.teacher_id = ?
                ORDER BY tsa.start_time
            ");
            $stmt->execute([$teacherId]);
            $schedule = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($schedule) > 0) {
                $response = "📅 Your Teaching Schedule\n\n";
                foreach ($schedule as $slot) {
                    $response .= "🕐 " . $slot['class_name'] . " — " . $slot['subject_name'] . "\n";
                    if ($slot['start_time'] && $slot['end_time']) {
                        $response .= "   " . date('H:i', strtotime($slot['start_time']))
                                   . " – " . date('H:i', strtotime($slot['end_time'])) . "\n";
                    }
                    $response .= "   Status: " . ucfirst($slot['status']) . "\n\n";
                }
            } else {
                $response = "No schedule found. Please contact administration.";
            }

            return ['success' => true, 'response' => $response, 'suggestions' => $defaultSuggestions, 'session' => $sessionData];
        }

        // ── Students ─────────────────────────────────────────────────────────
        if (strpos($msg, 'student') !== false) {
            $stmt = $conn->prepare("
                SELECT DISTINCT s.student_id, s.full_name, c.class_name
                FROM students s
                INNER JOIN classes c ON s.class_id = c.id
                INNER JOIN teacher_subject_allocation tsa ON tsa.class_id = c.id
                WHERE tsa.teacher_id = ? AND s.status = 'approved'
                ORDER BY c.class_name, s.full_name
            ");
            $stmt->execute([$teacherId]);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($students) > 0) {
                $response     = "👥 Students in Your Classes\n\n";
                $currentClass = '';
                foreach ($students as $student) {
                    if ($currentClass !== $student['class_name']) {
                        if ($currentClass !== '') $response .= "\n";
                        $response    .= "📋 " . $student['class_name'] . ":\n";
                        $currentClass = $student['class_name'];
                    }
                    $response .= "   • " . $student['full_name'] . " (ID: " . $student['student_id'] . ")\n";
                }
                $response .= "\nTotal: " . count($students) . " student" . (count($students) > 1 ? 's' : '');
            } else {
                $response = "No students found in your classes.";
            }

            return ['success' => true, 'response' => $response, 'suggestions' => $defaultSuggestions, 'session' => $sessionData];
        }

        // ── Attendance ───────────────────────────────────────────────────────
        if (strpos($msg, 'attendance') !== false) {
            $response  = "📊 Attendance Management\n\n";
            $response .= "Here's what you can do:\n\n";
            $response .= "• Take attendance for your classes via the mobile app\n";
            $response .= "• View attendance records and history\n";
            $response .= "• Check frequently absent students\n";
            $response .= "• Generate attendance reports\n\n";
            $response .= "Would you like me to show your classes to get started?";

            return [
                'success'     => true,
                'response'    => $response,
                'suggestions' => ['📚 Show My Classes', '👥 View My Students', '📅 My Schedule', '🚪 Logout'],
                'session'     => $sessionData
            ];
        }

        // ── Logout ───────────────────────────────────────────────────────────
        if (
            strpos($msg, 'logout')   !== false ||
            strpos($msg, 'log out')  !== false ||
            strpos($msg, 'sign out') !== false
        ) {
            return [
                'success'     => true,
                'response'    => "👋 You have been logged out successfully.\n\nThank you for using Capital University AI Assistant. Have a great day!",
                'suggestions' => ['👨‍🏫 Teacher Login', '🏛️ University Info', '📞 Contact Info'],
                'session'     => []
            ];
        }

    } catch (Exception $e) {
        return [
            'success'     => true,
            'response'    => "Sorry, there was an error retrieving your data. Please try again.",
            'suggestions' => $defaultSuggestions,
            'session'     => $sessionData
        ];
    }

    // Logged-in but unrecognized command — return false so Gemini handles it
    return false;
}

// ─────────────────────────────────────────────────────────────────────────────
// Gemini API call
// ─────────────────────────────────────────────────────────────────────────────
function callGeminiAPI($systemPrompt, $userMessage, $conversationHistory = []) {
    $contents = [];

    if (!empty($conversationHistory)) {
        // First turn embeds the system prompt
        $contents[] = [
            'role'  => 'user',
            'parts' => [['text' => $systemPrompt . "\n\nUser Question: " . $conversationHistory[0]['text']]]
        ];
        for ($i = 1; $i < count($conversationHistory); $i++) {
            $contents[] = [
                'role'  => $conversationHistory[$i]['role'],
                'parts' => [['text' => $conversationHistory[$i]['text']]]
            ];
        }
        $contents[] = ['role' => 'user', 'parts' => [['text' => $userMessage]]];
    } else {
        $contents[] = [
            'role'  => 'user',
            'parts' => [['text' => $systemPrompt . "\n\nUser Question: " . $userMessage]]
        ];
    }

    $data = [
        'contents'         => $contents,
        'generationConfig' => ['temperature' => 0.7, 'maxOutputTokens' => 1000],
        'safetySettings'   => [
            ['category' => 'HARM_CATEGORY_HARASSMENT',        'threshold' => 'BLOCK_NONE'],
            ['category' => 'HARM_CATEGORY_HATE_SPEECH',       'threshold' => 'BLOCK_NONE'],
            ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_NONE'],
            ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_NONE']
        ]
    ];

    $ch = curl_init(GEMINI_API_URL . '?key=' . GEMINI_API_KEY);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 200) {
        $result = json_decode($response, true);
        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            return ['success' => true, 'text' => $result['candidates'][0]['content']['parts'][0]['text']];
        }
        $finishReason = $result['candidates'][0]['finishReason'] ?? 'unknown';
        error_log('Gemini API Response: ' . json_encode($result));
        return ['success' => false, 'error' => 'Response ended: ' . $finishReason];
    }

    return ['success' => false, 'error' => 'API error ' . $httpCode . ': ' . $response];
}

// ─────────────────────────────────────────────────────────────────────────────
// Suggestion generator (for non-teacher-portal queries)
// ─────────────────────────────────────────────────────────────────────────────
function generateSuggestions($userMessage) {
    $msg = strtolower($userMessage);

    $related   = [];
    $different = '';

    if (strpos($msg, 'computer') !== false || strpos($msg, 'software') !== false ||
        strpos($msg, 'engineering') !== false || strpos($msg, 'networking') !== false ||
        strpos($msg, 'information technology') !== false) {
        $related   = ["Who is the Dean of Computer Science Faculty?", "How many programs does CS Faculty offer?", "What is the vision of CS Faculty?"];
        $different = "What are the admission requirements?";

    } elseif (strpos($msg, 'health') !== false || strpos($msg, 'nursing') !== false ||
              strpos($msg, 'midwifery') !== false || strpos($msg, 'nutrition') !== false ||
              strpos($msg, 'public health') !== false || strpos($msg, 'medical laboratory') !== false) {
        $related   = ["Who is the Dean of Health Sciences?", "How many departments are in Health Sciences?", "What programs does Health Sciences offer?"];
        $different = "Tell me about Faculty of Computer Science";

    } elseif (strpos($msg, 'medicine') !== false || strpos($msg, 'surgery') !== false ||
              strpos($msg, 'mbbs') !== false) {
        $related   = ["How many years is the MBBS program?", "How many credit hours for Medicine?", "What are admission requirements for Medicine?"];
        $different = "Tell me about Faculty of Health Sciences";

    } elseif (strpos($msg, 'economics') !== false || strpos($msg, 'management') !== false ||
              strpos($msg, 'business') !== false || strpos($msg, 'accounting') !== false ||
              strpos($msg, 'banking') !== false) {
        $related   = ["What programs does Economics Faculty offer?", "What is the vision of Economics Faculty?", "Tell me about the Accounting program"];
        $different = "How many students are enrolled?";

    } elseif (strpos($msg, 'agriculture') !== false || strpos($msg, 'environmental') !== false) {
        $related   = ["What programs does Agriculture Faculty offer?", "What is the goal of Agriculture program?", "What are Agriculture Faculty objectives?"];
        $different = "Tell me about Faculty of Computer Science";

    } elseif (strpos($msg, 'admission') !== false || strpos($msg, 'apply') !== false ||
              strpos($msg, 'enroll') !== false || strpos($msg, 'registration') !== false) {
        $related   = ["What documents do I need for admission?", "Is Medicine admission selective?", "Where can I get the application form?"];
        $different = "What faculties can I choose from?";

    } elseif (strpos($msg, 'rector') !== false || strpos($msg, 'ahmed') !== false) {
        $related   = ["What is the Rector's message to students?", "How can I contact the Rector's office?", "When was Capital University founded?"];
        $different = "What faculties does the university have?";

    } elseif (strpos($msg, 'contact') !== false || strpos($msg, 'email') !== false ||
              strpos($msg, 'phone') !== false || strpos($msg, 'registrar') !== false) {
        $related   = ["What is the Registrar Office email?", "What is the Rector Office email?", "How long does the university take to respond?"];
        $different = "What are the admission requirements?";

    } elseif (strpos($msg, 'verify') !== false || strpos($msg, 'graduate') !== false ||
              strpos($msg, 'diploma') !== false || strpos($msg, 'certificate') !== false) {
        $related   = ["What is the verification website?", "How do I verify my graduation?", "Why is verification important?"];
        $different = "Tell me about Faculty of Computer Science";

    } elseif (strpos($msg, 'who developed') !== false || strpos($msg, 'who created') !== false ||
              strpos($msg, 'developer') !== false || strpos($msg, 'bashir') !== false) {
        $related   = ["When was Capital University established?", "Who is the Rector?", "What is the university's mission?"];
        $different = "What faculties does Capital University have?";

    } elseif (strpos($msg, 'mission') !== false || strpos($msg, 'vision') !== false ||
              strpos($msg, 'values') !== false || strpos($msg, 'objectives') !== false) {
        $related   = ["What is the university slogan?", "What are the core values?", "What are the university objectives?"];
        $different = "Tell me about Faculty of Computer Science";

    } elseif (strpos($msg, 'founded') !== false || strpos($msg, 'history') !== false ||
              strpos($msg, 'established') !== false || strpos($msg, '2013') !== false) {
        $related   = ["Why was Capital University founded?", "Who is the Rector?", "What is the university's mission?"];
        $different = "What faculties are available?";

    } elseif (strpos($msg, 'alumni') !== false || strpos($msg, 'how many') !== false ||
              strpos($msg, 'statistics') !== false) {
        $related   = ["How many faculties does the university have?", "How many students are enrolled?", "How many alumni has the university graduated?"];
        $different = "What are the admission requirements?";

    } elseif (strpos($msg, 'partner') !== false || strpos($msg, 'international') !== false) {
        $related   = ["How many international partners does the university have?", "Does the university partner with WHO?", "What organizations does the university work with?"];
        $different = "Tell me about Faculty of Medicine";

    } elseif (strpos($msg, 'hello') !== false || strpos($msg, 'hi') !== false ||
              strpos($msg, 'hey') !== false || strpos($msg, 'salaam') !== false ||
              strpos($msg, 'subax') !== false) {
        $related   = ["What faculties does Capital University have?", "Tell me about Faculty of Computer Science", "When was Capital University founded?"];
        $different = "Who developed you?";

    } else {
        $related   = ["What faculties does Capital University have?", "Tell me about Faculty of Computer Science", "When was Capital University founded?"];
        $different = "What are the admission requirements?";
    }

    $all      = array_merge($related, [$different]);
    $filtered = [];
    foreach ($all as $s) {
        $sim = 0;
        similar_text($msg, strtolower($s), $sim);
        if ($sim < 60) $filtered[] = $s;
    }

    return array_slice($filtered, 0, 4);
}
?>