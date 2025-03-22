<?php
require_once 'config.php';

// Add Author
if(isset($_POST['add_author'])) {
    try {
        $stmt = $pdo->prepare("INSERT INTO authors (name, email, bio) 
                             VALUES (:name, :email, :bio)");
        
        $stmt->execute([
            ':name' => $_POST['name'],
            ':email' => $_POST['email'],
            ':bio' => $_POST['bio']
        ]);
        
        $_SESSION['success'] = "Author added successfully!";
        header("Location: authors.php");
        exit();
        
    } catch(PDOException $e) {
        die("Error adding author: " . $e->getMessage());
    }
}

// Fetch All Authors
try {
    $stmt = $pdo->query("SELECT * FROM authors");
    $authors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error fetching authors: " . $e->getMessage());
}
?>