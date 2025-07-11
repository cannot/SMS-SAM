// Global variables
let lastFocusedElement = null;
let variableDetectionInitialized = false;

// Global function for custom variable
function addCustomVariableFunction() {
    const customVar = document.getElementById('customVariable').value.trim();
    if (customVar) {
        insertVariable(customVar);
        addVariableToList(customVar);
        document.getElementById('customVariable').value = '';
        
        // Visual feedback
        const input = document.getElementById('customVariable');
        input.style.borderColor = '#28a745';
        setTimeout(function() {
            input.style.borderColor = '';
        }, 1000);
    } else {
        alert('Please enter a variable name');
        document.getElementById('customVariable').focus();
    }
}

// Variable management functions
function addVariable() {
    const container = document.getElementById('variables-container');
    const div = document.createElement('div');
    div.className = 'input-group mb-2 variable-row';
    div.innerHTML = '<input type="text" class="form-control" name="variables[]" placeholder="variable_name">' +
                    '<button type="button" class="btn btn-outline-danger" onclick="removeVariable(this)">' +
                    '<i class="fas fa-times"></i></button>';
    container.appendChild(div);
}

function removeVariable(button) {
    button.closest('.variable-row').remove();
}

function addVariableToList(varName) {
    const existingInputs = document.querySelectorAll('#variables-container input[name="variables[]"]');
    for (let input of existingInputs) {
        if (input.value === varName) {
            return;
        }
    }
    
    const container = document.getElementById('variables-container');
    const div = document.createElement('div');
    div.className = 'input-group mb-2 variable-row';
    div.innerHTML = '<input type="text" class="form-control" name="variables[]" value="' + varName + '">' +
                    '<button type="button" class="btn btn-outline-danger" onclick="removeVariable(this)">' +
                    '<i class="fas fa-times"></i></button>';
    container.appendChild(div);
}

// Insert variable function
function insertVariable(varName) {
    const varText = '{{' + varName + '}}';
    
    if (lastFocusedElement) {
        // TinyMCE editor (new format)
        if (lastFocusedElement.isTinyMCE && lastFocusedElement.editor) {
            lastFocusedElement.editor.insertContent(varText);
            lastFocusedElement.editor.focus();
            return;
        }
        
        // TinyMCE editor (legacy format)
        if (lastFocusedElement.id && tinymce.get(lastFocusedElement.id)) {
            const editor = tinymce.get(lastFocusedElement.id);
            editor.insertContent(varText);
            editor.focus();
            return;
        }
        
        // Regular input/textarea
        if (lastFocusedElement.value !== undefined && typeof lastFocusedElement.value === 'string') {
            const cursorPos = lastFocusedElement.selectionStart || 0;
            const textBefore = lastFocusedElement.value.substring(0, cursorPos);
            const textAfter = lastFocusedElement.value.substring(cursorPos);
            
            lastFocusedElement.value = textBefore + varText + textAfter;
            lastFocusedElement.focus();
            lastFocusedElement.selectionStart = lastFocusedElement.selectionEnd = cursorPos + varText.length;
            lastFocusedElement.dispatchEvent(new Event('input', { bubbles: true }));
            return;
        }
    }
    
    // Fallback to TinyMCE
    const htmlEditor = tinymce.get('body_html_template');
    if (htmlEditor) {
        htmlEditor.insertContent(varText);
        htmlEditor.focus();
    } else {
        alert('Please click in a text field first');
    }
}

// Variable detection functions - แก้ไขให้ดึงข้อมูลจาก TinyMCE ได้ถูกต้อง
function detectVariablesFromContent() {
    console.log('Detecting variables from content...');
    
    const subjectTemplate = document.getElementById('subject_template')?.value || '';
    const bodyTextTemplate = document.getElementById('body_text_template')?.value || '';
    let bodyHtmlTemplate = '';
    
    // ดึงเนื้อหาจาก TinyMCE - แก้ไขให้ใช้งานได้ถูกต้อง
    const htmlEditor = tinymce.get('body_html_template');
    if (htmlEditor) {
        bodyHtmlTemplate = htmlEditor.getContent();
        console.log('Got TinyMCE content:', bodyHtmlTemplate.substring(0, 100) + '...');
    } else {
        bodyHtmlTemplate = document.getElementById('body_html_template')?.value || '';
        console.log('Got textarea content:', bodyHtmlTemplate.substring(0, 100) + '...');
    }
    
    const allContent = subjectTemplate + ' ' + bodyHtmlTemplate + ' ' + bodyTextTemplate;
    console.log('All content for detection:', allContent.substring(0, 200) + '...');
    
    const variables = [];
    
    // Extract simple variables using regex - แก้ไข regex ให้ตรงกับรูปแบบ {{variable}}
    const variableMatches = allContent.match(/\{\{([^}#\/][^}]*?)\}\}/g);
    console.log('Variable matches found:', variableMatches);
    
    if (variableMatches) {
        variableMatches.forEach(function(match) {
            const varName = match.replace(/[{}]/g, '').split(':')[0].split('|')[0].trim();
            if (!variables.includes(varName) && !isSystemVariable(varName)) {
                variables.push(varName);
                console.log('Added variable:', varName);
            }
        });
    }
    
    // Extract conditional and loop variables
    const conditionalMatches = allContent.match(/\{\{#(if|each)\s+([^}]+)\}\}/g);
    console.log('Conditional matches found:', conditionalMatches);
    
    if (conditionalMatches) {
        conditionalMatches.forEach(function(match) {
            const varName = match.replace(/\{\{#(if|each)\s+/, '').replace(/\}\}/, '').trim();
            if (!variables.includes(varName) && !isSystemVariable(varName)) {
                variables.push(varName);
                console.log('Added conditional variable:', varName);
            }
        });
    }
    
    console.log('Final detected variables:', variables);
    return variables;
}

function updateDetectedVariables() {
    console.log('Updating detected variables...');
    const detectedVars = detectVariablesFromContent();
    
    let detectedSection = document.getElementById('detected-variables-section');
    
    // สร้าง section ถ้ายังไม่มี
    if (!detectedSection) {
        console.log('Creating detected variables section...');
        const variablesCard = document.querySelector('.card:has(#variables-container)');
        if (variablesCard) {
            const cardBody = variablesCard.querySelector('.card-body');
            if (cardBody) {
                detectedSection = document.createElement('div');
                detectedSection.id = 'detected-variables-section';
                detectedSection.className = 'mb-4';
                detectedSection.innerHTML = `
                    <h6 class="small fw-bold text-uppercase text-info">
                        <i class="fas fa-search me-1"></i>Detected Variables
                    </h6>
                    <div id="detected-variables-list" class="d-flex flex-wrap gap-1 mb-2"></div>
                    <small class="text-muted">Variables found in your template content. Click to add to required variables.</small>
                `;
                cardBody.insertBefore(detectedSection, cardBody.firstChild);
                console.log('Detected variables section created');
            }
        }
    }
    
    if (detectedSection) {
        const detectedList = document.getElementById('detected-variables-list');
        if (detectedList) {
            if (detectedVars.length > 0) {
                console.log('Showing', detectedVars.length, 'detected variables');
                detectedList.innerHTML = '';
                detectedVars.forEach(function(varName) {
                    const badge = document.createElement('span');
                    badge.className = 'badge bg-info variable-badge me-1 mb-1';
                    badge.textContent = '{{' + varName + '}}';
                    badge.title = 'Click to add to required variables';
                    badge.style.cursor = 'pointer';
                    badge.addEventListener('click', function() {
                        addVariableToList(varName);
                        // เปลี่ยนสีเป็นเขียวเมื่อคลิกแล้ว
                        this.classList.remove('bg-info');
                        this.classList.add('bg-success');
                        this.title = 'Added to required variables';
                    });
                    detectedList.appendChild(badge);
                });
                detectedSection.style.display = 'block';
            } else {
                console.log('No variables detected, hiding section');
                detectedSection.style.display = 'none';
            }
        }
    }
    
    // อัพเดท default variables
    updateDefaultVariablesJSON(detectedVars);
}

function updateDefaultVariablesJSON(detectedVars) {
    if (!detectedVars) {
        detectedVars = detectVariablesFromContent();
    }
    
    const defaultVarsField = document.getElementById('default_variables_json');
    if (!defaultVarsField) return;
    
    let currentJson = {};
    
    try {
        const currentValue = defaultVarsField.value.trim();
        if (currentValue) {
            currentJson = JSON.parse(currentValue);
        }
    } catch (e) {
        console.warn('Invalid JSON in default variables, starting fresh');
        currentJson = {};
    }
    
    let hasNewVars = false;
    detectedVars.forEach(function(varName) {
        if (!(varName in currentJson)) {
            currentJson[varName] = getSampleValueForVariable(varName);
            hasNewVars = true;
            console.log('Added new variable to JSON:', varName);
        }
    });
    
    // อัพเดทเฉพาะเมื่อมีตัวแปรใหม่หรือฟิลด์ว่าง
    if (hasNewVars || defaultVarsField.value.trim() === '') {
        defaultVarsField.value = JSON.stringify(currentJson, null, 2);
        console.log('Updated default variables JSON');
    }
}

// เพิ่มฟังก์ชันสร้างค่าตัวอย่างสำหรับตัวแปร
function getSampleValueForVariable(varName) {
    const sampleValues = {
        'subject': 'Sample Subject',
        'message': 'Sample message content',
        'sender_name': 'System Administrator',
        'user_name': 'John Doe',
        'user_department': 'IT Department',
        'user_title': 'Software Developer',
        'company': 'Your Company',
        'priority': 'High',
        'status': 'Active',
        'url': 'https://example.com',
        'amount': '1,000.00',
        'deadline': new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]
    };
    
    return sampleValues[varName] || 'Sample value';
}

function isSystemVariable(variable) {
    const systemVars = [
        'app_name', 'app_url', 'current_date', 'current_time', 'current_datetime', 
        'year', 'month', 'day', 'user_name', 'user_email', 'user_first_name', 
        'user_last_name', 'user_department', 'user_title'
    ];
    return systemVars.includes(variable);
}

function generateDefaultVariablesJSON() {
    const detectedVars = detectVariablesFromContent();
    const defaultVarsField = document.getElementById('default_variables_json');
    
    if (detectedVars.length === 0) {
        alert('No variables detected in template content. Please add some variables first.');
        return;
    }
    
    const currentValue = defaultVarsField.value.trim();
    if (currentValue && !confirm('This will overwrite existing default variables. Continue?')) {
        return;
    }
    
    const jsonObject = {};
    detectedVars.forEach(function(varName) {
        jsonObject[varName] = getSampleValueForVariable(varName);
    });
    
    defaultVarsField.value = JSON.stringify(jsonObject, null, 2);
    
    const successMsg = document.createElement('div');
    successMsg.className = 'alert alert-success mt-2';
    successMsg.innerHTML = '<i class="fas fa-check"></i> Generated default values for ' + detectedVars.length + ' variables';
    defaultVarsField.parentNode.insertBefore(successMsg, defaultVarsField.nextSibling);
    
    setTimeout(function() {
        if (successMsg.parentNode) {
            successMsg.parentNode.removeChild(successMsg);
        }
    }, 3000);
}

function validateTemplate() {
    const resultDiv = document.getElementById('validation-result');
    resultDiv.innerHTML = '<div class="text-info"><i class="fas fa-spinner fa-spin"></i> Validating...</div>';
    
    const subjectTemplate = document.getElementById('subject_template').value;
    const bodyTextTemplate = document.getElementById('body_text_template').value;
    let bodyHtmlTemplate = '';
    
    // ดึงเนื้อหาจาก TinyMCE
    const htmlEditor = tinymce.get('body_html_template');
    if (htmlEditor) {
        bodyHtmlTemplate = htmlEditor.getContent();
    } else {
        bodyHtmlTemplate = document.getElementById('body_html_template').value;
    }
    
    const allContent = subjectTemplate + ' ' + bodyHtmlTemplate + ' ' + bodyTextTemplate;
    const errors = [];
    const variables = [];
    
    // Check for unmatched tags
    const ifMatches = (allContent.match(/\{\{#if\s+[^}]+\}\}/g) || []).length;
    const endIfMatches = (allContent.match(/\{\{\/if\}\}/g) || []).length;
    if (ifMatches !== endIfMatches) {
        errors.push('Unmatched {{#if}} and {{/if}} tags');
    }
    
    const eachMatches = (allContent.match(/\{\{#each\s+[^}]+\}\}/g) || []).length;
    const endEachMatches = (allContent.match(/\{\{\/each\}\}/g) || []).length;
    if (eachMatches !== endEachMatches) {
        errors.push('Unmatched {{#each}} and {{/each}} tags');
    }
    
    // Extract variables
    const variableMatches = allContent.match(/\{\{([^}#\/][^}]*)\}\}/g);
    if (variableMatches) {
        variableMatches.forEach(function(match) {
            const varName = match.replace(/[{}]/g, '').split(':')[0].split('|')[0].trim();
            if (!variables.includes(varName) && !isSystemVariable(varName)) {
                variables.push(varName);
            }
        });
    }
    
    setTimeout(function() {
        let html = '';
        if (errors.length === 0) {
            html = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Template syntax is valid</div>';
            
            if (variables.length > 0) {
                html += '<div class="mt-2"><strong>Found variables:</strong><br>';
                variables.forEach(function(variable) {
                    html += '<span class="badge bg-secondary me-1">' + variable + '</span>';
                });
                html += '</div>';
            }
        } else {
            html = '<div class="alert alert-warning"><strong>Issues found:</strong><ul class="mb-0">';
            errors.forEach(function(error) {
                html += '<li>' + error + '</li>';
            });
            html += '</ul></div>';
        }
        
        resultDiv.innerHTML = html;
    }, 500);
}

function generatePreview() {
    let defaultVars = {};
    try {
        const defaultVarsText = document.getElementById('default_variables_json').value;
        if (defaultVarsText.trim()) {
            defaultVars = JSON.parse(defaultVarsText);
        }
    } catch (e) {
        console.warn('Invalid JSON in default variables');
    }
    
    // Get current user data safely
    const currentUserName = '{!! optional(auth()->user())->name ?: "John Doe" !!}';
    const currentUserEmail = '{!! optional(auth()->user())->email ?: "john.doe@company.com" !!}';
    
    const systemData = {
        // User data from LDAP/AD
        user_name: currentUserName,
        user_email: currentUserEmail,
        user_first_name: currentUserName.split(' ')[0] || 'John',
        user_last_name: currentUserName.split(' ')[1] || 'Doe',
        user_department: 'Information Technology',
        user_title: 'Software Developer',
        
        // System variables
        current_date: new Date().toISOString().split('T')[0],
        current_time: new Date().toTimeString().split(' ')[0],
        current_datetime: new Date().toISOString().replace('T', ' ').split('.')[0],
        app_name: '{!! config("app.name", "Smart Notification") !!}',
        app_url: '{!! config("app.url", "http://localhost") !!}',
        year: new Date().getFullYear().toString(),
        month: (new Date().getMonth() + 1).toString().padStart(2, '0'),
        day: new Date().getDate().toString().padStart(2, '0'),
        
        // Sample custom variables
        message: 'This is a sample notification message',
        subject: 'Important System Notification', 
        company: '{!! config("app.name", "Your Company") !!}',
        url: 'https://example.com/action',
        priority: 'High',
        status: 'Active',
        amount: '1,000.00',
        deadline: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]
    };
    
    // Override with user defaults
    Object.assign(systemData, defaultVars);
    
    generateClientSidePreview(systemData);
}

function generateClientSidePreview(systemData) {
    const subjectTemplate = document.getElementById('subject_template').value;
    const bodyTextTemplate = document.getElementById('body_text_template').value;
    let bodyHtmlTemplate = '';
    
    // Get TinyMCE content if available
    const htmlEditor = tinymce.get('body_html_template');
    if (htmlEditor) {
        bodyHtmlTemplate = htmlEditor.getContent();
    } else {
        bodyHtmlTemplate = document.getElementById('body_html_template').value;
    }
    
    // Simple variable replacement
    let previewSubject = subjectTemplate;
    let previewHtml = bodyHtmlTemplate;
    let previewText = bodyTextTemplate;
    
    Object.entries(systemData).forEach(function([key, value]) {
        const regex = new RegExp('\\{\\{\\s*' + key + '\\s*\\}\\}', 'g');
        previewSubject = previewSubject.replace(regex, value);
        previewHtml = previewHtml.replace(regex, value);
        previewText = previewText.replace(regex, value);
    });
    
    const preview = {
        subject: previewSubject,
        body_html: previewHtml,
        body_text: previewText
    };
    
    displayPreview(preview, systemData);
}

function displayPreview(preview, systemData) {
    let html = '<div class="row">';
    
    if (preview.subject) {
        html += '<div class="col-12 mb-3"><h6>Subject:</h6><div class="alert alert-info">' + preview.subject + '</div></div>';
    }
    
    if (preview.body_html) {
        html += '<div class="col-md-6"><h6>HTML Preview:</h6><div class="border rounded p-3">' + preview.body_html + '</div></div>';
    }
    
    if (preview.body_text) {
        html += '<div class="col-md-6"><h6>Text Preview:</h6><div class="bg-light border rounded p-3"><pre>' + preview.body_text + '</pre></div></div>';
    }
    
    html += '</div>';
    
    html += '<hr><div class="mt-3">';
    html += '<h6>Sample Data Used:</h6>';
    html += '<div class="table-responsive">';
    html += '<table class="table table-sm table-bordered">';
    html += '<thead><tr><th>Variable</th><th>Value</th></tr></thead>';
    html += '<tbody>';

    // จัดกลุ่มตัวแปร
    const groups = {
        'User Information': ['user_name', 'user_email', 'user_first_name', 'user_last_name', 'user_department', 'user_title'],
        'Date & Time': ['current_date', 'current_time', 'current_datetime', 'year', 'month', 'day'],
        'System': ['app_name', 'app_url', 'company'],
        'Content': ['message', 'subject', 'url', 'priority', 'status', 'amount', 'deadline'],
        'Alert Details': ['alert_title', 'severity_level', 'incident_time', 'description', 'impact', 'action_required']
    };

    // แสดงผลตามกลุ่ม
    Object.entries(groups).forEach(([groupName, variables]) => {
        html += `<tr class="table-light"><th colspan="2">${groupName}</th></tr>`;
        variables.forEach(key => {
            if (key in systemData) {
                let value = systemData[key];
                // ทำความสะอาดค่าที่มี Blade syntax
                value = value.replace(/\{!!\s*(.+?)\s*!!\}/g, '$1');
                html += `<tr>
                    <td><code>{{${key}}}</code></td>
                    <td>${value}</td>
                </tr>`;
            }
        });
    });

    html += '</tbody></table></div></div>';
    
    document.getElementById('previewContent').innerHTML = html;
    new bootstrap.Modal(document.getElementById('previewModal')).show();
}

// Setup function
function setupVariableBadgeHandlers() {
    document.querySelectorAll('.variable-badge[data-variable]').forEach(function(badge) {
        badge.addEventListener('click', function() {
            const variable = this.getAttribute('data-variable');
            const sampleValue = this.getAttribute('data-sample');
            
            insertVariable(variable);
            
            // Show sample value in tooltip or notification
            if (sampleValue) {
                // Create temporary tooltip showing current value
                const tooltip = document.createElement('div');
                tooltip.className = 'position-absolute bg-dark text-white p-2 rounded small';
                tooltip.style.zIndex = '9999';
                tooltip.style.top = (this.offsetTop - 40) + 'px';
                tooltip.style.left = this.offsetLeft + 'px';
                tooltip.textContent = 'Sample: ' + sampleValue;
                
                this.parentElement.appendChild(tooltip);
                
                setTimeout(function() {
                    if (tooltip.parentElement) {
                        tooltip.parentElement.removeChild(tooltip);
                    }
                }, 2000);
            }
            
            // Visual feedback
            this.style.backgroundColor = '#0d6efd';
            this.style.color = 'white';
            const self = this;
            setTimeout(function() {
                self.style.backgroundColor = '';
                self.style.color = '';
            }, 200);
        });
        
        badge.style.cursor = 'pointer';
        badge.title = badge.getAttribute('data-sample') ? 
            'Click to insert • Current: ' + badge.getAttribute('data-sample') : 
            'Click to insert variable';
    });
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction() {
        const args = arguments;
        const later = function() {
            clearTimeout(timeout);
            func.apply(this, args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Auto-generate slug from name
function generateSlugFromName() {
    const nameField = document.getElementById('name');
    const slugField = document.getElementById('slug');
    
    if (nameField && nameField.value && slugField && !slugField.value) {
        const slug = nameField.value
            .toLowerCase()
            .trim()
            .replace(/[^a-z0-9\s-]/g, '') // Remove special characters
            .replace(/\s+/g, '-') // Replace spaces with hyphens
            .replace(/-+/g, '-') // Replace multiple hyphens with single
            .replace(/^-|-$/g, ''); // Remove leading/trailing hyphens
        
        slugField.value = slug;
    }
}

// Setup variable detection with improved TinyMCE integration
function setupVariableDetection() {
    console.log('Setting up variable detection...');
    
    if (variableDetectionInitialized) {
        console.log('Variable detection already initialized');
        return;
    }
    
    const subjectField = document.getElementById('subject_template');
    const textField = document.getElementById('body_text_template');
    
    // Setup for regular input fields
    [subjectField, textField].forEach(function(field) {
        if (field) {
            field.addEventListener('input', debounce(updateDetectedVariables, 500));
            field.addEventListener('blur', updateDetectedVariables);
            console.log('Added event listeners to', field.id);
        }
    });
    
    // Setup for TinyMCE - ใช้ตัวเลือกหลายแบบ
    function setupTinyMCEListeners() {
        const htmlEditor = tinymce.get('body_html_template');
        if (htmlEditor) {
            console.log('Setting up TinyMCE event listeners...');
            
            // เพิ่ม event listeners หลายแบบเพื่อให้แน่ใจว่าจะจับได้
            htmlEditor.on('input', debounce(updateDetectedVariables, 500));
            htmlEditor.on('change', debounce(updateDetectedVariables, 500));
            htmlEditor.on('keyup', debounce(updateDetectedVariables, 1000));
            htmlEditor.on('paste', debounce(updateDetectedVariables, 1000));
            htmlEditor.on('undo', debounce(updateDetectedVariables, 500));
            htmlEditor.on('redo', debounce(updateDetectedVariables, 500));
            htmlEditor.on('setcontent', debounce(updateDetectedVariables, 500));
            
            console.log('TinyMCE event listeners added');
            variableDetectionInitialized = true;
            
            // เรียกใช้ทันทีเพื่อตรวจจับตัวแปรที่มีอยู่
            setTimeout(updateDetectedVariables, 1000);
        } else {
            console.log('TinyMCE not ready yet, retrying...');
            setTimeout(setupTinyMCEListeners, 1000);
        }
    }
    
    // เรียกใช้หลังจาก TinyMCE โหลดเสร็จ
    setTimeout(setupTinyMCEListeners, 1500);
}

// Document ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Content Loaded');
    
    // Initialize TinyMCE
    tinymce.init({
        selector: '#body_html_template',
        height: 300,
        menubar: false,
        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'help', 'wordcount'
        ],
        toolbar: 'undo redo | blocks | bold italic forecolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | code | help',
        content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
        setup: function (editor) {
            editor.on('focus', function () {
                lastFocusedElement = {
                    isTinyMCE: true,
                    editorId: editor.id,
                    editor: editor
                };
            });
            
            editor.on('click', function () {
                lastFocusedElement = {
                    isTinyMCE: true,
                    editorId: editor.id,
                    editor: editor
                };
            });
        },
        init_instance_callback: function(editor) {
            console.log('TinyMCE initialized successfully');
            // เริ่ม variable detection หลังจาก TinyMCE พร้อม
            setTimeout(function() {
                setupVariableDetection();
            }, 500);
        }
    });

    // Setup input focus tracking
    const inputs = document.querySelectorAll('textarea:not(#body_html_template), input[type="text"]');
    inputs.forEach(function(input) {
        input.addEventListener('focus', function() {
            lastFocusedElement = this;
        });
        
        input.addEventListener('click', function() {
            lastFocusedElement = this;
        });
    });

    // Setup variable badge handlers
    setupVariableBadgeHandlers();

    // Custom variable enter key
    const customVariableInput = document.getElementById('customVariable');
    if (customVariableInput) {
        customVariableInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                addCustomVariableFunction();
            }
        });
    }

    // Preview button
    const previewBtn = document.getElementById('previewBtn');
    if (previewBtn) {
        previewBtn.addEventListener('click', function() {
            generatePreview();
        });
    }

    // Form submission
    const templateForm = document.getElementById('templateForm');
    if (templateForm) {
        templateForm.addEventListener('submit', function(e) {
            console.log('Form submission started...');
            
            // Sync TinyMCE content
            if (tinymce.get('body_html_template')) {
                tinymce.get('body_html_template').save();
                console.log('TinyMCE content synced');
            }

            // Process default variables JSON
            const defaultVarsField = document.getElementById('default_variables_json');
            console.log('Default variables field value:', defaultVarsField.value);
            
            if (defaultVarsField.value.trim()) {
                try {
                    const parsed = JSON.parse(defaultVarsField.value);
                    console.log('Parsed JSON:', parsed);
                    
                    if (typeof parsed === 'object' && !Array.isArray(parsed)) {
                        const hiddenInput = document.createElement('input');
                        hiddenInput.type = 'hidden';
                        hiddenInput.name = 'default_variables';
                        hiddenInput.value = JSON.stringify(parsed);
                        this.appendChild(hiddenInput);
                        console.log('Added hidden input for default_variables');
                        
                        defaultVarsField.name = '';
                    } else {
                        e.preventDefault();
                        console.error('Default variables is not a JSON object');
                        alert('Default variables must be a JSON object, not an array. Example: {"variable": "value"}');
                        defaultVarsField.focus();
                        return false;
                    }
                } catch (error) {
                    e.preventDefault();
                    console.error('JSON parse error:', error);
                    alert('Invalid JSON format in default variables: ' + error.message);
                    defaultVarsField.focus();
                    return false;
                }
            } else {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'default_variables';
                hiddenInput.value = '{}';
                this.appendChild(hiddenInput);
                console.log('Added empty object for default_variables');
                
                defaultVarsField.name = '';
            }
            
            console.log('Form submission proceeding...');
        });
    }
    
    // Auto-generate slug when name changes
    const nameField = document.getElementById('name');
    if (nameField) {
        nameField.addEventListener('input', debounce(generateSlugFromName, 300));
        nameField.addEventListener('blur', generateSlugFromName);
    }

    // Manual button to trigger variable detection (for testing)
    const validateBtn = document.getElementById('validateBtn');
    if (validateBtn) {
        validateBtn.addEventListener('click', function() {
            console.log('Manual validation triggered');
            updateDetectedVariables();
        });
    }
});