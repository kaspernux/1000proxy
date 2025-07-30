{{-- Advanced File Upload Component Template --}}
@php
    $multiple = $multiple ?? false;
    $accept = $accept ?? '';
    $maxSize = $maxSize ?? null; // in MB
    $maxFiles = $maxFiles ?? 5;
    $showPreviews = $showPreviews ?? true;
    $allowDragDrop = $allowDragDrop ?? true;
    $uploadUrl = $uploadUrl ?? null;
    $variant = $variant ?? 'default'; // default, compact, minimal
@endphp

<div
    x-data="fileUpload()"
    x-init="
        multiple = {{ $multiple ? 'true' : 'false' }};
        accept = '{{ $accept }}';
        maxSize = {{ $maxSize ? $maxSize : 'null' }};
        maxFiles = {{ $maxFiles }};
        showPreviews = {{ $showPreviews ? 'true' : 'false' }};
        allowDragDrop = {{ $allowDragDrop ? 'true' : 'false' }};
        uploadUrl = '{{ $uploadUrl }}';
        variant = '{{ $variant }}';
    "
    {{ $attributes->merge(['class' => 'w-full']) }}
>
    {{-- Drop Zone --}}
    <div
        x-show="allowDragDrop"
        @dragover.prevent="handleDragOver"
        @dragleave.prevent="handleDragLeave"
        @drop.prevent="handleDrop"
        :class="getDropZoneClasses()"
        class="relative border-2 border-dashed rounded-lg transition-colors duration-200"
    >
        {{-- Drop Zone Content --}}
        <div class="flex flex-col items-center justify-center py-8 px-6 text-center">
            {{-- Upload Icon --}}
            <div class="mb-4">
                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
            </div>

            {{-- Upload Text --}}
            <div class="mb-2">
                <span class="text-lg font-medium text-gray-900">
                    <span x-show="!isDragOver">Drop files here, or</span>
                    <span x-show="isDragOver" class="text-blue-600">Drop files to upload</span>
                </span>
            </div>

            <p class="text-sm text-gray-500 mb-4">
                <span x-show="!isDragOver">
                    Support for {{ $multiple ? 'multiple files' : 'single file' }}
                    @if($accept)
                        ({{ $accept }})
                    @endif
                    @if($maxSize)
                        up to {{ $maxSize }}MB each
                    @endif
                </span>
                <span x-show="isDragOver" class="text-blue-600">
                    Release to upload
                </span>
            </p>

            {{-- Browse Button --}}
            <button
                @click="$refs.fileInput.click()"
                type="button"
                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
            >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Browse Files
            </button>
        </div>

        {{-- Overlay for drag state --}}
        <div
            x-show="isDragOver"
            class="absolute inset-0 bg-blue-50 bg-opacity-75 rounded-lg flex items-center justify-center"
        >
            <div class="text-center">
                <svg class="w-16 h-16 text-blue-500 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
                <p class="text-lg font-medium text-blue-600">Drop to upload</p>
            </div>
        </div>
    </div>

    {{-- Compact/Minimal Variant --}}
    <div x-show="!allowDragDrop || variant === 'compact' || variant === 'minimal'" class="flex items-center gap-3">
        <button
            @click="$refs.fileInput.click()"
            type="button"
            :class="getCompactButtonClasses()"
        >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Choose Files
        </button>

        <span x-show="selectedFiles.length === 0" class="text-sm text-gray-500">
            No files selected
        </span>
        <span x-show="selectedFiles.length > 0" class="text-sm text-gray-700">
            <span x-text="selectedFiles.length"></span> file<span x-show="selectedFiles.length > 1">s</span> selected
        </span>
    </div>

    {{-- Hidden File Input --}}
    <input
        x-ref="fileInput"
        type="file"
        :multiple="multiple"
        :accept="accept"
        @change="handleFileSelect"
        class="hidden"
    />

    {{-- File Preview Section --}}
    <div x-show="showPreviews && selectedFiles.length > 0" class="mt-6">
        <h4 class="text-sm font-medium text-gray-900 mb-3">
            Selected Files (<span x-text="selectedFiles.length"></span>)
        </h4>

        <div class="space-y-3 max-h-64 overflow-y-auto">
            <template x-for="(file, index) in selectedFiles" :key="file.id">
                <div class="flex items-center p-3 bg-gray-50 rounded-lg border">
                    {{-- File Preview/Icon --}}
                    <div class="flex-shrink-0 mr-3">
                        {{-- Image Preview --}}
                        <div x-show="file.type.startsWith('image/')" class="w-12 h-12 rounded-lg overflow-hidden">
                            <img :src="file.preview" :alt="file.name" class="w-full h-full object-cover">
                        </div>

                        {{-- File Type Icon --}}
                        <div x-show="!file.type.startsWith('image/')" class="w-12 h-12 bg-white rounded-lg border flex items-center justify-center">
                            <span class="text-xs font-medium text-gray-600 uppercase" x-text="getFileExtension(file.name)"></span>
                        </div>
                    </div>

                    {{-- File Info --}}
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate" x-text="file.name"></p>
                        <div class="flex items-center gap-2 text-xs text-gray-500">
                            <span x-text="formatFileSize(file.size)"></span>
                            <span x-show="file.lastModified">•</span>
                            <span x-show="file.lastModified" x-text="formatDate(file.lastModified)"></span>
                        </div>

                        {{-- Upload Progress --}}
                        <div x-show="file.uploading" class="mt-2">
                            <div class="flex items-center justify-between text-xs text-gray-600 mb-1">
                                <span>Uploading...</span>
                                <span x-text="file.progress + '%'"></span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-1">
                                <div class="bg-blue-600 h-1 rounded-full transition-all duration-300" :style="`width: ${file.progress}%`"></div>
                            </div>
                        </div>

                        {{-- Upload Status --}}
                        <div x-show="file.uploaded" class="mt-1 flex items-center text-xs text-green-600">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            Uploaded successfully
                        </div>

                        <div x-show="file.error" class="mt-1 flex items-center text-xs text-red-600">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                            <span x-text="file.error"></span>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-2 ml-2">
                        {{-- Retry Button --}}
                        <button
                            x-show="file.error && uploadUrl"
                            @click="retryUpload(file)"
                            class="p-1 text-gray-400 hover:text-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded"
                            title="Retry upload"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                        </button>

                        {{-- Remove Button --}}
                        <button
                            @click="removeFile(index)"
                            class="p-1 text-gray-400 hover:text-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 rounded"
                            title="Remove file"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- Upload Controls --}}
    <div x-show="selectedFiles.length > 0 && uploadUrl" class="mt-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <button
                @click="uploadAll()"
                :disabled="isUploading || allUploaded"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <svg x-show="!isUploading" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
                <svg x-show="isUploading" class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span x-text="isUploading ? 'Uploading...' : 'Upload All'"></span>
            </button>

            <button
                @click="clearAll()"
                class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
            >
                Clear All
            </button>
        </div>

        {{-- Overall Progress --}}
        <div x-show="isUploading" class="flex items-center gap-2 text-sm text-gray-600">
            <span x-text="`${completedUploads} of ${selectedFiles.length} completed`"></span>
            <div class="w-24 bg-gray-200 rounded-full h-2">
                <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" :style="`width: ${overallProgress}%`"></div>
            </div>
        </div>
    </div>

    {{-- Error Messages --}}
    <div x-show="errors.length > 0" class="mt-4">
        <div class="bg-red-50 border border-red-200 rounded-md p-3">
            <div class="flex">
                <svg class="flex-shrink-0 w-5 h-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">Upload Errors</h3>
                    <div class="mt-2 text-sm text-red-700">
                        <ul class="list-disc pl-5 space-y-1">
                            <template x-for="error in errors" :key="error">
                                <li x-text="error"></li>
                            </template>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Example Usage in Comments:
<!--
<!-- Basic File Upload -->
<x-ui.file-upload
    name="files"
    :multiple="true"
    accept="image/*,.pdf,.doc,.docx"
    :max-size="10"
    :max-files="5"
    upload-url="/api/upload"
/>

<!-- Compact Variant -->
<x-ui.file-upload
    variant="compact"
    name="avatar"
    accept="image/*"
    :max-size="2"
    :show-previews="false"
/>

<!-- With Custom Styling -->
<x-ui.file-upload
    class="border-2 border-blue-300"
    :allow-drag-drop="false"
    accept=".csv,.xlsx"
    :multiple="false"
/>
-->
--}}
