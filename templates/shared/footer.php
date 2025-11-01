    </main>
    <footer class="app-footer">
        <div>
            <p>&copy; <?= date('Y') ?> Kabarak University. All rights reserved.</p>
            <p class="footnote">Empowering secure, transparent student elections.</p>
        </div>
        <div class="badge-group">
            <span class="badge">Blockchain Secured</span>
            <span class="badge">Real-time Analytics</span>
        </div>
    </footer>
    <?php
    $basePath = kvs_base_path();
    ?>
    <script src="<?= htmlspecialchars($basePath . '/assets/js/main.js', ENT_QUOTES, 'UTF-8') ?>" defer></script>
    <?php if (($role ?? '') === 'admin'): ?>
        <script src="<?= htmlspecialchars($basePath . '/assets/js/admin.js', ENT_QUOTES, 'UTF-8') ?>" defer></script>
    <?php elseif (($role ?? '') === 'student'): ?>
        <script src="<?= htmlspecialchars($basePath . '/assets/js/user.js', ENT_QUOTES, 'UTF-8') ?>" defer></script>
    <?php endif; ?>
</body>
</html>

