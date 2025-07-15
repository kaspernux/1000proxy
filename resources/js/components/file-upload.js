/**
 * Advanced File Upload Component with Drag & Drop
 * Supports multiple files, progress tracking, and validation
 */
export default () => ( {
    // Component State
    files: [],
    dragOver: false,
    uploading: false,
    uploadProgress: {},

    // Configuration
    multiple: false,
    maxFiles: 5,
    maxFileSize: 10 * 1024 * 1024, // 10MB
    acceptedTypes: [ 'image/*', 'application/pdf', 'text/*' ],
    uploadUrl: '/api/upload',
    autoUpload: true,

    // Validation
    errors: [],

    // Preview
    showPreviews: true,
    previewImageSize: 'w-20 h-20',

    // Lifecycle
    init ()
    {
        this.setupEventListeners();
    },

    // Event Handlers
    handleFileSelect ( event )
    {
        const selectedFiles = Array.from( event.target.files );
        this.addFiles( selectedFiles );
    },

    handleDragEnter ( event )
    {
        event.preventDefault();
        this.dragOver = true;
    },

    handleDragLeave ( event )
    {
        event.preventDefault();
        // Only set dragOver to false if we're leaving the component entirely
        if ( !this.$el.contains( event.relatedTarget ) )
        {
            this.dragOver = false;
        }
    },

    handleDragOver ( event )
    {
        event.preventDefault();
        this.dragOver = true;
    },

    handleDrop ( event )
    {
        event.preventDefault();
        this.dragOver = false;

        const droppedFiles = Array.from( event.dataTransfer.files );
        this.addFiles( droppedFiles );
    },

    // File Management
    addFiles ( newFiles )
    {
        this.errors = [];
        const validFiles = [];

        for ( const file of newFiles )
        {
            // Check file count limit
            if ( !this.multiple && this.files.length >= 1 )
            {
                this.errors.push( 'Only one file is allowed' );
                break;
            }

            if ( this.files.length + validFiles.length >= this.maxFiles )
            {
                this.errors.push( `Maximum ${ this.maxFiles } files allowed` );
                break;
            }

            // Validate file
            const validation = this.validateFile( file );
            if ( validation.valid )
            {
                const fileData = {
                    id: this.generateId(),
                    file: file,
                    name: file.name,
                    size: file.size,
                    type: file.type,
                    preview: null,
                    uploaded: false,
                    uploading: false,
                    progress: 0,
                    error: null
                };

                // Generate preview for images
                if ( file.type.startsWith( 'image/' ) )
                {
                    this.generatePreview( fileData );
                }

                validFiles.push( fileData );
            } else
            {
                this.errors.push( ...validation.errors );
            }
        }

        // Add valid files
        this.files.push( ...validFiles );

        // Auto upload if enabled
        if ( this.autoUpload && validFiles.length > 0 )
        {
            this.uploadFiles( validFiles );
        }

        this.$dispatch( 'files-added', {
            files: validFiles,
            allFiles: this.files,
            errors: this.errors
        } );
    },

    removeFile ( fileId )
    {
        const fileIndex = this.files.findIndex( f => f.id === fileId );
        if ( fileIndex > -1 )
        {
            const file = this.files[ fileIndex ];

            // Cancel upload if in progress
            if ( file.uploading && file.abortController )
            {
                file.abortController.abort();
            }

            this.files.splice( fileIndex, 1 );

            this.$dispatch( 'file-removed', {
                fileId,
                files: this.files
            } );
        }
    },

    clearFiles ()
    {
        // Cancel all uploads
        this.files.forEach( file =>
        {
            if ( file.uploading && file.abortController )
            {
                file.abortController.abort();
            }
        } );

        this.files = [];
        this.errors = [];
        this.uploadProgress = {};

        this.$dispatch( 'files-cleared' );
    },

    // File Validation
    validateFile ( file )
    {
        const errors = [];

        // Check file size
        if ( file.size > this.maxFileSize )
        {
            errors.push( `File ${ file.name } is too large. Maximum size: ${ this.formatFileSize( this.maxFileSize ) }` );
        }

        // Check file type
        if ( this.acceptedTypes.length > 0 )
        {
            const isValidType = this.acceptedTypes.some( type =>
            {
                if ( type.endsWith( '/*' ) )
                {
                    return file.type.startsWith( type.slice( 0, -1 ) );
                }
                return file.type === type;
            } );

            if ( !isValidType )
            {
                errors.push( `File ${ file.name } type not allowed. Accepted types: ${ this.acceptedTypes.join( ', ' ) }` );
            }
        }

        return {
            valid: errors.length === 0,
            errors
        };
    },

    // File Upload
    async uploadFiles ( filesToUpload = null )
    {
        const files = filesToUpload || this.files.filter( f => !f.uploaded && !f.uploading );

        this.uploading = true;

        for ( const fileData of files )
        {
            await this.uploadFile( fileData );
        }

        this.uploading = false;
    },

    async uploadFile ( fileData )
    {
        fileData.uploading = true;
        fileData.progress = 0;
        fileData.error = null;

        const formData = new FormData();
        formData.append( 'file', fileData.file );

        // Create abort controller for cancellation
        const abortController = new AbortController();
        fileData.abortController = abortController;

        try
        {
            const response = await fetch( this.uploadUrl, {
                method: 'POST',
                body: formData,
                signal: abortController.signal,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector( 'meta[name="csrf-token"]' )?.getAttribute( 'content' )
                }
            } );

            if ( response.ok )
            {
                const result = await response.json();
                fileData.uploaded = true;
                fileData.progress = 100;
                fileData.uploadResult = result;

                this.$dispatch( 'file-uploaded', {
                    file: fileData,
                    result
                } );
            } else
            {
                throw new Error( `Upload failed: ${ response.statusText }` );
            }
        } catch ( error )
        {
            if ( error.name !== 'AbortError' )
            {
                fileData.error = error.message;
                this.$dispatch( 'file-upload-error', {
                    file: fileData,
                    error: error.message
                } );
            }
        } finally
        {
            fileData.uploading = false;
            delete fileData.abortController;
        }
    },

    // Preview Generation
    generatePreview ( fileData )
    {
        const reader = new FileReader();
        reader.onload = ( e ) =>
        {
            fileData.preview = e.target.result;
        };
        reader.readAsDataURL( fileData.file );
    },

    // Utility Methods
    generateId ()
    {
        return Math.random().toString( 36 ).substring( 2 ) + Date.now().toString( 36 );
    },

    formatFileSize ( bytes )
    {
        if ( bytes === 0 ) return '0 Bytes';

        const k = 1024;
        const sizes = [ 'Bytes', 'KB', 'MB', 'GB' ];
        const i = Math.floor( Math.log( bytes ) / Math.log( k ) );

        return parseFloat( ( bytes / Math.pow( k, i ) ).toFixed( 2 ) ) + ' ' + sizes[ i ];
    },

    getFileIcon ( fileType )
    {
        const icons = {
            'image': '🖼️',
            'application/pdf': '📄',
            'text': '📝',
            'video': '🎥',
            'audio': '🎵',
            'application': '📎'
        };

        for ( const [ type, icon ] of Object.entries( icons ) )
        {
            if ( fileType.startsWith( type ) )
            {
                return icon;
            }
        }

        return '📎';
    },

    // Style Getters
    getDropZoneClasses ()
    {
        const baseClasses = [
            'relative border-2 border-dashed rounded-lg p-6 transition-all duration-200',
            'flex flex-col items-center justify-center text-center cursor-pointer',
            'hover:border-blue-400 hover:bg-blue-50'
        ];

        const stateClasses = this.dragOver
            ? [ 'border-blue-400', 'bg-blue-50', 'scale-105' ]
            : [ 'border-gray-300', 'bg-gray-50' ];

        return [ ...baseClasses, ...stateClasses ].join( ' ' );
    },

    getFileItemClasses ( file )
    {
        const baseClasses = [
            'flex items-center gap-3 p-3 bg-white border rounded-lg shadow-sm'
        ];

        const stateClasses = [];
        if ( file.uploading )
        {
            stateClasses.push( 'border-blue-200' );
        } else if ( file.uploaded )
        {
            stateClasses.push( 'border-green-200', 'bg-green-50' );
        } else if ( file.error )
        {
            stateClasses.push( 'border-red-200', 'bg-red-50' );
        } else
        {
            stateClasses.push( 'border-gray-200' );
        }

        return [ ...baseClasses, ...stateClasses ].join( ' ' );
    },

    // Event Listeners Setup
    setupEventListeners ()
    {
        // Prevent default drag behaviors on document
        [ 'dragenter', 'dragover', 'dragleave', 'drop' ].forEach( eventName =>
        {
            document.addEventListener( eventName, ( e ) =>
            {
                e.preventDefault();
                e.stopPropagation();
            } );
        } );
    },

    // Accessibility
    getAriaLabel ()
    {
        const fileCount = this.files.length;
        const uploadingCount = this.files.filter( f => f.uploading ).length;

        let label = `File upload area. ${ fileCount } files selected.`;
        if ( uploadingCount > 0 )
        {
            label += ` ${ uploadingCount } files uploading.`;
        }

        return label;
    }
} );
