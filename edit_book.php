<?php
require_once 'config.php';

// Verify librarian role
session_start();
if (!isset($_SESSION['role']) ){
    header("Location: login.php");
    exit();
}
if ($_SESSION['role'] !== 'librarian') {
    header("Location: index.php");
    exit();
}

// Initialize variables
$book = [];
$categories = [];
$error = '';

try {
    // Get book ID
    $book_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    // Fetch book data
    $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->execute([$book_id]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$book) {
        $_SESSION['error'] = "Book not found!";
        header("Location: librarian_dashboard.php?page=books");
        exit();
    }

    // Fetch categories
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $title = htmlspecialchars(trim($_POST['title']));
        $author = htmlspecialchars(trim($_POST['author']));
        $isbn = htmlspecialchars(trim($_POST['isbn']));
        $category_id = (int)$_POST['category_id'];
        $quantity = (int)$_POST['quantity'];

        // Calculate new available count
        $available = $book['available'] + ($quantity - $book['quantity']);

        $stmt = $pdo->prepare("UPDATE books SET
            title = ?, 
            author = ?, 
            isbn = ?, 
            category_id = ?, 
            quantity = ?, 
            available = ?
            WHERE id = ?");

        $stmt->execute([
            $title, 
            $author, 
            $isbn, 
            $category_id, 
            $quantity, 
            $available, 
            $book_id
        ]);

        $_SESSION['success'] = "Book updated successfully!";
        header("Location: librarian_dashboard.php?page=books");
        exit();
    }

} catch(PDOException $e) {
    $error = "Database Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Book</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-4">Edit Book</h2>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Title</label>
                <input type="text" name="title" class="form-control" 
                    value="<?= htmlspecialchars($book['title']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Author</label>
                <input type="text" name="author" class="form-control"
                    value="<?= htmlspecialchars($book['author']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">ISBN</label>
                <input type="text" name="isbn" class="form-control"
                    value="<?= htmlspecialchars($book['isbn']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Category</label>
                <select name="category_id" class="form-select" required>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['id'] ?>"
                            <?= $category['id'] == $book['category_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Quantity</label>
                <input type="number" name="quantity" class="form-control"
                    value="<?= $book['quantity'] ?>" min="1" required>
            </div>

            <button type="submit" class="btn btn-primary">Update Book</button>
            <a href="librarian_dashboard.php?page=books" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</body>
</html>