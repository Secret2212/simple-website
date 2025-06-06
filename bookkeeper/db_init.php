<?php
// Database initialization script for TAYO Trucking Services
// Run this script to create or reset your database

// Database connection
$host = 'localhost';
$username = 'root';
$password = '';

// Create database connection
$conn = new mysqli($host, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Connected to MySQL server successfully.\n";

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS truckingsystem";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully or already exists.\n";
} else {
    die("Error creating database: " . $conn->error);
}

// Select the database
$conn->select_db("truckingsystem");

// Read the SQL Schema file
$sql_schema = file_get_contents('schema.sql');

// Execute multi-query SQL statements
if ($conn->multi_query($sql_schema)) {
    echo "Database schema executed successfully.\n";
    
    // Process all result sets
    do {
        // Store first result set
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->more_results() && $conn->next_result());
} else {
    die("Error executing SQL schema: " . $conn->error);
}

// Create additional tables if needed
$additional_tables = [
    // Example of additional tables that might be needed
    "CREATE TABLE IF NOT EXISTS customers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        address VARCHAR(255),
        contact_person VARCHAR(100),
        contact_number VARCHAR(50),
        email VARCHAR(100),
        tin VARCHAR(50),
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE IF NOT EXISTS trucks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        plate_number VARCHAR(20) NOT NULL UNIQUE,
        model VARCHAR(100),
        capacity VARCHAR(50),
        status VARCHAR(20) DEFAULT 'active',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE IF NOT EXISTS drivers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        license_number VARCHAR(50) NOT NULL,
        contact_number VARCHAR(50),
        address VARCHAR(255),
        status VARCHAR(20) DEFAULT 'active',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
    )"
];

// Execute each additional table creation
foreach ($additional_tables as $table_sql) {
    if ($conn->query($table_sql) === TRUE) {
        echo "Additional table created successfully.\n";
    } else {
        echo "Error creating additional table: " . $conn->error . "\n";
    }
}

echo "\nDatabase initialization completed.\n";
echo "==============================================\n";
echo "Default admin user created:\n";
echo "Username: admin\n";
echo "Password: admin123\n";
echo "==============================================\n";
echo "Please change the default password after logging in.\n";

$conn->close();
?>