<?php
namespace App\Core;

class BaseController {
    protected $conn;

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }

    protected function render($view, $data = []) {
        extract($data);
        // Normalize the view name (remove .php if present)
        $view = str_replace('.php', '', $view);
        
        // Define root relative to this file
        $viewPath = __DIR__ . "/../views/" . $view . ".php";
        
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            // Debugging output
            header('Content-Type: text/plain');
            echo "ERROR: View '$view' not found.\n";
            echo "Expected Path: $viewPath\n";
            echo "Current CWD: " . getcwd() . "\n";
            exit();
        }
    }

    protected function json($data, $statusCode = 200) {
        if (ob_get_length() > 0) ob_clean();
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }

    /**
     * CSRF Protection: Generate a token and store it in session.
     */
    protected function generateCsrfToken() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * CSRF Protection: Verify the token from POST or headers.
     */
    protected function verifyCsrfToken() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        
        if (!$token || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            $this->json(['success' => false, 'message' => 'Invalid CSRF token.'], 403);
        }
    }

    /**
     * Helper to escape HTML in views.
     */
    protected function h($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}
?>
