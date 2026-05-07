<?php
// Mock Database
class Database {
    private static ?Database $instance = null;
    public static function getInstance(): Database {
        if (self::$instance === null) { self::$instance = new self(); }
        return self::$instance;
    }
    public function getConnection() { return new class { 
        public function prepare($sql) { return new class { public function execute($params=[]) { return true; } public function fetch($mode=null) { return ['id'=>1, 'nom'=>'Test', 'prenom'=>'User', 'email'=>'test@test.com', 'role'=>'patient', 'statut'=>'actif', 'password'=>'hash']; } public function fetchAll($mode=null) { return []; } }; } 
    }; }
}

require_once __DIR__ . '/../repositories/UserRepository.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/UserController.php';

echo "Testing UserController methods existence...\n";
$userCtrl = new UserController();
$methods = ['profile', 'updateProfile', 'changePassword', 'updateAvatar', 'deleteAccount', 'listUsers', 'showUser', 'editUser', 'updateUser', 'deleteUser'];
foreach ($methods as $method) {
    if (method_exists($userCtrl, $method)) {
        echo "  Method $method: EXISTS\n";
    } else {
        echo "  Method $method: MISSING!\n";
    }
}
