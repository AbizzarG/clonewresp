<?php
require_once 'Migration.php';

// Check command line arguments
$action = $argv[1] ?? 'all';

$migration = new Migration();

switch ($action) {
    case 'users':
        $migration->runSeeders();
        break;
    case 'migrate':
        $migration->runMigrations();
        break;
    case 'seed':
        $migration->runSeeders();
        break;
    case 'all':
    default:
        $migration->runAll();
        break;
}
?>