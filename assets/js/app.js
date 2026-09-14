// assets/js/app.js

document.addEventListener('DOMContentLoaded', () => {
    
    // 1. Toast Dismissal
    const toasts = document.querySelectorAll('.toast');
    toasts.forEach(toast => {
        // Only append close button if it doesn't already have one
        if (!toast.querySelector('.toast-close')) {
            const closeBtn = document.createElement('span');
            closeBtn.className = 'toast-close';
            closeBtn.innerHTML = '&times;';
            closeBtn.onclick = () => toast.remove();
            toast.appendChild(closeBtn);
        }
    });

    // Password Eye Toggle
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    passwordInputs.forEach(input => {
        // Skip if already wrapped
        if (input.parentElement.classList.contains('password-wrapper')) return;

        // Wrap input
        const wrapper = document.createElement('div');
        wrapper.className = 'password-wrapper';
        wrapper.style.position = 'relative';
        wrapper.style.display = 'block';
        wrapper.style.width = '100%';
        wrapper.style.marginBottom = window.getComputedStyle(input).marginBottom;
        
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);

        // Ensure input uses full width of wrapper and has padding for icon
        input.style.width = '100%';
        input.style.paddingRight = '48px';
        input.style.marginBottom = '0';

        // Create toggle button
        const toggleBtn = document.createElement('button');
        toggleBtn.type = 'button';
        toggleBtn.className = 'password-toggle-btn';
        toggleBtn.style.position = 'absolute';
        toggleBtn.style.right = '16px';
        toggleBtn.style.top = '50%';
        toggleBtn.style.transform = 'translateY(-50%)';
        toggleBtn.style.background = 'transparent';
        toggleBtn.style.border = 'none';
        toggleBtn.style.cursor = 'pointer';
        toggleBtn.style.padding = '0';
        toggleBtn.style.color = 'var(--text-secondary)';
        toggleBtn.style.display = 'flex';
        toggleBtn.style.alignItems = 'center';
        toggleBtn.style.justifyContent = 'center';
        
        // SVGs for eye and eye-off
        const eyeIcon = `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>`;
        const eyeOffIcon = `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>`;

        toggleBtn.innerHTML = eyeIcon;

        toggleBtn.addEventListener('click', (e) => {
            e.preventDefault();
            if (input.type === 'password') {
                input.type = 'text';
                toggleBtn.innerHTML = eyeOffIcon;
            } else {
                input.type = 'password';
                toggleBtn.innerHTML = eyeIcon;
            }
        });

        wrapper.appendChild(toggleBtn);
    });

    // 2. Custom Select Implementation
    const selects = document.querySelectorAll('select:not([multiple])');
    selects.forEach(select => {
        // Skip if already wrapped
        if (select.parentElement.classList.contains('custom-select-wrapper')) return;

        // Wrap the native select
        const wrapper = document.createElement('div');
        wrapper.className = 'custom-select-wrapper';
        if(select.style.marginBottom) {
            wrapper.style.marginBottom = select.style.marginBottom;
            select.style.marginBottom = '0';
        }
        if(select.style.width) {
            wrapper.style.width = select.style.width;
        }

        select.parentNode.insertBefore(wrapper, select);
        wrapper.appendChild(select);

        // Create the visible UI
        const customSelect = document.createElement('div');
        customSelect.className = 'custom-select';
        
        const selectedOptionText = select.options[select.selectedIndex]?.text || '';
        const customSelectTriggerText = document.createElement('span');
        customSelectTriggerText.textContent = selectedOptionText;
        customSelectTriggerText.style.whiteSpace = 'nowrap';
        customSelectTriggerText.style.overflow = 'hidden';
        customSelectTriggerText.style.textOverflow = 'ellipsis';
        customSelectTriggerText.style.marginRight = '12px';
        customSelect.appendChild(customSelectTriggerText);
        wrapper.appendChild(customSelect);

        // Create options pane
        const customOptions = document.createElement('div');
        customOptions.className = 'custom-options';
        
        Array.from(select.options).forEach(option => {
            const customOption = document.createElement('div');
            customOption.className = 'custom-option';
            customOption.textContent = option.text;
            customOption.dataset.value = option.value;
            
            customOption.addEventListener('click', () => {
                select.value = option.value;
                customSelectTriggerText.textContent = option.text;
                // Trigger change event for any native listeners
                select.dispatchEvent(new Event('change'));
                wrapper.classList.remove('open');
                customSelect.classList.remove('open');
            });
            customOptions.appendChild(customOption);
        });
        wrapper.appendChild(customOptions);

        // Toggle dropdown
        customSelect.addEventListener('click', (e) => {
            e.stopPropagation();
            // Close all others
            document.querySelectorAll('.custom-select-wrapper.open').forEach(other => {
                if (other !== wrapper) {
                    other.classList.remove('open');
                    other.querySelector('.custom-select').classList.remove('open');
                }
            });
            wrapper.classList.toggle('open');
            customSelect.classList.toggle('open');
        });
    });

    // Close selects when clicking outside
    document.addEventListener('click', () => {
        document.querySelectorAll('.custom-select-wrapper.open').forEach(wrapper => {
            wrapper.classList.remove('open');
            wrapper.querySelector('.custom-select').classList.remove('open');
        });
    });

    // 3. Grid / List View Toggle
    const gridContainer = document.querySelector('.grid-auto-fit');
    const toggleGridBtn = document.getElementById('view-grid');
    const toggleListBtn = document.getElementById('view-list');

    if (gridContainer && toggleGridBtn && toggleListBtn) {
        // Load preference
        const savedView = localStorage.getItem('dpls-view-preference');
        if (savedView === 'grid') {
            gridContainer.classList.remove('list-view');
            toggleGridBtn.style.opacity = '1';
            toggleListBtn.style.opacity = '0.5';
        } else {
            // Default to list
            gridContainer.classList.add('list-view');
            toggleListBtn.style.opacity = '1';
            toggleGridBtn.style.opacity = '0.5';
        }

        toggleGridBtn.addEventListener('click', () => {
            gridContainer.classList.remove('list-view');
            localStorage.setItem('dpls-view-preference', 'grid');
            toggleGridBtn.style.opacity = '1';
            toggleListBtn.style.opacity = '0.5';
        });

        toggleListBtn.addEventListener('click', () => {
            gridContainer.classList.add('list-view');
            localStorage.setItem('dpls-view-preference', 'list');
            toggleListBtn.style.opacity = '1';
            toggleGridBtn.style.opacity = '0.5';
        });
    }

    // 4. Dark Mode Toggle
    const themeToggle = document.getElementById('theme-toggle');
    if (themeToggle) {
        const moonIcon = document.getElementById('moon-icon');
        const sunIcon = document.getElementById('sun-icon');
        
        const updateThemeIcon = (theme) => {
            if (theme === 'dark') {
                moonIcon.style.display = 'none';
                sunIcon.style.display = 'block';
            } else {
                sunIcon.style.display = 'none';
                moonIcon.style.display = 'block';
            }
        };

        const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
        updateThemeIcon(currentTheme);

        themeToggle.addEventListener('click', () => {
            let theme = document.documentElement.getAttribute('data-theme') || 'light';
            let newTheme = theme === 'dark' ? 'light' : 'dark';
            
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('dpls-theme', newTheme);
            updateThemeIcon(newTheme);
        });
    }

    // 5. Mobile Drawer Toggle
    const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
    const mobileMenuClose = document.getElementById('mobile-menu-close');
    const mobileDrawer = document.getElementById('mobile-drawer');
    const mobileOverlay = document.getElementById('mobile-overlay');

    if (mobileMenuToggle && mobileDrawer && mobileOverlay) {
        const closeDrawer = () => {
            mobileDrawer.classList.remove('open');
            mobileOverlay.classList.remove('active');
            document.body.style.overflow = '';
        };

        const openDrawer = () => {
            mobileDrawer.classList.add('open');
            mobileOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        };

        mobileMenuToggle.addEventListener('click', openDrawer);
        if (mobileMenuClose) mobileMenuClose.addEventListener('click', closeDrawer);
        mobileOverlay.addEventListener('click', closeDrawer);
    }
});
