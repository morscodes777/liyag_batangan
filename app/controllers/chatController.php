<?php
require_once __DIR__ . '/../models/chatModels.php';

class ChatController {
    private $chatModel;
    private $db;

    public function __construct($db_connection) {
        $this->db = $db_connection;
        $this->chatModel = new ChatModel($this->db);
    }

    private function respondWithError($message, $code = 400) {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $message]);
        exit;
    }

    private function checkThreadAuthorization($thread_id, $user_id) {
        $participants = $this->chatModel->getThreadParticipants($thread_id);
        if (!$participants || 
            !in_array($user_id, [$participants['customer_user_id'], $participants['vendor_user_id']])) 
        {
            $this->respondWithError('Unauthorized access to chat thread.', 403);
        }
    }

    public function loadChatAction() {
        if (!isset($_SESSION['user_id'])) {
            $this->respondWithError('Not logged in.', 401);
        }
        $user_id = $_SESSION['user_id'];
        $vendor_user_id = $_GET['vendor_user_id'] ?? null; 
        $thread_id_param = $_GET['thread_id'] ?? null;
        $last_timestamp = $_GET['last_timestamp'] ?? null;
        $messages = [];
        $thread_id = null;
        
        // CASE 1: Polling or loading a known thread (Used by both customer and vendor)
        if ($thread_id_param) {
            $thread_id = $thread_id_param;
            $this->checkThreadAuthorization($thread_id, $user_id);

            if ($last_timestamp) {
                $messages = $this->chatModel->getNewMessages($thread_id, $last_timestamp);
            } else {
                $messages = $this->chatModel->getMessages($thread_id);
            }
        } 
        // CASE 2: Initial load from customer (find or create thread)
        else {
            if (!$vendor_user_id) {
                $this->respondWithError('Vendor ID or Thread ID missing for initial load.', 400);
            }
            $thread_id = $this->chatModel->findOrCreateThread($user_id, $vendor_user_id);
            if (!$thread_id) {
                $this->respondWithError('Could not start chat thread.', 500);
            }
            // After finding/creating, load full history
            $messages = $this->chatModel->getMessages($thread_id);
        }

        $responseData = [
            'success' => true,
            'thread_id' => (int)$thread_id,
            'messages' => $messages,
            'current_user_id' => (int)$user_id
        ];
        header('Content-Type: application/json');
        echo json_encode($responseData);
    }
    
    public function sendChatMessageAction() {
        if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->respondWithError('Invalid request or not logged in.', 401);
        }
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $thread_id = $input['thread_id'] ?? null;
        $message_content = trim($input['message_content'] ?? '');
        $sender_id = $_SESSION['user_id'];
        
        if (!$thread_id || empty($message_content)) {
            $this->respondWithError('Thread ID or message content missing.', 400);
        }

        $this->checkThreadAuthorization($thread_id, $sender_id);
        
        $result = $this->chatModel->sendMessage($thread_id, $sender_id, $message_content);
        
        if ($result) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Message sent.', 'message_id' => $result]);
        } else {
            $failure_message = is_string($result) ? "Failed to save message: " . $result : 'Failed to save message to database.';
            $this->respondWithError($failure_message, 500);
        }
    }

    public function getVendorThreadsAction() {
        if (!isset($_SESSION['user_id'])) {
            $this->respondWithError('Not logged in.', 401);
        }
        $vendor_user_id = $_SESSION['user_id'];
        try {
            $threads = $this->chatModel->getVendorThreads($vendor_user_id); 
            header('Content-Type: application/json');
            http_response_code(200);
            echo json_encode($threads);
        } catch (Exception $e) {
            $this->respondWithError('Failed to load threads: ' . $e->getMessage(), 500);
        }
    }

    public function getThreadMessagesAction() {
        if (!isset($_SESSION['user_id'])) {
            $this->respondWithError('Not logged in.', 401);
        }
        $user_id = $_SESSION['user_id'];
        $thread_id = $_GET['thread_id'] ?? null;
        $last_timestamp = $_GET['last_timestamp'] ?? null;

        if (!$thread_id) {
            $this->respondWithError('Missing thread ID.', 400);
        }
        try {
            $this->checkThreadAuthorization($thread_id, $user_id);
            
            if ($last_timestamp) {
                $messages = $this->chatModel->getNewMessages($thread_id, $last_timestamp);
            } else {
                $messages = $this->chatModel->getMessages($thread_id);
            }
            header('Content-Type: application/json');
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'messages' => $messages,
                'thread_id' => (int)$thread_id,
                'current_user_id' => (int)$user_id
            ]);
        } catch (Exception $e) {
            $this->respondWithError('Failed to load messages: ' . $e->getMessage(), 500);
        }
    }
    
    public function findOrCreateThreadAndLoadAction() {
        $this->loadChatAction();
    }
}