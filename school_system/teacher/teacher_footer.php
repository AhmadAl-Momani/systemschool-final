    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php if (isset($_SESSION['success_message'])): ?>
<script>
    Swal.fire({
        icon: 'success',
        title: 'نجاح',
        text: '<?= $_SESSION['success_message'] ?>',
        confirmButtonText: 'موافق'
    });
</script>
<?php unset($_SESSION['success_message']); endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
<script>
    Swal.fire({
        icon: 'error',
        title: 'خطأ',
        text: '<?= $_SESSION['error_message'] ?>',
        confirmButtonText: 'موافق'
    });
</script>
<?php unset($_SESSION['error_message']); endif; ?>
</body>
</html>
