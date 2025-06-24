/**
 * Smart Notification System - Edit Template Handler
 * JavaScript file for editing notification templates
 * Depends on: template-creator.js
 */

// Global variables for edit mode
let editMode = false;
let originalTemplateData = {};

/**
 * Edit Template Initialization
 */
function initializeEditMode() {
    console.log('Initializing edit mode...');
    editMode = true;
    
    // Store original data for comparison
    storeOriginalTemplateData();
    
    // Setup edit-specific event listeners
    setupEditEventListeners();
    
    // Load any existing edit draft
    loadEditDraft();
    
    // Initialize variable detection for existing content
    setTimeout(() => {
        if (typeof updateDetectedVariables === 'function') {
            updateDetectedVariables();
        }
    }, 1000);
    
    console.log('Edit mode initialized');
}

/**
 * Store Original Template Data
 */
function storeOriginalTemplateData() {
    originalTemplateData = {
        name: document.getElementById('name')?.value || '',
        slug: document.getElementById('slug')?.value || '',
        category: document.getElementById('category')?.value || '',
        priority: document.getElementById('priority')?.value || '',
        description: document.getElementById('description')?.value || '',
        subject_template: document.getElementById('subject_template')?.value || '',
        body_html_template: document.getElementById('body_html_template')?.value || '',
        body_text_template: document.getElementById('body_text_template')?.value || '',
        default_variables_json: document.getElementById('default_variables_json')?.value || '',
        is_active: document.getElementById('is_active')?.checked || false
    };
    
    // Store supported channels
    originalTemplateData.supported_channels = [];
    document.querySelectorAll('input[name="supported_channels[]"]:checked').forEach(checkbox => {
        originalTemplateData.supported_channels.push(checkbox.value);
    });
    
    console.log('Original template data stored:', originalTemplateData);
}

/**
 * Setup Edit-Specific Event Listeners
 */
function setupEditEventListeners() {
    // Form submission for edit
    const editTemplateForm = document.getElementById('editTemplateForm') || document.getElementById('templateForm');
    
    if (editTemplateForm) {
        editTemplateForm.addEventListener('submit', function(e) {
            e.preventDefault();
            handleEditFormSubmission(this);
        });
    }
    
    // Detect changes for unsaved changes warning
    setupUnsavedChangesDetection();
    
    // Reset button functionality
    const resetBtn = document.getElementById('resetBtn');
    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            resetToOriginalData();
        });
    }
    
    // Preview button for edit mode
    const previewBtn = document.getElementById('previewBtn');
    if (previewBtn) {
        previewBtn.addEventListener('click', function() {
            if (typeof generatePreview === 'function') {
                generatePreview();
            }
        });
    }
}

/**
 * Handle Edit Form Submission
 */
function handleEditFormSubmission(form) {
    console.log('Handling edit form submission...');
    
    // Sync TinyMCE content
    if (typeof tinymce !== 'undefined' && tinymce.get('body_html_template')) {
        tinymce.get('body_html_template').save();
        console.log('TinyMCE content synced for edit');
    }
    
    // Validate form if validation functions exist
    if (typeof validateCurrentStep === 'function' && typeof totalSteps !== 'undefined' && totalSteps > 1) {
        let allValid = true;
        for (let i = 1; i <= totalSteps - 1; i++) {
            const originalStep = typeof currentStep !== 'undefined' ? currentStep : 1;
            if (typeof window.currentStep !== 'undefined') window.currentStep = i;
            if (!validateCurrentStep()) {
                allValid = false;
                if (typeof showStep === 'function') {
                    showStep(i);
                }
                break;
            }
            if (typeof window.currentStep !== 'undefined') window.currentStep = originalStep;
        }
        
        if (!allValid) {
            console.log('Validation failed, stopping submission');
            return;
        }
    }
    
    // Show loading state
    const saveBtn = document.getElementById('saveBtn') || document.getElementById('updateBtn');
    if (saveBtn) {
        const originalText = saveBtn.innerHTML;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
        saveBtn.disabled = true;
        
        // Collect form data using the same function as create
        const formData = typeof collectFormData === 'function' ? collectFormData() : collectEditFormData();
        
        // Add _method for Laravel PUT/PATCH request
        formData.append('_method', 'PUT'); // หรือ 'PATCH' ตามที่ route กำหนด
        
        // Submit to Laravel update endpoint
        submitEditToLaravel(formData, saveBtn, originalText);
    }
}

/**
 * Collect Form Data for Edit (fallback if collectFormData doesn't exist)
 */
function collectEditFormData() {
    const formData = new FormData();
    
    // Basic information
    formData.append('name', document.getElementById('name')?.value || '');
    formData.append('slug', document.getElementById('slug')?.value || '');
    formData.append('category', document.getElementById('category')?.value || '');
    formData.append('priority', document.getElementById('priority')?.value || '');
    formData.append('description', document.getElementById('description')?.value || '');
    formData.append('is_active', document.getElementById('is_active')?.checked ? '1' : '0');
    
    // Supported channels
    const channels = [];
    document.querySelectorAll('input[name="supported_channels[]"]:checked').forEach(checkbox => {
        channels.push(checkbox.value);
    });
    
    channels.forEach(channel => {
        formData.append('supported_channels[]', channel);
    });
    
    // Template content
    formData.append('subject_template', document.getElementById('subject_template')?.value || '');
    
    // HTML content - check TinyMCE
    let htmlContent = '';
    const htmlEditor = tinymce.get('body_html_template');
    if (htmlEditor) {
        htmlContent = htmlEditor.getContent();
    } else {
        htmlContent = document.getElementById('body_html_template')?.value || '';
    }
    formData.append('body_html_template', htmlContent);
    
    // Text content
    formData.append('body_text_template', document.getElementById('body_text_template')?.value || '');
    
    // Variables
    document.querySelectorAll('.variable-row').forEach((row, index) => {
        const nameInput = row.querySelector('input[placeholder="Variable name"]');
        const defaultInput = row.querySelector('input[placeholder="Default value"]');
        const typeSelect = row.querySelector('select');
        
        if (nameInput && nameInput.value.trim()) {
            const varName = nameInput.value.trim();
            const varDefault = defaultInput ? defaultInput.value.trim() : '';
            const varType = typeSelect ? typeSelect.value : 'text';
            
            formData.append(`variables[${index}][name]`, varName);
            formData.append(`variables[${index}][default]`, varDefault);
            formData.append(`variables[${index}][type]`, varType);
        }
    });
    
    // Default variables JSON
    const defaultVarsJson = document.getElementById('default_variables_json')?.value?.trim();
    let defaultVariables = {};
    
    if (defaultVarsJson) {
        try {
            defaultVariables = JSON.parse(defaultVarsJson);
            if (Array.isArray(defaultVariables)) {
                console.warn('Default variables should be an object, not an array');
                defaultVariables = {};
            }
        } catch (e) {
            console.error('Invalid JSON in default variables:', e);
            defaultVariables = {};
        }
    }
    
    Object.keys(defaultVariables).forEach(key => {
        formData.append(`default_variables[${key}]`, defaultVariables[key]);
    });
    
    // CSRF Token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                      document.querySelector('input[name="_token"]')?.value;
    if (csrfToken) {
        formData.append('_token', csrfToken);
    }
    
    return formData;
}

/**
 * Submit Edit Data to Laravel
 */
function submitEditToLaravel(formData, saveBtn, originalText) {
    // Get template ID
    const templateId = getTemplateId();
    
    if (!templateId) {
        console.error('Template ID not found');
        showEditAlert('error', 'Template ID not found');
        resetSaveButton(saveBtn, originalText);
        return;
    }
    
    // Create update URL
    const baseUrl = window.location.origin;
    const updateUrl = `${baseUrl}/templates/${templateId}`;
    
    console.log('Updating template:', templateId);
    console.log('Update URL:', updateUrl);
    
    // Log form data for debugging
    console.log('Form data to be sent:');
    for (let [key, value] of formData.entries()) {
        console.log(key + ':', value);
    }
    
    // Submit to Laravel
    fetch(updateUrl, {
        method: 'POST', // Use POST with _method: PUT in formData
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        }
    })
    .then(response => {
        console.log('Response status:', response.status);
        
        if (!response.ok) {
            return response.text().then(text => {
                console.error('Error response:', text);
                throw new Error(`HTTP ${response.status}: ${text}`);
            });
        }
        
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        } else {
            return { success: true, message: 'Template updated successfully' };
        }
    })
    .then(data => {
        console.log('Success response:', data);
        
        if (data.success !== false) {
            showEditAlert('success', data.message || 'Template updated successfully!');
            
            // Clear drafts
            localStorage.removeItem('template_draft');
            localStorage.removeItem('template_edit_draft');
            const templateId = getTemplateId();
            if (templateId) {
                localStorage.removeItem(`template_edit_draft_${templateId}`);
            }
            
            // Update original data to current data (no more unsaved changes)
            storeOriginalTemplateData();
            
            // Redirect or reload
            setTimeout(() => {
                if (data.redirect) {
                    window.location.href = data.redirect;
                } else if (data.template_id) {
                    // Go to show template page
                    window.location.href = `/templates/${data.template_id}`;
                } else {
                    // Reload current page or go to index
                    const currentPath = window.location.pathname;
                    if (currentPath.includes('/edit')) {
                        // Go to show page by removing /edit from URL
                        const showUrl = currentPath.replace('/edit', '');
                        window.location.href = showUrl;
                    } else {
                        window.location.reload();
                    }
                }
            }, 1500);
        } else {
            throw new Error(data.message || 'Failed to update template');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        
        let errorMessage = 'An error occurred while updating the template';
        
        if (error.message.includes('405')) {
            errorMessage = 'Route configuration error. Please check Laravel routes.';
        } else if (error.message.includes('419')) {
            errorMessage = 'CSRF token mismatch. Please refresh the page and try again.';
        } else if (error.message.includes('422')) {
            errorMessage = 'Validation error. Please check your input.';
        } else if (error.message.includes('404')) {
            errorMessage = 'Template not found. It may have been deleted.';
        } else if (error.message.includes('403')) {
            errorMessage = 'You do not have permission to edit this template.';
        } else if (error.message) {
            errorMessage = error.message;
        }
        
        showEditAlert('error', errorMessage);
        resetSaveButton(saveBtn, originalText);
    });
}

/**
 * Get Template ID from various sources
 */
function getTemplateId() {
    // Method 1: From URL parameter
    const urlParts = window.location.pathname.split('/');
    const templatesIndex = urlParts.indexOf('templates');
    if (templatesIndex >= 0 && urlParts[templatesIndex + 1]) {
        const id = urlParts[templatesIndex + 1];
        // Remove 'edit' if it's part of the ID
        return id === 'edit' ? urlParts[templatesIndex + 2] : id.replace('/edit', '');
    }
    
    // Method 2: From hidden input
    const hiddenInput = document.querySelector('input[name="template_id"], input[name="id"]');
    if (hiddenInput) {
        return hiddenInput.value;
    }
    
    // Method 3: From data attribute
    const form = document.getElementById('editTemplateForm') || document.getElementById('templateForm');
    if (form && form.dataset.templateId) {
        return form.dataset.templateId;
    }
    
    // Method 4: From meta tag
    const metaTemplateId = document.querySelector('meta[name="template-id"]');
    if (metaTemplateId) {
        return metaTemplateId.getAttribute('content');
    }
    
    // Method 5: From URL hash or search params
    const urlParams = new URLSearchParams(window.location.search);
    const idParam = urlParams.get('id');
    if (idParam) {
        return idParam;
    }
    
    return null;
}

/**
 * Reset Save Button
 */
function resetSaveButton(saveBtn, originalText) {
    if (saveBtn) {
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
    }
}

/**
 * Show Alert for Edit Mode
 */
function showEditAlert(type, message) {
    // Use existing showAlert function if available
    if (typeof showAlert === 'function') {
        showAlert(type, message);
        return;
    }
    
    // Fallback alert implementation
    const alertClass = type === 'success' ? 'alert-success' : 
                      type === 'error' ? 'alert-danger' : 
                      type === 'warning' ? 'alert-warning' : 'alert-info';
    
    const icon = type === 'success' ? 'fas fa-check-circle' : 
                type === 'error' ? 'fas fa-exclamation-triangle' : 
                type === 'warning' ? 'fas fa-exclamation-triangle' : 'fas fa-info-circle';
    
    // Remove existing alerts
    document.querySelectorAll('.alert-dismissible').forEach(alert => {
        alert.remove();
    });
    
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show position-fixed" 
             style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
            <i class="${icon}"></i> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', alertHtml);
    
    // Auto dismiss success messages
    if (type === 'success') {
        setTimeout(() => {
            const alert = document.querySelector('.alert-success');
            if (alert) {
                alert.remove();
            }
        }, 3000);
    }
}

/**
 * Draft Management for Edit Mode
 */
function saveEditDraft() {
    const templateId = getTemplateId();
    if (!templateId) return;
    
    const draftData = {
        template_id: templateId,
        name: document.getElementById('name')?.value || '',
        category: document.getElementById('category')?.value || '',
        priority: document.getElementById('priority')?.value || '',
        description: document.getElementById('description')?.value || '',
        subject_template: document.getElementById('subject_template')?.value || '',
        body_html_template: document.getElementById('body_html_template')?.value || '',
        body_text_template: document.getElementById('body_text_template')?.value || '',
        default_variables_json: document.getElementById('default_variables_json')?.value || '',
        is_active: document.getElementById('is_active')?.checked || false,
        timestamp: new Date().toISOString()
    };
    
    // Get content from TinyMCE if available
    const htmlEditor = tinymce.get('body_html_template');
    if (htmlEditor) {
        draftData.body_html_template = htmlEditor.getContent();
    }
    
    // Store supported channels
    draftData.supported_channels = [];
    document.querySelectorAll('input[name="supported_channels[]"]:checked').forEach(checkbox => {
        draftData.supported_channels.push(checkbox.value);
    });
    
    localStorage.setItem(`template_edit_draft_${templateId}`, JSON.stringify(draftData));
    
    // Show success message
    showEditAlert('info', 'Draft saved automatically');
}

function loadEditDraft() {
    const templateId = getTemplateId();
    if (!templateId) return;
    
    const draft = localStorage.getItem(`template_edit_draft_${templateId}`);
    if (draft) {
        try {
            const draftData = JSON.parse(draft);
            const draftAge = Date.now() - new Date(draftData.timestamp).getTime();
            
            // Load draft only if it's less than 24 hours old
            if (draftAge < 24 * 60 * 60 * 1000) {
                const shouldLoad = confirm('Found unsaved changes from ' + 
                    new Date(draftData.timestamp).toLocaleString() + 
                    '. Would you like to restore them?');
                
                if (shouldLoad) {
                    Object.keys(draftData).forEach(key => {
                        if (key !== 'timestamp' && key !== 'template_id' && key !== 'supported_channels') {
                            const element = document.getElementById(key);
                            if (element && draftData[key] !== null && draftData[key] !== undefined) {
                                if (element.type === 'checkbox') {
                                    element.checked = draftData[key];
                                } else {
                                    element.value = draftData[key];
                                }
                            }
                        }
                    });
                    
                    // Restore supported channels
                    if (draftData.supported_channels && Array.isArray(draftData.supported_channels)) {
                        // First uncheck all
                        document.querySelectorAll('input[name="supported_channels[]"]').forEach(checkbox => {
                            checkbox.checked = false;
                        });
                        // Then check the ones from draft
                        draftData.supported_channels.forEach(channel => {
                            const checkbox = document.querySelector(`input[name="supported_channels[]"][value="${channel}"]`);
                            if (checkbox) {
                                checkbox.checked = true;
                            }
                        });
                    }
                    
                    // Update TinyMCE
                    if (draftData.body_html_template) {
                        const htmlEditor = tinymce.get('body_html_template');
                        if (htmlEditor) {
                            htmlEditor.setContent(draftData.body_html_template);
                        }
                    }
                    
                    showEditAlert('success', 'Draft restored successfully');
                }
            } else {
                // Remove old draft
                localStorage.removeItem(`template_edit_draft_${templateId}`);
            }
        } catch (e) {
            console.error('Error loading edit draft:', e);
            localStorage.removeItem(`template_edit_draft_${templateId}`);
        }
    }
}

/**
 * Unsaved Changes Detection
 */
function setupUnsavedChangesDetection() {
    let hasUnsavedChanges = false;
    
    // Track changes in form fields
    const formElements = document.querySelectorAll('input, select, textarea');
    formElements.forEach(element => {
        element.addEventListener('input', function() {
            hasUnsavedChanges = detectChanges();
        });
        
        element.addEventListener('change', function() {
            hasUnsavedChanges = detectChanges();
        });
    });
    
    // Track TinyMCE changes
    if (typeof tinymce !== 'undefined') {
        const checkTinyMCE = setInterval(() => {
            const htmlEditor = tinymce.get('body_html_template');
            if (htmlEditor) {
                htmlEditor.on('input change', function() {
                    hasUnsavedChanges = detectChanges();
                });
                clearInterval(checkTinyMCE);
            }
        }, 1000);
    }
    
    // Warn before leaving page
    window.addEventListener('beforeunload', function(e) {
        if (hasUnsavedChanges) {
            e.preventDefault();
            e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
            return e.returnValue;
        }
    });
    
    // Update unsaved changes indicator
    function updateUnsavedChangesIndicator() {
        const indicator = document.getElementById('unsaved-changes-indicator');
        if (indicator) {
            if (hasUnsavedChanges) {
                indicator.style.display = 'inline';
                indicator.innerHTML = '<i class="fas fa-circle text-warning"></i> Unsaved changes';
            } else {
                indicator.style.display = 'none';
            }
        }
    }
    
    // Check for changes periodically
    setInterval(() => {
        const newHasUnsavedChanges = detectChanges();
        if (newHasUnsavedChanges !== hasUnsavedChanges) {
            hasUnsavedChanges = newHasUnsavedChanges;
            updateUnsavedChangesIndicator();
        }
    }, 2000);
}

function detectChanges() {
    // Compare current form data with original data
    const currentData = {
        name: document.getElementById('name')?.value || '',
        slug: document.getElementById('slug')?.value || '',
        category: document.getElementById('category')?.value || '',
        priority: document.getElementById('priority')?.value || '',
        description: document.getElementById('description')?.value || '',
        subject_template: document.getElementById('subject_template')?.value || '',
        body_html_template: document.getElementById('body_html_template')?.value || '',
        body_text_template: document.getElementById('body_text_template')?.value || '',
        default_variables_json: document.getElementById('default_variables_json')?.value || '',
        is_active: document.getElementById('is_active')?.checked || false
    };
    
    // Get TinyMCE content
    const htmlEditor = tinymce.get('body_html_template');
    if (htmlEditor) {
        currentData.body_html_template = htmlEditor.getContent();
    }
    
    // Check supported channels
    currentData.supported_channels = [];
    document.querySelectorAll('input[name="supported_channels[]"]:checked').forEach(checkbox => {
        currentData.supported_channels.push(checkbox.value);
    });
    
    // Compare with original data
    for (const key in currentData) {
        if (key === 'supported_channels') {
            const originalChannels = originalTemplateData.supported_channels || [];
            const currentChannels = currentData.supported_channels;
            if (originalChannels.length !== currentChannels.length || 
                !originalChannels.every(channel => currentChannels.includes(channel))) {
                return true;
            }
        } else if (currentData[key] !== originalTemplateData[key]) {
            return true;
        }
    }
    
    return false;
}

/**
 * Reset to Original Data
 */
function resetToOriginalData() {
    if (!confirm('Are you sure you want to discard all changes and reset to the original data?')) {
        return;
    }
    
    // Reset all form fields
    Object.keys(originalTemplateData).forEach(key => {
        if (key !== 'supported_channels') {
            const element = document.getElementById(key);
            if (element) {
                if (element.type === 'checkbox') {
                    element.checked = originalTemplateData[key];
                } else {
                    element.value = originalTemplateData[key];
                }
            }
        }
    });
    
    // Reset supported channels
    document.querySelectorAll('input[name="supported_channels[]"]').forEach(checkbox => {
        checkbox.checked = false;
    });
    
    if (originalTemplateData.supported_channels) {
        originalTemplateData.supported_channels.forEach(channel => {
            const checkbox = document.querySelector(`input[name="supported_channels[]"][value="${channel}"]`);
            if (checkbox) {
                checkbox.checked = true;
            }
        });
    }
    
    // Reset TinyMCE
    const htmlEditor = tinymce.get('body_html_template');
    if (htmlEditor) {
        htmlEditor.setContent(originalTemplateData.body_html_template || '');
    }
    
    // Clear edit draft
    const templateId = getTemplateId();
    if (templateId) {
        localStorage.removeItem(`template_edit_draft_${templateId}`);
    }
    
    showEditAlert('info', 'Form reset to original data');
}

/**
 * Auto-save for Edit Mode
 */
function startEditAutoSave() {
    setInterval(function() {
        // Check if we're in edit mode and on an edit page
        if (!editMode || !window.location.pathname.includes('/edit')) {
            return;
        }
        
        // Don't save if user is actively typing
        const activeElement = document.activeElement;
        if (activeElement && (activeElement.tagName === 'INPUT' || activeElement.tagName === 'TEXTAREA')) {
            return;
        }
        
        // Don't save if no changes detected
        if (!detectChanges()) {
            return;
        }
        
        // Save draft
        const nameField = document.getElementById('name');
        if (nameField && nameField.value.trim()) {
            saveEditDraft();
        }
    }, 5 * 60 * 1000); // 5 minutes
}

/**
 * Initialize Edit Mode on Page Load
 */
document.addEventListener('DOMContentLoaded', function() {
    // Check if we're on an edit page
    const isEditPage = window.location.pathname.includes('/edit') || 
                       document.getElementById('editTemplateForm') ||
                       (document.getElementById('templateForm') && document.querySelector('input[name="_method"][value="PUT"]'));
    
    if (isEditPage) {
        console.log('Edit page detected, initializing edit mode...');
        
        // Wait a bit for other scripts to load
        setTimeout(() => {
            initializeEditMode();
            startEditAutoSave();
        }, 1000);
    }
});

/**
 * Export functions for global access
 */
window.editTemplate = {
    initializeEditMode,
    saveEditDraft,
    loadEditDraft,
    resetToOriginalData,
    detectChanges,
    getTemplateId
};

console.log('Edit template script loaded successfully');