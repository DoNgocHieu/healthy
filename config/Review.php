<?php
require_once __DIR__ . '/Database.php';

class Review {
    private $db;
    private $userId;

    public function __construct($userId = null) {
        $this->db = Database::getInstance()->getConnection();
        $this->userId = $userId;
    }

    public function create($data) {
        try {
            $this->db->beginTransaction();

            // Verify order and item
            $stmt = $this->db->prepare('
                SELECT oi.*, o.order_status
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                WHERE o.id = ? AND oi.item_id = ? AND o.user_id = ?
            ');
            $stmt->execute([$data['order_id'], $data['item_id'], $this->userId]);
            $orderItem = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$orderItem) {
                return [
                    'success' => false,
                    'message' => 'Không tìm thấy món ăn trong đơn hàng'
                ];
            }

            if ($orderItem['order_status'] !== 'completed') {
                return [
                    'success' => false,
                    'message' => 'Chỉ có thể đánh giá món ăn từ đơn hàng đã hoàn thành'
                ];
            }

            // Check if already reviewed
            $stmt = $this->db->prepare('
                SELECT id FROM reviews
                WHERE user_id = ? AND item_id = ? AND order_id = ?
            ');
            $stmt->execute([$this->userId, $data['item_id'], $data['order_id']]);
            if ($stmt->fetch()) {
                return [
                    'success' => false,
                    'message' => 'Bạn đã đánh giá món ăn này từ đơn hàng này'
                ];
            }

            // Create review
            $stmt = $this->db->prepare('
                INSERT INTO reviews (
                    user_id, item_id, order_id, rating, comment, is_verified
                ) VALUES (?, ?, ?, ?, ?, true)
            ');

            $stmt->execute([
                $this->userId,
                $data['item_id'],
                $data['order_id'],
                $data['rating'],
                $data['comment'] ?? null
            ]);

            $reviewId = $this->db->lastInsertId();

            // Upload images if any
            if (!empty($data['images'])) {
                $stmt = $this->db->prepare('
                    INSERT INTO review_images (review_id, image_url)
                    VALUES (?, ?)
                ');

                foreach ($data['images'] as $imageUrl) {
                    $stmt->execute([$reviewId, $imageUrl]);
                }
            }

            // Update item rating
            $this->updateItemRating($data['item_id']);

            $this->db->commit();

            return [
                'success' => true,
                'review_id' => $reviewId,
                'message' => 'Đánh giá của bạn đã được ghi nhận'
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ];
        }
    }

    private function updateItemRating($itemId) {
        $stmt = $this->db->prepare('
            UPDATE items i
            SET rating = (
                SELECT AVG(r.rating)
                FROM reviews r
                WHERE r.item_id = i.id
                AND r.is_verified = true
            )
            WHERE i.id = ?
        ');
        $stmt->execute([$itemId]);
    }

    public function getReviewsByItem($itemId, $page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;

        $stmt = $this->db->prepare('
            SELECT r.*, u.fullname, u.avatar_url,
                   GROUP_CONCAT(ri.image_url) as images
            FROM reviews r
            LEFT JOIN users u ON r.user_id = u.id
            LEFT JOIN review_images ri ON r.id = ri.review_id
            WHERE r.item_id = ? AND r.is_verified = true
            GROUP BY r.id
            ORDER BY r.created_at DESC
            LIMIT ? OFFSET ?
        ');

        $stmt->execute([$itemId, $perPage, $offset]);
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($reviews as &$review) {
            if ($review['images']) {
                $review['images'] = explode(',', $review['images']);
            } else {
                $review['images'] = [];
            }
        }

        return $reviews;
    }

    public function getReviewsByUser($page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;

        $stmt = $this->db->prepare('
            SELECT r.*, i.name as item_name, i.image_url as item_image,
                   GROUP_CONCAT(ri.image_url) as images
            FROM reviews r
            JOIN items i ON r.item_id = i.id
            LEFT JOIN review_images ri ON r.id = ri.review_id
            WHERE r.user_id = ?
            GROUP BY r.id
            ORDER BY r.created_at DESC
            LIMIT ? OFFSET ?
        ');

        $stmt->execute([$this->userId, $perPage, $offset]);
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($reviews as &$review) {
            if ($review['images']) {
                $review['images'] = explode(',', $review['images']);
            } else {
                $review['images'] = [];
            }
        }

        return $reviews;
    }

    public function getReview($reviewId) {
        $stmt = $this->db->prepare('
            SELECT r.*, i.name as item_name, i.image_url as item_image,
                   GROUP_CONCAT(ri.image_url) as images
            FROM reviews r
            JOIN items i ON r.item_id = i.id
            LEFT JOIN review_images ri ON r.id = ri.review_id
            WHERE r.id = ? AND r.user_id = ?
            GROUP BY r.id
        ');

        $stmt->execute([$reviewId, $this->userId]);
        $review = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($review && $review['images']) {
            $review['images'] = explode(',', $review['images']);
        }

        return $review;
    }

    public function deleteReview($reviewId) {
        try {
            $this->db->beginTransaction();

            // Verify ownership
            $review = $this->getReview($reviewId);
            if (!$review) {
                return [
                    'success' => false,
                    'message' => 'Không tìm thấy đánh giá'
                ];
            }

            // Delete review images
            $stmt = $this->db->prepare('DELETE FROM review_images WHERE review_id = ?');
            $stmt->execute([$reviewId]);

            // Delete review
            $stmt = $this->db->prepare('DELETE FROM reviews WHERE id = ? AND user_id = ?');
            $stmt->execute([$reviewId, $this->userId]);

            // Update item rating
            $this->updateItemRating($review['item_id']);

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Đã xóa đánh giá'
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ];
        }
    }
}
