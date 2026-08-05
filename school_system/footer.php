</div>
</div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script>
  // تحديد الألوان حسب الجنس
  document.addEventListener('DOMContentLoaded', function () {
    const gender = '<?php echo $_SESSION["gender"] ?? "male"; ?>';
    if (gender === 'female') {
      document.documentElement.style.setProperty('--main-color', '#ff66b2');
      document.documentElement.style.setProperty('--secondary-color', '#ffccdd');
    }
  });
</script>
</body>

</html>