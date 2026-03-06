// Navigation utility
function navigateTo(page) {
    const baseUrl = window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '/');

    const pageMap = {
        'index': 'index.php',
        'login': 'login.php',
        'signup': 'signup.php',
        'dashboard': 'dashboard.php',
        'doctor-login': 'doctor-login.php',
        'doctor-signup': 'doctor-signup.php',
        'doctor-dashboard': 'doctor-dashboard.php',
        'settings': 'settings.php',
        'profile': 'profile.php',
        'child-profile': 'child-profile.php',
        'about': 'about.php',
        'contact': 'contact.php',
        'privacy': 'privacy.php',
        'terms': 'terms.php',
        'help': 'help.php',
        'features': 'features.php',
        'pricing': 'pricing.php',
        'demo': 'demo.php'
    };

    const targetPage = pageMap[page] || page + '.php';
    window.location.href = baseUrl + targetPage;
}

// Check authentication
function checkAuth() {
    return sessionStorage.getItem('isAuthenticated') === 'true';
}

// Set authentication
function setAuth(value) {
    sessionStorage.setItem('isAuthenticated', value.toString());
}

// Clear authentication
function clearAuth() {
    sessionStorage.removeItem('isAuthenticated');
    sessionStorage.removeItem('userData');
}

// Get user data
function getUserData() {
    const data = sessionStorage.getItem('userData');
    return data ? JSON.parse(data) : null;
}

// Set user data
function setUserData(data) {
    sessionStorage.setItem('userData', JSON.stringify(data));
}

// Protect dashboard page
function protectDashboard() {
    if (!checkAuth()) {
        navigateTo('login');
    }
}
