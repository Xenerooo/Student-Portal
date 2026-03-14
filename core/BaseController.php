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
}
?>
