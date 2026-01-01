<?php
require_once 'app/models/Ad.php';

class ApiController {
    private $pdo;
    private $adModel;
    
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        $this->adModel = new Ad($pdo);
    }
    
    public function ads() {
        $q = isset($_GET['q']) ? trim($_GET['q']) : null;
        $cat = isset($_GET['category']) && is_numeric($_GET['category']) ? (int)$_GET['category'] : null;
        $ads = $this->adModel->getAll($q, $cat);
        header('Content-Type: application/json');
        echo json_encode($ads);
    }
    
    public function ad($id) {
        $ad = $this->adModel->find($id);
        if (!$ad) {
            http_response_code(404);
            echo json_encode(['error' => 'Annonce non trouvée']);
            return;
        }
        header('Content-Type: application/json');
        echo json_encode($ad);
    }
}