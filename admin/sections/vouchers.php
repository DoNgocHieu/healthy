<?php
require_once __DIR__ . '/../../config/Database.php';

class VoucherAdmin {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getVouchers($page = 1, $perPage = 10) {
        try {
            $offset = ($page - 1) * $perPage;

            // Đếm tổng số voucher
            $stmt = $this->db->query('SELECT COUNT(*) as total FROM vouchers');
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Lấy danh sách voucher
            $query = "
                SELECT *,
                    0 as usage_count,
                    0 as total_discount
                FROM vouchers
                ORDER BY created_at DESC
                LIMIT ? OFFSET ?
            ";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$perPage, $offset]);
            $vouchers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'vouchers' => $vouchers,
                'total' => $total,
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => ceil($total / $perPage)
            ];
        } catch (Exception $e) {
            error_log("VoucherAdmin error: " . $e->getMessage());
            return [
                'vouchers' => [],
                'total' => 0,
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => 0,
                'error' => 'Lỗi: ' . $e->getMessage()
            ];
        }
    }
}

// Khởi tạo VoucherAdmin
$voucherAdmin = new VoucherAdmin();

// Xử lý phân trang - sử dụng p làm tham số phân trang để tránh xung đột với page routing
$currentPage = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$perPage = 10;

// Lấy danh sách voucher
$result = $voucherAdmin->getVouchers($currentPage, $perPage);
$vouchers = $result['vouchers'];
$totalPages = $result['totalPages'];

// Debug thông tin nếu không có dữ liệu
if (empty($vouchers)) {
    error_log("Debug - Vouchers is empty. Result: " . print_r($result, true));
}
?>

<!-- Tiêu đề -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h4 mb-0">Quản lý voucher</h2>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#voucherModal">
        <i class="bi bi-plus"></i> Thêm voucher
    </button>
</div>

<!-- Hiển thị thông báo lỗi nếu có -->
<?php if (!empty($result['error'])): ?>
    <div class="alert alert-danger">
        <?php echo htmlspecialchars($result['error']); ?>
    </div>
<?php endif; ?>

<!-- Bảng danh sách voucher -->
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>Mã</th>
                <th>Mô tả</th>
                <th>Điểm yêu cầu</th>
                <th>Giảm giá</th>
                <th>Đã dùng</th>
                <th>Tổng giảm</th>
                <th>Trạng thái</th>
                <th>Hết hạn</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($vouchers as $voucher): ?>
            <tr>
                <td><?php echo htmlspecialchars($voucher['code']); ?></td>
                <td><?php echo htmlspecialchars($voucher['description'] ?? ''); ?></td>
                <td><?php echo number_format($voucher['points_required']); ?></td>
                <td>
                    <?php
                    if ($voucher['discount_type'] === 'percent') {
                        echo number_format($voucher['discount_value'], 1) . '%';
                    } else {
                        echo number_format($voucher['discount_value']) . '₫';
                    }
                    ?>
                </td>
                <td><?php echo number_format($voucher['usage_count']); ?></td>
                <td>
                    <?php
                    if ($voucher['discount_type'] === 'percent') {
                        echo number_format($voucher['total_discount'], 1) . '%';
                    } else {
                        echo number_format($voucher['total_discount']) . '₫';
                    }
                    ?>
                </td>
                <td>
                    <span class="badge <?php echo $voucher['active'] ? 'bg-success' : 'bg-danger'; ?>">
                        <?php echo $voucher['active'] ? 'Đang mở' : 'Đã đóng'; ?>
                    </span>
                </td>
                <td>
                    <?php
                    if (empty($voucher['expires_at']) || $voucher['expires_at'] === '0000-00-00') {
                        echo 'Không giới hạn';
                    } else {
                        echo date('d/m/Y', strtotime($voucher['expires_at']));
                    }
                    ?>
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-info edit-voucher" data-voucher-id="<?php echo $voucher['id']; ?>">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-danger delete-voucher" data-voucher-id="<?php echo $voucher['id']; ?>">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($vouchers)): ?>
            <tr>
                <td colspan="8" class="text-center">Chưa có voucher nào</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Phân trang -->
<?php if ($totalPages > 1): ?>
<nav aria-label="Phân trang">
    <ul class="pagination justify-content-center">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?php echo $i === $currentPage ? 'active' : ''; ?>">
                <a class="page-link" href="?page=admin&section=vouchers&p=<?php echo $i; ?>">
                    <?php echo $i; ?>
                </a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>
<?php endif; ?>

<!-- Modal thêm/sửa voucher -->
<div class="modal fade" id="voucherModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Thêm/Sửa voucher</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="voucherForm">
                    <input type="hidden" name="id">

                    <div class="mb-3">
                        <label class="form-label">Mã voucher *</label>
                        <input type="text" class="form-control" name="code" required placeholder="VD: GIAM10K">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Mô tả *</label>
                        <input type="text" class="form-control" name="description" required placeholder="VD: Giảm 10.000đ">
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Điểm yêu cầu</label>
                                <input type="number" class="form-control" name="points_required" min="0" value="0">
                            </div>
                        </div>
                        
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Loại giảm giá</label>
                                <select class="form-select" name="discount_type" required>
                                    <option value="percent">Phần trăm (%)</option>
                                    <option value="amount">Số tiền (VNĐ)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Giá trị giảm</label>
                                <input type="number" class="form-control" name="discount_value" min="0" value="0" required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Ngày hết hạn</label>
                        <input type="date" class="form-control" name="expires_at">
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="active" value="1" checked>
                            <label class="form-check-label">Kích hoạt voucher</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-primary" id="saveVoucher">Lưu</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Vouchers page loaded, Bootstrap version:', typeof bootstrap !== 'undefined' ? 'loaded' : 'not loaded');

    const voucherModal = new bootstrap.Modal(document.getElementById('voucherModal'));
    const voucherForm = document.getElementById('voucherForm');

    // Xử lý khi click nút thêm voucher
    document.querySelector('[data-bs-target="#voucherModal"]').addEventListener('click', function() {
        voucherForm.reset();
        voucherForm.querySelector('[name="id"]').value = '';
        voucherModal.show();
    });

    // Xử lý khi click nút sửa
    document.querySelectorAll('.edit-voucher').forEach(button => {
        button.addEventListener('click', async function() {
            const voucherId = this.dataset.voucherId;

            try {
                const response = await fetch(`/healthy/admin/api/get_voucher.php?id=${voucherId}`);
                const data = await response.json();

                if (data.success) {
                    const voucher = data.voucher;
                    voucherForm.querySelector('[name="id"]').value = voucher.id;
                    voucherForm.querySelector('[name="code"]').value = voucher.code;
                    voucherForm.querySelector('[name="description"]').value = voucher.description;
                    voucherForm.querySelector('[name="points_required"]').value = voucher.points_required || 0;
                    voucherForm.querySelector('[name="discount_type"]').value = voucher.discount_type || 'amount';
                    voucherForm.querySelector('[name="discount_value"]').value = voucher.discount_value || 0;
                    voucherForm.querySelector('[name="expires_at"]').value = voucher.expires_at || '';
                    voucherForm.querySelector('[name="active"]').checked = voucher.active == 1;

                    voucherModal.show();
                } else {
                    alert('Không thể tải thông tin voucher');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi tải thông tin voucher');
            }
        });
    });

    // Xử lý khi click nút xóa
    document.querySelectorAll('.delete-voucher').forEach(button => {
        button.addEventListener('click', async function() {
            if (!confirm('Bạn có chắc chắn muốn xóa voucher này?')) {
                return;
            }

            const voucherId = this.dataset.voucherId;

            try {
                const response = await fetch('/healthy/admin/api/delete_voucher.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: voucherId })
                });
                const data = await response.json();

                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Không thể xóa voucher');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi xóa voucher');
            }
        });
    });

    // Xử lý khi lưu voucher
    document.getElementById('saveVoucher').addEventListener('click', async function() {
        if (!voucherForm.checkValidity()) {
            voucherForm.reportValidity();
            return;
        }

        const formData = new FormData(voucherForm);

        try {
            const response = await fetch('/healthy/admin/api/save_voucher.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();

            if (data.success) {
                voucherModal.hide();
                window.location.reload();
            } else {
                alert(data.message || 'Không thể lưu voucher');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Có lỗi xảy ra khi lưu voucher');
        }
    });
});
</script>
