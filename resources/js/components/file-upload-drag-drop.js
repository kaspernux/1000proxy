/**
 * File Upload with Drag and Drop Component
 * 
 * Advanced file upload component with drag-and-drop, preview, progress tracking, and validation
 * Features: multiple files, image preview, progress bars, file validation, chunked upload
 */

export default () => ( {
    // Upload state
    files: [],
    isDragOver: false,
    isUploading: false,
    uploadProgress: {},

    // Configuration
    maxFiles: 5,
    maxFileSize: 10 * 1024 * 1024, // 10MB
    allowedTypes: [ 'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf', 'text/plain' ],
    allowedExtensions: [ '.jpg', '.jpeg', '.png', '.gif', '.webp', '.pdf', '.txt' ],

    // Upload settings
    chunkSize: 1024 * 1024, // 1MB chunks
    uploadEndpoint: '/api/upload',
    deleteEndpoint: '/api/upload/delete',
    concurrent: 2, // Concurrent uploads

    // UI settings
    showPreview: true,
    showProgress: true,
    autoUpload: false,

    // Validation
    errors: [],
    validationRules: {},

    /**
     * Initialize the file upload component
     */
    init ()
    {
        this.setupDragAndDrop();
        this.setupPasteHandler();
        this.loadConfiguration();

        console.log( 'File upload component initialized:', {
            maxFiles: this.maxFiles,
            maxFileSize: this.formatFileSize( this.maxFileSize ),
            allowedTypes: this.allowedTypes,
            autoUpload: this.autoUpload
        } );
    },

    /**
     * Load configuration from data attributes
     */
    loadConfiguration ()
    {
        const element = this.$el;

        this.maxFiles = parseInt( element.dataset.maxFiles ) || this.maxFiles;
        this.maxFileSize = parseInt( element.dataset.maxFileSize ) || this.maxFileSize;
        this.autoUpload = element.dataset.autoUpload === 'true';
        this.uploadEndpoint = element.dataset.uploadEndpoint || this.uploadEndpoint;
        this.deleteEndpoint = element.dataset.deleteEndpoint || this.deleteEndpoint;

        if ( element.dataset.allowedTypes )
        {
            this.allowedTypes = element.dataset.allowedTypes.split( ',' ).map( type => type.trim() );
        }

        if ( element.dataset.allowedExtensions )
        {
            this.allowedExtensions = element.dataset.allowedExtensions.split( ',' ).map( ext => ext.trim() );
        }
    },

    /**
     * Setup drag and drop functionality
     */
    setupDragAndDrop ()
    {
        const dropZone = this.$el.querySelector( '.drop-zone' );
        if ( !dropZone ) return;

        // Prevent default drag behaviors
        [ 'dragenter', 'dragover', 'dragleave', 'drop' ].forEach( eventName =>
        {
            dropZone.addEventListener( eventName, this.preventDefaults.bind( this ), false );
            document.body.addEventListener( eventName, this.preventDefaults.bind( this ), false );
        } );

        // Highlight drop zone when item is dragged over it
        [ 'dragenter', 'dragover' ].forEach( eventName =>
        {
            dropZone.addEventListener( eventName, () =>
            {
                this.isDragOver = true;
            }, false );
        } );

        [ 'dragleave', 'drop' ].forEach( eventName =>
        {
            dropZone.addEventListener( eventName, () =>
            {
                this.isDragOver = false;
            }, false );
        } );

        // Handle dropped files
        dropZone.addEventListener( 'drop', ( e ) =>
        {
            const droppedFiles = Array.from( e.dataTransfer.files );
            this.handleFiles( droppedFiles );
        }, false );
    },

    /**
     * Setup paste handler for images
     */
    setupPasteHandler ()
    {
        document.addEventListener( 'paste', ( e ) =>
        {
            if ( !this.isActive() ) return;

            const items = Array.from( e.clipboardData.items );
            const files = items
                .filter( item => item.kind === 'file' )
                .map( item => item.getAsFile() )
                .filter( file => file );

            if ( files.length > 0 )
            {
                e.preventDefault();
                this.handleFiles( files );
            }
        } );
    },

    /**
     * Check if component is active (in viewport and focused)
     */
    isActive ()
    {
        const rect = this.$el.getBoundingClientRect();
        const isVisible = rect.top >= 0 && rect.left >= 0 &&
            rect.bottom <= window.innerHeight &&
            rect.right <= window.innerWidth;

        return isVisible || this.$el.contains( document.activeElement );
    },

    /**
     * Prevent default drag behaviors
     */
    preventDefaults ( e )
    {
        e.preventDefault();
        e.stopPropagation();
    },

    /**
     * Handle file input change
     */
    handleFileInput ( event )
    {
        const selectedFiles = Array.from( event.target.files );
        this.handleFiles( selectedFiles );

        // Clear input so same file can be selected again
        event.target.value = '';
    },

    /**
     * Handle new files
     */
    async handleFiles ( newFiles )
    {
        this.clearErrors();

        // Validate file count
        const totalFiles = this.files.length + newFiles.length;
        if ( totalFiles > this.maxFiles )
        {
            this.addError( `Maximum ${ this.maxFiles } files allowed. You selected ${ newFiles.length } files, but you already have ${ this.files.length }.` );
            return;
        }

        // Process each file
        for ( const file of newFiles )
        {
            const fileData = await this.processFile( file );
            if ( fileData )
            {
                this.files.push( fileData );
            }
        }

        // Auto-upload if enabled
        if ( this.autoUpload && this.files.some( f => f.status === 'pending' ) )
        {
            this.uploadAll();
        }

        // Dispatch file added event
        this.$dispatch( 'files-added', {
            files: this.files.filter( f => f.status === 'pending' ),
            totalFiles: this.files.length
        } );
    },

    /**
     * Process individual file
     */
    async processFile ( file )
    {
        // Validate file
        const validation = this.validateFile( file );
        if ( !validation.valid )
        {
            this.addError( validation.message );
            return null;
        }

        const fileId = this.generateFileId();
        const fileData = {
            id: fileId,
            file: file,
            name: file.name,
            size: file.size,
            type: file.type,
            status: 'pending', // pending, uploading, completed, error
            progress: 0,
            preview: null,
            thumbnail: null,
            error: null,
            uploadedUrl: null,
            uploadedAt: null
        };

        // Generate preview for images
        if ( this.showPreview && this.isImage( file ) )
        {
            try
            {
                fileData.preview = await this.generatePreview( file );
                fileData.thumbnail = await this.generateThumbnail( file );
            } catch ( error )
            {
                console.warn( 'Failed to generate preview:', error );
            }
        }

        return fileData;
    },

    /**
     * Validate file
     */
    validateFile ( file )
    {
        // Check file size
        if ( file.size > this.maxFileSize )
        {
            return {
                valid: false,
                message: `File "${ file.name }" is too large. Maximum size is ${ this.formatFileSize( this.maxFileSize ) }.`
            };
        }

        // Check file type
        if ( this.allowedTypes.length > 0 && !this.allowedTypes.includes( file.type ) )
        {
            const extension = '.' + file.name.split( '.' ).pop().toLowerCase();
            if ( !this.allowedExtensions.includes( extension ) )
            {
                return {
                    valid: false,
                    message: `File type "${ file.type }" is not allowed. Allowed types: ${ this.allowedTypes.join( ', ' ) }.`
                };
            }
        }

        // Check for duplicates
        const duplicate = this.files.find( f => f.name === file.name && f.size === file.size );
        if ( duplicate )
        {
            return {
                valid: false,
                message: `File "${ file.name }" is already selected.`
            };
        }

        return { valid: true };
    },

    /**
     * Generate preview for image files
     */
    generatePreview ( file )
    {
        return new Promise( ( resolve, reject ) =>
        {
            const reader = new FileReader();
            reader.onload = ( e ) => resolve( e.target.result );
            reader.onerror = reject;
            reader.readAsDataURL( file );
        } );
    },

    /**
     * Generate thumbnail for image files
     */
    async generateThumbnail ( file, maxSize = 150 )
    {
        return new Promise( ( resolve, reject ) =>
        {
            const canvas = document.createElement( 'canvas' );
            const ctx = canvas.getContext( '2d' );
            const img = new Image();

            img.onload = () =>
            {
                // Calculate thumbnail dimensions
                const ratio = Math.min( maxSize / img.width, maxSize / img.height );
                const width = img.width * ratio;
                const height = img.height * ratio;

                canvas.width = width;
                canvas.height = height;

                // Draw and compress
                ctx.drawImage( img, 0, 0, width, height );

                canvas.toBlob( ( blob ) =>
                {
                    const reader = new FileReader();
                    reader.onload = ( e ) => resolve( e.target.result );
                    reader.onerror = reject;
                    reader.readAsDataURL( blob );
                }, 'image/jpeg', 0.7 );
            };

            img.onerror = reject;
            img.src = URL.createObjectURL( file );
        } );
    },

    /**
     * Upload all pending files
     */
    async uploadAll ()
    {
        const pendingFiles = this.files.filter( f => f.status === 'pending' );
        if ( pendingFiles.length === 0 ) return;

        this.isUploading = true;

        try
        {
            // Upload files with concurrency limit
            await this.uploadFilesWithConcurrency( pendingFiles, this.concurrent );
        } finally
        {
            this.isUploading = false;
        }

        // Dispatch upload complete event
        const completedFiles = this.files.filter( f => f.status === 'completed' );
        const errorFiles = this.files.filter( f => f.status === 'error' );

        this.$dispatch( 'upload-complete', {
            completed: completedFiles.length,
            errors: errorFiles.length,
            total: this.files.length
        } );
    },

    /**
     * Upload files with concurrency control
     */
    async uploadFilesWithConcurrency ( files, concurrent )
    {
        const uploadPromises = [];

        for ( let i = 0; i < files.length; i += concurrent )
        {
            const batch = files.slice( i, i + concurrent );
            const batchPromises = batch.map( file => this.uploadFile( file ) );

            uploadPromises.push( ...batchPromises );

            // Wait for batch to complete before starting next batch
            if ( i + concurrent < files.length )
            {
                await Promise.allSettled( batchPromises );
            }
        }

        return Promise.allSettled( uploadPromises );
    },

    /**
     * Upload individual file
     */
    async uploadFile ( fileData )
    {
        try
        {
            fileData.status = 'uploading';
            fileData.progress = 0;

            // Use chunked upload for large files
            if ( fileData.size > this.chunkSize )
            {
                await this.uploadFileChunked( fileData );
            } else
            {
                await this.uploadFileSimple( fileData );
            }

            fileData.status = 'completed';
            fileData.uploadedAt = new Date();

            // Dispatch file uploaded event
            this.$dispatch( 'file-uploaded', fileData );

        } catch ( error )
        {
            fileData.status = 'error';
            fileData.error = error.message || 'Upload failed';

            // Dispatch file error event
            this.$dispatch( 'file-error', { file: fileData, error: error.message } );

            console.error( 'Upload error:', error );
        }
    },

    /**
     * Simple file upload
     */
    async uploadFileSimple ( fileData )
    {
        const formData = new FormData();
        formData.append( 'file', fileData.file );
        formData.append( 'filename', fileData.name );

        const response = await fetch( this.uploadEndpoint, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector( 'meta[name="csrf-token"]' )?.content
            }
        } );

        if ( !response.ok )
        {
            throw new Error( `Upload failed: ${ response.statusText }` );
        }

        const result = await response.json();
        fileData.uploadedUrl = result.url || result.path;
        fileData.progress = 100;
    },

    /**
     * Chunked file upload
     */
    async uploadFileChunked ( fileData )
    {
        const file = fileData.file;
        const totalChunks = Math.ceil( file.size / this.chunkSize );
        let uploadedChunks = 0;

        // Initialize chunked upload
        const initResponse = await fetch( `${ this.uploadEndpoint }/init`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector( 'meta[name="csrf-token"]' )?.content
            },
            body: JSON.stringify( {
                filename: file.name,
                size: file.size,
                totalChunks: totalChunks
            } )
        } );

        const { uploadId } = await initResponse.json();

        // Upload chunks
        for ( let i = 0; i < totalChunks; i++ )
        {
            const start = i * this.chunkSize;
            const end = Math.min( start + this.chunkSize, file.size );
            const chunk = file.slice( start, end );

            const formData = new FormData();
            formData.append( 'chunk', chunk );
            formData.append( 'uploadId', uploadId );
            formData.append( 'chunkIndex', i );
            formData.append( 'totalChunks', totalChunks );

            const response = await fetch( `${ this.uploadEndpoint }/chunk`, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector( 'meta[name="csrf-token"]' )?.content
                }
            } );

            if ( !response.ok )
            {
                throw new Error( `Chunk upload failed: ${ response.statusText }` );
            }

            uploadedChunks++;
            fileData.progress = Math.round( ( uploadedChunks / totalChunks ) * 100 );
        }

        // Complete chunked upload
        const completeResponse = await fetch( `${ this.uploadEndpoint }/complete`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector( 'meta[name="csrf-token"]' )?.content
            },
            body: JSON.stringify( { uploadId } )
        } );

        const result = await completeResponse.json();
        fileData.uploadedUrl = result.url || result.path;
    },

    /**
     * Remove file
     */
    async removeFile ( fileId )
    {
        const fileIndex = this.files.findIndex( f => f.id === fileId );
        if ( fileIndex === -1 ) return;

        const fileData = this.files[ fileIndex ];

        // Delete from server if uploaded
        if ( fileData.status === 'completed' && fileData.uploadedUrl )
        {
            try
            {
                await fetch( this.deleteEndpoint, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector( 'meta[name="csrf-token"]' )?.content
                    },
                    body: JSON.stringify( { url: fileData.uploadedUrl } )
                } );
            } catch ( error )
            {
                console.warn( 'Failed to delete file from server:', error );
            }
        }

        // Remove from list
        this.files.splice( fileIndex, 1 );

        // Dispatch file removed event
        this.$dispatch( 'file-removed', { fileId, file: fileData } );
    },

    /**
     * Retry failed upload
     */
    async retryUpload ( fileId )
    {
        const fileData = this.files.find( f => f.id === fileId );
        if ( !fileData || fileData.status !== 'error' ) return;

        fileData.status = 'pending';
        fileData.error = null;
        fileData.progress = 0;

        await this.uploadFile( fileData );
    },

    /**
     * Clear all files
     */
    async clearAll ()
    {
        // Delete uploaded files from server
        const uploadedFiles = this.files.filter( f => f.status === 'completed' && f.uploadedUrl );

        for ( const file of uploadedFiles )
        {
            try
            {
                await fetch( this.deleteEndpoint, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector( 'meta[name="csrf-token"]' )?.content
                    },
                    body: JSON.stringify( { url: file.uploadedUrl } )
                } );
            } catch ( error )
            {
                console.warn( 'Failed to delete file from server:', error );
            }
        }

        this.files = [];
        this.clearErrors();

        this.$dispatch( 'files-cleared' );
    },

    /**
     * Utility methods
     */
    isImage ( file )
    {
        return file.type.startsWith( 'image/' );
    },

    isPDF ( file )
    {
        return file.type === 'application/pdf';
    },

    getFileIcon ( file )
    {
        if ( this.isImage( file ) ) return '🖼️';
        if ( this.isPDF( file ) ) return '📄';
        if ( file.type.startsWith( 'text/' ) ) return '📝';
        return '📁';
    },

    formatFileSize ( bytes )
    {
        if ( bytes === 0 ) return '0 Bytes';

        const k = 1024;
        const sizes = [ 'Bytes', 'KB', 'MB', 'GB' ];
        const i = Math.floor( Math.log( bytes ) / Math.log( k ) );

        return parseFloat( ( bytes / Math.pow( k, i ) ).toFixed( 2 ) ) + ' ' + sizes[ i ];
    },

    generateFileId ()
    {
        return 'file_' + Math.random().toString( 36 ).substr( 2, 9 ) + '_' + Date.now();
    },

    /**
     * Error handling
     */
    addError ( message )
    {
        this.errors.push( message );
    },

    clearErrors ()
    {
        this.errors = [];
    },

    /**
     * Get upload summary
     */
    getUploadSummary ()
    {
        const total = this.files.length;
        const completed = this.files.filter( f => f.status === 'completed' ).length;
        const uploading = this.files.filter( f => f.status === 'uploading' ).length;
        const pending = this.files.filter( f => f.status === 'pending' ).length;
        const errors = this.files.filter( f => f.status === 'error' ).length;

        return {
            total,
            completed,
            uploading,
            pending,
            errors,
            completionPercentage: total > 0 ? Math.round( ( completed / total ) * 100 ) : 0
        };
    },

    /**
     * Export file data
     */
    getFileData ()
    {
        return this.files.map( file => ( {
            id: file.id,
            name: file.name,
            size: file.size,
            type: file.type,
            status: file.status,
            uploadedUrl: file.uploadedUrl,
            uploadedAt: file.uploadedAt
        } ) );
    }
} );
