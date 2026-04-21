  </div><!-- /.main-content -->
</div><!-- /.app-wrapper -->

<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<?php if (isset($extra_js)): ?>
  <?php foreach ($extra_js as $js): ?>
    <script src="<?= BASE_URL ?>/assets/js/<?= htmlspecialchars($js) ?>"></script>
  <?php endforeach; ?>
<?php endif; ?>
</body>
</html>
