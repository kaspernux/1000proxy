# Component Library Style Guide

## Overview

This comprehensive design system provides a consistent foundation for building user interfaces across the ProxyAdmin application. It includes design tokens, reusable components, and accessibility guidelines to ensure a cohesive and professional user experience.

## Design Principles

### 1. Consistency
- All components follow the same design patterns and spacing rules
- Consistent color palette and typography across all interfaces
- Standardized interaction patterns and feedback mechanisms

### 2. Accessibility
- WCAG 2.1 AA compliance for all components
- Keyboard navigation support
- Screen reader optimization
- High contrast mode support
- Proper focus management

### 3. Scalability
- Modular component architecture
- CSS custom properties for easy theming
- Responsive design patterns
- Performance-optimized implementations

### 4. Maintainability
- Clear component documentation
- Standardized naming conventions
- Separation of concerns (design tokens, components, utilities)
- Version-controlled design system

## Design Tokens

Design tokens are the foundational elements of our design system. They define colors, typography, spacing, and other design decisions in a centralized location.

### Color Palette

#### Primary Colors
```css
--color-primary-500: #3b82f6  /* Main brand color */
--color-primary-600: #2563eb  /* Hover states */
--color-primary-700: #1d4ed8  /* Active states */
```

#### Semantic Colors
```css
--color-success-600: #16a34a  /* Success states */
--color-warning-600: #d97706  /* Warning states */
--color-error-600: #dc2626    /* Error states */
--color-info-600: #0284c7     /* Information states */
```

#### Neutral Colors
```css
--color-text: var(--color-neutral-900)           /* Primary text */
--color-text-secondary: var(--color-neutral-700) /* Secondary text */
--color-text-tertiary: var(--color-neutral-500)  /* Tertiary text */
```

### Typography

#### Font Families
- **Sans-serif**: Inter (primary UI font)
- **Monospace**: Fira Code (code and data display)
- **Display**: Cal Sans (headings and emphasis)

#### Font Sizes
```css
--font-size-xs: 0.75rem   /* 12px - Small text */
--font-size-sm: 0.875rem  /* 14px - Body text */
--font-size-base: 1rem    /* 16px - Default size */
--font-size-lg: 1.125rem  /* 18px - Large text */
--font-size-xl: 1.25rem   /* 20px - Headings */
```

#### Font Weights
```css
--font-weight-normal: 400    /* Regular text */
--font-weight-medium: 500    /* Emphasized text */
--font-weight-semibold: 600  /* Subheadings */
--font-weight-bold: 700      /* Headings */
```

### Spacing

The spacing system is based on a 4px base unit:

```css
--spacing-1: 0.25rem   /* 4px */
--spacing-2: 0.5rem    /* 8px */
--spacing-3: 0.75rem   /* 12px */
--spacing-4: 1rem      /* 16px */
--spacing-5: 1.25rem   /* 20px */
--spacing-6: 1.5rem    /* 24px */
```

### Border Radius
```css
--border-radius-sm: 0.125rem   /* 2px - Small elements */
--border-radius-base: 0.25rem  /* 4px - Default */
--border-radius-md: 0.375rem   /* 6px - Form controls */
--border-radius-lg: 0.5rem     /* 8px - Cards */
--border-radius-xl: 0.75rem    /* 12px - Large cards */
```

## Component Library

### Buttons

Buttons are the primary way users interact with the application. They come in various styles and sizes to suit different use cases.

#### Basic Usage
```html
<button class="btn btn-primary">Primary Button</button>
<button class="btn btn-secondary">Secondary Button</button>
<button class="btn btn-outline btn-primary">Outline Button</button>
```

#### Button Variants
- **Primary**: Main actions (save, submit, confirm)
- **Secondary**: Secondary actions (cancel, back)
- **Success**: Positive actions (approve, enable)
- **Warning**: Cautionary actions (modify, review)
- **Danger**: Destructive actions (delete, remove)
- **Ghost**: Subtle actions in toolbars
- **Link**: Text-like actions

#### Button Sizes
```html
<button class="btn btn-primary btn-xs">Extra Small</button>
<button class="btn btn-primary btn-sm">Small</button>
<button class="btn btn-primary">Default</button>
<button class="btn btn-primary btn-lg">Large</button>
<button class="btn btn-primary btn-xl">Extra Large</button>
```

#### Button States
```html
<button class="btn btn-primary">Normal</button>
<button class="btn btn-primary" disabled>Disabled</button>
<button class="btn btn-primary loading">Loading</button>
```

#### Accessibility Features
- Keyboard navigation support
- Focus indicators
- Screen reader compatible
- Disabled state handling
- Loading state announcements

### Form Controls

Form controls are essential for user input and data collection.

#### Text Inputs
```html
<div class="form-group">
    <label class="form-label required" for="username">Username</label>
    <input type="text" class="form-control" id="username" placeholder="Enter username">
    <div class="form-text">Choose a unique username</div>
</div>
```

#### Select Dropdown
```html
<div class="form-group">
    <label class="form-label" for="country">Country</label>
    <select class="form-control" id="country">
        <option value="">Select a country</option>
        <option value="us">United States</option>
        <option value="ca">Canada</option>
    </select>
</div>
```

#### Checkbox and Radio
```html
<div class="form-check">
    <input class="form-check-input" type="checkbox" id="terms">
    <label class="form-check-label" for="terms">
        I agree to the terms and conditions
    </label>
</div>

<div class="form-check">
    <input class="form-check-input" type="radio" name="plan" id="basic" value="basic">
    <label class="form-check-label" for="basic">Basic Plan</label>
</div>
```

#### Form Validation
```html
<div class="form-group">
    <label class="form-label required" for="email">Email</label>
    <input type="email" class="form-control is-invalid" id="email" value="invalid-email">
    <div class="invalid-feedback">Please enter a valid email address</div>
</div>

<div class="form-group">
    <label class="form-label" for="password">Password</label>
    <input type="password" class="form-control is-valid" id="password" value="validpassword">
    <div class="valid-feedback">Password meets requirements</div>
</div>
```

#### Accessibility Features
- Proper label associations
- Required field indicators
- Validation error announcements
- Keyboard navigation
- Screen reader optimization

### Cards

Cards are flexible containers for grouping related content.

#### Basic Card
```html
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Server Status</h3>
        <p class="card-subtitle">Real-time monitoring</p>
    </div>
    <div class="card-body">
        <p class="card-text">All servers are running normally with 99.9% uptime.</p>
    </div>
    <div class="card-footer">
        <button class="btn btn-primary btn-sm">View Details</button>
    </div>
</div>
```

#### Card with Actions
```html
<div class="card">
    <div class="card-body">
        <h4 class="card-title">Proxy Configuration</h4>
        <p class="card-text">Configure your proxy settings and protocols.</p>
        <div class="d-flex gap-2">
            <button class="btn btn-primary btn-sm">Configure</button>
            <button class="btn btn-secondary btn-sm">Reset</button>
        </div>
    </div>
</div>
```

### Tables

Tables display structured data in rows and columns.

#### Basic Table
```html
<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th scope="col">Server ID</th>
                <th scope="col">Location</th>
                <th scope="col">Status</th>
                <th scope="col">Actions</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>SRV-001</td>
                <td>New York</td>
                <td><span class="badge badge-success">Online</span></td>
                <td>
                    <button class="btn btn-sm btn-ghost">Edit</button>
                    <button class="btn btn-sm btn-ghost text-danger">Delete</button>
                </td>
            </tr>
        </tbody>
    </table>
</div>
```

#### Table Variants
```html
<!-- Striped rows -->
<table class="table table-striped">...</table>

<!-- Hover effect -->
<table class="table table-hover">...</table>

<!-- Bordered -->
<table class="table table-bordered">...</table>

<!-- Compact size -->
<table class="table table-sm">...</table>
```

### Alerts

Alerts provide important messages to users.

#### Alert Types
```html
<div class="alert alert-primary" role="alert">
    <strong>Info!</strong> This is a primary alert message.
</div>

<div class="alert alert-success" role="alert">
    <strong>Success!</strong> Your action was completed successfully.
</div>

<div class="alert alert-warning" role="alert">
    <strong>Warning!</strong> Please review your settings.
</div>

<div class="alert alert-danger" role="alert">
    <strong>Error!</strong> Something went wrong. Please try again.
</div>
```

#### Dismissible Alerts
```html
<div class="alert alert-info alert-dismissible" role="alert">
    <strong>Notice:</strong> Maintenance scheduled for tonight.
    <button type="button" class="btn-close" aria-label="Close alert"></button>
</div>
```

### Badges

Badges highlight important information or status.

#### Badge Variants
```html
<span class="badge badge-primary">Primary</span>
<span class="badge badge-secondary">Secondary</span>
<span class="badge badge-success">Success</span>
<span class="badge badge-warning">Warning</span>
<span class="badge badge-danger">Danger</span>
<span class="badge badge-info">Info</span>
```

#### Badge Sizes
```html
<span class="badge badge-primary badge-sm">Small</span>
<span class="badge badge-primary">Default</span>
<span class="badge badge-primary badge-lg">Large</span>
```

### Navigation

Navigation components help users move through the application.

#### Tab Navigation
```html
<ul class="nav nav-tabs">
    <li class="nav-item">
        <a class="nav-link active" href="#overview">Overview</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="#settings">Settings</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="#logs">Logs</a>
    </li>
</ul>
```

#### Pill Navigation
```html
<ul class="nav nav-pills">
    <li class="nav-item">
        <a class="nav-link active" href="#dashboard">Dashboard</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="#servers">Servers</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="#users">Users</a>
    </li>
</ul>
```

#### Breadcrumbs
```html
<nav aria-label="Breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/">Home</a></li>
        <li class="breadcrumb-item"><a href="/admin">Admin</a></li>
        <li class="breadcrumb-item active" aria-current="page">Servers</li>
    </ol>
</nav>
```

#### Pagination
```html
<nav aria-label="Page navigation">
    <ul class="pagination">
        <li class="page-item disabled">
            <a class="page-link" href="#" aria-label="Previous">
                <span aria-hidden="true">&laquo;</span>
            </a>
        </li>
        <li class="page-item active"><a class="page-link" href="#">1</a></li>
        <li class="page-item"><a class="page-link" href="#">2</a></li>
        <li class="page-item"><a class="page-link" href="#">3</a></li>
        <li class="page-item">
            <a class="page-link" href="#" aria-label="Next">
                <span aria-hidden="true">&raquo;</span>
            </a>
        </li>
    </ul>
</nav>
```

## Utility Classes

Utility classes provide quick styling options for common patterns.

### Display
```html
<div class="d-none">Hidden element</div>
<div class="d-block">Block element</div>
<div class="d-flex">Flex container</div>
<div class="d-inline-flex">Inline flex container</div>
```

### Flexbox
```html
<div class="d-flex justify-content-center align-items-center">
    Centered content
</div>

<div class="d-flex justify-content-between">
    <span>Left</span>
    <span>Right</span>
</div>
```

### Spacing
```html
<div class="m-4 p-3">Margin 16px, Padding 12px</div>
<div class="mb-2">Margin bottom 8px</div>
<div class="pt-5">Padding top 20px</div>
```

### Text Alignment
```html
<p class="text-left">Left aligned</p>
<p class="text-center">Center aligned</p>
<p class="text-right">Right aligned</p>
```

### Text Colors
```html
<p class="text-primary">Primary text</p>
<p class="text-success">Success text</p>
<p class="text-danger">Danger text</p>
<p class="text-muted">Muted text</p>
```

### Background Colors
```html
<div class="bg-primary text-white p-3">Primary background</div>
<div class="bg-light p-3">Light background</div>
<div class="bg-dark text-white p-3">Dark background</div>
```

### Borders and Shadows
```html
<div class="border rounded p-3">Bordered container</div>
<div class="shadow-sm p-3">Small shadow</div>
<div class="shadow-lg p-3">Large shadow</div>
```

## Responsive Design

All components are designed to work across different screen sizes.

### Breakpoints
- **sm**: 640px and up
- **md**: 768px and up  
- **lg**: 1024px and up
- **xl**: 1280px and up
- **2xl**: 1536px and up

### Responsive Utilities
```html
<div class="d-none md:d-block">Hidden on mobile, visible on desktop</div>
<div class="flex-column md:flex-row">Stack on mobile, row on desktop</div>
<div class="text-center md:text-left">Center on mobile, left on desktop</div>
```

## Accessibility Guidelines

### Keyboard Navigation
- All interactive elements must be keyboard accessible
- Tab order should be logical and intuitive
- Focus indicators must be clearly visible
- Escape key should close modals and dropdowns

### Screen Readers
- Use semantic HTML elements
- Provide proper ARIA labels and descriptions
- Announce dynamic content changes
- Use heading hierarchy correctly

### Color and Contrast
- Maintain WCAG 2.1 AA contrast ratios
- Don't rely solely on color to convey information
- Support high contrast mode
- Provide alternative indicators for color-blind users

### Touch Targets
- Minimum 44x44px touch targets
- Adequate spacing between interactive elements
- Support for touch gestures where appropriate

## Best Practices

### Component Development
1. **Start with semantic HTML** - Use appropriate HTML elements
2. **Apply design tokens** - Use CSS custom properties
3. **Add accessibility features** - Include ARIA attributes and keyboard support
4. **Test across devices** - Ensure responsive behavior
5. **Document usage** - Provide clear examples and guidelines

### Naming Conventions
- Use BEM methodology for CSS classes
- Prefix component classes with the component name
- Use semantic naming for states and variants
- Keep names descriptive but concise

### Performance
- Minimize CSS bundle size
- Use efficient selectors
- Optimize for critical rendering path
- Implement progressive enhancement

### Maintenance
- Regular accessibility audits
- Cross-browser testing
- Performance monitoring
- User feedback collection

## Implementation Examples

### Admin Dashboard Card
```html
<div class="card">
    <div class="card-header">
        <h3 class="card-title">System Overview</h3>
        <span class="badge badge-success">All Systems Operational</span>
    </div>
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="text-muted">Active Servers</span>
            <strong>24/25</strong>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="text-muted">Total Users</span>
            <strong>1,247</strong>
        </div>
        <div class="d-flex justify-content-between align-items-center">
            <span class="text-muted">Revenue Today</span>
            <strong class="text-success">$2,456</strong>
        </div>
    </div>
    <div class="card-footer">
        <button class="btn btn-primary btn-sm btn-block">View Detailed Report</button>
    </div>
</div>
```

### User Profile Form
```html
<form class="component-base">
    <div class="form-group">
        <label class="form-label required" for="fullName">Full Name</label>
        <input type="text" class="form-control" id="fullName" required>
        <div class="form-text">Enter your first and last name</div>
    </div>
    
    <div class="form-group">
        <label class="form-label required" for="email">Email Address</label>
        <input type="email" class="form-control" id="email" required>
    </div>
    
    <div class="form-group">
        <label class="form-label" for="role">Role</label>
        <select class="form-control" id="role">
            <option value="">Select a role</option>
            <option value="admin">Administrator</option>
            <option value="user">User</option>
            <option value="viewer">Viewer</option>
        </select>
    </div>
    
    <div class="form-check mb-4">
        <input class="form-check-input" type="checkbox" id="notifications">
        <label class="form-check-label" for="notifications">
            Enable email notifications
        </label>
    </div>
    
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">Save Changes</button>
        <button type="button" class="btn btn-secondary">Cancel</button>
    </div>
</form>
```

### Data Table with Actions
```html
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Server Management</h3>
        <button class="btn btn-primary btn-sm">Add Server</button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th scope="col">
                            <input type="checkbox" class="form-check-input" aria-label="Select all">
                        </th>
                        <th scope="col">Server Name</th>
                        <th scope="col">Location</th>
                        <th scope="col">Status</th>
                        <th scope="col">Load</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <input type="checkbox" class="form-check-input" aria-label="Select server">
                        </td>
                        <td>
                            <strong>NY-01</strong>
                            <br>
                            <small class="text-muted">192.168.1.10</small>
                        </td>
                        <td>New York, US</td>
                        <td><span class="badge badge-success">Online</span></td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="progress me-2" style="width: 60px;">
                                    <div class="progress-bar bg-success" style="width: 35%"></div>
                                </div>
                                <small>35%</small>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <button class="btn btn-sm btn-ghost" aria-label="Edit server">
                                    <i class="icon-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-ghost" aria-label="View logs">
                                    <i class="icon-logs"></i>
                                </button>
                                <button class="btn btn-sm btn-ghost text-danger" aria-label="Delete server">
                                    <i class="icon-delete"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
```

## Migration Guide

### From Custom CSS to Design System

1. **Audit existing components** - Identify inconsistencies
2. **Map to design tokens** - Replace hardcoded values
3. **Update class names** - Use standardized naming
4. **Test thoroughly** - Ensure visual consistency
5. **Document changes** - Update component documentation

### Integration with Filament

The design system is compatible with Filament admin panels:

```php
// In your Filament resource
public static function form(Form $form): Form
{
    return $form
        ->schema([
            TextInput::make('name')
                ->extraAttributes(['class' => 'form-control'])
                ->label('Server Name'),
            
            Select::make('status')
                ->options([
                    'online' => 'Online',
                    'offline' => 'Offline',
                    'maintenance' => 'Maintenance',
                ])
                ->extraAttributes(['class' => 'form-control']),
        ]);
}
```

## Conclusion

This component library provides a solid foundation for building consistent, accessible, and maintainable user interfaces. By following these guidelines and using the provided components, you can create a professional and cohesive experience across the entire ProxyAdmin application.

For questions or contributions, please refer to the development team or create an issue in the project repository.
