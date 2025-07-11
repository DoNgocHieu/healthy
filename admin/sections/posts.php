<?php
require_once __DIR__ . '/../../config/Database.php';

class PostAdmin {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAllPosts() {
        try {
            $query = "
                SELECT
                    p.*,
                    u.username as author_name
                FROM posts p
                LEFT JOIN users u ON p.author_id = u.id
                ORDER BY p.created_at DESC
            ";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in getAllPosts: " . $e->getMessage());
            return [];
        }
    }

    public function createPost($title, $content, $thumbnail, $authorId) {
        try {
            $query = "
                INSERT INTO posts (title, content, thumbnail, author_id, created_at, updated_at)
                VALUES (?, ?, ?, ?, NOW(), NOW())
            ";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([$title, $content, $thumbnail, $authorId]);
        } catch (Exception $e) {
            error_log("Error in createPost: " . $e->getMessage());
            return false;
        }
    }

    public function updatePost($id, $title, $content, $thumbnail) {
        try {
            $query = "
                UPDATE posts
                SET title = ?,
                    content = ?,
                    thumbnail = ?,
                    updated_at = NOW()
                WHERE id = ?
            ";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([$title, $content, $thumbnail, $id]);
        } catch (Exception $e) {
            error_log("Error in updatePost: " . $e->getMessage());
            return false;
        }
    }

    public function deletePost($id) {
        try {
            $query = "DELETE FROM posts WHERE id = ?";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            error_log("Error in deletePost: " . $e->getMessage());
            return false;
        }
    }
}

$postAdmin = new PostAdmin();
$posts = $postAdmin->getAllPosts();
?>

<!-- Tiêu đề -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h4 mb-0">Quản lý bài viết</h2>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPostModal">
        <i class="bi bi-plus-lg"></i> Thêm bài viết mới
    </button>
</div>

<!-- Danh sách bài viết -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Tiêu đề</th>
                        <th>Hình ảnh</th>
                        <th>Tác giả</th>
                        <th>Ngày tạo</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($posts as $post): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($post['title']); ?></td>
                        <td>
                            <?php if ($post['thumbnail']): ?>
                                <img src="http://localhost/healthy/<?php echo htmlspecialchars(str_replace('/healthy/', '', $post['thumbnail'])); ?>" alt="Thumbnail" class="img-thumbnail" style="max-width: 100px; height: 100px; object-fit: cover;">
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($post['author_name']); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($post['created_at'])); ?></td>
                        <td>
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-outline-primary"
                                        onclick="editPost(<?php echo $post['id']; ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger"
                                        onclick="deletePost(<?php echo $post['id']; ?>)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal tạo bài viết mới -->
<div class="modal fade" id="createPostModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Thêm bài viết mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createPostForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="title" class="form-label">Tiêu đề</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="content" class="form-label">Nội dung</label>
                        <textarea class="form-control" id="content" name="content" rows="10" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="thumbnail" class="form-label">Hình ảnh đại diện</label>
                        <input type="file" class="form-control" id="thumbnail" name="thumbnail" accept="image/*">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary" id="savePostBtn">Lưu bài viết</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal chỉnh sửa bài viết -->
<div class="modal fade" id="editPostModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chỉnh sửa bài viết</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editPostForm" enctype="multipart/form-data">
                <input type="hidden" name="post_id" id="edit_post_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_title" class="form-label">Tiêu đề</label>
                        <input type="text" class="form-control" id="edit_title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_content" class="form-label">Nội dung</label>
                        <textarea class="form-control" id="edit_content" name="content" rows="10" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="edit_thumbnail" class="form-label">Hình ảnh đại diện</label>
                        <input type="file" class="form-control" id="edit_thumbnail" name="thumbnail" accept="image/*">
                        <div id="current_thumbnail" class="mt-2"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Cập nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editPost(postId) {
    // Lấy thông tin bài viết từ server
    fetch(`/healthy/api/get_post.php?id=${postId}`)
        .then(response => response.json())
        .then(post => {
            document.getElementById('edit_post_id').value = post.id;
            document.getElementById('edit_title').value = post.title;
            document.getElementById('edit_content').value = post.content;

            const thumbnailPreview = document.getElementById('current_thumbnail');
            if (post.thumbnail) {
                thumbnailPreview.innerHTML = `<img src="http://localhost/healthy/${post.thumbnail}" alt="Current thumbnail" class="img-thumbnail" style="max-width: 200px; height: 200px; object-fit: cover;">`;
            } else {
                thumbnailPreview.innerHTML = '';
            }

            const editModal = new bootstrap.Modal(document.getElementById('editPostModal'));
            editModal.show();
        });
}

function deletePost(postId) {
    if (confirm('Bạn có chắc chắn muốn xóa bài viết này?')) {
        fetch(`/healthy/api/delete_post.php?id=${postId}`, { method: 'DELETE' })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    location.reload();
                } else {
                    alert('Có lỗi xảy ra khi xóa bài viết.');
                }
            });
    }
}

// Xử lý form tạo bài viết mới
document.addEventListener('DOMContentLoaded', function() {
    const savePostBtn = document.getElementById('savePostBtn');
    const createPostForm = document.getElementById('createPostForm');
    const createPostModal = document.getElementById('createPostModal');

    if (savePostBtn) {
        savePostBtn.addEventListener('click', async function() {
            if (!createPostForm.checkValidity()) {
                createPostForm.reportValidity();
                return;
            }

            const formData = new FormData(createPostForm);

            try {
                savePostBtn.disabled = true;
                savePostBtn.textContent = 'Đang lưu...';

                const response = await fetch('/healthy/api/create_post.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    // Đóng modal và reload trang
                    const modal = bootstrap.Modal.getInstance(createPostModal);
                    modal.hide();
                    location.reload();
                } else {
                    alert(result.message || 'Có lỗi xảy ra khi tạo bài viết');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi tạo bài viết');
            } finally {
                savePostBtn.disabled = false;
                savePostBtn.textContent = 'Lưu bài viết';
            }
        });
    }
});
</script>
