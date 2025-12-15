<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$page_title = 'Giới thiệu';

// Statistics
$db = getDB();
$stats = [
    'rooms' => $db->query("SELECT COUNT(*) as count FROM rooms")->fetch()['count'],
    'bookings' => $db->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'confirmed'")->fetch()['count'],
    'users' => $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'")->fetch()['count'],
    'years' => 5 // Years in business
];

include 'includes/header.php';
?>

<section class="page-header">
    <div class="container">
        <h1>Về chúng tôi</h1>
        <p>Giải pháp đặt phòng họp chuyên nghiệp hàng đầu</p>
    </div>
</section>

<section class="about-intro-section">
    <div class="container">
        <div class="about-intro-grid">
            <div class="about-intro-content">
                <h2 class="section-title">Meeting Room Booking</h2>
                <p class="about-intro-text">
                    Chúng tôi cung cấp giải pháp đặt phòng họp trực tuyến hiện đại, giúp doanh nghiệp 
                    và cá nhân dễ dàng tìm kiếm và đặt phòng họp phù hợp với nhu cầu.
                </p>
                <p class="about-intro-text">
                    Với hệ thống phòng họp đa dạng, trang thiết bị hiện đại và dịch vụ chuyên nghiệp, 
                    chúng tôi cam kết mang đến trải nghiệm tốt nhất cho khách hàng.
                </p>
                <div class="about-features">
                    <div class="about-feature-item">
                        <span class="feature-icon">✓</span>
                        <span>Đặt phòng nhanh chóng, tiện lợi</span>
                    </div>
                    <div class="about-feature-item">
                        <span class="feature-icon">✓</span>
                        <span>Trang thiết bị hiện đại, đầy đủ</span>
                    </div>
                    <div class="about-feature-item">
                        <span class="feature-icon">✓</span>
                        <span>Giá cả hợp lý, minh bạch</span>
                    </div>
                    <div class="about-feature-item">
                        <span class="feature-icon">✓</span>
                        <span>Hỗ trợ 24/7</span>
                    </div>
                </div>
            </div>
            <div class="about-intro-image">
                <img src="https://images.unsplash.com/photo-1497366216548-37526070297c?w=800&h=600&fit=crop" 
                     alt="Modern Office">
            </div>
        </div>
    </div>
</section>

<section class="about-stats-section">
    <div class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">🏢</div>
                <div class="stat-number"><?php echo $stats['rooms']; ?>+</div>
                <div class="stat-label">Phòng họp</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📅</div>
                <div class="stat-number"><?php echo $stats['bookings']; ?>+</div>
                <div class="stat-label">Lượt đặt phòng</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-number"><?php echo $stats['users']; ?>+</div>
                <div class="stat-label">Khách hàng</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⭐</div>
                <div class="stat-number"><?php echo $stats['years']; ?>+</div>
                <div class="stat-label">Năm kinh nghiệm</div>
            </div>
        </div>
    </div>
</section>

<section class="about-mission-section">
    <div class="container">
        <div class="mission-grid">
            <div class="mission-card">
                <div class="mission-icon">🎯</div>
                <h3>Sứ mệnh</h3>
                <p>
                    Cung cấp không gian làm việc chuyên nghiệp, hiện đại với dịch vụ tốt nhất, 
                    giúp khách hàng tổ chức các cuộc họp hiệu quả và thành công.
                </p>
            </div>
            <div class="mission-card">
                <div class="mission-icon">👁️</div>
                <h3>Tầm nhìn</h3>
                <p>
                    Trở thành nền tảng đặt phòng họp hàng đầu tại Việt Nam, được tin dùng bởi 
                    hàng ngàn doanh nghiệp và cá nhân.
                </p>
            </div>
            <div class="mission-card">
                <div class="mission-icon">💎</div>
                <h3>Giá trị cốt lõi</h3>
                <p>
                    Chất lượng - Uy tín - Chuyên nghiệp. Chúng tôi luôn đặt sự hài lòng của 
                    khách hàng lên hàng đầu.
                </p>
            </div>
        </div>
    </div>
</section>

<section class="about-team-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Đội ngũ của chúng tôi</h2>
            <p class="section-subtitle">Những người đam mê mang đến dịch vụ tốt nhất</p>
        </div>
        <div class="team-grid">
            <div class="team-member">
                <div class="member-avatar">
                    <span class="avatar-placeholder">👨‍💼</span>
                </div>
                <h4>Nguyễn Văn A</h4>
                <p class="member-role">Giám đốc điều hành</p>
                <p class="member-bio">10+ năm kinh nghiệm trong ngành dịch vụ văn phòng</p>
            </div>
            <div class="team-member">
                <div class="member-avatar">
                    <span class="avatar-placeholder">👩‍💼</span>
                </div>
                <h4>Trần Thị B</h4>
                <p class="member-role">Giám đốc vận hành</p>
                <p class="member-bio">Chuyên gia quản lý dịch vụ khách hàng</p>
            </div>
            <div class="team-member">
                <div class="member-avatar">
                    <span class="avatar-placeholder">👨‍💻</span>
                </div>
                <h4>Lê Văn C</h4>
                <p class="member-role">Giám đốc công nghệ</p>
                <p class="member-bio">Chuyên gia phát triển hệ thống</p>
            </div>
            <div class="team-member">
                <div class="member-avatar">
                    <span class="avatar-placeholder">👩‍💻</span>
                </div>
                <h4>Phạm Thị D</h4>
                <p class="member-role">Trưởng phòng CSKH</p>
                <p class="member-bio">Luôn sẵn sàng hỗ trợ khách hàng 24/7</p>
            </div>
        </div>
    </div>
</section>

<section class="about-cta-section">
    <div class="container">
        <div class="cta-content">
            <h2>Sẵn sàng đặt phòng họp?</h2>
            <p>Trải nghiệm dịch vụ chuyên nghiệp của chúng tôi ngay hôm nay</p>
            <div class="cta-buttons">
                <a href="rooms.php" class="btn btn-primary btn-lg">Xem phòng họp</a>
                <a href="contact.php" class="btn btn-outline btn-lg">Liên hệ ngay</a>
            </div>
        </div>
    </div>
</section>

<style>
.about-intro-section {
    padding: 4rem 0;
}

.about-intro-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 4rem;
    align-items: center;
}

.about-intro-text {
    font-size: 1.125rem;
    line-height: 1.8;
    color: var(--gray);
    margin-bottom: 1.5rem;
}

.about-features {
    margin-top: 2rem;
}

.about-feature-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem 0;
    font-size: 1.0625rem;
}

.about-feature-item .feature-icon {
    color: var(--success);
    font-weight: bold;
    font-size: 1.25rem;
}

.about-intro-image img {
    width: 100%;
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-xl);
}

.about-stats-section {
    background: var(--light);
    padding: 4rem 0;
}

.about-mission-section {
    padding: 4rem 0;
}

.mission-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
}

.mission-card {
    text-align: center;
    padding: 2.5rem;
    background: var(--white);
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-md);
    transition: var(--transition);
}

.mission-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-xl);
}

.mission-icon {
    font-size: 3.5rem;
    margin-bottom: 1.5rem;
}

.mission-card h3 {
    font-size: 1.5rem;
    margin-bottom: 1rem;
}

.mission-card p {
    color: var(--gray);
    line-height: 1.7;
}

.about-team-section {
    padding: 4rem 0;
    background: var(--light);
}

.team-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
}

.team-member {
    text-align: center;
    padding: 2rem;
    background: var(--white);
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow);
    transition: var(--transition);
}

.team-member:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
}

.member-avatar {
    width: 120px;
    height: 120px;
    margin: 0 auto 1.5rem;
    background: var(--gradient-royal);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.avatar-placeholder {
    font-size: 4rem;
}

.team-member h4 {
    font-size: 1.25rem;
    margin-bottom: 0.5rem;
}

.member-role {
    color: var(--primary);
    font-weight: 600;
    margin-bottom: 0.75rem;
}

.member-bio {
    color: var(--gray);
    font-size: 0.9375rem;
    line-height: 1.6;
}

.about-cta-section {
    padding: 5rem 0;
    background: var(--gradient-royal);
    color: var(--white);
}

.cta-content {
    text-align: center;
}

.cta-content h2 {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    color: var(--white);
}

.cta-content p {
    font-size: 1.25rem;
    margin-bottom: 2rem;
    opacity: 0.9;
}

.cta-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .about-intro-grid {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    
    .team-grid {
        grid-template-columns: 1fr;
    }
    
    .cta-content h2 {
        font-size: 2rem;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
