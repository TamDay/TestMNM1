<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$page_title = 'Liên hệ';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_input($_POST['name'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $phone = sanitize_input($_POST['phone'] ?? '');
    $subject = sanitize_input($_POST['subject'] ?? '');
    $message = sanitize_input($_POST['message'] ?? '');
    
    // Validation
    if (empty($name) || empty($email) || empty($message)) {
        $error = 'Vui lòng nhập đầy đủ thông tin bắt buộc';
    } elseif (!is_valid_email($email)) {
        $error = 'Email không hợp lệ';
    } elseif (!empty($phone) && !is_valid_phone($phone)) {
        $error = 'Số điện thoại không hợp lệ';
    } else {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO contacts (name, email, phone, subject, message) 
                             VALUES (:name, :email, :phone, :subject, :message)");
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':subject', $subject);
        $stmt->bindParam(':message', $message);
        
        if ($stmt->execute()) {
            $success = 'Cảm ơn bạn đã liên hệ! Chúng tôi sẽ phản hồi trong thời gian sớm nhất.';
            // Clear form
            $name = $email = $phone = $subject = $message = '';
        } else {
            $error = 'Có lỗi xảy ra, vui lòng thử lại';
        }
    }
}

include 'includes/header.php';
?>

<section class="page-header">
    <div class="container">
        <h1>Liên hệ với chúng tôi</h1>
        <p>Chúng tôi luôn sẵn sàng hỗ trợ bạn</p>
    </div>
</section>

<section class="contact-section">
    <div class="container">
        <div class="contact-grid">
            <div class="contact-info">
                <h2>Thông tin liên hệ</h2>
                <p class="contact-intro">
                    Hãy liên hệ với chúng tôi qua form bên cạnh hoặc thông qua các kênh sau:
                </p>
                
                <div class="contact-items">
                    <div class="contact-item">
                        <div class="contact-icon">📍</div>
                        <div class="contact-details">
                            <h4>Địa chỉ</h4>
                            <p>123 Đường ABC, Quận 1, TP.HCM</p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <div class="contact-icon">📞</div>
                        <div class="contact-details">
                            <h4>Điện thoại</h4>
                            <p>
                                <a href="tel:0123456789">0123 456 789</a><br>
                                <a href="tel:0987654321">0987 654 321</a>
                            </p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <div class="contact-icon">📧</div>
                        <div class="contact-details">
                            <h4>Email</h4>
                            <p>
                                <a href="mailto:info@meetingroom.vn">info@meetingroom.vn</a><br>
                                <a href="mailto:support@meetingroom.vn">support@meetingroom.vn</a>
                            </p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <div class="contact-icon">🕐</div>
                        <div class="contact-details">
                            <h4>Giờ làm việc</h4>
                            <p>
                                Thứ 2 - Thứ 6: 8:00 - 18:00<br>
                                Thứ 7: 8:00 - 12:00<br>
                                Chủ nhật: Nghỉ
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="social-links-contact">
                    <h4>Kết nối với chúng tôi</h4>
                    <div class="social-icons">
                        <a href="#" class="social-icon">📘 Facebook</a>
                        <a href="#" class="social-icon">📷 Instagram</a>
                        <a href="#" class="social-icon">🐦 Twitter</a>
                        <a href="#" class="social-icon">💼 LinkedIn</a>
                    </div>
                </div>
            </div>
            
            <div class="contact-form-container">
                <h2>Gửi tin nhắn</h2>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <span class="alert-icon">⚠️</span>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <span class="alert-icon">✓</span>
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" class="contact-form">
                    <div class="form-group">
                        <label for="name">Họ và tên <span class="required">*</span></label>
                        <input type="text" id="name" name="name" 
                               value="<?php echo htmlspecialchars($name ?? ''); ?>" 
                               required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email <span class="required">*</span></label>
                            <input type="email" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($email ?? ''); ?>" 
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Số điện thoại</label>
                            <input type="tel" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($phone ?? ''); ?>" 
                                   placeholder="0123456789">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="subject">Tiêu đề</label>
                        <input type="text" id="subject" name="subject" 
                               value="<?php echo htmlspecialchars($subject ?? ''); ?>" 
                               placeholder="Vấn đề bạn muốn trao đổi">
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Nội dung <span class="required">*</span></label>
                        <textarea id="message" name="message" rows="6" 
                                  required placeholder="Nhập nội dung tin nhắn của bạn..."><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-lg btn-block">
                        📧 Gửi tin nhắn
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<section class="map-section">
    <div class="container">
        <h2 class="section-title">Vị trí của chúng tôi</h2>
        <div class="map-container">
            <iframe 
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3919.4326570642!2d106.69741731533397!3d10.776889992320164!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31752f4b3330bcc9%3A0xb7a8b6f4b1e3e8e8!2zVHLGsOG7nW5nIMSQ4bqhaSBo4buNYyBLaG9hIGjhu41jIFThu7Egbmhpw6puIC0gxJDhuqFpIGjhu41jIFF14buRYyBnaWEgVFAuSENN!5e0!3m2!1svi!2s!4v1234567890123!5m2!1svi!2s" 
                width="100%" 
                height="450" 
                style="border:0;" 
                allowfullscreen="" 
                loading="lazy" 
                referrerpolicy="no-referrer-when-downgrade">
            </iframe>
        </div>
    </div>
</section>

<style>
.contact-section {
    padding: 4rem 0;
}

.contact-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 4rem;
}

.contact-info h2,
.contact-form-container h2 {
    font-size: 2rem;
    margin-bottom: 1.5rem;
}

.contact-intro {
    font-size: 1.0625rem;
    color: var(--gray);
    margin-bottom: 2rem;
    line-height: 1.7;
}

.contact-items {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.contact-item {
    display: flex;
    gap: 1.5rem;
    padding: 1.5rem;
    background: var(--light);
    border-radius: var(--radius-lg);
    transition: var(--transition);
}

.contact-item:hover {
    background: var(--white);
    box-shadow: var(--shadow-md);
}

.contact-icon {
    font-size: 2.5rem;
    flex-shrink: 0;
}

.contact-details h4 {
    font-size: 1.125rem;
    margin-bottom: 0.5rem;
}

.contact-details p {
    color: var(--gray);
    line-height: 1.6;
}

.contact-details a {
    color: var(--primary);
    transition: var(--transition);
}

.contact-details a:hover {
    text-decoration: underline;
}

.social-links-contact {
    margin-top: 2rem;
    padding: 1.5rem;
    background: var(--gradient-royal);
    border-radius: var(--radius-lg);
    color: var(--white);
}

.social-links-contact h4 {
    margin-bottom: 1rem;
    color: var(--white);
}

.social-icons {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.social-icon {
    padding: 0.5rem 1rem;
    background: rgba(255, 255, 255, 0.2);
    border-radius: var(--radius-full);
    color: var(--white);
    font-size: 0.9375rem;
    transition: var(--transition);
}

.social-icon:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: translateY(-2px);
}

.contact-form-container {
    background: var(--white);
    padding: 2.5rem;
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-lg);
}

.contact-form .form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.map-section {
    padding: 4rem 0;
    background: var(--light);
}

.map-container {
    margin-top: 2rem;
    border-radius: var(--radius-xl);
    overflow: hidden;
    box-shadow: var(--shadow-lg);
}

.map-container iframe {
    display: block;
}

@media (max-width: 768px) {
    .contact-grid {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    
    .contact-form .form-row {
        grid-template-columns: 1fr;
    }
    
    .social-icons {
        flex-direction: column;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
