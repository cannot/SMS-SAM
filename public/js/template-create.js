let currentStep = 1;
const totalSteps = 4;
let tinyMCEInitialized = false;

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
            
            // อัพเดท toolbar เพื่อเพิ่มปุ่ม variable
            // editor.settings.toolbar += ' | insertvariable';
        },
        init_instance_callback: function(editor) {
            console.log('TinyMCE initialized for:', editor.id);
            tinyMCEInitialized = true;
            
            // เพิ่ม event listener สำหรับ focus
            editor.on('focus', function() {
                console.log('TinyMCE focused');
            });
        }
    });
}

// ฟังก์ชันเริ่มสร้างเทมเพลตใหม่ - แก้ไขให้ตรวจสอบ element ก่อนใช้งาน
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

// ฟังก์ชันแสดง Template Gallery - แก้ไขให้ตรวจสอบ element ก่อนใช้งาน
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

// ฟังก์ชันซ่อน Template Gallery
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

// ฟังก์ชันนำเข้าเทมเพลต
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
                    // นำข้อมูลเทมเพลตไปใส่ในฟอร์ม
                    fillTemplateForm(template);
                    // ซ่อน Template Gallery
                    const templateGallery = document.getElementById('templateGallery');
                    if (templateGallery) {
                        templateGallery.style.display = 'none';
                    }
                    // แสดงฟอร์มหลัก
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

// ฟังก์ชันใช้เทมเพลตจาก Gallery
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

function detectVariablesFromContent() {
    const variables = new Set();
    
    // ดึงเนื้อหาจาก subject template
    const subject = document.getElementById('subject_template')?.value || '';
    
    // ดึงเนื้อหาจาก HTML template
    let htmlContent = '';
    const htmlEditor = tinymce.get('body_html_template');
    if (htmlEditor) {
        htmlContent = htmlEditor.getContent();
    } else {
        htmlContent = document.getElementById('body_html_template')?.value || '';
    }
    
    // ดึงเนื้อหาจาก Text template
    const textContent = document.getElementById('body_text_template')?.value || '';
    
    // รวมเนื้อหาทั้งหมด
    const allContent = subject + ' ' + htmlContent + ' ' + textContent;
    
    // หาตัวแปรที่อยู่ในรูปแบบ {{variable_name}}
    const varRegex = /\{\{([^}]+)\}\}/g;
    let match;
    
    while ((match = varRegex.exec(allContent)) !== null) {
        const variableName = match[1].trim();
        // กรองตัวแปรที่ไม่ใช่ system variables
        if (!isSystemVariable(variableName)) {
            variables.add(variableName);
        }
    }
    
    return Array.from(variables);
}

function isSystemVariable(variableName) {
    const systemVars = [
        'current_date', 'current_time', 'current_datetime',
        'app_name', 'app_url', 'year', 'month', 'day',
        'user_name', 'user_email', 'user_department', 'user_title',
        'user_first_name', 'user_last_name'
    ];
    
    return systemVars.includes(variableName);
}

// ฟังก์ชันแสดงตัวแปรที่ตรวจพบ
function showDetectedVariables() {
    const detectedVars = detectVariablesFromContent();
    const detectedSection = document.getElementById('detected-variables-section');
    const detectedList = document.getElementById('detected-variables-list');
    
    if (detectedVars.length > 0 && detectedSection && detectedList) {
        // แสดงส่วน detected variables
        detectedSection.style.display = 'block';
        
        // ล้างรายการเก่า
        detectedList.innerHTML = '';
        
        // เพิ่มตัวแปรที่ตรวจพบ
        detectedVars.forEach(variable => {
            const badge = document.createElement('span');
            badge.className = 'badge bg-secondary variable-badge me-1 mb-1';
            badge.style.cursor = 'pointer';
            badge.setAttribute('data-variable', variable);
            badge.setAttribute('title', `Click to add ${variable} to required variables`);
            badge.textContent = `{{${variable}}}`;
            
            // เพิ่ม event listener สำหรับการคลิก
            badge.addEventListener('click', function() {
                addDetectedVariableToRequired(variable);
                this.classList.remove('bg-secondary');
                this.classList.add('bg-success');
                this.style.cursor = 'default';
                this.title = 'Added to required variables';
            });
            
            detectedList.appendChild(badge);
        });
    } else if (detectedSection) {
        // ซ่อนส่วน detected variables ถ้าไม่มีตัวแปร
        detectedSection.style.display = 'none';
    }
}

// ฟังก์ชันเพิ่มตัวแปรที่ตรวจพบไปยัง required variables
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

// ฟังก์ชันเติมข้อมูลเทมเพลตลงในฟอร์ม
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

// ฟังก์ชันแสดงขั้นตอน
function showStepx(step) {
    console.log('Showing step:', step);
    
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
            initializeTinyMCE();
        }, 100);
    }
    
    // อัพเดท step indicator, buttons, และ progress
    updateStepIndicator(step);
    updateButtons(step);
    updateProgress(step);
    
    currentStep = step;
}

function destroyTinyMCE() {
    if (typeof tinymce !== 'undefined' && tinymce.get('body_html_template')) {
        tinymce.get('body_html_template').remove();
        tinyMCEInitialized = false;
    }
}

function showStepxx(step) {
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
                // หาก TinyMCE ถูกทำลายไปแล้ว ให้เริ่มใหม่
                const htmlEditor = tinymce.get('body_html_template');
                if (!htmlEditor) {
                    tinyMCEInitialized = false;
                    initializeTinyMCE();
                }
            }
        }, 200);
    }
    
    // อัพเดท step indicator, buttons, และ progress
    updateStepIndicator(step);
    updateButtons(step);
    updateProgress(step);
    
    currentStep = step;
}

// แก้ไขฟังก์ชัน showStep เพื่อตรวจจับตัวแปรเมื่อไปถึง step 3
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
            showDetectedVariables();
            updateDefaultVariablesFromDetected();
        }, 100);
    }
    
    // อัพเดท step indicator, buttons, และ progress
    updateStepIndicator(step);
    updateButtons(step);
    updateProgress(step);
    
    currentStep = step;
}

// ฟังก์ชันอัพเดท default variables จากตัวแปรที่ตรวจพบ
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

// ฟังก์ชันสร้างค่าตัวอย่างสำหรับตัวแปร
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
        'project_name': 'Sample Project'
    };
    
    return sampleValues[variableName] || `Sample ${variableName}`;
}

// ฟังก์ชันอัพเดท step indicator
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

// ฟังก์ชันอัพเดทปุ่มกด
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

// ฟังก์ชันอัพเดท progress
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

// ฟังก์ชันไปขั้นตอนถัดไป
function nextStep() {
    if (validateCurrentStep()) {
        if (currentStep < totalSteps) {
            showStep(currentStep + 1);
        }
    }
}

// ฟังก์ชันย้อนกลับขั้นตอนก่อนหน้า
function previousStep() {
    if (currentStep > 1) {
        showStep(currentStep - 1);
    }
}

// ฟังก์ชันตรวจสอบความถูกต้องของแต่ละขั้นตอน
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
            if (tinymce.get('body_html_template')) {
                htmlContent = tinymce.get('body_html_template').getContent();
            } else {
                htmlContent = document.getElementById('body_html_template')?.value?.trim();
            }
            
            const textContent = document.getElementById('body_text_template')?.value?.trim();
            
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

// ฟังก์ชันแสดงข้อผิดพลาด
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

// ฟังก์ชันจัดการตัวแปร
function insertVariablex(variable) {
    const variableText = `{{${variable}}}`;
    
    // ตรวจสอบว่า TinyMCE active หรือไม่
    const activeEditor = tinymce.activeEditor;
    if (activeEditor && activeEditor.id === 'body_html_template') {
        // แทรกลงใน TinyMCE
        activeEditor.insertContent(variableText);
        activeEditor.focus();
        return;
    }
    
    // สำหรับ input/textarea ปกติ
    const activeElement = document.activeElement;
    if (activeElement && (activeElement.tagName === 'TEXTAREA' || activeElement.tagName === 'INPUT')) {
        const cursorPos = activeElement.selectionStart;
        const text = activeElement.value;
        
        activeElement.value = text.slice(0, cursorPos) + variableText + text.slice(cursorPos);
        activeElement.selectionStart = activeElement.selectionEnd = cursorPos + variableText.length;
        activeElement.focus();
    } else {
        // ถ้าไม่มี field ที่ active ให้ใส่ใน HTML template
        const htmlField = document.getElementById('body_html_template');
        if (htmlField && tinymce.get('body_html_template')) {
            tinymce.get('body_html_template').insertContent(variableText);
            tinymce.get('body_html_template').focus();
        } else if (htmlField) {
            htmlField.value += variableText;
            htmlField.focus();
        } else {
            alert('Please click on a text field first, then click the variable to insert it');
        }
    }
}

function insertVariable(variable) {
    const variableText = `{{${variable}}}`;
    
    // ตรวจสอบว่า TinyMCE มีอยู่และ active หรือไม่
    if (typeof tinymce !== 'undefined') {
        const htmlEditor = tinymce.get('body_html_template');
        if (htmlEditor) {
            // ตรวจสอบว่า HTML tab เป็น active tab หรือไม่
            const htmlTab = document.getElementById('html-tab');
            const htmlContent = document.getElementById('html-content');
            
            if (htmlTab && htmlTab.classList.contains('active') || 
                htmlContent && htmlContent.classList.contains('show', 'active')) {
                // แทรกลงใน TinyMCE
                htmlEditor.insertContent(variableText);
                htmlEditor.focus();
                return;
            }
        }
    }
    
    // สำหรับ input/textarea ปกติ
    const activeElement = document.activeElement;
    if (activeElement && (activeElement.tagName === 'TEXTAREA' || activeElement.tagName === 'INPUT')) {
        const cursorPos = activeElement.selectionStart || 0;
        const text = activeElement.value || '';
        
        activeElement.value = text.slice(0, cursorPos) + variableText + text.slice(cursorPos);
        activeElement.selectionStart = activeElement.selectionEnd = cursorPos + variableText.length;
        activeElement.focus();
    } else {
        // ถ้าไม่มี field ที่ active ให้ใส่ใน field ที่เหมาะสม
        const currentTab = document.querySelector('.nav-link.active');
        
        if (currentTab && currentTab.id === 'html-tab') {
            // ใส่ใน HTML field
            const htmlEditor = tinymce.get('body_html_template');
            if (htmlEditor) {
                htmlEditor.insertContent(variableText);
                htmlEditor.focus();
            } else {
                const htmlField = document.getElementById('body_html_template');
                if (htmlField) {
                    htmlField.value += variableText;
                    htmlField.focus();
                }
            }
        } else if (currentTab && currentTab.id === 'text-tab') {
            // ใส่ใน Text field
            const textField = document.getElementById('body_text_template');
            if (textField) {
                textField.value += variableText;
                textField.focus();
            }
        } else {
            // ใส่ใน subject field
            const subjectField = document.getElementById('subject_template');
            if (subjectField) {
                subjectField.value += variableText;
                subjectField.focus();
            } else {
                alert('Please click on a text field first, then click the variable to insert it');
            }
        }
    }
}

// ฟังก์ชันเพิ่มตัวแปรกำหนดเอง
function addCustomVariableFunction() {
    const customVarInput = document.getElementById('customVariable');
    if (!customVarInput) return;
    
    const variableName = customVarInput.value.trim();
    
    if (!variableName) {
        alert('Please enter a variable name');
        return;
    }
    
    // หาที่ที่จะใส่ badge ใหม่
    const customVarsContainer = document.querySelector('.mb-3:has(.badge.bg-warning)');
    if (customVarsContainer) {
        const badgeContainer = customVarsContainer.querySelector('.d-flex.flex-wrap.gap-1');
        if (badgeContainer) {
            const newBadge = document.createElement('span');
            newBadge.className = 'badge bg-warning text-dark variable-badge';
            newBadge.setAttribute('data-variable', variableName);
            newBadge.setAttribute('title', `Custom variable: ${variableName}`);
            newBadge.textContent = `{{${variableName}}}`;
            newBadge.onclick = () => insertVariable(variableName);
            
            badgeContainer.appendChild(newBadge);
        }
    }
    
    // ล้างค่าในช่องกรอก
    customVarInput.value = '';
}

// ฟังก์ชันสร้างเนื้อหาตัวอย่าง
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

// ฟังก์ชันตรวจสอบความถูกต้องของเทมเพลต
function validateTemplatex() {
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
}

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

// ฟังก์ชัน Quick Preview
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

// ฟังก์ชันแทนที่ตัวแปร
function replaceVariables(template, data) {
    let result = template;
    for (const [key, value] of Object.entries(data)) {
        const regex = new RegExp(`\\{\\{\\s*${key}\\s*\\}\\}`, 'g');
        result = result.replace(regex, value);
    }
    return result;
}

// ฟังก์ชันลบเนื้อหา
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

// ฟังก์ชันเพิ่มตัวแปร
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

// ฟังก์ชันลบตัวแปร
function removeVariableRow(button) {
    const row = button.closest('.variable-row');
    if (row) row.remove();
}

// ฟังก์ชันสร้าง JSON ตัวแปรเริ่มต้น
function generateDefaultVariablesJSONx() {
    const variables = {};
    
    // รวบรวมตัวแปรจาก rows
    document.querySelectorAll('.variable-row').forEach(row => {
        const nameInput = row.querySelector('input[placeholder="Variable name"]');
        const defaultInput = row.querySelector('input[placeholder="Default value"]');
        
        if (nameInput && defaultInput) {
            const name = nameInput.value.trim();
            const defaultValue = defaultInput.value.trim();
            
            if (name) {
                variables[name] = defaultValue || 'Sample Value';
            }
        }
    });
    
    // เพิ่มตัวแปรทั่วไป
    if (!variables.user_name) variables.user_name = 'John Doe';
    if (!variables.message) variables.message = 'Sample message content';
    if (!variables.subject) variables.subject = 'Sample Subject';
    
    const jsonField = document.getElementById('default_variables_json');
    if (jsonField) {
        jsonField.value = JSON.stringify(variables, null, 2);
    }
}

function generateDefaultVariablesJSON() {
    const variables = {};
    
    // รวบรวมตัวแปรจาก variable rows
    document.querySelectorAll('.variable-row').forEach(row => {
        const nameInput = row.querySelector('input[placeholder="Variable name"]');
        const defaultInput = row.querySelector('input[placeholder="Default value"]');
        
        if (nameInput && nameInput.value.trim()) {
            const name = nameInput.value.trim();
            const defaultValue = defaultInput ? defaultInput.value.trim() : getSampleValueForVariable(name);
            variables[name] = defaultValue || 'Sample Value';
        }
    });
    
    // เพิ่มตัวแปรที่ตรวจพบจากเนื้อหา
    const detectedVars = detectVariablesFromContent();
    detectedVars.forEach(variable => {
        if (!variables[variable]) {
            variables[variable] = getSampleValueForVariable(variable);
        }
    });
    
    // เพิ่มตัวแปรทั่วไป (ถ้ายังไม่มี)
    const commonVars = {
        'user_name': 'John Doe',
        'message': 'Sample message content',
        'subject': 'Sample Subject',
        'company': 'Your Company'
    };
    
    Object.keys(commonVars).forEach(key => {
        if (!variables[key]) {
            variables[key] = commonVars[key];
        }
    });
    
    const jsonField = document.getElementById('default_variables_json');
    if (jsonField) {
        jsonField.value = JSON.stringify(variables, null, 2);
    }
}

// ฟังก์ชันจัดรูปแบบ JSON
function formatJSON() {
    const textarea = document.getElementById('default_variables_json');
    if (!textarea) return;
    
    try {
        const json = JSON.parse(textarea.value);
        textarea.value = JSON.stringify(json, null, 2);
    } catch (e) {
        alert('Invalid JSON format. Please check your syntax.');
    }
}

// เพิ่มฟังก์ชัน event listener สำหรับการเปลี่ยนแปลงเนื้อหาใน step 2
function setupContentChangeListeners() {
    // เมื่อมีการเปลี่ยนแปลงใน subject template
    const subjectField = document.getElementById('subject_template');
    if (subjectField) {
        subjectField.addEventListener('input', debounce(function() {
            if (currentStep === 3) {
                showDetectedVariables();
            }
        }, 500));
    }
    
    // เมื่อมีการเปลี่ยนแปลงใน text template
    const textField = document.getElementById('body_text_template');
    if (textField) {
        textField.addEventListener('input', debounce(function() {
            if (currentStep === 3) {
                showDetectedVariables();
            }
        }, 500));
    }
}

// ฟังก์ชัน debounce เพื่อลด frequency ของการตรวจจับ
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// เพิ่มในการเริ่มต้น
document.addEventListener('DOMContentLoaded', function() {
    setupContentChangeListeners();
});

// ฟังก์ชันตรวจสอบตัวแปร
function validateVariables() {
    const resultDiv = document.getElementById('variable-validation-result');
    if (!resultDiv) return;
    
    const jsonField = document.getElementById('default_variables_json');
    const jsonText = jsonField ? jsonField.value.trim() : '';
    
    if (!jsonText) {
        resultDiv.innerHTML = '<div class="alert alert-info">No variables to validate</div>';
        return;
    }
    
    try {
        const variables = JSON.parse(jsonText);
        
        if (typeof variables !== 'object' || Array.isArray(variables)) {
            throw new Error('Variables must be a JSON object');
        }
        
        const variableCount = Object.keys(variables).length;
        resultDiv.innerHTML = `
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Variables are valid!
                <br><small>${variableCount} variables defined</small>
            </div>`;
            
    } catch (e) {
        resultDiv.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> Invalid JSON: ${e.message}
            </div>`;
    }
}

// ฟังก์ชันโหลดข้อมูลตัวอย่างสำหรับ preview
function loadSampleDataForPreview() {
    const finalPreview = document.getElementById('final-preview');
    if (!finalPreview) return;
    
    finalPreview.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading preview...</div>';
    
    // จำลองการโหลด
    setTimeout(() => {
        const subject = document.getElementById('subject_template')?.value || '';
        const htmlContent = document.getElementById('body_html_template')?.value || '';
        const textContent = document.getElementById('body_text_template')?.value || '';
        
        const sampleData = {
            user_name: 'Jane Smith',
            user_email: 'jane.smith@company.com',
            user_department: 'Marketing',
            subject: 'Important Update',
            message: 'This is an important update that requires your attention.',
            priority: 'HIGH',
            current_date: new Date().toLocaleDateString(),
            current_time: new Date().toLocaleTimeString(),
            current_datetime: new Date().toLocaleString(),
            company: 'Smart Notify Inc.',
            year: new Date().getFullYear()
        };
        
        const previewSubject = replaceVariables(subject, sampleData);
        const previewHtml = replaceVariables(htmlContent, sampleData);
        const previewText = replaceVariables(textContent, sampleData);
        
        finalPreview.innerHTML = `
            <div class="row">
                <div class="col-md-6">
                    <h6><i class="fas fa-envelope"></i> Email Preview</h6>
                    <div class="card">
                        <div class="card-header bg-light">
                            <strong>Subject:</strong> ${previewSubject}
                        </div>
                        <div class="card-body">${previewHtml}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <h6><i class="fas fa-sms"></i> Text Preview</h6>
                    <div class="card">
                        <div class="card-body">
                            <pre style="white-space: pre-wrap; font-family: inherit;">${previewText}</pre>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }, 1000);
}

// ฟังก์ชัน refresh preview
function refreshPreview() {
    loadSampleDataForPreview();
}

// ฟังก์ชันบันทึก draft
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

// ฟังก์ชันเริ่มต้น variable badges
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

// ฟังก์ชันนับตัวอักษร
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

// ฟังก์ชันสร้าง slug อัตโนมัติ
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

// ฟังก์ชันโหลด draft
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
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-info alert-dismissible fade show';
                    alert.innerHTML = `
                        <i class="fas fa-info-circle"></i> Draft loaded successfully
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    const container = document.querySelector('.container-fluid');
                    if (container) {
                        container.insertAdjacentElement('afterbegin', alert);
                    }
                    
                    setTimeout(() => {
                        if (alert.parentNode) {
                            alert.parentNode.removeChild(alert);
                        }
                    }, 3000);
                }
            }
        } catch (e) {
            console.error('Error loading draft:', e);
            localStorage.removeItem('template_draft');
        }
    }
}

// ฟังก์ชันเพิ่มเติมที่อาจจะถูกเรียกใช้จาก template
function showHtmlExamples() {
    alert('HTML Examples feature would show common HTML patterns here');
}

function saveTemplate() {
    // เรียกใช้การส่งฟอร์ม
    const form = document.getElementById('templateForm');
    if (form) {
        form.dispatchEvent(new Event('submit'));
    }
}

// เมื่อเอกสารโหลดเสร็จ
// document.addEventListener('DOMContentLoaded', function() {
//     console.log('DOM loaded, initializing...');
    
//     // เริ่มต้นส่วนประกอบต่างๆ
//     initializeVariableBadges();
//     updateCharacterCount();
//     initializeSlugGeneration();
//     loadDraft();
    
//     // เริ่มต้น Select2 ถ้ามี
//     if (typeof $ !== 'undefined' && $.fn.select2) {
//         $('.select2').select2({
//             theme: 'bootstrap-5',
//             width: '100%'
//         });
//     }
    
//     // แสดง step แรกถ้าเราอยู่ในฟอร์มหลัก
//     const mainForm = document.getElementById('mainForm');
//     if (mainForm && mainForm.style.display !== 'none') {
//         showStep(1);
//     }
    
//     // จัดการการส่งฟอร์ม
//     const templateForm = document.getElementById('templateForm');
//     if (templateForm) {
//         templateForm.addEventListener('submit', function(e) {
//             e.preventDefault();
            
//             // ตรวจสอบทุก step
//             let allValid = true;
//             for (let i = 1; i <= totalSteps - 1; i++) {
//                 const originalStep = currentStep;
//                 currentStep = i;
//                 if (!validateCurrentStep()) {
//                     allValid = false;
//                     showStep(i);
//                     break;
//                 }
//                 currentStep = originalStep;
//             }
            
//             if (allValid) {
//                 // แสดงสถานะการโหลด
//                 const saveBtn = document.getElementById('saveBtn');
//                 if (saveBtn) {
//                     const originalText = saveBtn.innerHTML;
//                     saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
//                     saveBtn.disabled = true;
                    
//                     // จำลองการส่งข้อมูล - ในการใช้งานจริงจะส่งไป Laravel backend
//                     setTimeout(() => {
//                         alert('Template created successfully! (This is a demo)');
                        
//                         // ลบ draft
//                         localStorage.removeItem('template_draft');
                        
//                         // รีเซ็ตปุ่ม
//                         saveBtn.innerHTML = originalText;
//                         saveBtn.disabled = false;
                        
//                         // ในกรณีจริงจะ redirect ไปหน้า templates
//                         // window.location.href = '/templates';
//                     }, 2000);
//                 }
//             }
//         });
//     }
    
//     console.log('Initialization complete');
// });
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing...');
    
    // เริ่มต้นส่วนประกอบต่างๆ
    initializeVariableBadges();
    updateCharacterCount();
    initializeSlugGeneration();
    loadDraft();
    
    // รอให้ TinyMCE โหลดเสร็จก่อนเริ่มต้น
    if (typeof tinymce !== 'undefined') {
        // ไม่เริ่มต้น TinyMCE ทันที ให้รอจนกว่าจะไปถึง step 2
        console.log('TinyMCE is available');
    }
    
    // เริ่มต้น Select2 ถ้ามี
    if (typeof $ !== 'undefined' && $.fn.select2) {
        $('.select2').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });
    }
    
    console.log('Initialization complete');
});

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

document.addEventListener('DOMContentLoaded', function() {
    const templateForm = document.getElementById('templateForm');
    if (templateForm) {
        templateForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // ตรวจสอบทุก step
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
            
            if (allValid) {
                // แสดงสถานะการโหลด
                const saveBtn = document.getElementById('saveBtn');
                if (saveBtn) {
                    const originalText = saveBtn.innerHTML;
                    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
                    saveBtn.disabled = true;
                    
                    // วิธี 1: FormData (แนะนำ)
                    const formData = collectFormData();
                    submitToLaravel(formData, saveBtn, originalText);
                    
                    // วิธี 2: JSON (ถ้าต้องการ)
                    // const jsonData = collectFormDataAsJSON();
                    // submitToLaravelAsJSON(jsonData, saveBtn, originalText);
                }
            }
        });
    }
});

// ฟังก์ชันรวบรวมข้อมูลฟอร์ม
function collectFormDatax() {
    const formData = new FormData();
    
    // ข้อมูลพื้นฐาน
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
    formData.append('supported_channels', JSON.stringify(channels));
    
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
    
    // Variables - แก้ไขให้เป็น object แทน array
    const variablesObject = {};
    
    // รวบรวมตัวแปรจาก variable rows
    document.querySelectorAll('.variable-row').forEach(row => {
        const nameInput = row.querySelector('input[placeholder="Variable name"]');
        const defaultInput = row.querySelector('input[placeholder="Default value"]');
        const typeSelect = row.querySelector('select');
        
        if (nameInput && nameInput.value.trim()) {
            const varName = nameInput.value.trim();
            const varDefault = defaultInput ? defaultInput.value.trim() : '';
            const varType = typeSelect ? typeSelect.value : 'text';
            
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
    
    // ส่งเป็น JSON object ไม่ใช่ array
    formData.append('variables', JSON.stringify(variablesObject));
    formData.append('default_variables', JSON.stringify(defaultVariables));
    
    // CSRF Token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                      document.querySelector('input[name="_token"]')?.value;
    if (csrfToken) {
        formData.append('_token', csrfToken);
    }
    
    return formData;
}

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

// อีกวิธีหนึ่ง: ส่งข้อมูลเป็น JSON แทน FormData
function collectFormDataAsJSON() {
    // รวบรวมข้อมูลเป็น object
    const data = {};
    
    // ข้อมูลพื้นฐาน
    data.name = document.getElementById('name')?.value || '';
    data.slug = document.getElementById('slug')?.value || '';
    data.category = document.getElementById('category')?.value || '';
    data.priority = document.getElementById('priority')?.value || '';
    data.description = document.getElementById('description')?.value || '';
    data.is_active = document.getElementById('is_active')?.checked;
    
    // Supported channels
    data.supported_channels = [];
    document.querySelectorAll('input[name="supported_channels[]"]:checked').forEach(checkbox => {
        data.supported_channels.push(checkbox.value);
    });
    
    // Template content
    data.subject_template = document.getElementById('subject_template')?.value || '';
    
    // HTML content
    let htmlContent = '';
    const htmlEditor = tinymce.get('body_html_template');
    if (htmlEditor) {
        htmlContent = htmlEditor.getContent();
    } else {
        htmlContent = document.getElementById('body_html_template')?.value || '';
    }
    data.body_html_template = htmlContent;
    
    // Text content
    data.body_text_template = document.getElementById('body_text_template')?.value || '';
    
    // Variables
    data.variables = [];
    document.querySelectorAll('.variable-row').forEach(row => {
        const nameInput = row.querySelector('input[placeholder="Variable name"]');
        const defaultInput = row.querySelector('input[placeholder="Default value"]');
        const typeSelect = row.querySelector('select');
        
        if (nameInput && nameInput.value.trim()) {
            data.variables.push({
                name: nameInput.value.trim(),
                default: defaultInput ? defaultInput.value.trim() : '',
                type: typeSelect ? typeSelect.value : 'text'
            });
        }
    });
    
    // Default variables
    const defaultVarsJson = document.getElementById('default_variables_json')?.value?.trim();
    if (defaultVarsJson) {
        try {
            data.default_variables = JSON.parse(defaultVarsJson);
        } catch (e) {
            console.error('Invalid JSON in default variables:', e);
            data.default_variables = {};
        }
    } else {
        data.default_variables = {};
    }
    
    return data;
}

// แก้ไขฟังก์ชัน submitToLaravel เพื่อรองรับทั้งสองวิธี
function submitToLaravel(formData, saveBtn, originalText) {
    // ตัวเลือก 1: ใช้ FormData (แนะนำ)
    console.log('Form data to be sent:');
    for (let [key, value] of formData.entries()) {
        console.log(key + ':', value);
    }
    
    // ตรวจสอบ URL
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

// ตัวเลือก 2: ใช้ JSON submission
function submitToLaravelAsJSON(data, saveBtn, originalText) {
    let submitUrl;
    const storeRoute = document.querySelector('meta[name="store-route"]')?.getAttribute('content');
    
    if (storeRoute) {
        submitUrl = storeRoute;
    } else {
        const baseUrl = window.location.origin;
        submitUrl = `${baseUrl}/templates`;
    }
    
    // เพิ่ม CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    fetch(submitUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        if (!response.ok) {
            return response.text().then(text => {
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
        if (data.success !== false) {
            showAlert('success', data.message || 'Template created successfully!');
            localStorage.removeItem('template_draft');
            
            setTimeout(() => {
                if (data.redirect) {
                    window.location.href = data.redirect;
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
        showAlert('error', error.message || 'An error occurred while saving the template');
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
    });
}

// ฟังก์ชันส่งข้อมูลไป Laravel
function submitToLaravelx(formData, saveBtn, originalText) {
    // แสดงข้อมูลที่จะส่งใน console สำหรับ debug
    console.log('Form data to be sent:');
    for (let [key, value] of formData.entries()) {
        console.log(key + ':', value);
    }
    
    // ตรวจสอบ URL ที่จะส่งไป - ใช้ store route
    let submitUrl;
    
    // ลองหา store route จาก meta tag หรือ window variable
    const storeRoute = document.querySelector('meta[name="store-route"]')?.getAttribute('content');
    
    if (storeRoute) {
        submitUrl = storeRoute;
    } else {
        // สร้าง URL แบบ manual
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
        console.log('Response headers:', response.headers);
        
        if (!response.ok) {
            return response.text().then(text => {
                console.error('Error response:', text);
                throw new Error(`HTTP ${response.status}: ${text}`);
            });
        }
        
        // ตรวจสอบ content type
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        } else {
            // ถ้าไม่ใช่ JSON (เช่น redirect) ให้ถือว่าสำเร็จ
            return { success: true, message: 'Template created successfully' };
        }
    })
    .then(data => {
        console.log('Success response:', data);
        
        if (data.success !== false) {
            // แสดงข้อความสำเร็จ
            showAlert('success', data.message || 'Template created successfully!');
            
            // ลบ draft
            localStorage.removeItem('template_draft');
            
            // Redirect ไปหน้า templates
            setTimeout(() => {
                if (data.redirect) {
                    window.location.href = data.redirect;
                } else if (data.template_id) {
                    window.location.href = `/templates/${data.template_id}`;
                } else {
                    // ไปหน้า index
                    window.location.href = '/templates';
                }
            }, 1500);
        } else {
            throw new Error(data.message || 'Failed to save template');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        
        // แสดงข้อผิดพลาด
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
        
        // รีเซ็ตปุ่ม
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
    });
}

// ฟังก์ชันแสดงข้อความแจ้งเตือน
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

// Auto-save ทุก 5 นาที
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

console.log('Template creator script loaded successfully');