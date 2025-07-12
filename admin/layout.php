<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra quyền admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /healthy/views/layout.php?page=login');
    exit;
}

// Lấy section hiện tại để xác định menu active
$currentSection = $_GET['section'] ?? 'dashboard';
?>
<?php
// Start output buffering to prevent header issues
ob_start();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Healthy Food Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/healthy/css/admin.css">
    
    <!-- Ensure jQuery loads before Bootstrap -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <nav id="sidebar" class="bg-dark text-white">
            <div class="sidebar-header">
                <h3>Healthy Food</h3>
                <p class="text-muted mb-0">Admin Panel</p>
            </div>

            <ul class="list-unstyled components">
                <li class="<?php echo $currentSection === 'dashboard' ? 'active' : ''; ?>">
                    <a href="/healthy/views/layout.php?page=admin&section=dashboard">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                <li class="<?php echo $currentSection === 'orders' ? 'active' : ''; ?>">
                    <a href="/healthy/views/layout.php?page=admin&section=orders">
                        <i class="bi bi-cart3"></i> Đơn hàng
                    </a>
                </li>
                <li class="<?php echo in_array($currentSection, ['items', 'categories']) ? 'active' : ''; ?>">
                    <a href="#itemsSubmenu" data-bs-toggle="collapse" aria-expanded="<?php echo in_array($currentSection, ['items', 'categories']) ? 'true' : 'false'; ?>">
                        <i class="bi bi-box-seam"></i> Món ăn
                    </a>
                    <ul class="collapse list-unstyled <?php echo in_array($currentSection, ['items', 'categories']) ? 'show' : ''; ?>" id="itemsSubmenu">
                        <li class="<?php echo $currentSection === 'items' ? 'active' : ''; ?>">
                            <a href="/healthy/views/layout.php?page=admin&section=items">Danh sách món</a>
                        </li>
                        <li class="<?php echo $currentSection === 'categories' ? 'active' : ''; ?>">
                            <a href="/healthy/views/layout.php?page=admin&section=categories">Danh mục</a>
                        </li>
                    </ul>
                </li>
                <li class="<?php echo $currentSection === 'users' ? 'active' : ''; ?>">
                    <a href="/healthy/views/layout.php?page=admin&section=users">
                        <i class="bi bi-people"></i> Người dùng
                    </a>
                </li>
                <li class="<?php echo $currentSection === 'vouchers' ? 'active' : ''; ?>">
                    <a href="/healthy/views/layout.php?page=admin&section=vouchers">
                        <i class="bi bi-ticket-perforated"></i> Mã giảm giá
                    </a>
                </li>
                <li class="<?php echo $currentSection === 'reviews' ? 'active' : ''; ?>">
                    <a href="/healthy/views/layout.php?page=admin&section=reviews">
                        <i class="bi bi-star"></i> Đánh giá
                    </a>
                </li>
                <li class="<?php echo $currentSection === 'posts' ? 'active' : ''; ?>">
                    <a href="/healthy/views/layout.php?page=admin&section=posts">
                        <i class="bi bi-file-earmark-text"></i> Bài viết
                    </a>
                </li>
                <li class="<?php echo $currentSection === 'reports' ? 'active' : ''; ?>">
                <li class="<?php echo $currentSection === 'reports' ? 'active' : ''; ?>">
                    <a href="/healthy/views/layout.php?page=admin&section=reports">
                        <i class="bi bi-graph-up"></i> Thống kê
                    </a>
                </li>
                <li class="<?php echo $currentSection === 'settings' ? 'active' : ''; ?>">
                    <a href="/healthy/views/layout.php?page=admin&section=settings">
                        <i class="bi bi-gear"></i> Cài đặt
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Page Content -->
        <div id="content">
            <!-- Top Navigation -->
            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn btn-outline-dark">
                        <i class="bi bi-list"></i>
                    </button>

                    <div class="ms-auto d-flex align-items-center">
                        <div class="dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button"
                               data-bs-toggle="dropdown" aria-expanded="false">
                                <?php if (isset($_SESSION['avatar_url']) && $_SESSION['avatar_url']): ?>
                                    <img src="<?php echo $_SESSION['avatar_url']; ?>"
                                         class="rounded-circle me-2" width="32" height="32" alt="Avatar"
                                         style="object-fit: cover;">
                                <?php else: ?>
                                    <div class="rounded-circle me-2 d-inline-flex align-items-center justify-content-center"
                                         style="width: 32px; height: 32px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; font-weight: bold; font-size: 14px;">
                                        <?php echo strtoupper(substr($_SESSION['fullname'] ?? 'A', 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($_SESSION['fullname'] ?? 'Admin'); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                              
                                <li>
                                    <a class="dropdown-item" href="/healthy/views/layout.php?page=change_password">
                                        <i class="bi bi-key"></i> Đổi mật khẩu
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="/healthy/views/logout.php">
                                        <i class="bi bi-box-arrow-right"></i> Đăng xuất
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Main Content -->
            <div class="container-fluid py-4">
                <!-- Content will be loaded here -->
                <?php if (isset($content)) echo $content; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#sidebarCollapse').on('click', function() {
                $('#sidebar').toggleClass('active');
            });
            
            // Ensure Bootstrap is properly initialized
            console.log('Bootstrap version:', bootstrap.Modal.VERSION || 'loaded');
        });
    </script>
</body>
</html>
