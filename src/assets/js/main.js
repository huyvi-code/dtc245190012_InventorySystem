// Main JavaScript for Inventory Management System
document.addEventListener('DOMContentLoaded', function () {
    // Auto dismiss flash alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });

    // Confirmation dialog for deletion
    const deleteButtons = document.querySelectorAll('.btn-confirm-delete');
    deleteButtons.forEach(function (button) {
        button.addEventListener('click', function (e) {
            const itemName = this.getAttribute('data-name') || 'mục này';
            if (!confirm(`Bạn có chắc chắn muốn xóa "${itemName}" không? Thao tác này không thể hoàn tác!`)) {
                e.preventDefault();
            }
        });
    });
});
