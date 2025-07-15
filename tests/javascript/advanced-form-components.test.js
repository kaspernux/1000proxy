/**
 * Advanced Form Components Test Suite
 * Comprehensive tests for form functionality
 */

describe('Advanced Form Components', () => {
    let container;
    
    beforeEach(() => {
        container = document.createElement('div');
        document.body.appendChild(container);
    });
    
    afterEach(() => {
        document.body.removeChild(container);
    });
    
    describe('Dynamic Form Builder', () => {
        let formBuilder;
        
        beforeEach(() => {
            const formSchema = {
                fields: [
                    { name: 'name', type: 'text', label: 'Name', required: true },
                    { name: 'email', type: 'email', label: 'Email', required: true },
                    { name: 'age', type: 'number', label: 'Age', min: 0, max: 120 }
                ],
                validationRules: {
                    name: ['required', 'min:2'],
                    email: ['required', 'email'],
                    age: ['numeric', 'min:0', 'max:120']
                }
            };
            
            formBuilder = window.dynamicForm(formSchema);
            container.innerHTML = `
                <form x-data="dynamicForm(${JSON.stringify(formSchema)})">
                    <div x-html="renderForm()"></div>
                    <button type="submit" @click.prevent="submitForm()">Submit</button>
                </form>
            `;
        });
        
        test('should initialize with form schema', () => {
            expect(formBuilder.schema.fields.length).toBe(3);
            expect(formBuilder.formData.name).toBe('');
            expect(formBuilder.formData.email).toBe('');
            expect(formBuilder.formData.age).toBe('');
        });
        
        test('should validate required fields', () => {
            formBuilder.formData.name = '';
            formBuilder.formData.email = 'test@example.com';
            
            const isValid = formBuilder.validateForm();
            
            expect(isValid).toBe(false);
            expect(formBuilder.errors.name).toContain('required');
        });
        
        test('should validate email format', () => {
            formBuilder.formData.email = 'invalid-email';
            
            formBuilder.validateField('email');
            
            expect(formBuilder.errors.email).toContain('email');
        });
        
        test('should validate number ranges', () => {
            formBuilder.formData.age = '150';
            
            formBuilder.validateField('age');
            
            expect(formBuilder.errors.age).toContain('max');
        });
        
        test('should clear validation errors', () => {
            formBuilder.errors.name = ['Name is required'];
            formBuilder.clearFieldError('name');
            
            expect(formBuilder.errors.name).toEqual([]);
        });
        
        test('should render form fields correctly', () => {
            const formHtml = formBuilder.renderForm();
            
            expect(formHtml).toContain('input[name="name"]');
            expect(formHtml).toContain('input[type="email"]');
            expect(formHtml).toContain('input[type="number"]');
        });
    });
    
    describe('Multi-step Form Wizard', () => {
        let wizard;
        
        beforeEach(() => {
            const steps = [
                {
                    title: 'Personal Info',
                    fields: ['name', 'email'],
                    validation: { name: 'required', email: 'required|email' }
                },
                {
                    title: 'Details',
                    fields: ['age', 'phone'],
                    validation: { age: 'numeric|min:18', phone: 'required' }
                },
                {
                    title: 'Confirmation',
                    fields: [],
                    validation: {}
                }
            ];
            
            wizard = window.multiStepForm({ steps });
        });
        
        test('should start at first step', () => {
            expect(wizard.currentStep).toBe(0);
            expect(wizard.isFirstStep).toBe(true);
            expect(wizard.isLastStep).toBe(false);
        });
        
        test('should navigate to next step', () => {
            wizard.formData.name = 'John Doe';
            wizard.formData.email = 'john@example.com';
            
            wizard.nextStep();
            
            expect(wizard.currentStep).toBe(1);
            expect(wizard.isFirstStep).toBe(false);
            expect(wizard.isLastStep).toBe(false);
        });
        
        test('should not advance if validation fails', () => {
            wizard.formData.name = '';
            wizard.formData.email = 'invalid';
            
            wizard.nextStep();
            
            expect(wizard.currentStep).toBe(0); // Should stay on first step
        });
        
        test('should navigate to previous step', () => {
            wizard.currentStep = 1;
            wizard.previousStep();
            
            expect(wizard.currentStep).toBe(0);
        });
        
        test('should go to specific step', () => {
            wizard.goToStep(2);
            
            expect(wizard.currentStep).toBe(2);
            expect(wizard.isLastStep).toBe(true);
        });
        
        test('should calculate progress percentage', () => {
            wizard.currentStep = 1; // Second step
            
            expect(wizard.progressPercentage).toBe(67); // 2/3 * 100
        });
    });
    
    describe('Smart Search Component', () => {
        let smartSearch;
        
        beforeEach(() => {
            const config = {
                dataSource: '/api/search',
                minChars: 2,
                debounceDelay: 300,
                maxResults: 10
            };
            
            smartSearch = window.smartSearch(config);
            
            // Mock API response
            testUtils.mockApiResponse('/api/search', [
                { id: 1, title: 'John Doe', subtitle: 'john@example.com' },
                { id: 2, title: 'Jane Smith', subtitle: 'jane@example.com' }
            ]);
        });
        
        test('should initialize with config', () => {
            expect(smartSearch.minChars).toBe(2);
            expect(smartSearch.debounceDelay).toBe(300);
            expect(smartSearch.maxResults).toBe(10);
        });
        
        test('should not search with short query', () => {
            smartSearch.query = 'a';
            smartSearch.search();
            
            expect(smartSearch.results).toEqual([]);
            expect(smartSearch.loading).toBe(false);
        });
        
        test('should search with valid query', async () => {
            smartSearch.query = 'john';
            await smartSearch.search();
            
            expect(smartSearch.results.length).toBe(2);
            expect(smartSearch.loading).toBe(false);
        });
        
        test('should select result', () => {
            const result = { id: 1, title: 'John Doe', subtitle: 'john@example.com' };
            smartSearch.selectResult(result);
            
            expect(smartSearch.selectedResult).toEqual(result);
            expect(smartSearch.query).toBe('John Doe');
            expect(smartSearch.showResults).toBe(false);
        });
        
        test('should highlight matching text', () => {
            const text = 'John Doe';
            const query = 'john';
            const highlighted = smartSearch.highlightMatch(text, query);
            
            expect(highlighted).toContain('<mark>John</mark>');
        });
        
        test('should handle keyboard navigation', () => {
            smartSearch.results = [
                { id: 1, title: 'John Doe' },
                { id: 2, title: 'Jane Smith' }
            ];
            
            smartSearch.handleKeyDown({ key: 'ArrowDown', preventDefault: jest.fn() });
            expect(smartSearch.selectedIndex).toBe(0);
            
            smartSearch.handleKeyDown({ key: 'ArrowDown', preventDefault: jest.fn() });
            expect(smartSearch.selectedIndex).toBe(1);
            
            smartSearch.handleKeyDown({ key: 'ArrowUp', preventDefault: jest.fn() });
            expect(smartSearch.selectedIndex).toBe(0);
        });
    });
    
    describe('Auto-save Form', () => {
        let autoSaveForm;
        
        beforeEach(() => {
            const config = {
                saveUrl: '/api/auto-save',
                saveInterval: 1000,
                storageKey: 'form-data'
            };
            
            autoSaveForm = window.autoSaveForm(config);
            
            // Mock successful save response
            testUtils.mockApiResponse('/api/auto-save', { success: true });
        });
        
        test('should save to localStorage', () => {
            autoSaveForm.formData.name = 'John Doe';
            autoSaveForm.saveToStorage();
            
            expect(localStorage.setItem).toHaveBeenCalledWith(
                'form-data',
                JSON.stringify(autoSaveForm.formData)
            );
        });
        
        test('should restore from localStorage', () => {
            const savedData = { name: 'John Doe', email: 'john@example.com' };
            localStorage.getItem.mockReturnValue(JSON.stringify(savedData));
            
            autoSaveForm.restoreFromStorage();
            
            expect(autoSaveForm.formData).toEqual(savedData);
        });
        
        test('should auto-save on data change', (done) => {
            autoSaveForm.formData.name = 'John Doe';
            autoSaveForm.scheduleAutoSave();
            
            setTimeout(() => {
                expect(autoSaveForm.lastSaved).toBeTruthy();
                done();
            }, 1100);
        });
        
        test('should handle save errors gracefully', async () => {
            testUtils.mockApiResponse('/api/auto-save', { error: 'Save failed' }, 500);
            
            await autoSaveForm.saveToServer();
            
            expect(autoSaveForm.saveError).toBeTruthy();
        });
    });
    
    describe('File Upload Component', () => {
        let fileUpload;
        
        beforeEach(() => {
            const config = {
                uploadUrl: '/api/upload',
                maxFileSize: 5 * 1024 * 1024, // 5MB
                allowedTypes: ['image/jpeg', 'image/png', 'application/pdf'],
                multiple: true
            };
            
            fileUpload = window.fileUpload(config);
            
            // Mock upload response
            testUtils.mockApiResponse('/api/upload', { 
                success: true, 
                file: { id: 1, name: 'test.jpg', url: '/uploads/test.jpg' }
            });
        });
        
        test('should validate file type', () => {
            const validFile = new File([''], 'test.jpg', { type: 'image/jpeg' });
            const invalidFile = new File([''], 'test.txt', { type: 'text/plain' });
            
            expect(fileUpload.isValidFileType(validFile)).toBe(true);
            expect(fileUpload.isValidFileType(invalidFile)).toBe(false);
        });
        
        test('should validate file size', () => {
            const smallFile = new File(['a'.repeat(1000)], 'small.jpg', { type: 'image/jpeg' });
            const largeFile = new File(['a'.repeat(10 * 1024 * 1024)], 'large.jpg', { type: 'image/jpeg' });
            
            expect(fileUpload.isValidFileSize(smallFile)).toBe(true);
            expect(fileUpload.isValidFileSize(largeFile)).toBe(false);
        });
        
        test('should add files to queue', () => {
            const file = new File([''], 'test.jpg', { type: 'image/jpeg' });
            fileUpload.addFile(file);
            
            expect(fileUpload.files.length).toBe(1);
            expect(fileUpload.files[0].file).toEqual(file);
            expect(fileUpload.files[0].status).toBe('pending');
        });
        
        test('should remove file from queue', () => {
            const file = new File([''], 'test.jpg', { type: 'image/jpeg' });
            fileUpload.addFile(file);
            fileUpload.removeFile(0);
            
            expect(fileUpload.files.length).toBe(0);
        });
        
        test('should track upload progress', () => {
            const file = new File([''], 'test.jpg', { type: 'image/jpeg' });
            fileUpload.addFile(file);
            
            fileUpload.updateProgress(0, 50);
            
            expect(fileUpload.files[0].progress).toBe(50);
        });
    });
    
    describe('Form Validation', () => {
        let validator;
        
        beforeEach(() => {
            validator = window.formValidator();
        });
        
        test('should validate required fields', () => {
            expect(validator.required('')).toBe(false);
            expect(validator.required('value')).toBe(true);
            expect(validator.required(null)).toBe(false);
            expect(validator.required(undefined)).toBe(false);
        });
        
        test('should validate email format', () => {
            expect(validator.email('test@example.com')).toBe(true);
            expect(validator.email('invalid-email')).toBe(false);
            expect(validator.email('')).toBe(true); // Empty is valid for email (use required separately)
        });
        
        test('should validate minimum length', () => {
            expect(validator.min('hello', 3)).toBe(true);
            expect(validator.min('hi', 3)).toBe(false);
        });
        
        test('should validate maximum length', () => {
            expect(validator.max('hello', 10)).toBe(true);
            expect(validator.max('this is a very long string', 10)).toBe(false);
        });
        
        test('should validate numeric values', () => {
            expect(validator.numeric('123')).toBe(true);
            expect(validator.numeric('123.45')).toBe(true);
            expect(validator.numeric('abc')).toBe(false);
        });
        
        test('should validate URL format', () => {
            expect(validator.url('https://example.com')).toBe(true);
            expect(validator.url('http://example.com')).toBe(true);
            expect(validator.url('not-a-url')).toBe(false);
        });
        
        test('should validate patterns', () => {
            expect(validator.pattern('123-456', /^\d{3}-\d{3}$/)).toBe(true);
            expect(validator.pattern('123456', /^\d{3}-\d{3}$/)).toBe(false);
        });
        
        test('should validate confirmation fields', () => {
            expect(validator.confirmed('password', 'password')).toBe(true);
            expect(validator.confirmed('password', 'different')).toBe(false);
        });
    });
});

console.log('✅ Advanced Form Components tests loaded');
