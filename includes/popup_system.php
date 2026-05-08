<?php
function showSuccessPopup($message) {
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            showPageAlert('" . addslashes($message) . "', 'success');
        });
    </script>";
}
?>

<style>
@keyframes slideInDown {
    from { transform: translateX(-50%) translateY(-100%); opacity: 0; }
    to { transform: translateX(-50%) translateY(0); opacity: 1; }
}
@keyframes slideOutUp {
    from { transform: translateX(-50%) translateY(0); opacity: 1; }
    to { transform: translateX(-50%) translateY(-100%); opacity: 0; }
}
</style>

<script>
function showPageAlert(msg, type) {
    var alertDiv = document.createElement('div');
    alertDiv.className = 'page-alert page-alert--' + type;
    alertDiv.innerHTML = '<div class="page-alert__content"><i class="fas fa-' + (type === 'success' ? 'check-circle' : 'exclamation-circle') + '"></i> ' + msg + '</div>';
    alertDiv.style.cssText = 'position:fixed;top:20px;left:50%;transform:translateX(-50%);z-index:9999;background:#fff;padding:16px 24px;border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,.15);display:flex;align-items:center;gap:12px;font-family:"Poppins",sans-serif;font-size:.95rem;font-weight:500;animation:slideInDown .4s ease;max-width:90%;';
    if (type === 'success') {
        alertDiv.style.color = '#166534';
        alertDiv.style.border = '1px solid #bbf7d0';
        alertDiv.style.background = '#dcfce7';
    } else {
        alertDiv.style.color = '#991b1b';
        alertDiv.style.border = '1px solid #fecaca';
        alertDiv.style.background = '#fef2f2';
    }
    document.body.appendChild(alertDiv);
    setTimeout(function() {
        alertDiv.style.animation = 'slideOutUp .4s ease';
        setTimeout(function() { if (alertDiv.parentNode) alertDiv.parentNode.removeChild(alertDiv); }, 400);
    }, 5000);
}
</script>
