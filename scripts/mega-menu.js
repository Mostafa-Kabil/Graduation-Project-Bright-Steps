// Mega Menu Interactivity
function toggleMegaMenu(menuId, event) {
    event.stopPropagation();
    
    const menuToOpen = document.getElementById(menuId);
    const triggerBtn = event.currentTarget;
    
    // Check if the current one is already open
    const isOpen = menuToOpen.classList.contains('open');
    
    // Close all open menus first
    document.querySelectorAll('.mega-menu').forEach(menu => {
        menu.classList.remove('open');
    });
    document.querySelectorAll('.dropdown-trigger').forEach(trigger => {
        trigger.classList.remove('active');
    });
    
    // If it wasn't open, open it
    if (!isOpen) {
        menuToOpen.classList.add('open');
        triggerBtn.classList.add('active');
    }
}

// Close when clicking outside
document.addEventListener('click', (event) => {
    if (!event.target.closest('.mega-menu') && !event.target.closest('.dropdown-trigger')) {
        document.querySelectorAll('.mega-menu').forEach(menu => {
            menu.classList.remove('open');
        });
        document.querySelectorAll('.dropdown-trigger').forEach(trigger => {
            trigger.classList.remove('active');
        });
    }
});
