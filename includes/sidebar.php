<div id="sidebar" class="fixed inset-y-0 left-0 z-50 w-60 bg-white shadow-sm transform -translate-x-full lg:translate-x-0 sidebar-transition">
    <div class="flex items-center justify-between h-16 px-6 border-b border-gray-200">
        <div class="flex items-center space-x-3">
            <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                <i class="fas fa-chart-line text-white text-sm"></i>
            </div>
            <span class="text-xl font-bold text-gray-900">Admin Panel</span>
        </div>
        <button onclick="toggleSidebar()" class="lg:hidden p-1 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100">
            <i class="fas fa-times text-lg"></i>
        </button>
    </div>

    <nav class="mt-6 px-3">
        <div class="space-y-1">
            <?php
            $current_page = basename($_SERVER['PHP_SELF']);
            $nav_items = [
                ['name' => 'Dashboard', 'href' => 'index.php', 'icon' => 'fa-chart-line'],
                ['name' => 'Users', 'href' => 'users.php', 'icon' => 'fa-users'],
                ['name' => 'Categories', 'href' => 'categories.php', 'icon' => 'fa-folder-open'],
                ['name' => 'Products', 'href' => 'Products.php', 'icon' => 'fa-image'],
                ['name' => 'Orders', 'href' => 'orders.php', 'icon' => 'fa-shopping-cart'],
                // ['name' => 'Settings', 'href' => 'settings.php', 'icon' => 'fa-cog'],
            ];
            
            foreach ($nav_items as $item):
                $is_active = $current_page === $item['href'];
            ?>
                <a href="<?php echo $item['href']; ?>" 
                   class="group flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 <?php echo $is_active ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-700' : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900'; ?>">
                    <i class="fas <?php echo $item['icon']; ?> mr-3 text-sm <?php echo $is_active ? 'text-blue-700' : 'text-gray-400 group-hover:text-gray-500'; ?>"></i>
                    <?php echo $item['name']; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </nav>
</div>

<div id="sidebar-overlay" class="fixed inset-0 z-40 bg-gray-600 bg-opacity-75 hidden lg:hidden" onclick="toggleSidebar()"></div>

<script src="https://cdn.tailwindcss.com"></script>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    const isOpen = !sidebar.classList.contains('-translate-x-full');

    if (isOpen) {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
    } else {
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden');
    }
}
</script>
