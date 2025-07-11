/**
 * Smart Notification System - Template Creator
 * Complete JavaScript file for creating notification templates
 */

// Global variables
let currentStep = 1;
const totalSteps = 4;
let tinyMCEInitialized = false;
let lastFocusedElement = null;
let variableDetectionInitialized = false;

/**
 * TinyMCE Initialization
 */
function initializeTinyMCE() {
    if (tinyMCEInitialized || typeof tinymce === 'undefined') {
        return;
    }
    
    tinymce.init({
        selector: '#body_html_template',
        height: 400,
        menubar: false,
        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'help', 'wordcount'
        ],
        toolbar: 'undo redo | formatselect | ' +
            'bold italic backcolor | alignleft aligncenter ' +
            'alignright alignjustify | bullist numlist outdent indent | ' +
            'removeformat | code | insertvariable | help',
        content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif; font-size: 14px; }',
        setup: function(editor) {
            // เพิ่มปุ่มแทรกตัวแปร
            editor.ui.registry.addMenuButton('insertvariable', {
                text: 'Variables',
                icon: 'template',
                fetch: function(callback) {
                    const items = [
                        {
                            type: 'menuitem',
                            text: 'User Name',
                            onAction: function() {
                                editor.insertContent('{{user_name}}');
                            }
                        },
                        {
                            type: 'menuitem',
                            text: 'User Email',
                            onAction: function() {
                                editor.insertContent('{{user_email}}');
                            }
                        },
                        {
                            type: 'menuitem',
                            text: 'Message',
                            onAction: function() {
                                editor.insertContent('{{message}}');
                            }
                        },
                        {
                            type: 'menuitem',
                            text: 'Company',
                            onAction: function() {
                                editor.insertContent('{{company}}');
                            }
                        },
                        {
                            type: 'menuitem',
                            text: 'Current Date',
                            onAction: function() {
                                editor.insertContent('{{current_date}}');
                            }
                        }
                    ];
                    callback(items);
                }
            });
            
            // Track focus for variable insertion
            editor.on('focus', function() {
                lastFocusedElement = {
                    isTinyMCE: true,
                    editorId: editor.id,
                    editor: editor
                };
            });
            
            editor.on('click', function() {
                lastFocusedElement = {
                    isTinyMCE: true,
                    editorId: editor.id,
                    editor: editor
                };
            });
        },
        init_instance_callback: function(editor) {
            console.log('TinyMCE initialized for:', editor.id);
            tinyMCEInitialized = true;
            
            // Setup variable detection after TinyMCE is ready
            setTimeout(function() {
                setupVariableDetection();
            }, 500);
            
            // เพิ่ม event listener สำหรับ focus
            editor.on('focus', function() {
                console.log('TinyMCE focused');
            });
        }
    });
}

function destroyTinyMCE() {
    if (typeof tinymce !== 'undefined' && tinymce.get('body_html_template')) {
        tinymce.get('body_html_template').remove();
        tinyMCEInitialized = false;
    }
}

/**
 * Quick Start Functions
 */
function startFromScratch() {
    console.log('Starting from scratch...');
    
    // ซ่อนส่วน Quick Start
    const quickStart = document.getElementById('quickStart');
    if (quickStart) {
        quickStart.style.display = 'none';
    }
    
    // ซ่อน Template Gallery ถ้ามี
    const templateGallery = document.getElementById('templateGallery');
    if (templateGallery) {
        templateGallery.style.display = 'none';
    }
    
    // แสดงฟอร์มหลัก
    const mainForm = document.getElementById('mainForm');
    if (mainForm) {
        mainForm.style.display = 'block';
    }
    
    // แสดง step indicator
    const stepIndicator = document.getElementById('stepIndicator');
    if (stepIndicator) {
        stepIndicator.style.display = 'flex';
    }
    
    // เริ่มที่ขั้นตอนที่ 1
    showStep(1);
}

function showTemplateGallery() {
    console.log('Showing template gallery...');
    
    // ซ่อนส่วน Quick Start
    const quickStart = document.getElementById('quickStart');
    if (quickStart) {
        quickStart.style.display = 'none';
    }
    
    // ซ่อนฟอร์มหลัก
    const mainForm = document.getElementById('mainForm');
    if (mainForm) {
        mainForm.style.display = 'none';
    }
    
    // แสดง Template Gallery
    const templateGallery = document.getElementById('templateGallery');
    if (templateGallery) {
        templateGallery.style.display = 'block';
    }
    
    // ซ่อน step indicator
    const stepIndicator = document.getElementById('stepIndicator');
    if (stepIndicator) {
        stepIndicator.style.display = 'none';
    }
}

function hideTemplateGallery() {
    console.log('Hiding template gallery...');
    
    // ซ่อน Template Gallery
    const templateGallery = document.getElementById('templateGallery');
    if (templateGallery) {
        templateGallery.style.display = 'none';
    }
    
    // แสดงส่วน Quick Start
    const quickStart = document.getElementById('quickStart');
    if (quickStart) {
        quickStart.style.display = 'block';
    }
}

function importTemplate() {
    console.log('Importing template...');
    
    // สร้าง input file ชั่วคราว
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = '.json';
    
    input.onchange = function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const template = JSON.parse(e.target.result);
                    fillTemplateForm(template);
                    const templateGallery = document.getElementById('templateGallery');
                    if (templateGallery) {
                        templateGallery.style.display = 'none';
                    }
                    startFromScratch();
                } catch (error) {
                    alert('ไฟล์ไม่ถูกต้อง กรุณาเลือกไฟล์ JSON ที่ถูกต้อง');
                }
            };
            reader.readAsText(file);
        }
    };

    input.click();
}

function useTemplate(templateId) {
    console.log('Using template:', templateId);
    
    // ข้อมูลเทมเพลตตัวอย่าง
    const templates = {
        system_alert: {
            name: 'System Alert Template',
            category: 'system',
            priority: 'high',
            description: 'Critical system notifications and alerts for emergency situations',
            subject_template: '[{{priority}}] System Alert: {{subject}}',
            body_html_template: `<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background-color: #dc3545; color: white; padding: 20px; text-align: center;">
        <h1 style="margin: 0; font-size: 24px;">🚨 SYSTEM ALERT</h1>
    </div>
    <div style="padding: 20px; background-color: #f8f9fa; border: 1px solid #dee2e6;">
        <h2 style="color: #dc3545; margin-top: 0;">{{subject}}</h2>
        <p style="font-size: 16px; line-height: 1.5;">{{message}}</p>
        <div style="background-color: white; padding: 15px; border-left: 4px solid #dc3545; margin: 20px 0;">
            <strong>Priority:</strong> {{priority}}<br>
            <strong>Time:</strong> {{current_datetime}}
        </div>
        <p style="margin-bottom: 0;">Please take immediate action if required.</p>
    </div>
</div>`,
            body_text_template: 'SYSTEM ALERT [{{priority}}]: {{subject}}\n\n{{message}}\n\nTime: {{current_datetime}}\nPriority: {{priority}}\n\nPlease take immediate action if required.',
            default_variables_json: JSON.stringify({
                subject: 'Database Connection Issue',
                message: 'The main database server is experiencing connectivity issues.',
                priority: 'HIGH'
            }, null, 2)
        },
        marketing_email: {
            name: 'Marketing Email Template',
            category: 'marketing',
            priority: 'normal',
            description: 'Promotional emails and newsletters with beautiful design',
            subject_template: '{{subject}} - {{company}}',
            body_html_template: `<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center;">
        <h1 style="margin: 0; font-size: 28px;">{{company}}</h1>
    </div>
    <div style="padding: 30px; background-color: white;">
        <h2 style="color: #333; margin-top: 0;">Hello {{user_name}},</h2>
        <p style="font-size: 16px; line-height: 1.6; color: #555;">{{message}}</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{action_url}}" style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;">{{action_text}}</a>
        </div>
        
        <p style="font-size: 14px; color: #888; margin-bottom: 0;">Best regards,<br>The {{company}} Team</p>
    </div>
</div>`,
            body_text_template: 'Hello {{user_name}},\n\n{{message}}\n\n{{action_text}}: {{action_url}}\n\nBest regards,\nThe {{company}} Team',
            default_variables_json: JSON.stringify({
                subject: 'Special Offer Just for You!',
                company: 'Smart Notify',
                message: 'We have an exclusive offer that we think you\'ll love!',
                action_text: 'View Offer',
                action_url: 'https://example.com/offer'
            }, null, 2)
        },
        meeting_reminder: {
            name: 'Meeting Reminder Template',
            category: 'operational',
            priority: 'medium',
            description: 'Meeting reminders and calendar notifications',
            subject_template: 'Reminder: {{meeting_title}} - {{meeting_date}}',
            body_html_template: `<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
    <div style="background-color: #0d6efd; color: white; padding: 20px; text-align: center;">
        <h1 style="margin: 0; font-size: 24px;">📅 Meeting Reminder</h1>
    </div>
    <div style="padding: 20px; background-color: white; border: 1px solid #dee2e6;">
        <h2 style="color: #0d6efd; margin-top: 0;">{{meeting_title}}</h2>
        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 5px 0; font-weight: bold; width: 100px;">Date:</td>
                    <td style="padding: 5px 0;">{{meeting_date}}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 0; font-weight: bold;">Time:</td>
                    <td style="padding: 5px 0;">{{meeting_time}}</td>
                </tr>
                <tr>
                    <td style="padding: 5px 0; font-weight: bold;">Location:</td>
                    <td style="padding: 5px 0;">{{meeting_location}}</td>
                </tr>
            </table>
        </div>
        <h3 style="color: #333;">Agenda:</h3>
        <p style="font-size: 16px; line-height: 1.5;">{{agenda}}</p>
    </div>
</div>`,
            body_text_template: 'MEETING REMINDER\n\n{{meeting_title}}\n\nDate: {{meeting_date}}\nTime: {{meeting_time}}\nLocation: {{meeting_location}}\n\nAgenda:\n{{agenda}}',
            default_variables_json: JSON.stringify({
                meeting_title: 'Weekly Team Standup',
                meeting_date: '2025-06-20',
                meeting_time: '10:00 AM - 11:00 AM',
                meeting_location: 'Conference Room A',
                agenda: '1. Review progress\n2. Discuss blockers\n3. Plan next week'
            }, null, 2)
        }
    };
    
    const template = templates[templateId];
    if (template) {
        fillTemplateForm(template);
    }
    
    // ซ่อน Template Gallery และแสดงฟอร์มหลัก
    hideTemplateGallery();
    startFromScratch();
}

function fillTemplateForm(template) {
    // เติมข้อมูลพื้นฐาน
    if (template.name) {
        const nameField = document.getElementById('name');
        if (nameField) nameField.value = template.name;
    }
    if (template.description) {
        const descField = document.getElementById('description');
        if (descField) descField.value = template.description;
    }
    if (template.category) {
        const catField = document.getElementById('category');
        if (catField) catField.value = template.category;
    }
    if (template.priority) {
        const priField = document.getElementById('priority');
        if (priField) priField.value = template.priority;
    }
    
    // เติมข้อมูลเนื้อหา
    if (template.subject_template) {
        const subjectField = document.getElementById('subject_template');
        if (subjectField) subjectField.value = template.subject_template;
    }
    if (template.body_html_template) {
        const htmlField = document.getElementById('body_html_template');
        if (htmlField) htmlField.value = template.body_html_template;
    }
    if (template.body_text_template) {
        const textField = document.getElementById('body_text_template');
        if (textField) textField.value = template.body_text_template;
    }
    
    // เติมข้อมูลตัวแปร
    if (template.default_variables_json) {
        const varsField = document.getElementById('default_variables_json');
        if (varsField) varsField.value = template.default_variables_json;
    }
    
    // เลือก channels ที่เหมาะสม
    const emailCheck = document.getElementById('channel_email');
    const teamsCheck = document.getElementById('channel_teams');
    const smsCheck = document.getElementById('channel_sms');
    
    if (template.body_html_template) {
        if (emailCheck) emailCheck.checked = true;
        if (teamsCheck) teamsCheck.checked = true;
    }
    if (template.body_text_template) {
        if (smsCheck) smsCheck.checked = true;
    }
}

/**
 * Step Management Functions
 */
function showStep(step) {
    console.log('Showing step:', step);
    
    // หาก step ปัจจุบันคือ 2 และกำลังจะออกไป ให้บันทึกเนื้อหา TinyMCE
    if (currentStep === 2 && step !== 2) {
        const htmlEditor = tinymce.get('body_html_template');
        if (htmlEditor) {
            const content = htmlEditor.getContent();
            const htmlField = document.getElementById('body_html_template');
            if (htmlField) {
                htmlField.value = content;
            }
        }
    }
    
    // ซ่อนทุก step
    document.querySelectorAll('.form-step').forEach(el => {
        el.style.display = 'none';
    });
    
    // แสดง step ที่ต้องการ
    const currentStepElement = document.querySelector(`.form-step[data-step="${step}"]`);
    if (currentStepElement) {
        currentStepElement.style.display = 'block';
    }
    
    // เริ่มต้น TinyMCE เมื่อไปถึง step 2
    if (step === 2) {
        setTimeout(() => {
            if (!tinyMCEInitialized) {
                initializeTinyMCE();
            } else {
                const htmlEditor = tinymce.get('body_html_template');
                if (!htmlEditor) {
                    tinyMCEInitialized = false;
                    initializeTinyMCE();
                }
            }
        }, 200);
    }
    
    // ตรวจจับตัวแปรเมื่อไปถึง step 3
    if (step === 3) {
        setTimeout(() => {
            updateDetectedVariables();
            updateDefaultVariablesFromDetected();
        }, 100);
    }
    
    // อัพเดท step indicator, buttons, และ progress
    updateStepIndicator(step);
    updateButtons(step);
    updateProgress(step);
    
    currentStep = step;
}

function updateStepIndicator(step) {
    document.querySelectorAll('.step').forEach((stepEl, index) => {
        const stepNumber = index + 1;
        stepEl.classList.remove('active', 'completed');
        
        if (stepNumber < step) {
            stepEl.classList.add('completed');
        } else if (stepNumber === step) {
            stepEl.classList.add('active');
        }
    });
}

function updateButtons(step) {
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const saveBtn = document.getElementById('saveBtn');
    
    // ปุ่มย้อนกลับ
    if (prevBtn) {
        prevBtn.style.display = step === 1 ? 'none' : 'inline-block';
    }
    
    // ปุ่มถัดไปและบันทึก
    if (nextBtn && saveBtn) {
        if (step === totalSteps) {
            nextBtn.style.display = 'none';
            saveBtn.style.display = 'inline-block';
        } else {
            nextBtn.style.display = 'inline-block';
            saveBtn.style.display = 'none';
        }
    }
}

function updateProgress(step) {
    const percentage = (step / totalSteps) * 100;
    const progressBar = document.getElementById('progress-bar');
    const progressPercentage = document.getElementById('progress-percentage');
    
    if (progressBar) {
        progressBar.style.width = percentage + '%';
    }
    
    if (progressPercentage) {
        progressPercentage.textContent = Math.round(percentage) + '%';
    }
    
    // อัพเดท step indicators ใน sidebar
    for (let i = 1; i <= totalSteps; i++) {
        const check = document.getElementById(`step${i}-check`);
        const pending = document.getElementById(`step${i}-pending`);
        
        if (check && pending) {
            if (i < step) {
                check.style.display = 'inline';
                pending.style.display = 'none';
            } else {
                check.style.display = 'none';
                pending.style.display = 'inline';
            }
        }
    }
}

function nextStep() {
    if (validateCurrentStep()) {
        if (currentStep < totalSteps) {
            showStep(currentStep + 1);
        }
    }
}

function previousStep() {
    if (currentStep > 1) {
        showStep(currentStep - 1);
    }
}

/**
 * Validation Functions
 */
function validateCurrentStep() {
    let isValid = true;
    
    // ลบข้อผิดพลาดเก่า
    document.querySelectorAll('.is-invalid').forEach(el => {
        el.classList.remove('is-invalid');
    });
    
    switch(currentStep) {
        case 1: // ข้อมูลพื้นฐาน
            const name = document.getElementById('name')?.value?.trim();
            const category = document.getElementById('category')?.value;
            const priority = document.getElementById('priority')?.value;
            const channels = document.querySelectorAll('input[name="supported_channels[]"]:checked');
            
            if (!name) {
                showFieldError('name', 'Template name is required');
                isValid = false;
            }
            if (!category) {
                showFieldError('category', 'Please select a category');
                isValid = false;
            }
            if (!priority) {
                showFieldError('priority', 'Please select a priority');
                isValid = false;
            }
            if (channels.length === 0) {
                alert('Please select at least one notification channel');
                isValid = false;
            }
            break;
            
        case 2: // เนื้อหาเทมเพลต
            const subject = document.getElementById('subject_template')?.value?.trim();
            
            // ตรวจสอบ HTML content จาก TinyMCE
            let htmlContent = '';
            const htmlEditor = tinymce.get('body_html_template');
            if (htmlEditor) {
                htmlContent = htmlEditor.getContent().trim();
            } else {
                htmlContent = document.getElementById('body_html_template')?.value?.trim() || '';
            }
            
            const textContent = document.getElementById('body_text_template')?.value?.trim() || '';
            
            if (!subject) {
                showFieldError('subject_template', 'Subject template is required');
                isValid = false;
            }
            if (!htmlContent && !textContent) {
                alert('Please provide at least one content template (HTML or Text)');
                isValid = false;
            }
            break;
            
        case 3: // ตัวแปร
            const defaultVars = document.getElementById('default_variables_json')?.value?.trim();
            if (defaultVars) {
                try {
                    JSON.parse(defaultVars);
                } catch (e) {
                    showFieldError('default_variables_json', 'Invalid JSON format');
                    isValid = false;
                }
            }
            break;
    }
    
    return isValid;
}

function showFieldError(fieldId, message) {
    const field = document.getElementById(fieldId);
    if (field) {
        field.classList.add('is-invalid');
        
        // สร้างหรืออัพเดทข้อความผิดพลาด
        let feedback = field.parentNode.querySelector('.invalid-feedback');
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            field.parentNode.appendChild(feedback);
        }
        feedback.textContent = message;
        feedback.style.display = 'block';
    }
}

/**
 * Variable Detection and Management
 */
function detectVariablesFromContent() {
    console.log('Detecting variables from content...');
    
    const subjectTemplate = document.getElementById('subject_template')?.value || '';
    const bodyTextTemplate = document.getElementById('body_text_template')?.value || '';
    let bodyHtmlTemplate = '';
    
    // ดึงเนื้อหาจาก TinyMCE
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
    
    // Extract simple variables using regex
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
                        addDetectedVariableToRequired(varName);
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

function updateDefaultVariablesFromDetected() {
    const detectedVars = detectVariablesFromContent();
    const defaultVarsTextarea = document.getElementById('default_variables_json');
    
    if (defaultVarsTextarea && detectedVars.length > 0) {
        let currentVars = {};
        
        // ถ้ามี JSON อยู่แล้ว ให้ parse ก่อน
        const currentJson = defaultVarsTextarea.value.trim();
        if (currentJson) {
            try {
                currentVars = JSON.parse(currentJson);
            } catch (e) {
                console.warn('Invalid JSON in default variables, starting fresh');
                currentVars = {};
            }
        }
        
        // เพิ่มตัวแปรที่ตรวจพบ (ถ้ายังไม่มี)
        detectedVars.forEach(variable => {
            if (!currentVars[variable]) {
                currentVars[variable] = getSampleValueForVariable(variable);
            }
        });
        
        // อัพเดท textarea
        defaultVarsTextarea.value = JSON.stringify(currentVars, null, 2);
    }
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

function addDetectedVariableToRequired(variableName) {
    // ตรวจสอบว่าตัวแปรนี้มีอยู่แล้วหรือไม่
    const existingRows = document.querySelectorAll('.variable-row');
    let exists = false;
    
    existingRows.forEach(row => {
        const nameInput = row.querySelector('input[placeholder="Variable name"]');
        if (nameInput && nameInput.value.trim() === variableName) {
            exists = true;
        }
    });
    
    // ถ้ายังไม่มี ให้เพิ่มใหม่
    if (!exists) {
        const container = document.getElementById('variables-container');
        if (container) {
            const variableCount = container.children.length;
            
            const row = document.createElement('div');
            row.className = 'row mb-3 variable-row';
            row.innerHTML = `
                <div class="col-md-4">
                    <input type="text" class="form-control" placeholder="Variable name" 
                           name="variables[${variableCount}][name]" value="${variableName}" readonly>
                </div>
                <div class="col-md-4">
                    <input type="text" class="form-control" placeholder="Default value" 
                           name="variables[${variableCount}][default]">
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="variables[${variableCount}][type]">
                        <option value="text">Text</option>
                        <option value="number">Number</option>
                        <option value="date">Date</option>
                        <option value="url">URL</option>
                        <option value="email">Email</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeVariableRow(this)">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            
            container.appendChild(row);
            
            // เพิ่ม animation
            row.classList.add('animate__animated', 'animate__fadeIn');
        }
    }
}

function getSampleValueForVariable(variableName) {
    const sampleValues = {
        'subject': 'Sample Subject',
        'message': 'Sample message content',
        'company': 'SAM',
        'priority': 'HIGH',
        'status': 'Active',
        'url': 'https://example.com',
        'action_url': 'https://example.com/action',
        'action_text': 'Click Here',
        'deadline': '2025-12-31',
        'amount': '1000',
        'meeting_title': 'Team Meeting',
        'meeting_date': '2025-06-20',
        'meeting_time': '10:00 AM',
        'meeting_location': 'Conference Room A',
        'agenda': 'Discussion topics',
        'project_name': 'Sample Project',
        'user_department': 'IT Department',
        'user_title': 'Software Developer',
        'sender_name': 'System Administrator'
    };
    
    return sampleValues[variableName] || `Sample ${variableName}`;
}

function isSystemVariable(variable) {
    const systemVars = [
        'app_name', 'app_url', 'current_date', 'current_time', 'current_datetime', 
        'year', 'month', 'day', 'user_name', 'user_email', 'user_first_name', 
        'user_last_name', 'user_department', 'user_title'
    ];
    return systemVars.includes(variable);
}

/**
 * Variable Insertion Functions
 */
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

function addCustomVariableFunction() {
    const customVar = document.getElementById('customVariable')?.value?.trim();
    if (customVar) {
        insertVariable(customVar);
        addVariableToList(customVar);
        document.getElementById('customVariable').value = '';
        
        // Visual feedback
        const input = document.getElementById('customVariable');
        if (input) {
            input.style.borderColor = '#28a745';
            setTimeout(function() {
                input.style.borderColor = '';
            }, 1000);
        }
    } else {
        alert('Please enter a variable name');
        const input = document.getElementById('customVariable');
        if (input) input.focus();
    }
}

function addVariable() {
    const container = document.getElementById('variables-container');
    if (!container) return;
    
    const variableCount = container.children.length;
    
    const row = document.createElement('div');
    row.className = 'row mb-3 variable-row';
    row.innerHTML = `
        <div class="col-md-4">
            <input type="text" class="form-control" placeholder="Variable name" name="variables[${variableCount}][name]">
        </div>
        <div class="col-md-4">
            <input type="text" class="form-control" placeholder="Default value" name="variables[${variableCount}][default]">
        </div>
        <div class="col-md-3">
            <select class="form-select" name="variables[${variableCount}][type]">
                <option value="text">Text</option>
                <option value="number">Number</option>
                <option value="date">Date</option>
                <option value="url">URL</option>
                <option value="email">Email</option>
            </select>
        </div>
        <div class="col-md-1">
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeVariableRow(this)">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    container.appendChild(row);
}

function removeVariableRow(button) {
    const row = button.closest('.variable-row');
    if (row) row.remove();
}

function addVariableToList(varName) {
    const existingInputs = document.querySelectorAll('#variables-container input[name*="[name]"]');
    for (let input of existingInputs) {
        if (input.value === varName) {
            return;
        }
    }
    
    const container = document.getElementById('variables-container');
    if (container) {
        const variableCount = container.children.length;
        
        const row = document.createElement('div');
        row.className = 'row mb-3 variable-row';
        row.innerHTML = `
            <div class="col-md-4">
                <input type="text" class="form-control" placeholder="Variable name" 
                       name="variables[${variableCount}][name]" value="${varName}">
            </div>
            <div class="col-md-4">
                <input type="text" class="form-control" placeholder="Default value" 
                       name="variables[${variableCount}][default]">
            </div>
            <div class="col-md-3">
                <select class="form-select" name="variables[${variableCount}][type]">
                    <option value="text">Text</option>
                    <option value="number">Number</option>
                    <option value="date">Date</option>
                    <option value="url">URL</option>
                    <option value="email">Email</option>
                </select>
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeVariableRow(this)">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        container.appendChild(row);
    }
}

/**
 * Content Generation Functions
 */
function generateSampleContent() {
    const category = document.getElementById('category')?.value;
    
    let sampleSubject = '';
    let sampleHtml = '';
    let sampleText = '';
    
    switch(category) {
        case 'system':
            sampleSubject = '[{{priority}}] System Alert: {{subject}}';
            sampleHtml = `<h2 style="color: #dc3545;">System Alert</h2>
<p>Dear {{user_name}},</p>
<p>{{message}}</p>
<p><strong>Priority:</strong> {{priority}}<br>
<strong>Time:</strong> {{current_datetime}}</p>`;
            sampleText = 'SYSTEM ALERT: {{subject}}\n\n{{message}}\n\nPriority: {{priority}}\nTime: {{current_datetime}}';
            break;
            
        case 'marketing':
            sampleSubject = '{{subject}} - {{company}}';
            sampleHtml = `<h2>Hello {{user_name}}!</h2>
<p>{{message}}</p>
<p style="text-align: center;">
<a href="{{action_url}}" style="background-color: #0d6efd; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">{{action_text}}</a>
</p>
<p>Best regards,<br>The {{company}} Team</p>`;
            sampleText = 'Hello {{user_name}}!\n\n{{message}}\n\n{{action_text}}: {{action_url}}\n\nBest regards,\nThe {{company}} Team';
            break;
            
        default:
            sampleSubject = '{{subject}}';
            sampleHtml = `<p>Dear {{user_name}},</p>
<p>{{message}}</p>
<p>Best regards,<br>{{sender_name}}</p>`;
            sampleText = 'Dear {{user_name}},\n\n{{message}}\n\nBest regards,\n{{sender_name}}';
    }
    
    // ใส่เนื้อหาตัวอย่างลงในฟอร์ม
    const subjectField = document.getElementById('subject_template');
    if (subjectField) subjectField.value = sampleSubject;
    
    // สำหรับ TinyMCE
    if (tinymce.get('body_html_template')) {
        tinymce.get('body_html_template').setContent(sampleHtml);
    } else {
        const htmlField = document.getElementById('body_html_template');
        if (htmlField) htmlField.value = sampleHtml;
    }
    
    // สำหรับ text field
    const textField = document.getElementById('body_text_template');
    if (textField) textField.value = sampleText;
}

function generateDefaultVariablesJSON() {
    const detectedVars = detectVariablesFromContent();
    const defaultVarsField = document.getElementById('default_variables_json');
    
    if (!defaultVarsField) return;
    
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

function formatJSON() {
    const textarea = document.getElementById('default_variables_json');
    if (!textarea) return;
    
    try {
        const json = JSON.parse(textarea.value);
        textarea.value = JSON.stringify(json, null, 2);
        showAlert('success', 'JSON formatted successfully');
    } catch (e) {
        showAlert('error', 'Invalid JSON format. Please check your syntax.');
        textarea.focus();
    }
}

/**
 * Preview Functions
 */
function previewContent() {
    const previewDiv = document.getElementById('content-preview');
    if (!previewDiv) return;
    
    previewDiv.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> กำลังสร้างตัวอย่าง...</div>';
    
    // ข้อมูลตัวอย่าง
    const sampleData = {
        user_name: 'John Doe',
        user_email: 'john.doe@company.com',
        subject: 'Sample Notification',
        message: 'This is a sample message content.',
        priority: 'HIGH',
        current_date: new Date().toLocaleDateString(),
        current_time: new Date().toLocaleTimeString(),
        current_datetime: new Date().toLocaleString(),
        company: 'Smart Notify',
        action_text: 'View Details',
        action_url: '#'
    };
    
    const subject = document.getElementById('subject_template')?.value || '';
    
    // ดึงเนื้อหาจาก TinyMCE
    let htmlContent = '';
    if (tinymce.get('body_html_template')) {
        htmlContent = tinymce.get('body_html_template').getContent();
    } else {
        htmlContent = document.getElementById('body_html_template')?.value || '';
    }
    
    // แทนที่ตัวแปร
    let previewSubject = replaceVariables(subject, sampleData);
    let previewHtml = replaceVariables(htmlContent, sampleData);
    
    previewDiv.innerHTML = `
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Preview</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>Subject:</strong>
                    <div class="alert alert-info small mb-0">${previewSubject}</div>
                </div>
                <div class="mb-3">
                    <strong>Content:</strong>
                    <div class="border rounded p-3 small" style="background-color: #f8f9fa;">${previewHtml}</div>
                </div>
            </div>
        </div>
    `;
}

function replaceVariables(template, data) {
    let result = template;
    for (const [key, value] of Object.entries(data)) {
        const regex = new RegExp(`\\{\\{\\s*${key}\\s*\\}\\}`, 'g');
        result = result.replace(regex, value);
    }
    return result;
}

function generatePreview() {
    let defaultVars = {};
    try {
        const defaultVarsText = document.getElementById('default_variables_json')?.value;
        if (defaultVarsText && defaultVarsText.trim()) {
            defaultVars = JSON.parse(defaultVarsText);
        }
    } catch (e) {
        console.warn('Invalid JSON in default variables');
    }
    
    // System data
    const systemData = {
        // User data
        user_name: 'John Doe',
        user_email: 'john.doe@company.com',
        user_first_name: 'John',
        user_last_name: 'Doe',
        user_department: 'Information Technology',
        user_title: 'Software Developer',
        
        // System variables
        current_date: new Date().toISOString().split('T')[0],
        current_time: new Date().toTimeString().split(' ')[0],
        current_datetime: new Date().toISOString().replace('T', ' ').split('.')[0],
        app_name: 'Smart Notification',
        app_url: window.location.origin,
        year: new Date().getFullYear().toString(),
        month: (new Date().getMonth() + 1).toString().padStart(2, '0'),
        day: new Date().getDate().toString().padStart(2, '0'),
        
        // Sample custom variables
        message: 'This is a sample notification message',
        subject: 'Important System Notification', 
        company: 'Your Company',
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
    const subjectTemplate = document.getElementById('subject_template')?.value || '';
    const bodyTextTemplate = document.getElementById('body_text_template')?.value || '';
    let bodyHtmlTemplate = '';
    
    // Get TinyMCE content if available
    const htmlEditor = tinymce.get('body_html_template');
    if (htmlEditor) {
        bodyHtmlTemplate = htmlEditor.getContent();
    } else {
        bodyHtmlTemplate = document.getElementById('body_html_template')?.value || '';
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
    
    // แสดงข้อมูลตัวอย่างที่ใช้
    html += '<hr><div class="mt-3">';
    html += '<h6>Sample Data Used:</h6>';
    html += '<div class="table-responsive">';
    html += '<table class="table table-sm table-bordered">';
    html += '<thead><tr><th>Variable</th><th>Value</th></tr></thead>';
    html += '<tbody>';

    Object.entries(systemData).forEach(([key, value]) => {
        html += `<tr>
            <td><code>{{${key}}}</code></td>
            <td>${value}</td>
        </tr>`;
    });

    html += '</tbody></table></div></div>';
    
    const previewModal = document.getElementById('previewModal');
    if (previewModal) {
        document.getElementById('previewContent').innerHTML = html;
        new bootstrap.Modal(previewModal).show();
    } else {
        // Fallback: แสดงใน div ถ้าไม่มี modal
        const previewDiv = document.getElementById('content-preview');
        if (previewDiv) {
            previewDiv.innerHTML = html;
        }
    }
}

/**
 * Form Data Collection for Laravel Submission
 */
function collectFormData() {
    const formData = new FormData();
    
    // ข้อมูลพื้นฐาน
    formData.append('name', document.getElementById('name')?.value || '');
    formData.append('slug', document.getElementById('slug')?.value || '');
    formData.append('category', document.getElementById('category')?.value || '');
    formData.append('priority', document.getElementById('priority')?.value || '');
    formData.append('description', document.getElementById('description')?.value || '');
    formData.append('is_active', document.getElementById('is_active')?.checked ? '1' : '0');
    
    // Supported channels - ส่งเป็น array items แยกกัน
    const channels = [];
    document.querySelectorAll('input[name="supported_channels[]"]:checked').forEach(checkbox => {
        channels.push(checkbox.value);
    });
    
    // ส่ง channels เป็น array items แยกกัน
    channels.forEach(channel => {
        formData.append('supported_channels[]', channel);
    });
    
    // Template content
    formData.append('subject_template', document.getElementById('subject_template')?.value || '');
    
    // HTML content - ตรวจสอบ TinyMCE
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
    
    // Variables - รวบรวมเป็น object
    const variablesObject = {};
    
    // รวบรวมตัวแปรจาก variable rows
    document.querySelectorAll('.variable-row').forEach((row, index) => {
        const nameInput = row.querySelector('input[placeholder="Variable name"]');
        const defaultInput = row.querySelector('input[placeholder="Default value"]');
        const typeSelect = row.querySelector('select');
        
        if (nameInput && nameInput.value.trim()) {
            const varName = nameInput.value.trim();
            const varDefault = defaultInput ? defaultInput.value.trim() : '';
            const varType = typeSelect ? typeSelect.value : 'text';
            
            // ส่งเป็น array format สำหรับ Laravel
            formData.append(`variables[${index}][name]`, varName);
            formData.append(`variables[${index}][default]`, varDefault);
            formData.append(`variables[${index}][type]`, varType);
            
            variablesObject[varName] = {
                default: varDefault,
                type: varType
            };
        }
    });
    
    // Default variables JSON
    const defaultVarsJson = document.getElementById('default_variables_json')?.value?.trim();
    let defaultVariables = {};
    
    if (defaultVarsJson) {
        try {
            defaultVariables = JSON.parse(defaultVarsJson);
            // ตรวจสอบว่าเป็น object (ไม่ใช่ array)
            if (Array.isArray(defaultVariables)) {
                console.warn('Default variables should be an object, not an array');
                defaultVariables = {};
            }
        } catch (e) {
            console.error('Invalid JSON in default variables:', e);
            defaultVariables = {};
        }
    }
    
    // รวม default variables กับ variables object
    Object.keys(defaultVariables).forEach(key => {
        if (!variablesObject[key]) {
            variablesObject[key] = {
                default: defaultVariables[key],
                type: 'text'
            };
        } else {
            // อัพเดท default value ถ้ามีใน JSON
            variablesObject[key].default = defaultVariables[key];
        }
    });
    
    // ส่ง default_variables เป็น object แต่ละ key
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
 * Laravel Submission Functions
 */
function submitToLaravel(formData, saveBtn, originalText) {
    // แสดงข้อมูลที่จะส่งใน console สำหรับ debug
    console.log('Form data to be sent:');
    for (let [key, value] of formData.entries()) {
        console.log(key + ':', value);
    }
    
    // ตรวจสอบ URL ที่จะส่งไป
    let submitUrl;
    const storeRoute = document.querySelector('meta[name="store-route"]')?.getAttribute('content');
    
    if (storeRoute) {
        submitUrl = storeRoute;
    } else {
        const baseUrl = window.location.origin;
        submitUrl = `${baseUrl}/templates`;
    }
    
    console.log('Submitting to:', submitUrl);
    
    // ส่งข้อมูลไป Laravel
    fetch(submitUrl, {
        method: 'POST',
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
            return { success: true, message: 'Template created successfully' };
        }
    })
    .then(data => {
        console.log('Success response:', data);
        
        if (data.success !== false) {
            showAlert('success', data.message || 'Template created successfully!');
            localStorage.removeItem('template_draft');
            
            setTimeout(() => {
                if (data.redirect) {
                    window.location.href = data.redirect;
                } else if (data.template_id) {
                    window.location.href = `/templates/${data.template_id}`;
                } else {
                    window.location.href = '/templates';
                }
            }, 1500);
        } else {
            throw new Error(data.message || 'Failed to save template');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        
        let errorMessage = 'An error occurred while saving the template';
        
        if (error.message.includes('405')) {
            errorMessage = 'Route configuration error. Please check Laravel routes.';
        } else if (error.message.includes('419')) {
            errorMessage = 'CSRF token mismatch. Please refresh the page and try again.';
        } else if (error.message.includes('422')) {
            errorMessage = 'Validation error. Please check your input.';
        } else if (error.message) {
            errorMessage = error.message;
        }
        
        showAlert('error', errorMessage);
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
    });
}

/**
 * Alert Functions
 */
function showAlert(type, message) {
    // ลบ alert เก่า
    document.querySelectorAll('.alert-dismissible').forEach(alert => {
        alert.remove();
    });
    
    const alertClass = type === 'success' ? 'alert-success' : 
                      type === 'error' ? 'alert-danger' : 
                      type === 'warning' ? 'alert-warning' : 'alert-info';
    
    const icon = type === 'success' ? 'fas fa-check-circle' : 
                type === 'error' ? 'fas fa-exclamation-triangle' : 
                type === 'warning' ? 'fas fa-exclamation-triangle' : 'fas fa-info-circle';
    
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
 * Draft Management Functions
 */
function saveDraft() {
    const draftData = {
        name: document.getElementById('name')?.value || '',
        category: document.getElementById('category')?.value || '',
        priority: document.getElementById('priority')?.value || '',
        description: document.getElementById('description')?.value || '',
        subject_template: document.getElementById('subject_template')?.value || '',
        body_html_template: document.getElementById('body_html_template')?.value || '',
        body_text_template: document.getElementById('body_text_template')?.value || '',
        default_variables_json: document.getElementById('default_variables_json')?.value || '',
        timestamp: new Date().toISOString()
    };
    
    // ดึงเนื้อหาจาก TinyMCE ถ้ามี
    const htmlEditor = tinymce.get('body_html_template');
    if (htmlEditor) {
        draftData.body_html_template = htmlEditor.getContent();
    }
    
    localStorage.setItem('template_draft', JSON.stringify(draftData));
    
    // แสดงข้อความสำเร็จ
    const alert = document.createElement('div');
    alert.className = 'alert alert-success alert-dismissible fade show position-fixed';
    alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999;';
    alert.innerHTML = `
        <i class="fas fa-save"></i> Draft saved successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alert);
    
    setTimeout(() => {
        if (alert.parentNode) {
            alert.parentNode.removeChild(alert);
        }
    }, 3000);
}

function loadDraft() {
    const draft = localStorage.getItem('template_draft');
    if (draft) {
        try {
            const draftData = JSON.parse(draft);
            const draftAge = Date.now() - new Date(draftData.timestamp).getTime();
            
            // โหลด draft เฉพาะถ้าอายุไม่เกิน 24 ชั่วโมง
            if (draftAge < 24 * 60 * 60 * 1000) {
                const shouldLoad = confirm('Found a saved draft from ' + 
                    new Date(draftData.timestamp).toLocaleString() + 
                    '. Would you like to continue from where you left off?');
                
                if (shouldLoad) {
                    Object.keys(draftData).forEach(key => {
                        if (key !== 'timestamp') {
                            const element = document.getElementById(key);
                            if (element && draftData[key]) {
                                element.value = draftData[key];
                            }
                        }
                    });
                    
                    // แสดงข้อความ
                    showAlert('info', 'Draft loaded successfully');
                }
            }
        } catch (e) {
            console.error('Error loading draft:', e);
            localStorage.removeItem('template_draft');
        }
    }
}

/**
 * Utility Functions
 */
function clearContent() {
    if (confirm('Are you sure you want to clear all content?')) {
        const subjectField = document.getElementById('subject_template');
        if (subjectField) subjectField.value = '';
        
        // ล้าง TinyMCE
        if (tinymce.get('body_html_template')) {
            tinymce.get('body_html_template').setContent('');
        } else {
            const htmlField = document.getElementById('body_html_template');
            if (htmlField) htmlField.value = '';
        }
        
        const textField = document.getElementById('body_text_template');
        if (textField) textField.value = '';
        
        const previewDiv = document.getElementById('content-preview');
        if (previewDiv) previewDiv.innerHTML = '';
        
        const resultDiv = document.getElementById('validation-result');
        if (resultDiv) resultDiv.innerHTML = '';
    }
}

function validateTemplate() {
    const resultDiv = document.getElementById('validation-result');
    if (!resultDiv) return;
    
    resultDiv.innerHTML = '<div class="text-info"><i class="fas fa-spinner fa-spin"></i> กำลังตรวจสอบ...</div>';
    
    const subject = document.getElementById('subject_template')?.value || '';
    
    // ดึงเนื้อหาจาก TinyMCE
    let htmlContent = '';
    if (tinymce.get('body_html_template')) {
        htmlContent = tinymce.get('body_html_template').getContent();
    } else {
        htmlContent = document.getElementById('body_html_template')?.value || '';
    }
    
    const textContent = document.getElementById('body_text_template')?.value || '';
    
    const allContent = subject + ' ' + htmlContent + ' ' + textContent;
    const errors = [];
    const variables = new Set();
    
    // ดึงตัวแปร
    const varRegex = /\{\{([^}]+)\}\}/g;
    let match;
    
    while ((match = varRegex.exec(allContent)) !== null) {
        variables.add(match[1].trim());
    }
    
    // ตรวจสอบปัญหาทั่วไป
    if (subject && !subject.includes('{{')) {
        errors.push('Subject template might benefit from variables for personalization');
    }
    
    // แสดงผล
    setTimeout(() => {
        if (errors.length > 0) {
            resultDiv.innerHTML = `
                <div class="alert alert-warning">
                    <strong>Suggestions:</strong>
                    <ul class="mb-0">
                        ${errors.map(err => `<li>${err}</li>`).join('')}
                    </ul>
                </div>`;
        } else {
            resultDiv.innerHTML = `
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> Template looks good!
                    <br><small>Variables found: ${Array.from(variables).join(', ')}</small>
                </div>`;
        }
    }, 1000);
}

function showHtmlExamples() {
    const examples = `
Common HTML patterns:

1. Headings:
   <h1>Main Title</h1>
   <h2>Section Title</h2>

2. Paragraphs:
   <p>Your message here</p>

3. Links:
   <a href="{{url}}">Click here</a>

4. Bold/Italic:
   <strong>Bold text</strong>
   <em>Italic text</em>

5. Lists:
   <ul>
     <li>Item 1</li>
     <li>Item 2</li>
   </ul>

6. Styled elements:
   <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px;">
     Highlighted content
   </div>
`;
    
    alert(examples);
}

function refreshPreview() {
    generatePreview();
}

function saveTemplate() {
    // เรียกใช้การส่งฟอร์ม
    const form = document.getElementById('templateForm');
    if (form) {
        form.dispatchEvent(new Event('submit'));
    }
}

/**
 * Variable Detection Setup
 */
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
    
    // Setup for TinyMCE
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

function updateCharacterCount() {
    const textContent = document.getElementById('body_text_template');
    const charCount = document.getElementById('text-char-count');
    
    if (textContent && charCount) {
        textContent.addEventListener('input', function() {
            const count = this.value.length;
            charCount.textContent = `${count} characters`;
            
            if (count > 160) {
                charCount.className = 'badge bg-warning ms-2';
            } else {
                charCount.className = 'badge bg-secondary ms-2';
            }
        });
    }
}

function initializeVariableBadges() {
    document.querySelectorAll('.variable-badge').forEach(badge => {
        badge.addEventListener('click', function() {
            const variable = this.getAttribute('data-variable');
            if (variable) {
                insertVariable(variable);
            }
        });
    });
}

function initializeSlugGeneration() {
    const nameField = document.getElementById('name');
    const slugField = document.getElementById('slug');
    
    if (nameField && slugField) {
        nameField.addEventListener('input', function() {
            if (!slugField.value) {
                const slug = this.value
                    .toLowerCase()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/(^-|-$)/g, '');
                slugField.value = slug;
            }
        });
    }
}

/**
 * Auto-save functionality
 */
setInterval(function() {
    // ตรวจสอบว่าผู้ใช้กำลังพิมพ์อยู่หรือไม่
    const activeElement = document.activeElement;
    if (activeElement && (activeElement.tagName === 'INPUT' || activeElement.tagName === 'TEXTAREA')) {
        return; // ไม่ save ถ้ากำลังพิมพ์
    }
    
    // ตรวจสอบว่ามีข้อมูลในฟอร์มหรือไม่
    const nameField = document.getElementById('name');
    if (nameField && nameField.value.trim()) {
        saveDraft();
    }
}, 5 * 60 * 1000); // 5 นาที

/**
 * Document Ready Event Handlers
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing template creator...');
    
    // เริ่มต้นส่วนประกอบต่างๆ
    initializeVariableBadges();
    updateCharacterCount();
    initializeSlugGeneration();
    setupVariableBadgeHandlers();
    loadDraft();
    
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
    
    // เริ่มต้น Select2 ถ้ามี
    if (typeof $ !== 'undefined' && $.fn.select2) {
        $('.select2').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });
    }
    
    // จัดการการส่งฟอร์ม
    const templateForm = document.getElementById('templateForm');
    if (templateForm) {
        templateForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            console.log('Form submission started...');
            
            // Sync TinyMCE content
            if (tinymce.get('body_html_template')) {
                tinymce.get('body_html_template').save();
                console.log('TinyMCE content synced');
            }
            
            // ตรวจสอบทุก step ถ้าใช้ step wizard
            if (typeof totalSteps !== 'undefined' && totalSteps > 1) {
                let allValid = true;
                for (let i = 1; i <= totalSteps - 1; i++) {
                    const originalStep = currentStep;
                    currentStep = i;
                    if (!validateCurrentStep()) {
                        allValid = false;
                        showStep(i);
                        break;
                    }
                    currentStep = originalStep;
                }
                
                if (!allValid) return;
            }
            
            // แสดงสถานะการโหลด
            const saveBtn = document.getElementById('saveBtn');
            if (saveBtn) {
                const originalText = saveBtn.innerHTML;
                saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
                saveBtn.disabled = true;
                
                // รวบรวมข้อมูลฟอร์ม
                const formData = collectFormData();
                
                // ส่งข้อมูลไป Laravel
                submitToLaravel(formData, saveBtn, originalText);
            }
        });
    }
    
    console.log('Template creator initialization complete');
});

console.log('Template creator script loaded successfully');