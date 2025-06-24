<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$database = new Database();
$db = $database->getConnection();

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $slug = generateSlug($_POST['name']);
                $stmt = $db->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)");
                $stmt->execute([$_POST['name'], $slug]);
                
                $categoryId = $db->lastInsertId();
                $stmt = $db->prepare("INSERT INTO slugs (slug, type, reference_id) VALUES (?, 'category', ?)");
                $stmt->execute([$slug, $categoryId]);
                
                $success = "Category created successfully!";
                break;
                
            case 'update':
                $slug = generateSlug($_POST['name']);
                $stmt = $db->prepare("UPDATE categories SET name = ?, slug = ? WHERE id = ?");
                $stmt->execute([$_POST['name'], $slug, $_POST['id']]);
                
                $stmt = $db->prepare("UPDATE slugs SET slug = ? WHERE type = 'category' AND reference_id = ?");
                $stmt->execute([$slug, $_POST['id']]);
                
                $success = "Category updated successfully!";
                break;
                
            case 'delete':
                $stmt = $db->prepare("DELETE FROM slugs WHERE type = 'category' AND reference_id = ?");
                $stmt->execute([$_POST['id']]);
                
                $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                
                $success = "Category deleted successfully!";
                break;
        }
    }
}

// Handle search and pagination
$search = $_GET['search'] ?? '';
$page = $_GET['page'] ?? 1;
$perPage = 10;

$whereClause = '';
$params = [];

if ($search) {
    $whereClause = "WHERE name LIKE ?";
    $params = ["%$search%"];
}

// Get total count
$countSql = "SELECT COUNT(*) as total FROM categories $whereClause";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$pagination = paginate($total, $page, $perPage);

// Get categories
$sql = "SELECT * FROM categories $whereClause ORDER BY created_at DESC LIMIT {$pagination['perPage']} OFFSET {$pagination['offset']}";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get category for editing
$editCategory = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editCategory = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.2/css/dataTables.dataTables.css" />
  

    <style>
        .sidebar {
            min-height: 100vh;
            background: #343a40;
        }
        .sidebar .nav-link {
            color: #fff;
            padding: 1rem;
        }
        .sidebar .nav-link:hover {
            background: #495057;
            color: #fff;
        }
    </style>
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.2/css/dataTables.dataTables.css" />
</head>
<body>
    <div class="container-fluid">
        <div class="flex h-screen bg-gray-50">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>

            <div class="flex-1 flex flex-col">
        <?php include 'includes/navbar.php'; ?>
            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Categories Management</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal">
                        <i class="fas fa-plus"></i> Add Category
                    </button>
                </div>

                <?php if (isset($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

            


                
                

                <!-- Categories Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">

                        
                            <table id="myTable" class="display">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Slug</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td><?php echo $category['id']; ?></td>
                                        <td><?php echo htmlspecialchars($category['name']); ?></td>
                                        <td><code><?php echo htmlspecialchars($category['slug']); ?></code></td>
                                        <td><?php echo date('M d, Y', strtotime($category['created_at'])); ?></td>
                                        <td>
                                            <a href="?edit=<?php echo $category['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($pagination['totalPages'] > 1): ?>
                        <nav>
                            <ul class="pagination justify-content-center">
                                <?php if ($pagination['hasPrev']): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $pagination['currentPage'] - 1; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
                                </li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $pagination['totalPages']; $i++): ?>
                                <li class="page-item <?php echo $i == $pagination['currentPage'] ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                                </li>
                                <?php endfor; ?>
                                
                                <?php if ($pagination['hasNext']): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $pagination['currentPage'] + 1; ?>&search=<?php echo urlencode($search); ?>">Next</a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Category Modal -->
    <div class="modal fade" id="categoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo $editCategory ? 'Edit Category' : 'Add Category'; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="<?php echo $editCategory ? 'update' : 'create'; ?>">
                        <?php if ($editCategory): ?>
                        <input type="hidden" name="id" value="<?php echo $editCategory['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" required value="<?php echo $editCategory ? htmlspecialchars($editCategory['name']) : ''; ?>">
                            <div class="form-text">Slug will be generated automatically</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><?php echo $editCategory ? 'Update' : 'Create'; ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    . <script
  src="https://code.jquery.com/jquery-3.7.1.js"
  integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4="
  crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/2.3.2/js/dataTables.js"></script>
<script >

let table = new DataTable('#myTable');
</script>
    
    <script src="https://cdn.datatables.net/2.3.2/js/dataTables.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php if ($editCategory): ?>
    
    </script>
    <?php endif; ?>
</body>
</html>
