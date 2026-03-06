// Theme Toggle Functionality
function toggleTheme() {
    const html = document.documentElement;
    const currentTheme = html.getAttribute('data-theme');

    if (currentTheme === 'dark') {
        html.removeAttribute('data-theme');
        localStorage.setItem('theme', 'light');
    } else {
        html.setAttribute('data-theme', 'dark');
        localStorage.setItem('theme', 'dark');
    }
}

// Load saved theme on page load
(function () {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
    }
})();
