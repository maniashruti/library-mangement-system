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
$category = [];
$error = '';

try {
    // Get category ID
    $category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    // Fetch category data
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$category) {
        $_SESSION['error'] = "Category not found!";
        header("Location: librarian_dashboard.php?page=categories");
        exit();
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = htmlspecialchars(trim($_POST['name']));
        $description = htmlspecialchars(trim($_POST['description']));

        $stmt = $pdo->prepare("UPDATE categories SET
            name = ?, 
            description = ?
            WHERE id = ?");

        $stmt->execute([$name, $description, $category_id]);

        $_SESSION['success'] = "Category updated successfully!";
        header("Location: librarian_dashboard.php?page=categories");
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
    <title>Edit Category</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-4">Edit Category</h2>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Category Name</label>
                <input type="text" name="name" class="form-control"
                    value="<?= htmlspecialchars($category['name']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="3"><?= 
                    htmlspecialchars($category['description']) ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary">Update Category</button>
            <a href="librarian_dashboard.php?page=categories" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</body>
</html>