<?php
require_once '../config/Database.php';
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo '<h2>Bài viết không tồn tại!</h2>';
    exit;
}
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT title, content, created_at FROM posts WHERE id = ? LIMIT 1");
$stmt->execute([$_GET['id']]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$post) {
    echo '<h2>Bài viết không tồn tại!</h2>';
    exit;
}
?>
<link rel="stylesheet" href="../css/ct.css">

<main class="recipe-container" style="max-width:700px;margin:auto;">
    <section>
        <div style="background:#fff;border:1px solid #e0e0e0;border-radius:16px;box-shadow:0 4px 16px rgba(0,0,0,0.07);padding:2.5rem 2rem 2rem 2rem;margin:2.5rem 0;">
    <h1 style="margin-bottom:1.5rem;color:#8b2f45;font-size:2rem;">
      <?php echo htmlspecialchars($post['title']); ?>
    </h1>
    <div style="margin-bottom:2rem;font-size:1.08rem;line-height:1.7;color:#222;">
      <?php echo $post['content']; ?>
    </div>
    <div style="color:#aaa;font-size:0.95rem;">
      Đăng ngày: <?php echo date('d/m/Y H:i', strtotime($post['created_at'])); ?>
    </div>
  </div>
    </section>
</main>
