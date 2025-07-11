<link rel="stylesheet" href="../css/address.css">
<!-- Thêm vào <head> -->
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
<script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
<?php
// views/address.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';
$pdo = getDb();

// Bảo vệ route
if (empty($_SESSION['user_id'])) {
    header('Location: /healthy/views/layout.php?page=login');
    exit;
}
$userId = $_SESSION['user_id'];

// Get user info
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$userId]);
$userInfo = $stmt->fetch(PDO::FETCH_ASSOC);

// Lấy profile
$stmt = $pdo->prepare(
    "SELECT p.avatar, p.fullname
     FROM profiles p
     WHERE p.user_id = :uid"
);
$stmt->execute([':uid' => $userId]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['avatar' => null, 'fullname' => null];

// Merge user info
$user = array_merge($userInfo ?: [], $profile);

// User display name
$displayName = !empty($user['fullname']) ? $user['fullname'] : (!empty($user['username']) ? $user['username'] : 'User');

// Get avatar URL
$avatarUrl = getAvatarUrl($user['avatar']);

// Xử lý POST
$errors = [];
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $action = $_POST['action'] ?? '';
      if ($action === 'set_default') {
        $addrId = (int)$_POST['address_id'];
        // Reset lại tất cả về 0 rồi set riêng địa chỉ này về 1
        $pdo->beginTransaction();
        $pdo->exec("UPDATE user_addresses SET is_default = 0 WHERE user_id = $userId");
        $stmt = $pdo->prepare("UPDATE user_addresses SET is_default = 1 WHERE id = :aid AND user_id = :uid");
        $stmt->execute([':aid' => $addrId, ':uid' => $userId]);
        $pdo->commit();
        header('Location: layout.php?page=address');
        exit;
    }
        if ($action === 'unset_default') {
        $addrId = (int)$_POST['address_id'];
        // Chỉ cần set is_default = 0 cho địa chỉ này
        $stmt = $pdo->prepare("
          UPDATE user_addresses
             SET is_default = 0
           WHERE id = :aid AND user_id = :uid
        ");
        $stmt->execute([':aid'=>$addrId,':uid'=>$userId]);
        header('Location: layout.php?page=address');
        exit;
    }
        if ($action === 'delete') {
        $addrId = (int)$_POST['address_id'];
        $stmt = $pdo->prepare(
            "DELETE FROM user_addresses
             WHERE id = :aid AND user_id = :uid"
        );
        $stmt->execute([':aid' => $addrId, ':uid' => $userId]);
        header('Location: layout.php?page=address');
        exit;
    }
  // Gom address lại thành chuỗi
  $district  = $_POST['district'] ?? '';
  $ward      = $_POST['ward']     ?? '';
  $street    = trim($_POST['street']    ?? '');
  $house_no  = trim($_POST['house_no']  ?? '');
  $fullname  = trim($_POST['fullname']  ?? '');
  $phone     = trim($_POST['phone']     ?? '');
  $address   = trim($_POST['address']   ?? '');

  if (!$fullname || !$phone || !$address) {
    $errors[] = 'Vui lòng điền đầy đủ thông tin.';
  } else {
    if ($action==='add') {
      $ins = $pdo->prepare(
        "INSERT INTO user_addresses (user_id, fullname, phone, address)
         VALUES (:uid,:fn,:ph,:ad)"
      );
      $ins->execute([
        ':uid'=>$userId,':fn'=>$fullname,
        ':ph'=>$phone, ':ad'=>$address
      ]);
    }
    if ($action==='edit') {
      $addrId = (int)$_POST['address_id'];
      $upd = $pdo->prepare(
        "UPDATE user_addresses
           SET fullname=:fn, phone=:ph, address=:ad
         WHERE id=:aid AND user_id=:uid"
      );
      $upd->execute([
        ':fn'=>$fullname, ':ph'=>$phone,
        ':ad'=>$address, ':aid'=>$addrId,
        ':uid'=>$userId
      ]);
    }
    header('Location: layout.php?page=address');
    exit;
  }
}

$stmt = $pdo->prepare("
  SELECT id, fullname, phone, address, is_default
    FROM user_addresses
   WHERE user_id=:uid
   ORDER BY is_default DESC, id ASC
");
$stmt->execute([':uid'=>$userId]);
$addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="container">
  <nav class="menu">
    <div class="menu-profile">
      <img
        src="<?= htmlspecialchars($avatarUrl) ?>"
        class="avatar-preview"
        alt="Avatar">
      <p><?= htmlspecialchars($displayName) ?></p>
    </div>
    <a href="layout.php?page=info" class="active">Thông tin tài khoản</a>
    <a href="layout.php?page=points">Điểm & voucher</a>
    <a href="layout.php?page=address">Địa chỉ giao hàng</a>
    <a href="layout.php?page=orders">Đơn hàng hiện tại</a>
  </nav>
  <section class="main">
    <h2>Địa chỉ giao hàng</h2>
    <p>Quản lý địa chỉ giao hàng của bạn</p>
    <hr/>
    <?php if (!empty($errors)): ?>
      <ul class="errors">
      <?php foreach($errors as $e): ?><li><?=htmlspecialchars($e)?></li><?php endforeach; ?>
      </ul>
    <?php endif; ?>
<div class="address-list">
  <?php foreach($addresses as $addr):
    // Tách chuỗi address thành 4 phần:
    $parts    = array_map('trim', explode(',', $addr['address']));
    $houseNo  = $parts[0] ?? '';
    $street   = $parts[1] ?? '';
    $ward     = $parts[2] ?? '';
    $district = $parts[3] ?? '';
  ?>
    <div class="address-item <?= $addr['is_default'] ? 'default' : '' ?>">
      <p><strong><?=htmlspecialchars($addr['fullname'])?></strong> – <?=htmlspecialchars($addr['phone'])?></p>
      <p><?=nl2br(htmlspecialchars($addr['address']))?></p>
      <div class="actions">
        <!-- Đặt hoặc bỏ mặc định -->
        <?php if (!$addr['is_default']): ?>
          <form method="post" class="d-inline">
            <input type="hidden" name="action" value="set_default">
            <input type="hidden" name="address_id" value="<?= $addr['id'] ?>">
            <button type="submit" class="btn-default">Đặt làm mặc định</button>
          </form>
        <?php else: ?>
          <form method="post" class="d-inline">
            <input type="hidden" name="action" value="unset_default">
            <input type="hidden" name="address_id" value="<?= $addr['id'] ?>">
            <button type="submit" class="btn-unset">Bỏ mặc định</button>
          </form>
        <?php endif; ?>

        <!-- Nút Sửa -->
        <button
          class="btn-edit"
          data-id="<?= $addr['id'] ?>"
          data-fullname="<?= htmlspecialchars($addr['fullname'], ENT_QUOTES) ?>"
          data-phone="<?= htmlspecialchars($addr['phone'], ENT_QUOTES) ?>"
          data-address="<?= htmlspecialchars($addr['address'], ENT_QUOTES) ?>"
        >Sửa</button>

        <!-- Nút Xóa -->
        <form method="post" class="d-inline">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="address_id" value="<?=$addr['id']?>">
          <button type="submit" class="btn-delete" onclick="return confirm('Xác nhận xóa?')">Xóa</button>
        </form>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<button id="btnAdd" class="btn-add">Thêm địa chỉ mới</button>

<!-- Modal add/edit -->
<div id="addressModal" class="modal">
  <div class="modal-content">
    <span class="close">&times;</span>
    <h3 id="modalTitle">Thêm địa chỉ</h3>
    <form method="post" id="addressForm">
      <input type="hidden" name="action" id="formAction" value="add">
      <input type="hidden" name="address_id" id="addressId" value="">
      <!-- Họ & tên -->
      <div class="form-group">
        <label>Họ & tên</label>
        <input type="text" name="fullname" id="fullname" required>
      </div>
      <!-- SDT -->
      <div class="form-group">
        <label>Số điện thoại</label>
        <input type="text" name="phone" id="phone" required>
      </div>
      <!-- Tìm địa chỉ -->
      <div class="form-group">
       
      </div>
      <input type="text" name="address" id="address" placeholder="Địa chỉ sẽ hiển thị ở đây..." required readonly>
      <div id="map" style="height: 300px; margin-bottom: 10px;"></div>
      <button type="submit" class="btn-save">Lưu</button>
    </form>
  </div>
</div>

<script>

const modal    = document.getElementById('addressModal');
const btnAdd   = document.getElementById('btnAdd');
const closeBtn = modal.querySelector('.close');
const form     = document.getElementById('addressForm');
const title    = document.getElementById('modalTitle');


// Mở modal thêm
btnAdd.onclick = () => {
  title.textContent   = 'Thêm địa chỉ';
  form.reset();
  document.getElementById('formAction').value = 'add';
  document.getElementById('addressId').value = '';
  modal.style.display = 'block';
};

// Mở modal sửa
document.querySelectorAll('.btn-edit').forEach(btn => {
  btn.onclick = () => {
    title.textContent   = 'Sửa địa chỉ';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('addressId').value = btn.dataset.id;
    document.getElementById('fullname').value  = btn.dataset.fullname;
    document.getElementById('phone').value     = btn.dataset.phone;
    document.getElementById('address').value   = btn.dataset.address; // Sửa dòng này
    modal.style.display = 'block';
  };
});

// Đóng modal
closeBtn.onclick = () => modal.style.display = 'none';
window.onclick = e => {
  if (e.target === modal) modal.style.display = 'none';
};

let map = L.map('map').setView([10.7769, 106.7009], 12); 
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '© OpenStreetMap contributors'
}).addTo(map);

// Thêm nút search
L.Control.geocoder({
  defaultMarkGeocode: false
})
.on('markgeocode', function(e) {
  const bbox = e.geocode.bbox;
  const poly = L.polygon([
    bbox.getSouthEast(),
    bbox.getNorthEast(),
    bbox.getNorthWest(),
    bbox.getSouthWest()
  ]).addTo(map);
  map.fitBounds(poly.getBounds());

  // Đặt marker và điền địa chỉ vào ô input
  if (marker) map.removeLayer(marker);
  marker = L.marker(e.geocode.center).addTo(map);
  document.getElementById('address').value = e.geocode.name || '';
})
.addTo(map);

let marker;
map.on('click', function(e) {
  if (marker) map.removeLayer(marker);
  marker = L.marker(e.latlng).addTo(map);

  // Gọi Nominatim API để lấy địa chỉ
  fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${e.latlng.lat}&lon=${e.latlng.lng}`)
    .then(res => res.json())
    .then(data => {
      document.getElementById('address').value = data.display_name || '';
    });
});

// Đảm bảo modal luôn ẩn khi trang vừa load
window.onload = function() {
  document.getElementById('addressModal').style.display = 'none';
};
</script>
