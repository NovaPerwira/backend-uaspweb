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
                $stmt = $db->prepare("INSERT INTO slugs (slug, type, reference_id) VALUES (?, ?, ?)");
                $stmt->execute([$_POST['slug'], $_POST['type'], $_POST['reference_id']]);
                $success = "Slug created successfully!";
                break;
                
            case 'update':
                $stmt = $db->prepare("UPDATE slugs SET slug = ?, type = ?, reference_id = ? WHERE id = ?");
                $stmt->execute([$_POST['slug'], $_POST['type'], $_POST['reference_id'], $_POST['id']]);
                $success = "Slug updated successfully!";
                break;
                
            case 'delete':
                $stmt = $db->prepare("DELETE FROM slugs WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                $success = "Slug deleted successfully!";
                break;
        }
    }
}

// Get categories and products for dropdown
$stmt = $db->query("SELECT id, name FROM categories ORDER BY name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->query("SELECT id, title FROM products ORDER BY title");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle search and pagination
$search = $_GET['search'] ?? '';
$typeFilter = $_GET['type'] ?? '';
$page = $_GET['page'] ?? 1;
$perPage = 10;

$whereClause = '';
$params = [];

$conditions = [];
if ($search) {
    $conditions[] = "slug LIKE ?";
    $params[] = "%$search%";
}

if ($typeFilter) {
    $conditions[] = "type = ?";
    $params[] = $typeFilter;
}

if ($conditions) {
    $whereClause = "WHERE " . implode(" AND ", $conditions);
}

// Get total count
$countSql = "SELECT COUNT(*) as total FROM slugs $whereClause";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$pagination = paginate($total, $page, $perPage);

// Get slugs with reference names
$sql = "SELECT s.*, 
        CASE 
            WHEN s.type = 'category' THEN c.name
            WHEN s.type = 'product' THEN p.title
        END as reference_name
        FROM slugs s 
        LEFT JOIN categories c ON s.type = 'category' AND s.reference_id = c.id
        LEFT JOIN products p ON s.type = 'product' AND s.reference_id = p.id
        $whereClause 
        ORDER BY s.id DESC 
        LIMIT {$pagination['perPage']} OFFSET {$pagination['offset']}";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$slugs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get slug for editing
$editSlug = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM slugs WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editSlug = $stmt->fetch(PDO::FETCH_ASSOC);
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
                    <h1 class="h2">Slugs Management</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#slugModal">
                        <i class="fas fa-plus"></i> Add Slug
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
                            <input type="text" name="search" class="form-control" placeholder="Search slugs..." value="<?php echo htmlspecialchars($search); ?>">
                            <input type="hidden" name="type" value="<?php echo htmlspecialchars($typeFilter); ?>">
                            <button type="submit" class="btn btn-outline-secondary ms-2">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
                    </div>
                    <div class="col-md-3">
                        <form method="GET">
                            <select name="type" class="form-select" onchange="this.form.submit()">
                                <option value="">All Types</option>
                                <option value="product" <?php echo $typeFilter == 'product' ? 'selected' : ''; ?>>Product</option>
                                <option value="category" <?php echo $typeFilter == 'category' ? 'selected' : ''; ?>>Category</option>
                            </select>
                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                        </form>
                    </div>
                </div>

                <!-- Slugs Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="myTable" class="display">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Slug</th>
                                        <th>Type</th>
                                        <th>Reference</th>
                                        <th>Reference ID</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($slugs as $slug): ?>
                                    <tr>
                                        <td><?php echo $slug['id']; ?></td>
                                        <td><code><?php echo htmlspecialchars($slug['slug']); ?></code></td>
                                        <td>
                                            <span class="badge bg-<?php echo $slug['type'] == 'product' ? 'primary' : 'success'; ?>">
                                                <?php echo ucfirst($slug['type']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($slug['reference_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo $slug['reference_id']; ?></td>
                                        <td>
                                            <a href="?edit=<?php echo $slug['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $slug['id']; ?>">
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
                                    <a class="page-link" href="?page=<?php echo $pagination['currentPage'] - 1; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($typeFilter); ?>">Previous</a>
                                </li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $pagination['totalPages']; $i++): ?>
                                <li class="page-item <?php echo $i == $pagination['currentPage'] ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($typeFilter); ?>"><?php echo $i; ?></a>
                                </li>
                                <?php endfor; ?>
                                
                                <?php if ($pagination['hasNext']): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $pagination['currentPage'] + 1; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($typeFilter); ?>">Next</a>
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

    <!-- Slug Modal -->
    <div class="modal fade" id="slugModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo $editSlug ? 'Edit Slug' : 'Add Slug'; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="<?php echo $editSlug ? 'update' : 'create'; ?>">
                        <?php if ($editSlug): ?>
                        <input type="hidden" name="id" value="<?php echo $editSlug['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="form-label">Slug</label>
                            <input type="text" name="slug" class="form-control" required value="<?php echo $editSlug ? htmlspecialchars($editSlug['slug']) : ''; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Type</label>
                            <select name="type" class="form-select" required id="slugType" onchange="updateReferenceOptions()">
                                <option value="">Select Type</option>
                                <option value="product" <?php echo ($editSlug && $editSlug['type'] == 'product') ? 'selected' : ''; ?>>Product</option>
                                <option value="category" <?php echo ($editSlug && $editSlug['type'] == 'category') ? 'selected' : ''; ?>>Category</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Reference</label>
                            <select name="reference_id" class="form-select" required id="referenceSelect">
                                <option value="">Select Reference</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><?php echo $editSlug ? 'Update' : 'Create'; ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const categories = <?php echo json_encode($categories); ?>;
        const products = <?php echo json_encode($products); ?>;
        const editSlug = <?php echo $editSlug ? json_encode($editSlug) : 'null'; ?>;

        function updateReferenceOptions() {
            const typeSelect = document.getElementById('slugType');
            const referenceSelect = document.getElementById('referenceSelect');
            const selectedType = typeSelect.value;
            
            referenceSelect.innerHTML = '<option value="">Select Reference</option>';
            
            if (selectedType === 'category') {
                categories.forEach(category => {
                    const option = document.createElement('option');
                    option.value = category.id;
                    option.textContent = category.name;
                    referenceSelect.appendChild(option);
                });
            } else if (selectedType === 'product') {
                products.forEach(product => {
                    const option = document.createElement('option');
                    option.value = product.id;
                    option.textContent = product.title;
                    referenceSelect.appendChild(option);
                });
            }
            
            // Set selected value if editing
            if (editSlug && editSlug.type === selectedType) {
                referenceSelect.value = editSlug.reference_id;
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            if (editSlug) {
                updateReferenceOptions();
            }
        });
    </script>
           . <script
  src="https://code.jquery.com/jquery-3.7.1.js"
  integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4="
  crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/2.3.2/js/dataTables.js"></script>
<script >

let table = new DataTable('#myTable');
</script>

    <?php if ($editSlug): ?>
    <script>
        var slugModal = new bootstrap.Modal(document.getElementById('slugModal'));
        slugModal.show();
    </script>
    <?php endif; ?>
</body>
</html>
