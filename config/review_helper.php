<?php
/**
 * Review Helper Functions
 * Handles review operations and rating calculations
 * (Replaces database triggers for free hosting compatibility)
 */

require_once __DIR__ . '/database.php';

/**
 * Validate rating value
 * @param int $rating Rating value to validate
 * @return bool True if valid, false otherwise
 */
function validateRating($rating) {
    return is_numeric($rating) && $rating >= 1 && $rating <= 5;
}

/**
 * Add a new review
 * @param int $user_id User ID
 * @param int $room_id Room ID
 * @param int $rating Rating (1-5)
 * @param string $comment Review comment
 * @param int|null $booking_id Optional booking ID
 * @return array Result with success status and message
 */
function addReview($user_id, $room_id, $rating, $comment = '', $booking_id = null) {
    try {
        $db = getDB();
        
        // Validate rating
        if (!validateRating($rating)) {
            return [
                'success' => false,
                'message' => 'Rating phải từ 1 đến 5'
            ];
        }
        
        // Check if user already reviewed this room
        $stmt = $db->prepare("
            SELECT id FROM reviews 
            WHERE user_id = :user_id AND room_id = :room_id
        ");
        $stmt->execute([
            'user_id' => $user_id,
            'room_id' => $room_id
        ]);
        
        if ($stmt->fetch()) {
            return [
                'success' => false,
                'message' => 'Bạn đã đánh giá phòng này rồi'
            ];
        }
        
        // Insert review
        $stmt = $db->prepare("
            INSERT INTO reviews (user_id, room_id, booking_id, rating, comment, status, created_at)
            VALUES (:user_id, :room_id, :booking_id, :rating, :comment, 'pending', NOW())
        ");
        
        $stmt->execute([
            'user_id' => $user_id,
            'room_id' => $room_id,
            'booking_id' => $booking_id,
            'rating' => $rating,
            'comment' => $comment
        ]);
        
        return [
            'success' => true,
            'message' => 'Đánh giá của bạn đã được gửi và đang chờ duyệt',
            'review_id' => $db->lastInsertId()
        ];
        
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Lỗi: ' . $e->getMessage()
        ];
    }
}

/**
 * Update review status (approve/reject)
 * @param int $review_id Review ID
 * @param string $status New status ('approved', 'rejected')
 * @param string $admin_response Optional admin response
 * @return array Result with success status and message
 */
function updateReviewStatus($review_id, $status, $admin_response = '') {
    try {
        $db = getDB();
        
        // Get review details
        $stmt = $db->prepare("SELECT room_id, status as old_status FROM reviews WHERE id = :id");
        $stmt->execute(['id' => $review_id]);
        $review = $stmt->fetch();
        
        if (!$review) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy đánh giá'
            ];
        }
        
        // Update review status
        $stmt = $db->prepare("
            UPDATE reviews 
            SET status = :status,
                admin_response = :admin_response,
                responded_at = NOW()
            WHERE id = :id
        ");
        
        $stmt->execute([
            'id' => $review_id,
            'status' => $status,
            'admin_response' => $admin_response
        ]);
        
        // If status changed to approved, update room rating
        if ($status === 'approved' && $review['old_status'] !== 'approved') {
            updateRoomRating($review['room_id']);
        }
        
        return [
            'success' => true,
            'message' => 'Cập nhật trạng thái đánh giá thành công'
        ];
        
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Lỗi: ' . $e->getMessage()
        ];
    }
}

/**
 * Update room rating based on approved reviews
 * @param int $room_id Room ID
 * @return bool Success status
 */
function updateRoomRating($room_id) {
    try {
        $db = getDB();
        
        // Calculate average rating and count
        $stmt = $db->prepare("
            SELECT 
                COALESCE(AVG(rating), 0) as avg_rating,
                COUNT(*) as review_count
            FROM reviews 
            WHERE room_id = :room_id AND status = 'approved'
        ");
        
        $stmt->execute(['room_id' => $room_id]);
        $result = $stmt->fetch();
        
        // Update room
        $stmt = $db->prepare("
            UPDATE rooms 
            SET rating = :rating,
                total_reviews = :total_reviews
            WHERE id = :room_id
        ");
        
        $stmt->execute([
            'room_id' => $room_id,
            'rating' => round($result['avg_rating'], 2),
            'total_reviews' => $result['review_count']
        ]);
        
        return true;
        
    } catch (PDOException $e) {
        error_log("Error updating room rating: " . $e->getMessage());
        return false;
    }
}

/**
 * Get reviews for a room
 * @param int $room_id Room ID
 * @param string $status Filter by status (default: 'approved')
 * @param int $limit Number of reviews to return
 * @return array List of reviews
 */
function getRoomReviews($room_id, $status = 'approved', $limit = 10) {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            SELECT 
                r.*,
                u.full_name,
                u.avatar
            FROM reviews r
            JOIN users u ON r.user_id = u.id
            WHERE r.room_id = :room_id AND r.status = :status
            ORDER BY r.created_at DESC
            LIMIT :limit
        ");
        
        $stmt->bindValue(':room_id', $room_id, PDO::PARAM_INT);
        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
        
    } catch (PDOException $e) {
        error_log("Error getting room reviews: " . $e->getMessage());
        return [];
    }
}

/**
 * Get user's review for a room
 * @param int $user_id User ID
 * @param int $room_id Room ID
 * @return array|false Review data or false if not found
 */
function getUserRoomReview($user_id, $room_id) {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            SELECT * FROM reviews 
            WHERE user_id = :user_id AND room_id = :room_id
            LIMIT 1
        ");
        
        $stmt->execute([
            'user_id' => $user_id,
            'room_id' => $room_id
        ]);
        
        return $stmt->fetch();
        
    } catch (PDOException $e) {
        error_log("Error getting user review: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete a review
 * @param int $review_id Review ID
 * @return array Result with success status and message
 */
function deleteReview($review_id) {
    try {
        $db = getDB();
        
        // Get room_id before deleting
        $stmt = $db->prepare("SELECT room_id FROM reviews WHERE id = :id");
        $stmt->execute(['id' => $review_id]);
        $review = $stmt->fetch();
        
        if (!$review) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy đánh giá'
            ];
        }
        
        // Delete review
        $stmt = $db->prepare("DELETE FROM reviews WHERE id = :id");
        $stmt->execute(['id' => $review_id]);
        
        // Update room rating
        updateRoomRating($review['room_id']);
        
        return [
            'success' => true,
            'message' => 'Xóa đánh giá thành công'
        ];
        
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Lỗi: ' . $e->getMessage()
        ];
    }
}

/**
 * Get rating statistics for a room
 * @param int $room_id Room ID
 * @return array Rating statistics
 */
function getRoomRatingStats($room_id) {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total_reviews,
                AVG(rating) as avg_rating,
                SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as rating_5,
                SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as rating_4,
                SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as rating_3,
                SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as rating_2,
                SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as rating_1
            FROM reviews 
            WHERE room_id = :room_id AND status = 'approved'
        ");
        
        $stmt->execute(['room_id' => $room_id]);
        $stats = $stmt->fetch();
        
        // Calculate percentages
        $total = $stats['total_reviews'];
        if ($total > 0) {
            $stats['rating_5_percent'] = round(($stats['rating_5'] / $total) * 100);
            $stats['rating_4_percent'] = round(($stats['rating_4'] / $total) * 100);
            $stats['rating_3_percent'] = round(($stats['rating_3'] / $total) * 100);
            $stats['rating_2_percent'] = round(($stats['rating_2'] / $total) * 100);
            $stats['rating_1_percent'] = round(($stats['rating_1'] / $total) * 100);
        } else {
            $stats['rating_5_percent'] = 0;
            $stats['rating_4_percent'] = 0;
            $stats['rating_3_percent'] = 0;
            $stats['rating_2_percent'] = 0;
            $stats['rating_1_percent'] = 0;
        }
        
        $stats['avg_rating'] = round($stats['avg_rating'], 2);
        
        return $stats;
        
    } catch (PDOException $e) {
        error_log("Error getting rating stats: " . $e->getMessage());
        return [
            'total_reviews' => 0,
            'avg_rating' => 0,
            'rating_5' => 0,
            'rating_4' => 0,
            'rating_3' => 0,
            'rating_2' => 0,
            'rating_1' => 0,
            'rating_5_percent' => 0,
            'rating_4_percent' => 0,
            'rating_3_percent' => 0,
            'rating_2_percent' => 0,
            'rating_1_percent' => 0
        ];
    }
}
