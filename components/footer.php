<?php
// components/footer.php
?>
    <?php if (isset($use_dashboard_layout) && $use_dashboard_layout): ?>
            </main>
        </div>
    <?php else: ?>
        </main>
    <?php endif; ?>
    <footer class="footer">
        <div class="footer-nav">
            <a href="<?= BASE_URL ?>index.php" class="label">Home</a>
            <a href="<?= BASE_URL ?>contact.php" class="label">Contact</a>
            <a href="#" class="label">Terms</a>
            <a href="#" class="label">Privacy Policy</a>
            <span class="label">&copy; <?= date('Y') ?> DPLS</span>
        </div>
    </footer>
    <script src="<?= BASE_URL ?>assets/js/app.js?v=2"></script>
</body>
</html>
