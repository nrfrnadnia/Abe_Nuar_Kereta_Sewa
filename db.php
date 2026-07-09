<?php
/**
 * Database Connection & Initialization Blueprint
 * Filename: db.php
 * * Instructions: Include this file at the top of your PHP pages using:
 * require_once 'db.php';
 */

// 1. Database Connection Configuration
$host     = 'localhost';
$username = 'root';
$password = ''; // Default XAMPP MySQL password is empty
$dbname   = 'keretasewa_db';

try {
    // 2. Connect to MySQL Server first (without selecting database) to ensure it exists
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Create database if it does not exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    
    // 3. Switch context to your car rental database
    $pdo->exec("USE `$dbname`;");
    

    // Execute each table creation statement
    foreach ($tables as $sql) {
        $pdo->exec($sql);
    }

    // 5. SEED INITIAL ADMIN ACCOUNT (If table is completely empty)
    $checkAdmin = $pdo->query("SELECT COUNT(*) FROM `admin`")->fetchColumn();
    if ($checkAdmin == 0) {
        $defaultAdminName = 'admin';
        // Securely hash the password 'admin123' using PHP's production-grade hashing standard
        $defaultAdminPass = password_hash('admin123', PASSWORD_BCRYPT);
        
        $seedStmt = $pdo->prepare("INSERT INTO `admin` (`adminName`, `adminPass`) VALUES (?, ?)");
        $seedStmt->execute([$defaultAdminName, $defaultAdminPass]);
    }

} catch (PDOException $e) {
    // Stop system execution and output connection structural context if failure occurs
    die("Database engine initialization fault: " . $e->getMessage());
}

// Global active $pdo database channel instance handles subsequent structural routing queries
?>