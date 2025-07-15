{{-- Keyboard Navigation JavaScript --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Global keyboard shortcuts
    const shortcuts = @json($shortcuts);

    // Keyboard navigation handler
    document.addEventListener('keydown', function(e) {
        // Handle global shortcuts
        handleGlobalShortcuts(e);

        // Handle table navigation
        if (e.target.closest('[role="grid"], table')) {
            handleTableNavigation(e);
        }

        // Handle form navigation
        if (e.target.closest('form')) {
            handleFormNavigation(e);
        }

        // Handle dropdown navigation
        if (e.target.closest('[role="menu"], [role="listbox"]')) {
            handleDropdownNavigation(e);
        }
    });

    function handleGlobalShortcuts(e) {
        const key = getKeyString(e);

        switch(key) {
            case 'alt+m':
                e.preventDefault();
                focusElement('[role="navigation"], nav', 'Main navigation');
                break;
            case 'alt+s':
                e.preventDefault();
                focusElement('input[type="search"], [role="searchbox"]', 'Search');
                break;
            case 'alt+h':
                e.preventDefault();
                window.location.href = '/';
                break;
            case 'alt+l':
                e.preventDefault();
                window.location.href = '/login';
                break;
            case 'escape':
                e.preventDefault();
                closeModalsAndDropdowns();
                break;
        }
    }

    function handleTableNavigation(e) {
        const table = e.target.closest('[role="grid"], table');
        const currentCell = e.target.closest('td, th, [role="gridcell"]');

        if (!currentCell) return;

        const rows = Array.from(table.querySelectorAll('tr'));
        const currentRow = currentCell.closest('tr');
        const currentRowIndex = rows.indexOf(currentRow);
        const cells = Array.from(currentRow.querySelectorAll('td, th, [role="gridcell"]'));
        const currentCellIndex = cells.indexOf(currentCell);

        switch(e.key) {
            case 'ArrowUp':
                e.preventDefault();
                if (currentRowIndex > 0) {
                    const prevRow = rows[currentRowIndex - 1];
                    const prevCells = prevRow.querySelectorAll('td, th, [role="gridcell"]');
                    if (prevCells[currentCellIndex]) {
                        focusCell(prevCells[currentCellIndex]);
                        announceToScreenReader(`Row ${currentRowIndex}, Column ${currentCellIndex + 1}`);
                    }
                }
                break;
            case 'ArrowDown':
                e.preventDefault();
                if (currentRowIndex < rows.length - 1) {
                    const nextRow = rows[currentRowIndex + 1];
                    const nextCells = nextRow.querySelectorAll('td, th, [role="gridcell"]');
                    if (nextCells[currentCellIndex]) {
                        focusCell(nextCells[currentCellIndex]);
                        announceToScreenReader(`Row ${currentRowIndex + 2}, Column ${currentCellIndex + 1}`);
                    }
                }
                break;
            case 'ArrowLeft':
                e.preventDefault();
                if (currentCellIndex > 0) {
                    focusCell(cells[currentCellIndex - 1]);
                    announceToScreenReader(`Column ${currentCellIndex}`);
                }
                break;
            case 'ArrowRight':
                e.preventDefault();
                if (currentCellIndex < cells.length - 1) {
                    focusCell(cells[currentCellIndex + 1]);
                    announceToScreenReader(`Column ${currentCellIndex + 2}`);
                }
                break;
            case 'Home':
                e.preventDefault();
                focusCell(cells[0]);
                announceToScreenReader('First column');
                break;
            case 'End':
                e.preventDefault();
                focusCell(cells[cells.length - 1]);
                announceToScreenReader('Last column');
                break;
            case 'PageUp':
                e.preventDefault();
                if (rows[0]) {
                    const firstCells = rows[0].querySelectorAll('td, th, [role="gridcell"]');
                    if (firstCells[currentCellIndex]) {
                        focusCell(firstCells[currentCellIndex]);
                        announceToScreenReader('First row');
                    }
                }
                break;
            case 'PageDown':
                e.preventDefault();
                if (rows[rows.length - 1]) {
                    const lastCells = rows[rows.length - 1].querySelectorAll('td, th, [role="gridcell"]');
                    if (lastCells[currentCellIndex]) {
                        focusCell(lastCells[currentCellIndex]);
                        announceToScreenReader('Last row');
                    }
                }
                break;
            case 'Enter':
                e.preventDefault();
                activateCell(currentCell);
                break;
            case ' ':
                e.preventDefault();
                toggleCellSelection(currentCell);
                break;
        }
    }

    function handleFormNavigation(e) {
        const form = e.target.closest('form');

        switch(e.key) {
            case 'Tab':
                // Enhanced tab navigation with proper focus management
                if (!e.shiftKey) {
                    const nextField = getNextFormField(e.target, form);
                    if (nextField && nextField !== e.target) {
                        e.preventDefault();
                        nextField.focus();
                    }
                } else {
                    const prevField = getPreviousFormField(e.target, form);
                    if (prevField && prevField !== e.target) {
                        e.preventDefault();
                        prevField.focus();
                    }
                }
                break;
            case 'Enter':
                // Submit form only from submit buttons or if explicitly allowed
                if (e.target.type === 'submit' || e.target.closest('[data-submit-on-enter]')) {
                    form.submit();
                } else if (e.target.type !== 'textarea') {
                    e.preventDefault();
                    const submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
                    if (submitButton) {
                        submitButton.focus();
                    }
                }
                break;
            case 'Escape':
                e.preventDefault();
                const cancelButton = form.querySelector('[data-cancel], button[type="button"]');
                if (cancelButton) {
                    cancelButton.click();
                } else {
                    announceToScreenReader('Form cancelled');
                }
                break;
        }
    }

    function handleDropdownNavigation(e) {
        const dropdown = e.target.closest('[role="menu"], [role="listbox"]');
        const items = Array.from(dropdown.querySelectorAll('[role="menuitem"], [role="option"]'));
        const currentIndex = items.indexOf(e.target);

        switch(e.key) {
            case 'ArrowDown':
                e.preventDefault();
                const nextIndex = (currentIndex + 1) % items.length;
                items[nextIndex].focus();
                announceToScreenReader(items[nextIndex].textContent);
                break;
            case 'ArrowUp':
                e.preventDefault();
                const prevIndex = currentIndex > 0 ? currentIndex - 1 : items.length - 1;
                items[prevIndex].focus();
                announceToScreenReader(items[prevIndex].textContent);
                break;
            case 'Home':
                e.preventDefault();
                items[0].focus();
                announceToScreenReader(items[0].textContent + ' - First item');
                break;
            case 'End':
                e.preventDefault();
                items[items.length - 1].focus();
                announceToScreenReader(items[items.length - 1].textContent + ' - Last item');
                break;
            case 'Enter':
            case ' ':
                e.preventDefault();
                e.target.click();
                break;
            case 'Escape':
                e.preventDefault();
                const trigger = document.querySelector(`[aria-controls="${dropdown.id}"]`);
                if (trigger) {
                    trigger.focus();
                    closeDropdown(dropdown);
                }
                break;
        }
    }

    function getKeyString(e) {
        const parts = [];
        if (e.altKey) parts.push('alt');
        if (e.ctrlKey) parts.push('ctrl');
        if (e.shiftKey) parts.push('shift');
        if (e.metaKey) parts.push('meta');
        parts.push(e.key.toLowerCase());
        return parts.join('+');
    }

    function focusElement(selector, description) {
        const element = document.querySelector(selector);
        if (element) {
            element.focus();
            if (description) {
                announceToScreenReader(`Focused on ${description}`);
            }
        }
    }

    function focusCell(cell) {
        if (cell.querySelector('button, input, select, textarea, a')) {
            cell.querySelector('button, input, select, textarea, a').focus();
        } else {
            cell.setAttribute('tabindex', '0');
            cell.focus();
        }
    }

    function activateCell(cell) {
        const interactive = cell.querySelector('button, a, input[type="checkbox"], input[type="radio"]');
        if (interactive) {
            interactive.click();
            announceToScreenReader('Activated');
        }
    }

    function toggleCellSelection(cell) {
        const checkbox = cell.querySelector('input[type="checkbox"]');
        if (checkbox) {
            checkbox.checked = !checkbox.checked;
            announceToScreenReader(checkbox.checked ? 'Selected' : 'Deselected');
        }
    }

    function closeModalsAndDropdowns() {
        // Close modals
        const modals = document.querySelectorAll('[role="dialog"], .modal');
        modals.forEach(modal => {
            const closeButton = modal.querySelector('[data-dismiss], .close, [aria-label*="close"]');
            if (closeButton) {
                closeButton.click();
            }
        });

        // Close dropdowns
        const dropdowns = document.querySelectorAll('[aria-expanded="true"]');
        dropdowns.forEach(trigger => {
            trigger.setAttribute('aria-expanded', 'false');
            const dropdown = document.getElementById(trigger.getAttribute('aria-controls'));
            if (dropdown) {
                dropdown.style.display = 'none';
            }
        });

        announceToScreenReader('Closed open dialogs and dropdowns');
    }

    function closeDropdown(dropdown) {
        dropdown.style.display = 'none';
        const trigger = document.querySelector(`[aria-controls="${dropdown.id}"]`);
        if (trigger) {
            trigger.setAttribute('aria-expanded', 'false');
        }
    }

    function getNextFormField(current, form) {
        const fields = Array.from(form.querySelectorAll('input, select, textarea, button'));
        const visibleFields = fields.filter(field => {
            return field.offsetWidth > 0 && field.offsetHeight > 0 && !field.disabled;
        });
        const currentIndex = visibleFields.indexOf(current);
        return visibleFields[currentIndex + 1] || visibleFields[0];
    }

    function getPreviousFormField(current, form) {
        const fields = Array.from(form.querySelectorAll('input, select, textarea, button'));
        const visibleFields = fields.filter(field => {
            return field.offsetWidth > 0 && field.offsetHeight > 0 && !field.disabled;
        });
        const currentIndex = visibleFields.indexOf(current);
        return visibleFields[currentIndex - 1] || visibleFields[visibleFields.length - 1];
    }

    function announceToScreenReader(message) {
        const announcer = getOrCreateAnnouncer();
        announcer.textContent = message;

        // Clear after announcement
        setTimeout(() => {
            announcer.textContent = '';
        }, 1000);
    }

    function getOrCreateAnnouncer() {
        let announcer = document.getElementById('sr-announcer');
        if (!announcer) {
            announcer = document.createElement('div');
            announcer.id = 'sr-announcer';
            announcer.setAttribute('aria-live', 'polite');
            announcer.setAttribute('aria-atomic', 'true');
            announcer.style.cssText = 'position: absolute; left: -10000px; width: 1px; height: 1px; overflow: hidden;';
            document.body.appendChild(announcer);
        }
        return announcer;
    }

    // Skip link functionality
    const skipLinks = document.querySelectorAll('a[href^="#"]');
    skipLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href').substring(1);
            const target = document.getElementById(targetId);
            if (target) {
                e.preventDefault();
                target.setAttribute('tabindex', '-1');
                target.focus();
                announceToScreenReader(`Skipped to ${target.getAttribute('aria-label') || targetId}`);
            }
        });
    });

    // Enhanced focus indicators
    document.addEventListener('focusin', function(e) {
        e.target.setAttribute('data-user-focused', 'true');
    });

    document.addEventListener('focusout', function(e) {
        e.target.removeAttribute('data-user-focused');
    });

    // Announce page changes for SPA navigation
    let currentPath = window.location.pathname;
    const observer = new MutationObserver(function(mutations) {
        if (window.location.pathname !== currentPath) {
            currentPath = window.location.pathname;
            const title = document.title || 'Page changed';
            announceToScreenReader(`Navigated to ${title}`);
        }
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
});

// Add custom CSS for accessibility
const accessibilityCSS = `
    /* High contrast focus indicators */
    [data-user-focused="true"] {
        outline: 3px solid #005fcc !important;
        outline-offset: 2px !important;
        box-shadow: 0 0 0 1px #ffffff !important;
    }

    /* Skip links */
    .skip-link {
        position: absolute;
        top: -40px;
        left: 6px;
        background: #000;
        color: #fff;
        padding: 8px;
        text-decoration: none;
        z-index: 1000;
        border-radius: 4px;
    }

    .skip-link:focus {
        top: 6px;
    }

    /* Screen reader only text */
    .sr-only {
        position: absolute !important;
        width: 1px !important;
        height: 1px !important;
        padding: 0 !important;
        margin: -1px !important;
        overflow: hidden !important;
        clip: rect(0, 0, 0, 0) !important;
        white-space: nowrap !important;
        border: 0 !important;
    }

    /* Reduced motion support */
    @media (prefers-reduced-motion: reduce) {
        * {
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
        }
    }

    /* High contrast mode support */
    @media (prefers-contrast: high) {
        * {
            border-color: currentColor !important;
        }

        button, input, select, textarea {
            border: 2px solid currentColor !important;
        }
    }
`;

// Inject accessibility CSS
const style = document.createElement('style');
style.textContent = accessibilityCSS;
document.head.appendChild(style);
</script>
