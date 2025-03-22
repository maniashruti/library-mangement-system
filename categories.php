<?php
require_once 'config.php';

// Add Category
if(isset($_POST['add_category'])) {
    try {
        $stmt = $pdo->prepare("INSERT INTO categories (name, description) 
                             VALUES (:name, :description)");
        
        $stmt->execute([
            ':name' => $_POST['name'],
            ':description' => $_POST['description']
        ]);
        
        $_SESSION['success'] = "Category added successfully!";
        header("Location: categories.php");
        exit();
        
    } catch(PDOException $e) {
        die("Error adding category: " . $e->getMessage());
    }
}

// Fetch All Categories
try {
    $stmt = $pdo->query("SELECT * FROM categories");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error fetching categories: " . $e->getMessage());
}
?>