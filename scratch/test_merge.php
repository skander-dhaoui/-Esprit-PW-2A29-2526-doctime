<?php
// Mock Database class to avoid connection errors during structural testing
class Database {
    private static ?Database $instance = null;
    public static function getInstance(): Database {
        if (self::$instance === null) { self::$instance = new self(); }
        return self::$instance;
    }
    public function getConnection() { return new class { 
        public function prepare($sql) { return new class { public function execute($params=[]) { return true; } public function fetch() { return []; } public function fetchAll() { return []; } }; } 
    }; }
    public function query($sql) { return []; }
    public function queryOne($sql) { return null; }
    public function queryScalar($sql) { return null; }
    public function execute($sql, $params=[]) { return true; }
}

// Prevent config/database.php from being included if it uses a class already defined
// We will manually include everything else

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Event.php';
require_once __DIR__ . '/../repositories/UserRepository.php';
require_once __DIR__ . '/../repositories/ArticleRepository.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/UserController.php';
require_once __DIR__ . '/../controllers/EventController.php';

echo "Testing User Module Structure...\n";
if (class_exists('UserController')) {
    try {
        $userCtrl = new UserController();
        echo "OK: UserController exists and instantiated.\n";
    } catch (Throwable $e) {
        echo "ERROR: UserController instantiation failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "ERROR: UserController NOT found.\n";
}

if (class_exists('App\Models\User')) {
    echo "OK: User Model (App\Models\User) exists.\n";
} else {
    echo "ERROR: User Model (App\Models\User) NOT found.\n";
}

echo "\nTesting Event Module Structure...\n";
if (class_exists('EventController')) {
    try {
        $eventCtrl = new EventController();
        echo "OK: EventController exists and instantiated.\n";
        
        $methods = ['index', 'show', 'create', 'store', 'edit', 'update', 'delete', 'listAdmin', 'showAdmin', 'advanced'];
        foreach ($methods as $method) {
            if (method_exists($eventCtrl, $method)) {
                echo "  Method $method: EXISTS\n";
            } else {
                echo "  Method $method: MISSING!\n";
            }
        }
    } catch (Throwable $e) {
        echo "ERROR: EventController instantiation failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "ERROR: EventController NOT found.\n";
}

if (class_exists('App\Models\Event')) {
    echo "OK: Event Model (App\Models\Event) exists.\n";
    $eventModel = new App\Models\Event();
    
    $methods = ['getUpcomingEvents', 'getPastEvents', 'getAllEvents', 'getById', 'getParticipants', 'isUserParticipant', 'getCategories', 'create', 'update', 'delete', 'getAll', 'getTopEventsByParticipants', 'getRevenueEvents', 'getSpecialtyDistribution'];
    foreach ($methods as $method) {
        if (method_exists($eventModel, $method)) {
            echo "  Method $method: EXISTS\n";
        } else {
            echo "  Method $method: MISSING!\n";
        }
    }
} else {
    echo "ERROR: Event Model (App\Models\Event) NOT found.\n";
}
