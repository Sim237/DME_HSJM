<!-- JAVASCRIPT LOCAL -->
    <script src="<?= BASE_URL ?>public/js/jquery.min.js"></script>
    <script src="<?= BASE_URL ?>public/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>public/js/main.js"></script>

    <!-- Scripts spécifiques à certaines pages (si besoin) -->
    <?php if (isset($extra_js)): ?>
        <script src="<?= BASE_URL ?>public/js/<?= $extra_js ?>"></script>
    <?php endif; ?>
</body>
</html>