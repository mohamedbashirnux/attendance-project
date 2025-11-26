<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Include database connection
include "conn.php";

// Gemini API Configuration
define('GEMINI_API_KEY', 'AIzaSyBiPq50YH9mIAWLrcEhG5f8B4C9qK4nbHc');
define('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent');

try {
    // Get user message and conversation history from request
    $input = json_decode(file_get_contents('php://input'), true);
    $userMessage = isset($input['message']) ? trim($input['message']) : '';
    $conversationHistory = isset($input['history']) ? $input['history'] : [];
    
    if (empty($userMessage)) {
        echo json_encode([
            'success' => false,
            'message' => 'Message is required'
        ]);
        exit;
    }
    
    // Load training data from ai_train.txt
    $trainingData = file_get_contents(__DIR__ . '/ai_train.txt');
    
    // Prepare prompt with training data
    $systemPrompt = $trainingData;
    $systemPrompt .= "\n\nIMPORTANT: Answer in plain text only. Do NOT use markdown formatting like **, *, #, or any other markdown symbols. Just use simple plain text.";
    
    // Call Gemini API with conversation history
    $response = callGeminiAPI($systemPrompt, $userMessage, $conversationHistory);
    
    if ($response['success']) {
        // Generate suggested questions based on the user's query
        $suggestions = generateSuggestions($userMessage);
        
        echo json_encode([
            'success' => true,
            'response' => $response['text'],
            'suggestions' => $suggestions
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $response['error']
        ]);
    }
    
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}



function callGeminiAPI($systemPrompt, $userMessage, $conversationHistory = []) {
    // Build conversation contents with history
    $contents = [];
    
    // If there's conversation history, add it
    if (!empty($conversationHistory)) {
        // First message should include system prompt
        $firstUserMsg = $conversationHistory[0]['text'];
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $systemPrompt . "\n\nUser Question: " . $firstUserMsg]]
        ];
        
        // Add the rest of the history (skip first user message since we already added it)
        for ($i = 1; $i < count($conversationHistory); $i++) {
            $contents[] = [
                'role' => $conversationHistory[$i]['role'],
                'parts' => [['text' => $conversationHistory[$i]['text']]]
            ];
        }
        
        // Add current user message
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $userMessage]]
        ];
    } else {
        // No history, just add system prompt with current message
        $fullPrompt = $systemPrompt . "\n\nUser Question: " . $userMessage;
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $fullPrompt]]
        ];
    }
    
    $data = [
        'contents' => $contents,
        'generationConfig' => [
            'temperature' => 0.7,
            'maxOutputTokens' => 1000,
        ],
        'safetySettings' => [
            [
                'category' => 'HARM_CATEGORY_HARASSMENT',
                'threshold' => 'BLOCK_NONE'
            ],
            [
                'category' => 'HARM_CATEGORY_HATE_SPEECH',
                'threshold' => 'BLOCK_NONE'
            ],
            [
                'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                'threshold' => 'BLOCK_NONE'
            ],
            [
                'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                'threshold' => 'BLOCK_NONE'
            ]
        ]
    ];
    
    $ch = curl_init(GEMINI_API_URL . '?key=' . GEMINI_API_KEY);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        $result = json_decode($response, true);
        
        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            return [
                'success' => true,
                'text' => $result['candidates'][0]['content']['parts'][0]['text']
            ];
        } else {
            // Check if response was blocked or filtered
            $errorMsg = 'Invalid response from AI';
            if (isset($result['candidates'][0]['finishReason'])) {
                $finishReason = $result['candidates'][0]['finishReason'];
                if ($finishReason === 'SAFETY') {
                    $errorMsg = 'Response was blocked by safety filters. Please try rephrasing your question.';
                } else if ($finishReason === 'RECITATION') {
                    $errorMsg = 'Response was blocked due to recitation. Please try again.';
                } else {
                    $errorMsg = 'Response ended with reason: ' . $finishReason;
                }
            }
            
            // Log the full response for debugging
            error_log('Gemini API Response: ' . json_encode($result));
            
            return [
                'success' => false,
                'error' => $errorMsg,
                'debug' => json_encode($result)
            ];
        }
    } else {
        return [
            'success' => false,
            'error' => 'API request failed with code ' . $httpCode . ': ' . $response
        ];
    }
}

function generateSuggestions($userMessage) {
    $message = strtolower($userMessage);
    $relatedSuggestions = [];
    $differentSuggestion = "";
    
    // Faculty of Computer Science - 3 related + 1 different
    if (strpos($message, 'computer') !== false || strpos($message, 'software') !== false || 
        strpos($message, 'engineering') !== false || strpos($message, 'programming') !== false ||
        strpos($message, 'networking') !== false || strpos($message, 'it ') !== false ||
        strpos($message, 'information technology') !== false) {
        $relatedSuggestions = [
            "Who is the Dean of Computer Science Faculty?",
            "How many programs does Computer Science Faculty offer?",
            "What is the vision of Computer Science Faculty?"
        ];
        $differentSuggestion = "What are the admission requirements?";
    }
    // Faculty of Health Sciences - 3 related + 1 different
    else if (strpos($message, 'health') !== false || strpos($message, 'nursing') !== false || 
        strpos($message, 'midwifery') !== false || strpos($message, 'nutrition') !== false ||
        strpos($message, 'public health') !== false || strpos($message, 'medical laboratory') !== false) {
        $relatedSuggestions = [
            "Who is the Dean of Health Sciences Faculty?",
            "How many departments are in Health Sciences Faculty?",
            "What programs does Health Sciences Faculty offer?"
        ];
        $differentSuggestion = "Tell me about Faculty of Computer Science";
    }
    // Faculty of Medicine - 3 related + 1 different
    else if (strpos($message, 'medicine') !== false || strpos($message, 'surgery') !== false || 
             strpos($message, 'doctor') !== false || strpos($message, 'mbbs') !== false) {
        $relatedSuggestions = [
            "How many years is the Medicine program?",
            "How many credit hours for Medicine program?",
            "What are the admission requirements for Medicine?"
        ];
        $differentSuggestion = "Tell me about Faculty of Health Sciences";
    }
    // Faculty of Economics - 3 related + 1 different
    else if (strpos($message, 'economics') !== false || strpos($message, 'management') !== false || 
             strpos($message, 'business') !== false || strpos($message, 'accounting') !== false ||
             strpos($message, 'finance') !== false || strpos($message, 'banking') !== false) {
        $relatedSuggestions = [
            "What programs does Economics Faculty offer?",
            "What is the vision of Economics Faculty?",
            "Tell me about the Accounting program"
        ];
        $differentSuggestion = "How many students are currently enrolled?";
    }
    // Faculty of Agriculture - 3 related + 1 different
    else if (strpos($message, 'agriculture') !== false || strpos($message, 'environmental') !== false || 
             strpos($message, 'farming') !== false || strpos($message, 'crop') !== false) {
        $relatedSuggestions = [
            "What programs does Agriculture Faculty offer?",
            "What is the goal of Agriculture program?",
            "What are the objectives of Agriculture Faculty?"
        ];
        $differentSuggestion = "Tell me about Faculty of Computer Science";
    }
    // Admission/Requirements questions - 3 related + 1 different
    else if (strpos($message, 'admission') !== false || strpos($message, 'requirements') !== false || 
        strpos($message, 'apply') !== false || strpos($message, 'application') !== false ||
        strpos($message, 'how to join') !== false || strpos($message, 'enroll') !== false ||
        strpos($message, 'registration') !== false) {
        $relatedSuggestions = [
            "What documents do I need for admission?",
            "Is admission to Medicine Faculty selective?",
            "Where can I get the application form?"
        ];
        $differentSuggestion = "What faculties can I choose from?";
    }
    // Rector questions - 3 related + 1 different
    else if (strpos($message, 'rector') !== false || strpos($message, 'dr. ahmed') !== false || 
             strpos($message, 'ahmed ga\'al') !== false || strpos($message, 'president') !== false) {
        $relatedSuggestions = [
            "What is the Rector's message to students?",
            "How can I contact the Rector's office?",
            "When was Capital University founded?"
        ];
        $differentSuggestion = "What faculties does the university have?";
    }
    // Contact/Email questions - 3 related + 1 different
    else if (strpos($message, 'contact') !== false || strpos($message, 'email') !== false || 
             strpos($message, 'phone') !== false || strpos($message, 'reach') !== false ||
             strpos($message, 'registrar') !== false) {
        $relatedSuggestions = [
            "What is the Registrar Office email?",
            "What is the Rector Office email?",
            "How long does the university take to respond?"
        ];
        $differentSuggestion = "What are the admission requirements?";
    }
    // Verification/Graduate questions - 3 related + 1 different
    else if (strpos($message, 'verify') !== false || strpos($message, 'verification') !== false || 
             strpos($message, 'graduate') !== false || strpos($message, 'graduated') !== false ||
             strpos($message, 'diploma') !== false || strpos($message, 'certificate') !== false) {
        $relatedSuggestions = [
            "What is the verification website?",
            "How do I verify my graduation?",
            "Why is verification important?"
        ];
        $differentSuggestion = "Tell me about Faculty of Computer Science";
    }
    // AI/Chatbot/Developer questions - 3 related + 1 different (MUST BE BEFORE "who" check)
    else if (strpos($message, 'who developed') !== false || strpos($message, 'who created') !== false || 
             strpos($message, 'who made') !== false || strpos($message, 'developer') !== false ||
             strpos($message, 'bashir') !== false || strpos($message, 'mohamed bashir') !== false) {
        $relatedSuggestions = [
            "When was Capital University established?",
            "Who is the Rector of Capital University?",
            "What is the university's mission?"
        ];
        $differentSuggestion = "What faculties does Capital University have?";
    }
    // Who are you questions - 3 related + 1 different (MUST BE BEFORE "who" check)
    else if (strpos($message, 'who are you') !== false || strpos($message, 'what are you') !== false ||
             strpos($message, 'ai assistant') !== false) {
        $relatedSuggestions = [
            "When was Capital University founded?",
            "Who is the Rector?",
            "What is the university's vision?"
        ];
        $differentSuggestion = "What faculties does Capital University have?";
    }
    // Achievements/Statistics questions - 3 related + 1 different
    else if (strpos($message, 'achievement') !== false || strpos($message, 'alumni') !== false || 
             strpos($message, 'students') !== false || strpos($message, 'how many') !== false ||
             strpos($message, 'statistics') !== false || strpos($message, 'numbers') !== false) {
        $relatedSuggestions = [
            "How many faculties does the university have?",
            "How many students are currently enrolled?",
            "How many alumni has the university graduated?"
        ];
        $differentSuggestion = "What are the admission requirements?";
    }
    // International/Partners questions - 3 related + 1 different
    else if (strpos($message, 'partner') !== false || strpos($message, 'international') !== false || 
             strpos($message, 'collaboration') !== false || strpos($message, 'external') !== false) {
        $relatedSuggestions = [
            "How many international partners does the university have?",
            "What organizations does the university partner with?",
            "Does the university partner with WHO?"
        ];
        $differentSuggestion = "Tell me about Faculty of Medicine";
    }
    // Authorization/Accreditation questions - 3 related + 1 different
    else if (strpos($message, 'authorized') !== false || strpos($message, 'accredited') !== false || 
             strpos($message, 'recognized') !== false || strpos($message, 'government') !== false ||
             strpos($message, 'official') !== false || strpos($message, 'legitimate') !== false) {
        $relatedSuggestions = [
            "Who authorized Capital University?",
            "Is the university recognized by the government?",
            "What does accreditation mean for students?"
        ];
        $differentSuggestion = "How many students are enrolled?";
    }
    // Mission/Vision/Values questions - 3 related + 1 different
    else if (strpos($message, 'mission') !== false || strpos($message, 'vision') !== false ||
             strpos($message, 'values') !== false || strpos($message, 'objectives') !== false ||
             strpos($message, 'slogan') !== false || strpos($message, 'motto') !== false) {
        $relatedSuggestions = [
            "What is the university slogan?",
            "What are the core values?",
            "What are the university objectives?"
        ];
        $differentSuggestion = "Tell me about Faculty of Computer Science";
    }
    // Founded/History questions - 3 related + 1 different
    else if (strpos($message, 'founded') !== false || strpos($message, 'when') !== false || 
             strpos($message, 'history') !== false || strpos($message, 'established') !== false ||
             strpos($message, 'background') !== false || strpos($message, '2013') !== false) {
        $relatedSuggestions = [
            "Why was Capital University founded?",
            "Who is the Rector?",
            "What is the university's mission?"
        ];
        $differentSuggestion = "What faculties are available?";
    }
    // Programs/Courses questions - 3 related + 1 different
    else if (strpos($message, 'program') !== false || strpos($message, 'course') !== false || 
             strpos($message, 'degree') !== false || strpos($message, 'bachelor') !== false) {
        $relatedSuggestions = [
            "How long are the programs?",
            "What faculties offer programs?",
            "Tell me about Computer Science programs"
        ];
        $differentSuggestion = "What are the admission requirements?";
    }
    // General faculty questions - 3 related + 1 different
    else if (strpos($message, 'faculty') !== false || strpos($message, 'faculties') !== false) {
        $relatedSuggestions = [
            "Tell me about Faculty of Computer Science",
            "Tell me about Faculty of Health Sciences",
            "Tell me about Faculty of Medicine"
        ];
        $differentSuggestion = "What are the admission requirements?";
    }
    // Greetings (English and Somali) - Start exploring the university!
    else if (strpos($message, 'hello') !== false || strpos($message, 'hi') !== false || 
             strpos($message, 'hey') !== false || strpos($message, 'greetings') !== false ||
             strpos($message, 'good morning') !== false || strpos($message, 'good afternoon') !== false ||
             strpos($message, 'assalamu alaikum') !== false || strpos($message, 'salaam') !== false ||
             strpos($message, 'saaxiib') !== false || strpos($message, 'waa kusalaame') !== false ||
             strpos($message, 'subax wanaagsan') !== false || strpos($message, 'sidee tahay') !== false) {
        $relatedSuggestions = [
            "What faculties does Capital University have?",
            "Tell me about Faculty of Computer Science",
            "When was Capital University founded?"
        ];
        $differentSuggestion = "Who developed you?";
    }
    // Default suggestions - 3 related + 1 different
    else {
        $relatedSuggestions = [
            "What faculties does Capital University have?",
            "Tell me about Faculty of Computer Science",
            "When was Capital University founded?"
        ];
        $differentSuggestion = "What are the admission requirements?";
    }
    
    // Combine related suggestions with the different one
    $allSuggestions = array_merge($relatedSuggestions, [$differentSuggestion]);
    
    // Filter out suggestions that are too similar to the user's question
    $filteredSuggestions = [];
    foreach ($allSuggestions as $suggestion) {
        $suggestionLower = strtolower($suggestion);
        $similarity = 0;
        
        // Check if the suggestion is too similar to the user's message
        similar_text($message, $suggestionLower, $similarity);
        
        // Only include if similarity is less than 60%
        if ($similarity < 60) {
            $filteredSuggestions[] = $suggestion;
        }
    }
    
    // Return only 4 suggestions (3 related + 1 different)
    return array_slice($filteredSuggestions, 0, 4);
}
?>
