<?php
// php/includes/footer.php
?>
<?php if (!isset($hideFooter) || !$hideFooter): ?>
<footer class="footer" <?= isset($footerStyle) ? "style=\"$footerStyle\"" : '' ?>>
    <div class="container">
        <?php if (isset($showFullFooter) && $showFullFooter): ?>
        <div class="footer-grid">
            <div class="footer-brand">
                <a class="logo" href="index.php"><i class="fa-solid fa-droplet" style="color:#ffd966"></i> LifeFlow</a>
                <p>India's most trusted smart blood donation platform. Connecting donors, patients, and hospitals for a healthier tomorrow.</p>
            </div>
            <div>
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="view_requests.php">Blood Requests</a></li>
                    <li><a href="contact.php">Contact</a></li>
                </ul>
            </div>
            <div>
                <h4>For Users</h4>
                <ul>
                    <li><a href="register.php?role=donor">Become a Donor</a></li>
                    <li><a href="blood_request.php">Request Blood</a></li>
                    <li><a href="register.php?role=hospital">Register Hospital</a></li>
                    <li><a href="login.php">Sign In</a></li>
                </ul>
            </div>
            <div>
                <h4>Emergency</h4>
                <ul>
                    <li><a href="tel:+911800BLOOD">📞 1800-BLOOD</a></li>
                    <li><a href="tel:+91112">🚨 Dial 112</a></li>
                    <li><a href="contact.php">Email Us</a></li>
                    <li><a href="view_requests.php?urgency=critical">Critical Requests</a></li>
                </ul>
            </div>
        </div>
        <?php endif; ?>
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> LifeFlow. All rights reserved. <?= isset($showFullFooter) && $showFullFooter ? '| Made with ❤️ to save lives.' : '' ?></p>
        </div>
    </div>
</footer>
<?php endif; ?>

<script src="../js/app.js"></script>
<?php if (isset($extraScripts)) echo $extraScripts; ?>
</body>
</html>

