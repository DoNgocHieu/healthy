<?php
// promotions.php
session_start();
require_once __DIR__ . '/config/config.php';
$pdo = getDb();

// Chỉ cho user đã login
if (empty($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}
$userId = $_SESSION['user_id'];

// Lấy danh sách voucher chưa dùng
$stmt = $pdo->prepare("
  SELECT id, code, description, discount_amount
    FROM vouchers
   WHERE user_id = :uid
     AND is_used = 0
   ORDER BY created_at DESC
");
$stmt->execute([':uid'=>$userId]);
$vouchers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Áp dụng mã khuyến mãi</title>
  <style>
    body { font-family: sans-serif; padding: 1rem; }
    .container { max-width: 500px; margin: auto; }
    h2 { margin-bottom: 1rem; }
    .voucher-list {
      max-height: 200px;
      overflow-y: auto;
      border: 1px solid #ccc;
      border-radius: 4px;
      padding: 0.5rem;
      margin-bottom: 1rem;
    }
    .voucher-item {
      display: flex;
      justify-content: space-between;
      padding: 0.4rem 0;
      border-bottom: 1px solid #eee;
      cursor: pointer;
    }
    .voucher-item:last-child { border-bottom: none; }
    .voucher-item:hover { background: #f9f9f9; }
    .voucher-item.selected { background: #e8f5e9; }
    input[type="text"] {
      width: calc(100% - 1rem);
      padding: 0.5rem;
      margin-bottom: 1rem;
      border: 1px solid #ccc;
      border-radius: 4px;
      font-size: 1rem;
    }
    button {
      padding: 0.6rem 1.2rem;
      background: #2196f3;
      color: #fff;
      border: none;
      border-radius: 4px;
      font-size: 1rem;
      cursor: pointer;
    }
    button:hover { background: #1976d2; }
  </style>
</head>
<body>
  <div class="container">
    <h2>Áp dụng mã khuyến mãi</h2>

    <!-- Danh sách voucher cuộn -->
    <div class="voucher-list" id="voucherList">
      <?php if (count($vouchers) === 0): ?>
        <p>Chưa có voucher nào.</p>
      <?php else: ?>
        <?php foreach($vouchers as $v): ?>
          <div class="voucher-item" data-code="<?=htmlspecialchars($v['code'])?>">
            <div>
              <strong><?=htmlspecialchars($v['code'])?></strong>
              &ndash; <?=htmlspecialchars($v['description'])?>
            </div>
            <div>–<?=number_format($v['discount_amount'],0,',','.')?>₫</div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Hoặc nhập tay -->
    <input type="text" id="manualCode" placeholder="Nhập mã khuyến mãi">

    <!-- Nút Áp dụng -->
    <button id="applyBtn">Áp dụng</button>
  </div>

  <script>
    // Khi click vào voucher-item thì highlight và copy code vào input
    document.querySelectorAll('.voucher-item').forEach(item => {
      item.addEventListener('click', () => {
        // Bỏ chọn các item khác
        document.querySelectorAll('.voucher-item.selected')
                .forEach(x => x.classList.remove('selected'));
        // đánh dấu item hiện tại
        item.classList.add('selected');
        // ghi code vào ô nhập tay
        document.getElementById('manualCode').value = item.dataset.code;
      });
    });

    // Áp dụng mã
    document.getElementById('applyBtn').addEventListener('click', () => {
      const code = document.getElementById('manualCode').value.trim();
      if (!code) return alert('Vui lòng chọn hoặc nhập mã!');
      // gửi AJAX về server
      fetch('apply_promo.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'code=' + encodeURIComponent(code)
      })
      .then(r => r.json())
      .then(json => {
        if (json.status === 'ok') {
          alert('Áp dụng thành công! Giảm '+ new Intl.NumberFormat('vi-VN').format(json.discount)+'₫');
          // có thể redirect, reload giỏ hàng, v.v.
          window.location.href = 'cart.php';
        } else {
          alert(json.error || 'Áp dụng không thành công');
        }
      })
      .catch(() => alert('Lỗi kết nối'));
    });
  </script>
</body>
</html>
