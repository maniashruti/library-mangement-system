<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['role'] !== 'librarian') {
    header("Location: index.php");
    exit();
}

$current_page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$error = '';
$success = '';
$books = [];
$categories = [];
$pending_students = [];
$approved_students = [];
$pending_faculty = [];
$approved_faculty = [];
$all_categories = [];
$transactions = [];
$available_books = [];
$users = [];
$penalties = [];
$users_with_fines = [];
$rejected_students = [];
$rejected_faculty = [];

if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        switch($current_page) {
            case 'books':
                if (isset($_POST['add_book'])) {
                    $title = htmlspecialchars(trim($_POST['title']));
                    $author = htmlspecialchars(trim($_POST['author']));
                    $isbn = htmlspecialchars(trim($_POST['isbn']));
                    $publisher = htmlspecialchars(trim($_POST['publisher']));
                    $pages = (int)$_POST['pages'];
                    $price = (float)$_POST['price'];
                    $edition = htmlspecialchars(trim($_POST['edition']));
                    $category_id = (int)$_POST['category_id'];
                    $quantity = (int)$_POST['quantity'];

                    $stmt = $pdo->prepare("INSERT INTO books 
                        (title, author, isbn, publisher, pages, price, edition, category_id, quantity, available)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$title, $author, $isbn, $publisher, $pages, $price, $edition, $category_id, $quantity, $quantity]);
                    
                    $_SESSION['success'] = "Book added successfully!";
                    header("Location: librarian_dashboard.php?page=books");
                    exit();
                }
                break;

            case 'categories':
                if (isset($_POST['add_category'])) {
                    $name = htmlspecialchars(trim($_POST['name']));
                    $description = htmlspecialchars(trim($_POST['description']));
                    
                    $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
                    $stmt->execute([$name, $description]);
                    
                    $_SESSION['success'] = "Category added successfully!";
                    header("Location: librarian_dashboard.php?page=categories");
                    exit();
                }
                break;

            case 'transactions':
                if (isset($_POST['issue_book'])) {
                    $user_id = (int)$_POST['user_id'];
                    $book_id = (int)$_POST['book_id'];

                    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $user_role = $stmt->fetchColumn();

                    $issue_date = new DateTime();
                    $due_date = clone $issue_date;
                    $due_date = $user_role === 'faculty' ? $due_date->add(new DateInterval('P1M')) : $due_date->add(new DateInterval('P15D'));
                    $due_date_str = $due_date->format('Y-m-d');

                    $stmt = $pdo->prepare("SELECT available FROM books WHERE id = ?");
                    $stmt->execute([$book_id]);
                    $available = $stmt->fetchColumn();

                    if ($available < 1) {
                        $error = "Selected book is no longer available";
                    } else {
                        $pdo->beginTransaction();
                        try {
                            $stmt = $pdo->prepare("INSERT INTO transactions 
                                (book_id, user_id, issue_date, due_date, status)
                                VALUES (?, ?, NOW(), ?, 'issued')");
                            $stmt->execute([$book_id, $user_id, $due_date_str]);

                            $stmt = $pdo->prepare("UPDATE books SET available = available - 1 WHERE id = ?");
                            $stmt->execute([$book_id]);
                            
                            $pdo->commit();
                            $_SESSION['success'] = "Book issued successfully!";
                        } catch(PDOException $e) {
                            $pdo->rollBack();
                            $error = "Error issuing book: " . $e->getMessage();
                        }
                    }
                    header("Location: librarian_dashboard.php?page=transactions");
                    exit();
                }
                break;

            case 'penalties':
                if (isset($_POST['add_penalty'])) {
                    $user_id = (int)$_POST['user_id'];
                    $amount = (float)$_POST['amount'];
                    $reason = htmlspecialchars(trim($_POST['reason']));
                    
                    $stmt = $pdo->prepare("INSERT INTO fines 
                        (user_id, amount, reason, paid_status, created_at)
                        VALUES (?, ?, ?, 0, NOW())");
                    $stmt->execute([$user_id, $amount, $reason]);
                    
                    $_SESSION['success'] = "Fine added successfully!";
                    header("Location: librarian_dashboard.php?page=penalties");
                    exit();
                }
                break;
        }
    }

    if (isset($_GET['delete_id'])) {
        switch($current_page) {
            case 'books':
                $stmt = $pdo->prepare("DELETE FROM books WHERE id = ?");
                $stmt->execute([$_GET['delete_id']]);
                $_SESSION['success'] = "Book deleted successfully!";
                header("Location: librarian_dashboard.php?page=books");
                exit();
                break;

            case 'categories':
                $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                $stmt->execute([$_GET['delete_id']]);
                $_SESSION['success'] = "Category deleted successfully!";
                header("Location: librarian_dashboard.php?page=categories");
                exit();
                break;

            case 'students':
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$_GET['delete_id']]);
                $_SESSION['success'] = "Student deleted successfully!";
                header("Location: librarian_dashboard.php?page=students");
                exit();
                break;

            case 'faculty':
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$_GET['delete_id']]);
                $_SESSION['success'] = "Faculty deleted successfully!";
                header("Location: librarian_dashboard.php?page=faculty");
                exit();
                break;

            case 'penalties':
                $stmt = $pdo->prepare("DELETE FROM fines WHERE id = ?");
                $stmt->execute([$_GET['delete_id']]);
                $_SESSION['success'] = "Fine deleted successfully!";
                header("Location: librarian_dashboard.php?page=penalties");
                exit();
                break;
        }
    }

    if (isset($_GET['reject_id'])) {
        $current_page = $_GET['page'] ?? 'dashboard';
        $user_id = (int)$_GET['reject_id'];

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("DELETE FROM pending_approvals WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            $stmt = $pdo->prepare("UPDATE users SET approved = 2 WHERE id = ?");
            $stmt->execute([$user_id]);
            
            $pdo->commit();
            $_SESSION['success'] = "Application rejected successfully!";
        } catch(PDOException $e) {
            $pdo->rollBack();
            $error = "Error rejecting application: " . $e->getMessage();
        }
        header("Location: librarian_dashboard.php?page=" . $current_page);
        exit();
    }

    if (isset($_GET['approve_id'])) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("UPDATE users SET approved = 1 WHERE id = ?");
            $stmt->execute([$_GET['approve_id']]);

            $stmt = $pdo->prepare("DELETE FROM pending_approvals WHERE user_id = ?");
            $stmt->execute([$_GET['approve_id']]);
            
            $pdo->commit();
            $_SESSION['success'] = "User approved successfully!";
        } catch(PDOException $e) {
            $pdo->rollBack();
            $error = "Error approving user: " . $e->getMessage();
        }
        header("Location: librarian_dashboard.php?page=" . $current_page);
        exit();
    }

    if (isset($_GET['return_id'])) {
        $transaction_id = (int)$_GET['return_id'];

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT book_id FROM transactions WHERE id = ?");
            $stmt->execute([$transaction_id]);
            $book_id = $stmt->fetchColumn();

            $stmt = $pdo->prepare("UPDATE transactions 
                SET return_date = NOW(), status = 'returned'
                WHERE id = ?");
            $stmt->execute([$transaction_id]);

            $stmt = $pdo->prepare("UPDATE books SET available = available + 1 WHERE id = ?");
            $stmt->execute([$book_id]);
            
            $pdo->commit();
            $_SESSION['success'] = "Book returned successfully!";
        } catch(PDOException $e) {
            $pdo->rollBack();
            $error = "Error returning book: " . $e->getMessage();
        }
        header("Location: librarian_dashboard.php?page=transactions");
        exit();
    }

    if (isset($_GET['pay_id'])) {
        $fine_id = (int)$_GET['pay_id'];
        $stmt = $pdo->prepare("UPDATE fines SET paid_status = 1, paid_at = NOW() WHERE id = ?");
        $stmt->execute([$fine_id]);
        $_SESSION['success'] = "Fine marked as paid!";
        header("Location: librarian_dashboard.php?page=penalties");
        exit();
    }

    switch($current_page) {
        case 'dashboard':
            $stmt = $pdo->query("SELECT COUNT(*) FROM books");
            $total_books = $stmt->fetchColumn();
            
            $stmt = $pdo->query("SELECT SUM(available) FROM books");
            $available_books_count = $stmt->fetchColumn();
            break;

        case 'books':
            $stmt = $pdo->query("SELECT b.*, c.name AS category 
                FROM books b
                LEFT JOIN categories c ON b.category_id = c.id
                ORDER BY title");
            $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'categories':
            $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
            $all_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'students':
            $stmt = $pdo->query("SELECT u.*, p.requested_at 
                FROM users u
                JOIN pending_approvals p ON u.id = p.user_id
                WHERE u.role = 'student' AND u.approved = 0");
            $pending_students = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $stmt = $pdo->query("SELECT * FROM users
                WHERE role = 'student' AND approved = 1
                ORDER BY fullname");
            $approved_students = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $pdo->query("SELECT * FROM users
                WHERE role = 'student' AND approved = 2
                ORDER BY fullname");
            $rejected_students = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'faculty':
            $stmt = $pdo->query("SELECT u.*, p.requested_at 
                FROM users u
                JOIN pending_approvals p ON u.id = p.user_id
                WHERE u.role = 'faculty' AND u.approved = 0");
            $pending_faculty = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $stmt = $pdo->query("SELECT * FROM users
                WHERE role = 'faculty' AND approved = 1
                ORDER BY fullname");
            $approved_faculty = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $pdo->query("SELECT * FROM users
                WHERE role = 'faculty' AND approved = 2
                ORDER BY fullname");
            $rejected_faculty = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'transactions':
            $stmt = $pdo->query("SELECT t.*, b.title AS book_title, 
                u.fullname AS user_name, u.role AS user_role
                FROM transactions t
                JOIN books b ON t.book_id = b.id
                JOIN users u ON t.user_id = u.id
                ORDER BY t.issue_date DESC");
            $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $stmt = $pdo->query("SELECT * FROM books WHERE available > 0 ORDER BY title");
            $available_books = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $stmt = $pdo->query("SELECT id, fullname, role 
                FROM users
                WHERE role = 'student' OR (role = 'faculty' AND approved = 1)
                ORDER BY fullname");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;

        case 'penalties':
            $stmt = $pdo->query("SELECT f.*, u.fullname 
                FROM fines f
                JOIN users u ON f.user_id = u.id
                ORDER BY f.created_at DESC");
            $penalties = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $stmt = $pdo->query("SELECT id, fullname 
                FROM users
                WHERE role IN ('student', 'faculty')
                ORDER BY fullname");
            $users_with_fines = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
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
    <title>Library Management - Librarian Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1a237e;
            --secondary-color: #0d47a1;
            --accent-color: #42a5f5;
            --light-bg: #f8f9fa;
            --dark-text: #2b2d42;
        }

        body {
            background-color: #f4f6fc;
            min-height: 100vh;
        }

        .sidebar {
            background: linear-gradient(180deg, var(--primary-color), var(--secondary-color));
            height: 100vh;
            width: 280px;
            position: fixed;
            z-index: 1000;
            transition: transform 0.3s ease;
        }

        .nav-link {
            color: rgba(255,255,255,0.9);
            padding: 15px 25px;
            margin: 8px 15px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 15px;
            font-weight: 500;
        }

        .nav-link:hover {
            background: rgba(255,255,255,0.1);
            transform: translateX(10px);
        }

        .nav-link.active {
            background: rgba(255,255,255,0.95);
            color: var(--primary-color) !important;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .main-content {
            margin-left: 280px;
            padding: 30px;
            min-height: 100vh;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }

        .search-box {
            position: relative;
            margin-bottom: 1rem;
        }

        .search-box input {
            padding-right: 40px;
        }

        .search-box i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }

        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <button class="btn btn-primary sidebar-toggle" style="position: fixed; left: 20px; top: 20px; z-index: 1001;">
        <i class="fas fa-bars"></i>
    </button>

    <div class="sidebar">
        <div class="p-4 text-center">
            <img src="logo.png" alt="Library Logo" class="w-50 mb-3">
            <h4 class="mb-4 text-white">Library Admin Panel</h4>
        </div>
        <nav class="nav flex-column">
            <a class="nav-link <?= $current_page === 'dashboard' ? 'active' : '' ?>" href="?page=dashboard">
                <i class="fas fa-chart-line"></i> Dashboard
            </a>
            <a class="nav-link <?= $current_page === 'books' ? 'active' : '' ?>" href="?page=books">
                <i class="fas fa-book"></i> Book Management
            </a>
            <a class="nav-link <?= $current_page === 'categories' ? 'active' : '' ?>" href="?page=categories">
                <i class="fas fa-tags"></i> Categories
            </a>
            <a class="nav-link <?= $current_page === 'students' ? 'active' : '' ?>" href="?page=students">
                <i class="fas fa-users"></i> Student Management
            </a>
            <a class="nav-link <?= $current_page === 'faculty' ? 'active' : '' ?>" href="?page=faculty">
                <i class="fas fa-chalkboard-teacher"></i> Faculty Management
            </a>
            <a class="nav-link <?= $current_page === 'transactions' ? 'active' : '' ?>" href="?page=transactions">
                <i class="fas fa-exchange-alt"></i> Issue/Return Books
            </a>
            <a class="nav-link <?= $current_page === 'penalties' ? 'active' : '' ?>" href="?page=penalties">
                <i class="fas fa-money-bill-wave"></i> Penalty/Fine Management
            </a>
            <a class="nav-link text-white bg-danger-hover" href="logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </nav>
    </div>

    <div class="main-content">
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <?php switch($current_page):
            case 'dashboard': ?>
                <h2 class="mb-4">Dashboard Overview</h2>
                <div class="row g-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h5>Total Books</h5>
                            <h2><?= number_format($total_books) ?></h2>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h5>Available Books</h5>
                            <h2><?= number_format($available_books_count) ?></h2>
                        </div>
                    </div>
                </div>
                <?php break; ?>

            <?php case 'books': ?>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Book Management</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBookModal">
                        <i class="fas fa-plus me-2"></i> Add New Book
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Title</th>
                                <th>Author</th>
                                <th>ISBN</th>
                                <th>Publisher</th>
                                <th>Pages</th>
                                <th>Price</th>
                                <th>Edition</th>
                                <th>Category</th>
                                <th>Available</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($books as $book): ?>
                            <tr>
                                <td><?= htmlspecialchars($book['title']) ?></td>
                                <td><?= htmlspecialchars($book['author']) ?></td>
                                <td><?= htmlspecialchars($book['isbn']) ?></td>
                                <td><?= htmlspecialchars($book['Publisher']) ?></td>
                                <td><?= $book['Pages'] ?></td>
                                <td><?= number_format($book['price'], 2) ?></td>
                                <td><?= htmlspecialchars($book['Edition']) ?></td>
                                <td><?= htmlspecialchars($book['category']) ?></td>
                                <td><?= $book['available'] ?></td>
                                <td>
                                    <a href="edit_book.php?id=<?= $book['id'] ?>" class="btn btn-sm btn-warning">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?page=books&delete_id=<?= $book['id'] ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Are you sure?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="modal fade" id="addBookModal">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title">Add New Book</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form method="POST">
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label>Title</label>
                                        <input type="text" name="title" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label>Author</label>
                                        <input type="text" name="author" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label>ISBN</label>
                                        <input type="text" name="isbn" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label>Publisher</label>
                                        <input type="text" name="publisher" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label>Number of Pages</label>
                                        <input type="number" name="pages" class="form-control" min="1" required>
                                    </div>
                                    <div class="mb-3">
                                        <label>Price</label>
                                        <input type="number" name="price" step="0.01" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label>Edition</label>
                                        <input type="text" name="edition" class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label>Category</label>
                                        <select name="category_id" class="form-select" required>
                                            <?php foreach ($categories as $category): ?>
                                            <option value="<?= $category['id'] ?>">
                                                <?= htmlspecialchars($category['name']) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label>Quantity</label>
                                        <input type="number" name="quantity" class="form-control" min="1" required>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" name="add_book" class="btn btn-primary">Add Book</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php break; ?>

            <?php case 'categories': ?>
                <section class="mb-5">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="text-primary">Category Management</h2>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                            <i class="fas fa-plus me-2"></i> Add Category
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Category Name</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_categories as $category): ?>
                                <tr>
                                    <td><?= $category['id'] ?></td>
                                    <td><?= htmlspecialchars($category['name']) ?></td>
                                    <td><?= htmlspecialchars($category['description']) ?></td>
                                    <td>
                                        <a href="edit_category.php?id=<?= $category['id'] ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?page=categories&delete_id=<?= $category['id'] ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Delete this category?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <div class="modal fade" id="addCategoryModal">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title">Add New Category</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form method="POST">
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label>Category Name</label>
                                        <input type="text" name="name" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label>Description</label>
                                        <textarea name="description" class="form-control" rows="3"></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" name="add_category" class="btn btn-primary">Add Category</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php break; ?>

            <?php case 'students': ?>
                <section class="mb-5">
                    <h2 class="mb-4">Student Management</h2>
                    <div class="card mb-4">
                        <div class="card-header bg-warning text-dark">
                            <i class="fas fa-user-clock me-2"></i> Pending Approvals
                        </div>
                        <div class="card-body">
                            <?php if (empty($pending_students)): ?>
                                <div class="alert alert-info">No pending student approvals</div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Registration Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($pending_students as $student): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($student['fullname']) ?></td>
                                                <td><?= htmlspecialchars($student['email']) ?></td>
                                                <td><?= date('M d, Y', strtotime($student['requested_at'])) ?></td>
                                                <td>
                                                    <a href="?page=students&approve_id=<?= $student['id'] ?>" 
                                                       class="btn btn-sm btn-success"
                                                       onclick="return confirm('Approve this student?')">
                                                        <i class="fas fa-check"></i> Approve
                                                    </a>
                                                    <a href="?page=students&reject_id=<?= $student['id'] ?>" 
                                                       class="btn btn-sm btn-danger"
                                                       onclick="return confirm('Reject this application?')">
                                                        <i class="fas fa-times"></i> Reject
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <i class="fas fa-user-check me-2"></i> Approved Students
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Registration Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($approved_students as $student): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($student['fullname']) ?></td>
                                            <td><?= htmlspecialchars($student['email']) ?></td>
                                            <td><?= date('M d, Y', strtotime($student['created_at'])) ?></td>
                                            <td>
                                                <a href="edit_student.php?id=<?= $student['id'] ?>" 
                                                   class="btn btn-sm btn-warning">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="?page=students&delete_id=<?= $student['id'] ?>" 
                                                   class="btn btn-sm btn-danger"
                                                   onclick="return confirm('Delete this student?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header bg-danger text-white">
                            <i class="fas fa-user-times me-2"></i> Rejected Applications
                        </div>
                        <div class="card-body">
                            <?php if (empty($rejected_students)): ?>
                                <div class="alert alert-info">No rejected student applications</div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Registration Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($rejected_students as $student): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($student['fullname']) ?></td>
                                                <td><?= htmlspecialchars($student['email']) ?></td>
                                                <td><?= date('M d, Y', strtotime($student['created_at'])) ?></td>
                                                <td>
                                                    <a href="?page=students&approve_id=<?= $student['id'] ?>" 
                                                       class="btn btn-sm btn-success"
                                                       onclick="return confirm('Approve this student?')">
                                                        <i class="fas fa-check"></i> Approve
                                                    </a>
                                                    <a href="?page=students&delete_id=<?= $student['id'] ?>" 
                                                       class="btn btn-sm btn-danger"
                                                       onclick="return confirm('Permanently delete this student?')">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>
                <?php break; ?>

            <?php case 'faculty': ?>
                <section class="mb-5">
                    <h2 class="mb-4">Faculty Management</h2>
                    <div class="card mb-4">
                        <div class="card-header bg-warning text-dark">
                            <i class="fas fa-user-clock me-2"></i> Pending Approvals
                        </div>
                        <div class="card-body">
                            <?php if (empty($pending_faculty)): ?>
                                <div class="alert alert-info">No pending faculty approvals</div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Department</th>
                                                <th>Request Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($pending_faculty as $faculty): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($faculty['fullname']) ?></td>
                                                <td><?= htmlspecialchars($faculty['email']) ?></td>
                                                <td><?= htmlspecialchars($faculty['department']) ?></td>
                                                <td><?= date('M d, Y', strtotime($faculty['requested_at'])) ?></td>
                                                <td>
                                                    <a href="?page=faculty&approve_id=<?= $faculty['id'] ?>" 
                                                       class="btn btn-sm btn-success"
                                                       onclick="return confirm('Approve this faculty member?')">
                                                        <i class="fas fa-check"></i> Approve
                                                    </a>
                                                    <a href="?page=faculty&reject_id=<?= $faculty['id'] ?>" 
                                                       class="btn btn-sm btn-danger"
                                                       onclick="return confirm('Reject this application?')">
                                                        <i class="fas fa-times"></i> Reject
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <i class="fas fa-user-check me-2"></i> Approved Faculty
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Department</th>
                                            <th>Position</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($approved_faculty as $faculty): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($faculty['fullname']) ?></td>
                                            <td><?= htmlspecialchars($faculty['email']) ?></td>
                                            <td><?= htmlspecialchars($faculty['department'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($faculty['position'] ?? 'N/A') ?></td>
                                            <td>
                                                <a href="edit_faculty.php?id=<?= $faculty['id'] ?>" 
                                                   class="btn btn-sm btn-warning">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="?page=faculty&delete_id=<?= $faculty['id'] ?>" 
                                                   class="btn btn-sm btn-danger"
                                                   onclick="return confirm('Delete this faculty member?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header bg-danger text-white">
                            <i class="fas fa-user-times me-2"></i> Rejected Applications
                        </div>
                        <div class="card-body">
                            <?php if (empty($rejected_faculty)): ?>
                                <div class="alert alert-info">No rejected faculty applications</div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Department</th>
                                                <th>Request Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($rejected_faculty as $faculty): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($faculty['fullname']) ?></td>
                                                <td><?= htmlspecialchars($faculty['email']) ?></td>
                                                <td><?= htmlspecialchars($faculty['department'] ?? 'N/A') ?></td>
                                                <td><?= date('M d, Y', strtotime($faculty['created_at'])) ?></td>
                                                <td>
                                                    <a href="?page=faculty&approve_id=<?= $faculty['id'] ?>" 
                                                       class="btn btn-sm btn-success"
                                                       onclick="return confirm('Approve this faculty member?')">
                                                        <i class="fas fa-check"></i> Approve
                                                    </a>
                                                    <a href="?page=faculty&delete_id=<?= $faculty['id'] ?>" 
                                                       class="btn btn-sm btn-danger"
                                                       onclick="return confirm('Permanently delete this faculty member?')">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>
                <?php break; ?>

            <?php case 'transactions': ?>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Issue/Return Management</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#issueBookModal">
                        <i class="fas fa-plus me-2"></i> Issue New Book
                    </button>
                </div>
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <i class="fas fa-book-open me-2"></i> Active Issues
                    </div>
                    <div class="card-body">
                    <?php 
$has_active_issues = !empty(array_filter($transactions, function($t) { 
    return $t['status'] === 'issued'; 
}));
if (!$has_active_issues): 
?>
                            <div class="alert alert-info">No active book issues</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Book Title</th>
                                            <th>User</th>
                                            <th>Issue Date</th>
                                            <th>Due Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($transactions as $t): ?>
                                            <?php if ($t['status'] === 'issued'): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($t['book_title']) ?></td>
                                                    <td><?= htmlspecialchars($t['user_name']) ?> (<?= $t['user_role'] ?>)</td>
                                                    <td><?= date('M d, Y', strtotime($t['issue_date'])) ?></td>
                                                    <td><?= date('M d, Y', strtotime($t['due_date'])) ?></td>
                                                    <td>
                                                        <a href="?page=transactions&return_id=<?= $t['id'] ?>" 
                                                           class="btn btn-sm btn-success"
                                                           onclick="return confirm('Mark this book as returned?')">
                                                            <i class="fas fa-check"></i> Return
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <i class="fas fa-history me-2"></i> Return History
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Book Title</th>
                                        <th>User</th>
                                        <th>Issue Date</th>
                                        <th>Return Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transactions as $t): ?>
                                        <?php if ($t['status'] === 'returned'): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($t['book_title']) ?></td>
                                                <td><?= htmlspecialchars($t['user_name']) ?> (<?= $t['user_role'] ?>)</td>
                                                <td><?= date('M d, Y', strtotime($t['issue_date'])) ?></td>
                                                <td><?= date('M d, Y', strtotime($t['return_date'])) ?></td>
                                            </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="issueBookModal">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title">Issue New Book</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form method="POST">
                                <div class="modal-body">
                                    <div class="search-box">
                                        <input type="text" id="userSearch" class="form-control" placeholder="Search users...">
                                        <i class="fas fa-search"></i>
                                    </div>
                                    <div class="mb-3">
                                        <label>Select User</label>
                                        <select name="user_id" class="form-select" id="userSelect" required>
                                            <?php foreach ($users as $user): ?>
                                            <option value="<?= $user['id'] ?>">
                                                <?= htmlspecialchars($user['fullname']) ?> (<?= $user['role'] ?>)
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label>Select Book</label>
                                        <select name="book_id" class="form-select" required>
                                            <?php foreach ($available_books as $book): ?>
                                            <option value="<?= $book['id'] ?>">
                                                <?= htmlspecialchars($book['title']) ?>
                                                (Available: <?= $book['available'] ?>)
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" name="issue_book" class="btn btn-primary">Issue Book</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php break; ?>

            <?php case 'penalties': ?>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Fine Management</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPenaltyModal">
                        <i class="fas fa-plus me-2"></i> Add New Fine
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>User</th>
                                <th>Amount</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($penalties as $fine): ?>
                            <tr>
                                <td><?= htmlspecialchars($fine['fullname']) ?></td>
                                <td>₹<?= number_format($fine['amount'], 2) ?></td>
                                <td><?= htmlspecialchars($fine['reason']) ?></td>
                                <td>
                                    <span class="badge <?= $fine['paid_status'] ? 'bg-success' : 'bg-danger' ?>">
                                        <?= $fine['paid_status'] ? 'Paid' : 'Unpaid' ?>
                                    </span>
                                </td>
                                <td><?= date('M d, Y', strtotime($fine['created_at'])) ?></td>
                                <td>
                                    <?php if (!$fine['paid_status']): ?>
                                        <a href="?page=penalties&pay_id=<?= $fine['id'] ?>" 
                                           class="btn btn-sm btn-success"
                                           onclick="return confirm('Mark this fine as paid?')">
                                            <i class="fas fa-check"></i> Mark Paid
                                        </a>
                                    <?php endif; ?>
                                    <a href="?page=penalties&delete_id=<?= $fine['id'] ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Delete this fine?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="modal fade" id="addPenaltyModal">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title">Add New Fine</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form method="POST">
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label>Select User</label>
                                        <select name="user_id" class="form-select" required>
                                            <?php foreach ($users_with_fines as $user): ?>
                                            <option value="<?= $user['id'] ?>">
                                                <?= htmlspecialchars($user['fullname']) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label>Amount</label>
                                        <input type="number" name="amount" step="0.01" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label>Reason</label>
                                        <textarea name="reason" class="form-control" rows="3" required></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" name="add_penalty" class="btn btn-primary">Add Fine</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php break; ?>
        <?php endswitch; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelector('.sidebar-toggle').addEventListener('click', () => {
            document.querySelector('.sidebar').classList.toggle('active');
        });

        document.addEventListener('click', (e) => {
            if (window.innerWidth < 992 &&
                !e.target.closest('.sidebar') &&
                !e.target.closest('.sidebar-toggle')) {
                document.querySelector('.sidebar').classList.remove('active');
            }
        });

        document.getElementById('userSearch').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const options = document.querySelectorAll('#userSelect option');
            
            options.forEach(option => {
                const text = option.textContent.toLowerCase();
                option.style.display = text.includes(searchTerm) ? 'block' : 'none';
            });
        });

        document.querySelectorAll('.table-responsive').forEach(table => {
            new bootstrap.ResponsiveTableComponent(table);
        });
    </script>
</body>
</html>