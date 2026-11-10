<?php
require 'backend/src/Config/Config.php';
require 'backend/src/Database/Database.php';

App\Config\Config::load();
$pdo = App\Database\Database::getConnection();

$stmt = $pdo->query("SELECT id, email FROM auth.users LIMIT 5");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

