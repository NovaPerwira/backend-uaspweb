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
                $stmt = $db->prepare("INSERT INTO orders (user_id, total_price, status) VALUES (?, ?, ?)");
                $stmt->execute([$_POST['user_id'], $_POST['total_price'], $_POST['status']]);
                $success = "Order created successfully!";
                break;
                
            case 'update':
                $stmt = $db->prepare("UPDATE orders SET user_id = ?, total_price = ?, status = ? WHERE id = ?");
                $stmt->execute([$_POST['user_id'], $_POST['total_price'], $_POST['status'], $_POST['id']]);
                $success = "Order updated successfully!";
                break;
                
            case 'delete':
                $stmt = $db->prepare("DELETE FROM orders WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                $success = "Order deleted successfully!";
                break;
        }
    }
}

// Get users for dropdown
$stmt = $db->query("SELECT id, name FROM users ORDER BY name");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle search and pagination
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$page = $_GET['page'] ?? 1;
$perPage = 10;

$whereClause = '';
$params = [];

$conditions = [];
if ($search) {
    $conditions[] = "u.name LIKE ?";
    $params[] = "%$search%";
}

if ($statusFilter) {
    $conditions[] = "o.status = ?";
    $params[] = $statusFilter;
}

if ($conditions) {
    $whereClause = "WHERE " . implode(" AND ", $conditions);
}

// Get total count
$countSql = "SELECT COUNT(*) as total FROM orders o JOIN users u ON o.user_id = u.id $whereClause";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$pagination = paginate($total, $page, $perPage);

// Get orders with user names
$sql = "SELECT o.*, u.name as user_name FROM orders o 
        JOIN users u ON o.user_id = u.id 
        $whereClause 
        ORDER BY o.created_at DESC 
        LIMIT {$pagination['perPage']} OFFSET {$pagination['offset']}";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get order for editing
$editOrder = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editOrder = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management</title>
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
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <h4 class="text-white text-center mb-4">Dashboard</h4>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="users.php">
                                <i class="fas fa-users"></i> Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="categories.php">
                                <i class="fas fa-tags"></i> Categories
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="products.php">
                                <i class="fas fa-box"></i> Products
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="orders.php">
                                <i class="fas fa-shopping-cart"></i> Orders
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="slugs.php">
                                <i class="fas fa-link"></i> Slugs
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Orders Management</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#orderModal">
                        <i class="fas fa-plus"></i> Add Order
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
                            <input type="text" name="search" class="form-control" placeholder="Search by customer name..." value="<?php echo htmlspecialchars($search); ?>">
                            <input type="hidden" name="status" value="<?php echo htmlspecialchars($statusFilter); ?>">
                            <button type="submit" class="btn btn-outline-secondary ms-2">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
                    </div>
                    <div class="col-md-3">
                        <form method="GET">
                            <select name="status" class="form-select" onchange="this.form.submit()">
                                <option value="">All Status</option>
                                <option value="pending" <?php echo $statusFilter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="completed" <?php echo $statusFilter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="shipped" <?php echo $statusFilter == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                <option value="cancelled" <?php echo $statusFilter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                        </form>
                    </div>
                </div>

                <!-- Orders Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="myTable" class="display">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Total Price</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><?php echo $order['id']; ?></td>
                                        <td><?php echo htmlspecialchars($order['user_name']); ?></td>
                                        <td>$<?php echo number_format($order['total_price'], 2); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $order['status'] == 'completed' ? 'success' : ($order['status'] == 'pending' ? 'warning' : ($order['status'] == 'shipped' ? 'info' : 'danger')); ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                        <td>
                                            <a href="?edit=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $order['id']; ?>">
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
                                    <a class="page-link" href="?page=<?php echo $pagination['currentPage'] - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>">Previous</a>
                                </li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $pagination['totalPages']; $i++): ?>
                                <li class="page-item <?php echo $i == $pagination['currentPage'] ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>"><?php echo $i; ?></a>
                                </li>
                                <?php endfor; ?>
                                
                                <?php if ($pagination['hasNext']): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $pagination['currentPage'] + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>">Next</a>
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

    <!-- Order Modal -->
    <div class="modal fade" id="orderModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo $editOrder ? 'Edit Order' : 'Add Order'; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="<?php echo $editOrder ? 'update' : 'create'; ?>">
                        <?php if ($editOrder): ?>
                        <input type="hidden" name="id" value="<?php echo $editOrder['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="form-label">Customer</label>
                            <select name="user_id" class="form-select" required>
                                <option value="">Select Customer</option>
                                <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>" <?php echo ($editOrder && $editOrder['user_id'] == $user['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Total Price</label>
                            <input type="number" name="total_price" class="form-control" step="0.01" required value="<?php echo $editOrder ? $editOrder['total_price'] : ''; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="pending" <?php echo ($editOrder && $editOrder['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="completed" <?php echo ($editOrder && $editOrder['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                                <option value="shipped" <?php echo ($editOrder && $editOrder['status'] == 'shipped') ? 'selected' : ''; ?>>Shipped</option>
                                <option value="cancelled" <?php echo ($editOrder && $editOrder['status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><?php echo $editOrder ? 'Update' : 'Create'; ?></button>
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
    <?php if ($editOrder): ?>
    <script>
        var orderModal = new bootstrap.Modal(document.getElementById('orderModal'));
        orderModal.show();
    </script>
    <?php endif; ?>
</body>
</html>
