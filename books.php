<?php
require_once 'config.php';

// Add Book
if(isset($_POST['add_book'])) {
    try {
        $stmt = $pdo->prepare("INSERT INTO books (title, author_id, isbn, quantity, category_id) 
                             VALUES (:title, :author_id, :isbn, :quantity, :category_id)");
        
        $stmt->execute([
            ':title' => $_POST['title'],
            ':author_id' => $_POST['author_id'],
            ':isbn' => $_POST['isbn'],
            ':quantity' => $_POST['quantity'],
            ':category_id' => $_POST['category_id']
        ]);
        
        $_SESSION['success'] = "Book added successfully!";
        header("Location: books.php");
        exit();
        
    } catch(PDOException $e) {
        die("Error adding book: " . $e->getMessage());
    }
}

// Fetch All Books
try {
    $stmt = $pdo->query("
        SELECT b.*, a.name AS author_name, c.name AS category_name 
        FROM books b
        LEFT JOIN authors a ON b.author_id = a.id
        LEFT JOIN categories c ON b.category_id = c.id
    ");
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error fetching books: " . $e->getMessage());
}
?>