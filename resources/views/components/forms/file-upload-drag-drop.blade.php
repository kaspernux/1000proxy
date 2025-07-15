<!-- File Upload with Drag and Drop Component -->
<div x-data="fileUploadDragDrop()" 
     x-init="init()"
     class="file-upload-container"
     data-max-files="5"
     data-max-file-size="10485760"
     data-allowed-types="image/jpeg,image/png,image/gif,image/webp,application/pdf,text/plain"
     data-allowed-extensions=".jpg,.jpeg,.png,.gif,.webp,.pdf,.txt"
     data-auto-upload="false"
     data-upload-endpoint="/api/upload"
     data-delete-endpoint="/api/upload/delete">
    
    <!-- Drop Zone -->
    <div class="drop-zone"
         :class="{ 
             'drag-over': isDragOver, 
             'has-files': files.length > 0,
             'is-uploading': isUploading 
         }"
         @click="$refs.fileInput.click()">
        
        <!-- Drop Zone Content -->
        <div class="drop-zone-content">
            <div class="drop-zone-icon">
                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                </svg>
            </div>
            
            <div class="drop-zone-text">
                <p class="text-lg font-semibold text-gray-700 dark:text-gray-300">
                    <span x-show="!isDragOver">Drag and drop files here</span>
                    <span x-show="isDragOver" class="text-blue-600 dark:text-blue-400">Release to upload files</span>
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    or <button type="button" class="text-blue-600 hover:text-blue-500 font-medium">browse files</button>
                </p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">
                    Maximum <span x-text="maxFiles"></span> files, 
                    <span x-text="formatFileSize(maxFileSize)"></span> each
                </p>
            </div>
        </div>
        
        <!-- Hidden File Input -->
        <input x-ref="fileInput" 
               type="file" 
               multiple 
               class="hidden"
               :accept="allowedTypes.join(',')"
               @change="handleFileInput($event)">
    </div>
    
    <!-- Error Messages -->
    <div x-show="errors.length > 0" class="error-messages mt-4">
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 dark:bg-red-900/20 dark:border-red-800">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800 dark:text-red-200">
                        Upload Errors
                    </h3>
                    <div class="mt-2 text-sm text-red-700 dark:text-red-300">
                        <ul class="list-disc list-inside space-y-1">
                            <template x-for="error in errors" :key="error">
                                <li x-text="error"></li>
                            </template>
                        </ul>
                    </div>
                    <div class="mt-3">
                        <button @click="clearErrors()" 
                                class="text-sm text-red-600 hover:text-red-500 dark:text-red-400 dark:hover:text-red-300">
                            Dismiss
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- File List -->
    <div x-show="files.length > 0" class="file-list mt-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <!-- File List Header -->
            <div class="file-list-header p-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                        Files (<span x-text="files.length"></span>)
                    </h3>
                    
                    <div class="flex items-center space-x-3">
                        <!-- Upload All Button -->
                        <button x-show="!autoUpload && files.some(f => f.status === 'pending')"
                                @click="uploadAll()"
                                :disabled="isUploading"
                                class="btn btn-primary btn-sm">
                            <span x-show="!isUploading">Upload All</span>
                            <span x-show="isUploading" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Uploading...
                            </span>
                        </button>
                        
                        <!-- Clear All Button -->
                        <button @click="clearAll()"
                                class="btn btn-secondary btn-sm">
                            Clear All
                        </button>
                    </div>
                </div>
                
                <!-- Upload Progress Summary -->
                <div x-show="isUploading" class="mt-3">
                    <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                        <span x-text="`${getUploadSummary().completed} of ${getUploadSummary().total} files uploaded`"></span>
                        (<span x-text="`${getUploadSummary().completionPercentage}%`"></span>)
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" 
                             :style="`width: ${getUploadSummary().completionPercentage}%`"></div>
                    </div>
                </div>
            </div>
            
            <!-- File Items -->
            <div class="file-items">
                <template x-for="file in files" :key="file.id">
                    <div class="file-item border-b border-gray-100 dark:border-gray-700 last:border-b-0">
                        <div class="p-4">
                            <div class="flex items-start space-x-4">
                                <!-- File Preview/Icon -->
                                <div class="flex-shrink-0">
                                    <div class="file-preview">
                                        <template x-if="file.thumbnail">
                                            <img :src="file.thumbnail" 
                                                 :alt="file.name"
                                                 class="w-12 h-12 object-cover rounded-lg border border-gray-200 dark:border-gray-600">
                                        </template>
                                        
                                        <template x-if="!file.thumbnail">
                                            <div class="w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 flex items-center justify-center">
                                                <span class="text-xl" x-text="getFileIcon(file.file)"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                                
                                <!-- File Info -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between">
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                                <span x-text="file.name"></span>
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                <span x-text="formatFileSize(file.size)"></span>
                                                <span class="mx-1">•</span>
                                                <span x-text="file.type"></span>
                                            </p>
                                        </div>
                                        
                                        <!-- File Status -->
                                        <div class="flex items-center space-x-2">
                                            <!-- Status Badge -->
                                            <span class="file-status-badge"
                                                  :class="{
                                                      'status-pending': file.status === 'pending',
                                                      'status-uploading': file.status === 'uploading',
                                                      'status-completed': file.status === 'completed',
                                                      'status-error': file.status === 'error'
                                                  }">
                                                <span x-show="file.status === 'pending'">Pending</span>
                                                <span x-show="file.status === 'uploading'">Uploading</span>
                                                <span x-show="file.status === 'completed'">Completed</span>
                                                <span x-show="file.status === 'error'">Error</span>
                                            </span>
                                            
                                            <!-- Actions -->
                                            <div class="flex items-center space-x-1">
                                                <!-- Retry Button -->
                                                <button x-show="file.status === 'error'"
                                                        @click="retryUpload(file.id)"
                                                        class="text-blue-600 hover:text-blue-500 text-sm">
                                                    Retry
                                                </button>
                                                
                                                <!-- View Button -->
                                                <button x-show="file.status === 'completed' && file.uploadedUrl"
                                                        @click="window.open(file.uploadedUrl, '_blank')"
                                                        class="text-green-600 hover:text-green-500 text-sm">
                                                    View
                                                </button>
                                                
                                                <!-- Remove Button -->
                                                <button @click="removeFile(file.id)"
                                                        class="text-red-600 hover:text-red-500 text-sm">
                                                    Remove
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Progress Bar -->
                                    <div x-show="file.status === 'uploading' || (file.status === 'completed' && file.progress === 100)"
                                         class="mt-2">
                                        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                            <span x-text="`${file.progress}%`"></span>
                                            <span x-show="file.status === 'uploading'">Uploading...</span>
                                            <span x-show="file.status === 'completed'">Complete</span>
                                        </div>
                                        <div class="mt-1 progress-bar">
                                            <div class="progress-fill" 
                                                 :style="`width: ${file.progress}%`"
                                                 :class="{
                                                     'bg-blue-500': file.status === 'uploading',
                                                     'bg-green-500': file.status === 'completed'
                                                 }"></div>
                                        </div>
                                    </div>
                                    
                                    <!-- Error Message -->
                                    <div x-show="file.status === 'error' && file.error" 
                                         class="mt-2 text-xs text-red-600 dark:text-red-400">
                                        <span x-text="file.error"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
    
    <!-- Upload Summary -->
    <div x-show="files.length > 0" class="upload-summary mt-4">
        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                <div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white" x-text="getUploadSummary().total"></div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Total Files</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400" x-text="getUploadSummary().completed"></div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Completed</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400" x-text="getUploadSummary().uploading"></div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Uploading</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-red-600 dark:text-red-400" x-text="getUploadSummary().errors"></div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">Errors</div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* File Upload Styles */
.file-upload-container {
    @apply w-full;
}

.drop-zone {
    @apply relative border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8 text-center cursor-pointer transition-all duration-200 hover:border-gray-400 dark:hover:border-gray-500;
}

.drop-zone.drag-over {
    @apply border-blue-500 bg-blue-50 dark:bg-blue-900/20;
}

.drop-zone.has-files {
    @apply border-gray-400 dark:border-gray-500;
}

.drop-zone.is-uploading {
    @apply border-blue-500 bg-blue-50 dark:bg-blue-900/20;
}

.drop-zone-content {
    @apply flex flex-col items-center space-y-4;
}

.drop-zone-icon {
    @apply flex items-center justify-center;
}

.drop-zone-text {
    @apply space-y-1;
}

/* File List Styles */
.file-list {
    @apply w-full;
}

.file-item {
    @apply transition-colors duration-150 hover:bg-gray-50 dark:hover:bg-gray-700/50;
}

.file-preview img {
    @apply shadow-sm;
}

/* Status Badges */
.file-status-badge {
    @apply inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium;
}

.file-status-badge.status-pending {
    @apply bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300;
}

.file-status-badge.status-uploading {
    @apply bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300;
}

.file-status-badge.status-completed {
    @apply bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300;
}

.file-status-badge.status-error {
    @apply bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300;
}

/* Progress Bar */
.progress-bar {
    @apply w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 overflow-hidden;
}

.progress-fill {
    @apply h-full transition-all duration-300 ease-in-out rounded-full;
}

.progress-fill:not([class*="bg-"]) {
    @apply bg-blue-500;
}

/* Buttons */
.btn {
    @apply inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors duration-150;
}

.btn-primary {
    @apply text-white bg-blue-600 hover:bg-blue-700 focus:ring-blue-500;
}

.btn-secondary {
    @apply text-gray-700 bg-white border-gray-300 hover:bg-gray-50 focus:ring-blue-500 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700;
}

.btn-sm {
    @apply px-3 py-1.5 text-sm;
}

/* Error Messages */
.error-messages {
    @apply w-full;
}

/* Upload Summary */
.upload-summary {
    @apply w-full;
}

/* Responsive Design */
@media (max-width: 640px) {
    .drop-zone {
        @apply p-6;
    }
    
    .file-item .flex {
        @apply flex-col space-x-0 space-y-3;
    }
    
    .file-item .flex-shrink-0 {
        @apply flex-shrink;
    }
    
    .upload-summary .grid {
        @apply grid-cols-2;
    }
}

/* Animations */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.file-item {
    animation: fadeIn 0.3s ease-out;
}

/* Dark Mode Enhancements */
@media (prefers-color-scheme: dark) {
    .drop-zone-icon svg {
        @apply text-gray-500;
    }
    
    .file-preview img {
        @apply border-gray-600;
    }
}
</style>

<script>
// Import the file upload component
import fileUploadDragDrop from '../js/components/file-upload-drag-drop.js';

// Register the component globally
window.fileUploadDragDrop = fileUploadDragDrop;
</script>
