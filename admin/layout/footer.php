    </div><!-- end content -->
</div><!-- end page -->

<script>
function showToast(message, type) {
    Toastify({
        text: message,
        duration: 3000,
        close: true,
        gravity: "top",
        position: "center",
        backgroundColor: type === "error" ? "#FF3030" : "#2F8C37",
        stopOnFocus: true
    }).showToast();
}

<?php if (isset($_SESSION['message'])): ?>
    showToast("<?= addslashes($_SESSION['message']) ?>", "<?= $_SESSION['status'] ?? 'success' ?>");
    <?php unset($_SESSION['message'], $_SESSION['status']); ?>
<?php endif; ?>
</script>
<script src="/sport-club/assets/js/admin.js"></script>
</body>
</html>
