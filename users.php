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
                $stmt = $db->prepare("INSERT INTO users (name, email, password, phone, address) VALUES (?, ?, ?, ?, ?)");
                $hashedPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt->execute([$_POST['name'], $_POST['email'], $hashedPassword, $_POST['phone'], $_POST['address']]);
                $success = "User created successfully!";
                break;
                
            case 'update':
                $sql = "UPDATE users SET name = ?, email = ?, phone = ?, address = ?";
                $params = [$_POST['name'], $_POST['email'], $_POST['phone'], $_POST['address']];
                
                if (!empty($_POST['password'])) {
                    $sql .= ", password = ?";
                    $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
                }
                
                $sql .= " WHERE id = ?";
                $params[] = $_POST['id'];
                
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $success = "User updated successfully!";
                break;
                
            case 'delete':
                $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                $success = "User deleted successfully!";
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
    $whereClause = "WHERE name LIKE ? OR email LIKE ?";
    $params = ["%$search%", "%$search%"];
}

// Get total count
$countSql = "SELECT COUNT(*) as total FROM users $whereClause";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$pagination = paginate($total, $page, $perPage);

// Get users
$sql = "SELECT * FROM users $whereClause ORDER BY created_at DESC LIMIT {$pagination['perPage']} OFFSET {$pagination['offset']}";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user for editing
$editUser = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editUser = $stmt->fetch(PDO::FETCH_ASSOC);
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
                    <h1 class="h2">Users Management</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal">
                        <i class="fas fa-plus"></i> Add User
                    </button>
                </div>

                <?php if (isset($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Search -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <form method="GET" class="d-flex">
                            <input type="text" name="search" class="form-control" placeholder="Search users..." value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit" class="btn btn-outline-secondary ms-2">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="myTable" class="display">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo $user['id']; ?></td>
                                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <a href="?edit=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
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

    <!-- User Modal -->
    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo $editUser ? 'Edit User' : 'Add User'; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="<?php echo $editUser ? 'update' : 'create'; ?>">
                        <?php if ($editUser): ?>
                        <input type="hidden" name="id" value="<?php echo $editUser['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" required value="<?php echo $editUser ? htmlspecialchars($editUser['name']) : ''; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required value="<?php echo $editUser ? htmlspecialchars($editUser['email']) : ''; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Password <?php echo $editUser ? '(leave blank to keep current)' : ''; ?></label>
                            <input type="password" name="password" class="form-control" <?php echo !$editUser ? 'required' : ''; ?>>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" value="<?php echo $editUser ? htmlspecialchars($editUser['phone']) : ''; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="3"><?php echo $editUser ? htmlspecialchars($editUser['address']) : ''; ?></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><?php echo $editUser ? 'Update' : 'Create'; ?></button>
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
    <?php if ($editUser): ?>
    <script>
        var userModal = new bootstrap.Modal(document.getElementById('userModal'));
        userModal.show();
    </script>
    <?php endif; ?>
</body>
</html>
