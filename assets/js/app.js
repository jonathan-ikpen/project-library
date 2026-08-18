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
