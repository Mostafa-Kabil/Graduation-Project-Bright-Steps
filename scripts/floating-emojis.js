document.addEventListener("DOMContentLoaded", () => {
    const heroSection = document.querySelector('.hero-section');
    if (!heroSection) return;

    const shapes = [
        `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>`,
        `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>`,
        `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>`,
        `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M12 2v20M2 12h20M12 12A10 10 0 0 1 12 2a10 10 0 0 1 0 20z"/></svg>`,
        `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/></svg>`,
        `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>`,
        `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M19.439 7.85c-.049.322-.059.648-.059.975 0 3.25 2.613 5.892 5.835 5.836V18h-3v3h-6v-3H9v3H3v-6h3v-6H3V3h6v3h3V3h1.836A5.85 5.85 0 0 0 19.44 7.85Z"/></svg>`
    ];

    // Increased element count
    const elementCount = 35;

    const container = document.createElement('div');
    container.style.position = 'absolute';
    container.style.top = '0';
    container.style.left = '0';
    container.style.width = '100%';
    container.style.height = '100%';
    container.style.pointerEvents = 'none';
    container.style.overflow = 'hidden';
    container.style.zIndex = '0'; 
    
    if (getComputedStyle(heroSection).position === 'static') {
        heroSection.style.position = 'relative';
    }

    heroSection.appendChild(container);
    
    const floatingElements = [];
    const colors = ['var(--blue-500)', 'var(--purple-500)', 'var(--green-500)', 'var(--slate-400)', 'rgba(255, 255, 255, 0.4)'];

    for (let i = 0; i < elementCount; i++) {
        const wrapper = document.createElement('div');
        wrapper.style.position = 'absolute';
        wrapper.style.left = '0px';
        wrapper.style.top = '0px';
        wrapper.style.transition = 'none !important';
        
        const el = document.createElement('div');
        el.style.transition = 'none !important';
        el.innerHTML = shapes[Math.floor(Math.random() * shapes.length)];
        const svg = el.firstElementChild;
        svg.style.transition = 'none !important';
        
        const size = Math.random() * 20 + 15; 
        svg.style.width = `${size}px`;
        svg.style.height = `${size}px`;
        svg.style.color = colors[Math.floor(Math.random() * colors.length)];
        
        wrapper.appendChild(el);
        container.appendChild(wrapper);

        // Track logical position
        const posX = Math.random() * window.innerWidth;
        const posY = Math.random() * window.innerHeight;
        
        const obj = {
            wrapper,
            inner: el,
            x: posX,
            y: posY,
            baseX: posX,
            baseY: posY,
            vx: 0,
            vy: 0,
            driftX: (Math.random() - 0.5) * 0.5,
            driftY: (Math.random() - 0.5) * 0.5,
            mass: Math.random() * 1.5 + 0.5
        };
        
        floatingElements.push(obj);

        // Add base rotation animation
        el.animate([
            { transform: `rotate(0deg)` },
            { transform: `rotate(${Math.random() > 0.5 ? 360 : -360}deg)` }
        ], {
            duration: (Math.random() * 20 + 20) * 1000,
            iterations: Infinity,
            easing: 'linear'
        });
    }

    let mouseX = -1000;
    let mouseY = -1000;
    
    document.addEventListener('mousemove', (e) => {
        // We need coordinates relative to the hero section container
        const rect = container.getBoundingClientRect();
        mouseX = e.clientX - rect.left;
        mouseY = e.clientY - rect.top;
    });
    
    document.addEventListener('mouseleave', () => {
        mouseX = -1000;
        mouseY = -1000;
    });

    function update() {
        floatingElements.forEach(item => {
            // Apply constant slow drift
            item.baseX += item.driftX;
            item.baseY += item.driftY;
            
            // Loop around screen softly (Teleport physical coordinates with the anchor)
            if (item.baseX > window.innerWidth + 50) { item.baseX = -50; item.x = -50; }
            if (item.baseX < -50) { item.baseX = window.innerWidth + 50; item.x = window.innerWidth + 50; }
            if (item.baseY > window.innerHeight + 50) { item.baseY = -50; item.y = -50; }
            if (item.baseY < -50) { item.baseY = window.innerHeight + 50; item.y = window.innerHeight + 50; }
            
            // Calculate repulsion physics
            const dx = item.x - mouseX;
            const dy = item.y - mouseY;
            const distance = Math.sqrt(dx * dx + dy * dy);
            
            const interactionRadius = 150; // pixels
            
            if (distance < interactionRadius && distance > 1) { // Prevent div by zero or extreme proximity
                // Slower Space-like Fly away force
                const force = (interactionRadius - distance) / interactionRadius; 
                const repelStrength = 0.3 * force; // Very calm push
                
                // direction vector
                const dirX = dx / distance;
                const dirY = dy / distance;
                
                item.vx += (dirX * repelStrength) / item.mass;
                item.vy += (dirY * repelStrength) / item.mass;
            }
            
            // Hard speed limit to prevent flying across screen
            const maxSpeed = 3.5;
            if (item.vx > maxSpeed) item.vx = maxSpeed;
            if (item.vx < -maxSpeed) item.vx = -maxSpeed;
            if (item.vy > maxSpeed) item.vy = maxSpeed;
            if (item.vy < -maxSpeed) item.vy = -maxSpeed;
            
            // Apply velocity
            item.x += item.vx;
            item.y += item.vy;
            
            // Return to natural drift path via very gentle spring force (zero-G feel)
            const springX = item.baseX - item.x;
            const springY = item.baseY - item.y;
            item.vx += springX * 0.002; // Extremely weak spring constant for zero-G feel
            item.vy += springY * 0.002;
            
            // Space Friction (less harsh friction so they glide smoothly)
            item.vx *= 0.94;
            item.vy *= 0.94;
            
            // Apply render
            item.wrapper.style.transform = `translate(${item.x}px, ${item.y}px)`;
        });
        
        requestAnimationFrame(update);
    }
    
    update();
});
