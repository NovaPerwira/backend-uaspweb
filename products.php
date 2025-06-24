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
                $slug = generateSlug($_POST['title']);
                $thumbnail = '';
                
                if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] == 0) {
    
    
                    $upload = uploadImage($_FILES['thumbnail']);
                    if ($upload['success']) {
                        $thumbnail = $upload['filepath'];
                    }
                }
                
                $stmt = $db->prepare("INSERT INTO products (title, description, thumbnail, slug, price, category_id) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$_POST['title'], $_POST['description'], $thumbnail, $slug, $_POST['price'], $_POST['category_id']]);
                
                $productId = $db->lastInsertId();
                $stmt = $db->prepare("INSERT INTO slugs (slug, type, reference_id) VALUES (?, 'product', ?)");
                $stmt->execute([$slug, $productId]);
                
                $success = "Product created successfully!";
                break;
                
            case 'update':
                $slug = generateSlug($_POST['title']);
                $thumbnail = $_POST['current_thumbnail'];
                
                if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] == 0) {
                    $upload = uploadImage($_FILES['thumbnail']);
                    if ($upload['success']) {
                        if ($thumbnail && file_exists($thumbnail)) {
                            unlink($thumbnail);
                        }
                        $thumbnail = $upload['filepath'];
                    }
                }
                
                $stmt = $db->prepare("UPDATE products SET title = ?, description = ?, thumbnail = ?, slug = ?, price = ?, category_id = ? WHERE id = ?");
                $stmt->execute([$_POST['title'], $_POST['description'], $thumbnail, $slug, $_POST['price'], $_POST['category_id'], $_POST['id']]);
                
                $stmt = $db->prepare("UPDATE slugs SET slug = ? WHERE type = 'product' AND reference_id = ?");
                $stmt->execute([$slug, $_POST['id']]);
                
                $success = "Product updated successfully!";
                break;
                
            case 'delete':
                $stmt = $db->prepare("SELECT thumbnail FROM products WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($product['thumbnail'] && file_exists($product['thumbnail'])) {
                    unlink($product['thumbnail']);
                }
                
                $stmt = $db->prepare("DELETE FROM slugs WHERE type = 'product' AND reference_id = ?");
                $stmt->execute([$_POST['id']]);
                
                $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                
                $success = "Product deleted successfully!";
                break;
        }
    }
}

// Get categories for dropdown
$stmt = $db->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle search and pagination
$search = $_GET['search'] ?? '';
$categoryFilter = $_GET['category'] ?? '';
$page = $_GET['page'] ?? 1;
$perPage = 10;

$whereClause = '';
$params = [];

$conditions = [];
if ($search) {
    $conditions[] = "(title LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($categoryFilter) {
    $conditions[] = "category_id = ?";
    $params[] = $categoryFilter;
}

if ($conditions) {
    $whereClause = "WHERE " . implode(" AND ", $conditions);
}

// Get total count
$countSql = "SELECT COUNT(*) as total FROM products $whereClause";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$pagination = paginate($total, $page, $perPage);

// Get products with category names
$sql = "SELECT p.*, c.name as category_name FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        $whereClause 
        ORDER BY p.created_at DESC 
        LIMIT {$pagination['perPage']} OFFSET {$pagination['offset']}";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get product for editing
$editProduct = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editProduct = $stmt->fetch(PDO::FETCH_ASSOC);
}

$page_title = 'Dashboard';
include 'includes/header.php';
?>

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
                    <h1 class="h2">Products Management</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#productModal">
                        <i class="fas fa-plus"></i> Add Product
                    </button>
                </div>

                <?php if (isset($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Search and Filter -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <form method="GET" class="d-flex">
                            <input type="text" name="search" class="form-control" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
                            <input type="hidden" name="category" value="<?php echo htmlspecialchars($categoryFilter); ?>">
                            <button type="submit" class="btn btn-outline-secondary ms-2">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
                    </div>
                    <div class="col-md-3">
                        <form method="GET">
                            <select name="category" class="form-select" onchange="this.form.submit()">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo $categoryFilter == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                        </form>
                    </div>
                </div>

                <!-- Products Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="myTable" class="display">
                                <thead>
                                    <tr>
                                        <th>Image</th>
                                        <th>Title</th>
                                        <th>Category</th>
                                        <th>Price</th>
                                        <th>Slug</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td>
                                            <?php if ($product['thumbnail']): ?>
                                            <img src="<?php echo htmlspecialchars($product['thumbnail']); ?>" class="product-thumbnail" alt="Thumbnail">
                                            <?php else: ?>
                                            <div class="product-thumbnail bg-light d-flex align-items-center justify-content-center">
                                                <i class="fas fa-image text-muted"></i>
                                            </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($product['title']); ?></td>
                                        <td><?php echo htmlspecialchars($product['category_name'] ?? 'No Category'); ?></td>
                                        <td>$<?php echo number_format($product['price'], 2); ?></td>
                                        <td><code><?php echo htmlspecialchars($product['slug']); ?></code></td>
                                        <td><?php echo date('M d, Y', strtotime($product['created_at'])); ?></td>
                                        <td>
                                            <a href="?edit=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
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
                                    <a class="page-link" href="?page=<?php echo $pagination['currentPage'] - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($categoryFilter); ?>">Previous</a>
                                </li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $pagination['totalPages']; $i++): ?>
                                <li class="page-item <?php echo $i == $pagination['currentPage'] ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($categoryFilter); ?>"><?php echo $i; ?></a>
                                </li>
                                <?php endfor; ?>
                                
                                <?php if ($pagination['hasNext']): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $pagination['currentPage'] + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($categoryFilter); ?>">Next</a>
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

    <!-- Product Modal -->
    <div class="modal fade" id="productModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo $editProduct ? 'Edit Product' : 'Add Product'; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="<?php echo $editProduct ? 'update' : 'create'; ?>">
                        <?php if ($editProduct): ?>
                        <input type="hidden" name="id" value="<?php echo $editProduct['id']; ?>">
                        <input type="hidden" name="current_thumbnail" value="<?php echo htmlspecialchars($editProduct['thumbnail']); ?>">
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Title</label>
                                    <input type="text" name="title" class="form-control" required value="<?php echo $editProduct ? htmlspecialchars($editProduct['title']) : ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Price</label>
                                    <input type="number" name="price" class="form-control" step="0.01" required value="<?php echo $editProduct ? $editProduct['price'] : ''; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo ($editProduct && $editProduct['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="4"><?php echo $editProduct ? htmlspecialchars($editProduct['description']) : ''; ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Thumbnail Image</label>
                            <input type="file" name="thumbnail" class="form-control" accept="image/*">
                            <?php if ($editProduct && $editProduct['thumbnail']): ?>
                            <div class="mt-2">
                                <img src="<?php echo htmlspecialchars($editProduct['thumbnail']); ?>" class="img-thumbnail" style="max-width: 100px;">
                                <small class="text-muted d-block">Current image</small>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><?php echo $editProduct ? 'Update' : 'Create'; ?></button>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php if ($editProduct): ?>
    <script>
        var productModal = new bootstrap.Modal(document.getElementById('productModal'));
        productModal.show();
    </script>
    <?php endif; ?>
</body>
</html>
