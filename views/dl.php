
  <link rel="stylesheet" href="../css/dl.css">

  <main class="container">
    <!-- Danh sách bài viết chính -->
    <section class="main-posts" aria-label="Danh sách bài viết">
      <?php
        require_once '../config/Database.php';
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id, title, thumbnail, content FROM posts ORDER BY created_at DESC");
        $stmt->execute();
        $allPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($allPosts as $post) {
          $url   = 'layout.php?page=post_detail&id=' . urlencode($post['id']);
          $img   = !empty($post['thumbnail']) ? '../' . htmlspecialchars($post['thumbnail']) : '../ct/default.png';
          $title = htmlspecialchars($post['title']);
          // Lấy mô tả ngắn từ content (nếu có), hoặc cắt chuỗi
          $desc = '';
          if (!empty($post['content'])) {
            $desc = strip_tags($post['content']);
            $desc = mb_substr($desc, 0, 100) . (mb_strlen($desc) > 100 ? '...' : '');
          }
          echo "<a href=\"{$url}\" class=\"post-link\">";
          echo "  <article class=\"post\">";
          echo "    <div class=\"thumb\" style=\"background-image: url('{$img}');\"></div>";
          echo "    <h3>{$title}</h3>";
          echo "    <p>{$desc}</p>";
          echo "  </article>";
          echo "</a>";
        }
      ?>
    </section>

    <!-- Sidebar bài viết mới -->
    <aside class="sidebar" aria-label="Bài viết mới">
      <h2>BÀI VIẾT MỚI</h2>
      <ul class="recent-posts">
        <?php
        require_once '../config/Database.php';
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id, title, thumbnail FROM posts ORDER BY created_at DESC LIMIT 5");
        $stmt->execute();
        $recentPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($recentPosts as $post):
          $url = 'layout.php?page=post_detail&id=' . urlencode($post['id']);
          $img = !empty($post['thumbnail']) ? '../' . htmlspecialchars($post['thumbnail']) : '../ct/default.png';
        ?>
          <li>
            <a href="<?php echo $url; ?>">
              <img src="<?php echo $img; ?>" alt="Ảnh bài viết <?php echo htmlspecialchars($post['title']); ?>">
              <span><?php echo htmlspecialchars($post['title']); ?></span>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </aside>
  </main>
