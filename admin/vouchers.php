<?php
require_once '../config/Auth.php';
require_once '../config/Database.php';

// Check if user is admin
$auth = new Auth();
if (!$auth->isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Generate unique voucher code
function generateVoucherCode() {
    $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = '';
    for ($i = 0; $i < 8; $i++) {
        $code .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $code;
}

// Handle AJAX requests
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
    // Create/Update voucher
    if ($_POST['action'] === 'save_voucher') {
        try {
            $db->beginTransaction();

            $data = [
                'code' => $_POST['code'] ?: generateVoucherCode(),
                'description' => $_POST['description'],
                'discount_type' => $_POST['discount_type'],
                'discount_value' => $_POST['discount_value'],
                'min_order' => $_POST['min_order'],
                'max_discount' => $_POST['max_discount'],
                'start_date' => $_POST['start_date'],
                'end_date' => $_POST['end_date'],
                'usage_limit' => $_POST['usage_limit']
            ];

            if (empty($_POST['id'])) {
                // Create new voucher
                $sql = 'INSERT INTO vouchers (code, description, discount_type, discount_value, min_order, max_discount,
                                            start_date, end_date, usage_limit, created_at)
                        VALUES (:code, :description, :discount_type, :discount_value, :min_order, :max_discount,
                                :start_date, :end_date, :usage_limit, NOW())';
            } else {
                // Update existing voucher
                $sql = 'UPDATE vouchers
                        SET code = :code, description = :description, discount_type = :discount_type,
                            discount_value = :discount_value, min_order = :min_order, max_discount = :max_discount,
                            start_date = :start_date, end_date = :end_date, usage_limit = :usage_limit,
                            updated_at = NOW()
                        WHERE id = :id';
                $data['id'] = $_POST['id'];
            }

            $stmt = $db->prepare($sql);
            $stmt->execute($data);

            $db->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Lưu thành công'
            ]);
            exit;

        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    // Delete voucher
    if ($_POST['action'] === 'delete_voucher') {
        try {
            $stmt = $db->prepare('DELETE FROM vouchers WHERE id = ?');
            $stmt->execute([$_POST['id']]);

            echo json_encode([
                'success' => true,
                'message' => 'Xóa thành công'
            ]);
            exit;

        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    // Get voucher details
    if ($_POST['action'] === 'get_voucher') {
        $stmt = $db->prepare('SELECT * FROM vouchers WHERE id = ?');
        $stmt->execute([$_POST['id']]);
        $voucher = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode($voucher);
        exit;
    }
}

// Get filters from query params
$filters = [
    'status' => $_GET['status'] ?? null,
    'type' => $_GET['type'] ?? null,
    'search' => $_GET['search'] ?? null
];

// Get vouchers with filters and pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];

if ($filters['status'] === 'active') {
    $where[] = 'NOW() BETWEEN start_date AND end_date AND (usage_limit > times_used OR usage_limit = 0)';
} elseif ($filters['status'] === 'expired') {
    $where[] = 'NOW() > end_date OR (usage_limit > 0 AND times_used >= usage_limit)';
} elseif ($filters['status'] === 'upcoming') {
    $where[] = 'NOW() < start_date';
}

if (!empty($filters['type'])) {
    $where[] = 'discount_type = ?';
    $params[] = $filters['type'];
}

if (!empty($filters['search'])) {
    $where[] = '(code LIKE ? OR description LIKE ?)';
    $searchTerm = "%{$filters['search']}%";
    $params = array_merge($params, [$searchTerm, $searchTerm]);
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total records for pagination
$stmt = $db->prepare('
    SELECT COUNT(*) as total
    FROM vouchers
    ' . $whereClause
);
$stmt->execute($params);
$total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get vouchers with usage stats
$stmt = $db->prepare('
    SELECT v.*,
           COUNT(DISTINCT o.id) as orders_used,
           SUM(CASE WHEN o.order_status != "cancelled" THEN o.total_amount ELSE 0 END) as total_discount
    FROM vouchers v
    LEFT JOIN orders o ON v.id = o.voucher_id
    ' . $whereClause . '
    GROUP BY v.id
    ORDER BY v.created_at DESC
    LIMIT ? OFFSET ?
');

$params = array_merge($params, [$perPage, $offset]);
$stmt->execute($params);
$vouchers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$title = "Quản lý mã giảm giá";
require_once 'layout.php';
?>

<div class="container-fluid py-4">
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header pb-0">
            <div class="row">
                <div class="col">
                    <h6>Bộ lọc</h6>
                </div>
                <div class="col text-end">
                    <button type="button" class="btn btn-primary btn-sm mb-0" data-bs-toggle="modal" data-bs-target="#voucherModal">
                        Thêm mã giảm giá
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <form id="filter-form" method="GET">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-control-label">Trạng thái</label>
                            <select name="status" class="form-control">
                                <option value="">Tất cả</option>
                                <option value="active" <?= ($filters['status'] === 'active') ? 'selected' : '' ?>>Đang hoạt động</option>
                                <option value="expired" <?= ($filters['status'] === 'expired') ? 'selected' : '' ?>>Hết hạn</option>
                                <option value="upcoming" <?= ($filters['status'] === 'upcoming') ? 'selected' : '' ?>>Sắp diễn ra</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-control-label">Loại giảm giá</label>
                            <select name="type" class="form-control">
                                <option value="">Tất cả</option>
                                <option value="percent" <?= ($filters['type'] === 'percent') ? 'selected' : '' ?>>Phần trăm</option>
                                <option value="fixed" <?= ($filters['type'] === 'fixed') ? 'selected' : '' ?>>Số tiền</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-control-label">Tìm kiếm</label>
                            <input type="text" name="search" class="form-control" placeholder="Mã giảm giá hoặc mô tả" value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary mb-0">Lọc</button>
                        <a href="vouchers.php" class="btn btn-outline-secondary mb-0 ms-2">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Vouchers Table -->
    <div class="card">
        <div class="table-responsive">
            <table class="table align-items-center mb-0">
                <thead>
                    <tr>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Mã giảm giá</th>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Giảm giá</th>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Thời gian</th>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Sử dụng</th>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Tổng giảm</th>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Trạng thái</th>
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vouchers as $voucher): ?>
                    <tr>
                        <td>
                            <div class="d-flex px-2 py-1">
                                <div class="d-flex flex-column justify-content-center">
                                    <h6 class="mb-0 text-xs"><?= htmlspecialchars($voucher['code']) ?></h6>
                                    <p class="text-xs text-secondary mb-0"><?= htmlspecialchars($voucher['description']) ?></p>
                                </div>
                            </div>
                        </td>
                        <td>
                            <p class="text-xs font-weight-bold mb-0">
                                <?php if ($voucher['discount_type'] === 'percent'): ?>
                                    <?= $voucher['discount_value'] ?>%
                                    <?php if ($voucher['max_discount'] > 0): ?>
                                        <span class="text-secondary">(Tối đa <?= number_format($voucher['max_discount']) ?>đ)</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?= number_format($voucher['discount_value']) ?>đ
                                <?php endif; ?>
                            </p>
                            <?php if ($voucher['min_order'] > 0): ?>
                            <p class="text-xs text-secondary mb-0">
                                Đơn tối thiểu: <?= number_format($voucher['min_order']) ?>đ
                            </p>
                            <?php endif; ?>
                        </td>
                        <td>
                            <p class="text-xs font-weight-bold mb-0">
                                <?= date('d/m/Y', strtotime($voucher['start_date'])) ?> -
                                <?= date('d/m/Y', strtotime($voucher['end_date'])) ?>
                            </p>
                        </td>
                        <td>
                            <p class="text-xs font-weight-bold mb-0">
                                <?= number_format($voucher['times_used']) ?>/<?= $voucher['usage_limit'] ?: '∞' ?>
                            </p>
                            <p class="text-xs text-secondary mb-0">
                                <?= number_format($voucher['orders_used']) ?> đơn hàng
                            </p>
                        </td>
                        <td>
                            <span class="text-xs font-weight-bold">
                                <?= number_format($voucher['total_discount']) ?>đ
                            </span>
                        </td>
                        <td>
                            <?php
                            $now = time();
                            $start = strtotime($voucher['start_date']);
                            $end = strtotime($voucher['end_date']);
                            $isActive = $now >= $start && $now <= $end;
                            $isExpired = $now > $end || ($voucher['usage_limit'] > 0 && $voucher['times_used'] >= $voucher['usage_limit']);
                            $isUpcoming = $now < $start;
                            ?>
                            <span class="badge badge-sm bg-gradient-<?= $isActive ? 'success' : ($isExpired ? 'danger' : 'warning') ?>">
                                <?= $isActive ? 'Đang hoạt động' : ($isExpired ? 'Hết hạn' : 'Sắp diễn ra') ?>
                            </span>
                        </td>
                        <td>
                            <button type="button" class="btn btn-link text-dark mb-0 edit-voucher" data-voucher-id="<?= $voucher['id'] ?>">
                                <i class="fas fa-pencil-alt text-dark me-2"></i>
                                Sửa
                            </button>
                            <button type="button" class="btn btn-link text-danger mb-0 delete-voucher" data-voucher-id="<?= $voucher['id'] ?>">
                                <i class="fas fa-trash text-danger me-2"></i>
                                Xóa
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total > $perPage): ?>
        <div class="card-footer d-flex justify-content-center">
            <ul class="pagination pagination-primary m-0">
                <?php
                $totalPages = ceil($total / $perPage);
                for ($i = 1; $i <= $totalPages; $i++):
                ?>
                    <li class="page-item <?= ($page === $i) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?><?= !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['page' => ''])) : '' ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Voucher Modal -->
<div class="modal fade" id="voucherModal" tabindex="-1" role="dialog" aria-labelledby="voucherModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="voucherModalLabel">Thêm/Sửa mã giảm giá</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="voucherForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="save_voucher">
                    <input type="hidden" name="id" id="voucher_id">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-control-label">Mã giảm giá</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="code" placeholder="Để trống để tự động tạo">
                                    <button type="button" class="btn btn-outline-primary mb-0" id="generate-code">
                                        Tạo mã
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-control-label">Mô tả</label>
                                <input type="text" class="form-control" name="description" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-control-label">Loại giảm giá</label>
                                <select name="discount_type" class="form-control" required>
                                    <option value="percent">Phần trăm</option>
                                    <option value="fixed">Số tiền</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-control-label">Giá trị giảm</label>
                                <input type="number" class="form-control" name="discount_value" required min="0">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-control-label">Đơn tối thiểu</label>
                                <input type="number" class="form-control" name="min_order" value="0" min="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-control-label">Giảm tối đa</label>
                                <input type="number" class="form-control" name="max_discount" value="0" min="0">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-control-label">Ngày bắt đầu</label>
                                <input type="datetime-local" class="form-control" name="start_date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-control-label">Ngày kết thúc</label>
                                <input type="datetime-local" class="form-control" name="end_date" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-control-label">Giới hạn sử dụng (0 = không giới hạn)</label>
                        <input type="number" class="form-control" name="usage_limit" value="0" min="0">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary">Lưu</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Show voucher modal for edit
    $('.edit-voucher').click(function() {
        const voucherId = $(this).data('voucher-id');

        $.ajax({
            url: 'vouchers.php',
            type: 'POST',
            data: {
                action: 'get_voucher',
                id: voucherId
            },
            success: function(voucher) {
                $('#voucher_id').val(voucher.id);
                $('input[name="code"]').val(voucher.code);
                $('input[name="description"]').val(voucher.description);
                $('select[name="discount_type"]').val(voucher.discount_type);
                $('input[name="discount_value"]').val(voucher.discount_value);
                $('input[name="min_order"]').val(voucher.min_order);
                $('input[name="max_discount"]').val(voucher.max_discount);
                $('input[name="start_date"]').val(voucher.start_date.slice(0, 16));
                $('input[name="end_date"]').val(voucher.end_date.slice(0, 16));
                $('input[name="usage_limit"]').val(voucher.usage_limit);

                $('#voucherModal').modal('show');
            }
        });
    });

    // Generate voucher code
    $('#generate-code').click(function() {
        $('input[name="code"]').val(generateVoucherCode());
    });

    // Reset form when modal is closed
    $('#voucherModal').on('hidden.bs.modal', function() {
        $('#voucherForm')[0].reset();
        $('#voucher_id').val('');
    });

    // Handle form submit
    $('#voucherForm').submit(function(e) {
        e.preventDefault();

        $.ajax({
            url: 'vouchers.php',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#voucherModal').modal('hide');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    toastr.error(response.message);
                }
            }
        });
    });

    // Delete voucher
    $('.delete-voucher').click(function() {
        const voucherId = $(this).data('voucher-id');

        if (!confirm('Bạn có chắc muốn xóa mã giảm giá này?')) {
            return;
        }

        $.ajax({
            url: 'vouchers.php',
            type: 'POST',
            data: {
                action: 'delete_voucher',
                id: voucherId
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    toastr.error(response.message);
                }
            }
        });
    });

    // Validate date inputs
    $('input[name="start_date"], input[name="end_date"]').change(function() {
        const startDate = $('input[name="start_date"]').val();
        const endDate = $('input[name="end_date"]').val();

        if (startDate && endDate && startDate > endDate) {
            toastr.error('Ngày kết thúc phải sau ngày bắt đầu');
            $(this).val('');
        }
    });

    // Generate random voucher code
    function generateVoucherCode() {
        const chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        let code = '';
        for (let i = 0; i < 8; i++) {
            code += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return code;
    }
});</script>
