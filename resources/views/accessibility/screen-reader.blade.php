{{-- Screen Reader Optimizations --}}
<div class="sr-only" aria-live="polite" id="page-announcements"></div>
<div class="sr-only" aria-live="assertive" id="urgent-announcements"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize screen reader optimizations
    initializeScreenReaderSupport();

    function initializeScreenReaderSupport() {
        // Add ARIA landmarks if missing
        addMissingLandmarks();

        // Enhance tables for screen readers
        enhanceTablesForScreenReaders();

        // Add progress indicators
        addProgressIndicators();

        // Enhance form labels and descriptions
        enhanceFormAccessibility();

        // Add status announcements
        addStatusAnnouncements();

        // Handle dynamic content changes
        handleDynamicContentChanges();
    }

    function addMissingLandmarks() {
        // Add main landmark if missing
        if (!document.querySelector('main, [role="main"]')) {
            const mainContent = document.querySelector('.container, .content, #content');
            if (mainContent) {
                mainContent.setAttribute('role', 'main');
                mainContent.setAttribute('aria-label', 'Main content');
            }
        }

        // Add navigation landmark
        const nav = document.querySelector('nav:not([role])');
        if (nav) {
            nav.setAttribute('role', 'navigation');
            nav.setAttribute('aria-label', 'Main navigation');
        }

        // Add banner landmark
        const header = document.querySelector('header:not([role])');
        if (header) {
            header.setAttribute('role', 'banner');
        }

        // Add contentinfo landmark
        const footer = document.querySelector('footer:not([role])');
        if (footer) {
            footer.setAttribute('role', 'contentinfo');
        }

        // Add search landmark
        const searchForm = document.querySelector('form[action*="search"], form:has(input[type="search"])');
        if (searchForm) {
            searchForm.setAttribute('role', 'search');
            searchForm.setAttribute('aria-label', 'Search');
        }
    }

    function enhanceTablesForScreenReaders() {
        const tables = document.querySelectorAll('table');

        tables.forEach(table => {
            // Add table role if missing
            if (!table.getAttribute('role')) {
                table.setAttribute('role', 'table');
            }

            // Add caption if missing
            if (!table.querySelector('caption')) {
                const caption = document.createElement('caption');
                caption.className = 'sr-only';
                caption.textContent = table.getAttribute('aria-label') || 'Data table';
                table.insertBefore(caption, table.firstChild);
            }

            // Enhance headers
            const headers = table.querySelectorAll('th');
            headers.forEach((header, index) => {
                if (!header.getAttribute('scope')) {
                    // Determine scope based on position
                    const row = header.closest('tr');
                    const isFirstRow = row === table.querySelector('tr');
                    header.setAttribute('scope', isFirstRow ? 'col' : 'row');
                }

                // Add sort indicators for sortable columns
                if (header.querySelector('.sort-indicator, [class*="sort"]')) {
                    const sortState = getSortState(header);
                    header.setAttribute('aria-sort', sortState);

                    // Add sort button if missing
                    if (!header.querySelector('button')) {
                        wrapHeaderContentInButton(header);
                    }
                }
            });

            // Add row and column count information
            const rows = table.querySelectorAll('tbody tr, tr').length;
            const cols = table.querySelectorAll('th, td').length / rows;
            table.setAttribute('aria-describedby', addTableDescription(table, rows, cols));
        });
    }

    function getSortState(header) {
        const classList = header.classList;
        if (classList.contains('sort-asc') || classList.contains('sorted-asc')) {
            return 'ascending';
        } else if (classList.contains('sort-desc') || classList.contains('sorted-desc')) {
            return 'descending';
        }
        return 'none';
    }

    function wrapHeaderContentInButton(header) {
        const content = header.innerHTML;
        const button = document.createElement('button');
        button.innerHTML = content;
        button.className = 'table-sort-button';
        button.setAttribute('aria-label', `Sort by ${header.textContent.trim()}`);
        header.innerHTML = '';
        header.appendChild(button);
    }

    function addTableDescription(table, rows, cols) {
        const descId = 'table-desc-' + Math.random().toString(36).substr(2, 9);
        const description = document.createElement('div');
        description.id = descId;
        description.className = 'sr-only';
        description.textContent = `Table with ${rows} rows and ${cols} columns`;
        table.parentNode.insertBefore(description, table);
        return descId;
    }

    function addProgressIndicators() {
        // Add progress indicators for loading states
        const loadingElements = document.querySelectorAll('.loading, [data-loading]');

        loadingElements.forEach(element => {
            if (!element.getAttribute('aria-busy')) {
                element.setAttribute('aria-busy', 'true');
                element.setAttribute('aria-live', 'polite');
                element.setAttribute('aria-label', 'Loading content');
            }
        });

        // Monitor for progress bars
        const progressBars = document.querySelectorAll('.progress-bar, [role="progressbar"]');
        progressBars.forEach(bar => {
            if (!bar.getAttribute('role')) {
                bar.setAttribute('role', 'progressbar');
            }

            // Add value attributes if missing
            if (!bar.getAttribute('aria-valuenow')) {
                const percentage = extractPercentage(bar);
                bar.setAttribute('aria-valuenow', percentage);
                bar.setAttribute('aria-valuemin', '0');
                bar.setAttribute('aria-valuemax', '100');
                bar.setAttribute('aria-label', `Progress: ${percentage}%`);
            }
        });
    }

    function extractPercentage(element) {
        // Try to extract percentage from various sources
        const style = element.style.width || element.style.value;
        const textContent = element.textContent;
        const dataValue = element.dataset.value;

        let percentage = 0;

        if (style && style.includes('%')) {
            percentage = parseInt(style);
        } else if (textContent && textContent.includes('%')) {
            percentage = parseInt(textContent);
        } else if (dataValue) {
            percentage = parseInt(dataValue);
        }

        return Math.max(0, Math.min(100, percentage));
    }

    function enhanceFormAccessibility() {
        const forms = document.querySelectorAll('form');

        forms.forEach(form => {
            // Add form labels and descriptions
            const inputs = form.querySelectorAll('input, select, textarea');

            inputs.forEach(input => {
                // Ensure proper labeling
                ensureProperLabeling(input);

                // Add error announcements
                addErrorAnnouncements(input);

                // Add required field indicators
                addRequiredFieldIndicators(input);

                // Add input format hints
                addInputFormatHints(input);
            });

            // Add form submission feedback
            addFormSubmissionFeedback(form);
        });
    }

    function ensureProperLabeling(input) {
        const existingLabel = document.querySelector(`label[for="${input.id}"]`);

        if (!existingLabel && !input.getAttribute('aria-label') && !input.getAttribute('aria-labelledby')) {
            // Try to find nearby text that could serve as a label
            const nearbyText = findNearbyLabelText(input);
            if (nearbyText) {
                const labelId = 'label-' + Math.random().toString(36).substr(2, 9);
                nearbyText.id = labelId;
                input.setAttribute('aria-labelledby', labelId);
            } else {
                // Create a label based on input attributes
                const labelText = input.name || input.placeholder || input.type;
                input.setAttribute('aria-label', labelText);
            }
        }
    }

    function findNearbyLabelText(input) {
        // Look for text in parent elements, siblings, etc.
        const parent = input.closest('.form-group, .field, .input-group');
        if (parent) {
            const labelText = parent.querySelector('.label, .field-label, label');
            return labelText;
        }
        return null;
    }

    function addErrorAnnouncements(input) {
        const form = input.closest('form');

        input.addEventListener('invalid', function() {
            const errorMessage = this.validationMessage || 'This field has an error';
            announceToScreenReader(errorMessage, 'assertive');
        });

        // Watch for validation changes
        input.addEventListener('input', function() {
            if (this.validity.valid && this.getAttribute('aria-invalid') === 'true') {
                this.removeAttribute('aria-invalid');
                announceToScreenReader('Error resolved', 'polite');
            }
        });
    }

    function addRequiredFieldIndicators(input) {
        if (input.hasAttribute('required')) {
            const label = document.querySelector(`label[for="${input.id}"]`) ||
                         document.getElementById(input.getAttribute('aria-labelledby'));

            if (label && !label.querySelector('.required-indicator')) {
                const indicator = document.createElement('span');
                indicator.className = 'required-indicator sr-only';
                indicator.textContent = ' (required)';
                label.appendChild(indicator);
            }

            input.setAttribute('aria-required', 'true');
        }
    }

    function addInputFormatHints(input) {
        const type = input.type;
        const pattern = input.pattern;

        let hint = '';

        switch(type) {
            case 'email':
                hint = 'Enter a valid email address';
                break;
            case 'url':
                hint = 'Enter a valid URL';
                break;
            case 'tel':
                hint = 'Enter a phone number';
                break;
            case 'password':
                hint = 'Enter your password';
                break;
        }

        if (pattern) {
            hint += ` Following pattern: ${pattern}`;
        }

        if (hint && !input.getAttribute('aria-describedby')) {
            const hintId = 'hint-' + Math.random().toString(36).substr(2, 9);
            const hintElement = document.createElement('div');
            hintElement.id = hintId;
            hintElement.className = 'sr-only';
            hintElement.textContent = hint;
            input.parentNode.insertBefore(hintElement, input.nextSibling);
            input.setAttribute('aria-describedby', hintId);
        }
    }

    function addFormSubmissionFeedback(form) {
        form.addEventListener('submit', function() {
            announceToScreenReader('Form submitted, please wait...', 'assertive');
        });

        // Handle form success/error responses
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) { // Element node
                        if (node.classList.contains('success') || node.classList.contains('alert-success')) {
                            announceToScreenReader('Form submitted successfully', 'assertive');
                        } else if (node.classList.contains('error') || node.classList.contains('alert-error')) {
                            announceToScreenReader('Form submission failed, please check for errors', 'assertive');
                        }
                    }
                });
            });
        });

        observer.observe(form, { childList: true, subtree: true });
    }

    function addStatusAnnouncements() {
        // Monitor for status changes
        const statusElements = document.querySelectorAll('.status, .alert, .notification, [role="status"], [role="alert"]');

        statusElements.forEach(element => {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList' || mutation.type === 'characterData') {
                        const text = element.textContent.trim();
                        if (text) {
                            const urgency = element.matches('[role="alert"], .error, .danger') ? 'assertive' : 'polite';
                            announceToScreenReader(text, urgency);
                        }
                    }
                });
            });

            observer.observe(element, {
                childList: true,
                subtree: true,
                characterData: true
            });
        });
    }

    function handleDynamicContentChanges() {
        // Monitor for new content being added
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) { // Element node
                        // Re-apply accessibility enhancements to new content
                        enhanceNewContent(node);
                    }
                });
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    function enhanceNewContent(element) {
        // Apply all enhancements to newly added content
        if (element.querySelector) {
            // Tables
            const tables = element.querySelectorAll('table');
            tables.forEach(table => {
                // Re-apply table enhancements
                enhanceTablesForScreenReaders();
            });

            // Forms
            const forms = element.querySelectorAll('form');
            forms.forEach(form => {
                enhanceFormAccessibility();
            });

            // Progress indicators
            addProgressIndicators();
        }
    }

    function announceToScreenReader(message, priority = 'polite') {
        const announcer = document.getElementById(
            priority === 'assertive' ? 'urgent-announcements' : 'page-announcements'
        );

        if (announcer) {
            announcer.textContent = message;

            // Clear after announcement
            setTimeout(() => {
                announcer.textContent = '';
            }, 1000);
        }
    }

    // Expose functions globally for use by other scripts
    window.accessibilityHelpers = {
        announceToScreenReader,
        enhanceNewContent,
        addProgressIndicators
    };
});
</script>

{{-- Screen reader specific styles --}}
<style>
    /* Enhanced screen reader support */
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

    /* Focus management for screen readers */
    [tabindex="-1"]:focus {
        outline: none !important;
    }

    /* Table sort buttons */
    .table-sort-button {
        background: none;
        border: none;
        width: 100%;
        text-align: inherit;
        font: inherit;
        color: inherit;
        cursor: pointer;
    }

    .table-sort-button:focus {
        outline: 2px solid #005fcc;
        outline-offset: 2px;
    }

    /* Required field indicators */
    .required-indicator {
        color: #d32f2f;
        font-weight: bold;
    }

    /* Status announcements styling */
    #page-announcements,
    #urgent-announcements {
        position: absolute;
        left: -10000px;
        width: 1px;
        height: 1px;
        overflow: hidden;
    }

    /* High contrast mode adjustments */
    @media (prefers-contrast: high) {
        .table-sort-button:focus {
            outline: 3px solid currentColor;
        }

        [aria-invalid="true"] {
            border: 2px solid #d32f2f !important;
        }

        [aria-required="true"] {
            border-left: 4px solid #1976d2;
        }
    }

    /* Reduced motion preferences */
    @media (prefers-reduced-motion: reduce) {
        .loading {
            animation: none !important;
        }

        .fade,
        .collapse,
        .modal {
            transition: none !important;
        }
    }
</style>
