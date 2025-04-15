document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('changePasswordForm');
  const toggleSenha = document.getElementById('mostrarSenha');

  if (form) {
    form.addEventListener('submit', function (e) {
      const newPassword = document.getElementById('newPassword');
      const confirmPassword = document.getElementById('confirmPassword');

      if (newPassword && confirmPassword && newPassword.value !== confirmPassword.value) {
        e.preventDefault();
        alert('New passwords do not match.');
      }
    });
  }

  if (toggleSenha) {
    toggleSenha.addEventListener('change', function () {
      const senhaFields = ['currentPassword', 'newPassword', 'confirmPassword'];
      senhaFields.forEach(id => {
        const input = document.getElementById(id);
        if (input) {
          input.type = this.checked ? 'text' : 'password';
        }
      });
    });
  }
});
