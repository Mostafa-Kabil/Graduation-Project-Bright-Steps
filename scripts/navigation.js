// Navigation utility
function navigateTo(page) {
    const baseUrl = window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '/');

    const pageMap = {
        'index': 'index.html',
        'login': 'login.html',
        'signup': 'signup.html',
        'dashboard': 'dashboard.html',
        'doctor-login': 'doctor-login.html',
        'doctor-signup': 'doctor-signup.html',
        'doctor-dashboard': 'doctor-dashboard.html',
        'settings': 'settings.html',
        'profile': 'profile.html',
        'child-profile': 'child-profile.html',
        'about': 'about.html',
        'contact': 'contact.html',
        'privacy': 'privacy.html',
        'terms': 'terms.html',
        'help': 'help.html',
        'features': 'features.html',
        'pricing': 'pricing.html'
    };

    const targetPage = pageMap[page] || page + '.html';
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
