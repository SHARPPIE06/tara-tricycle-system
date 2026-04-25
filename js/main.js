// js/main.js

document.addEventListener('DOMContentLoaded', () => {
    // Check for URL parameters (error or success messages)
    const urlParams = new URLSearchParams(window.location.search);
    const errorMsg = urlParams.get('error');
    const successMsg = urlParams.get('success');

    // Handle Login Alert
    const loginAlert = document.getElementById('loginAlert');
    if (loginAlert) {
        if (errorMsg) {
            loginAlert.textContent = errorMsg;
            loginAlert.className = 'alert error';
        } else if (successMsg) {
            loginAlert.textContent = successMsg;
            loginAlert.className = 'alert success';
        }
    }

    // Handle Register Alert
    const registerAlert = document.getElementById('registerAlert');
    if (registerAlert) {
        if (errorMsg) {
            registerAlert.textContent = errorMsg;
            registerAlert.className = 'alert error';
        } else if (successMsg) {
            registerAlert.textContent = successMsg;
            registerAlert.className = 'alert success';
        }
    }
});
