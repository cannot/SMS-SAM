/**
 * Smart Notification System - Template Creator (Thai Version)
 * ระบบสร้างเทมเพลตการแจ้งเตือนภาษาไทย
 */

import { templateGallery } from './template-gallery.js';
import { allSystemVariables, getVariablesByCategory, generateSampleData } from './system-variables.js';
import { 
    replaceVariables, 
    detectVariablesFromContent, 
    convertTextToHtml, 
    convertHtmlToText,
    validateTemplateSyntax,
    generateSlug,
    parseJSON,
    formatJSON,
    generateDefaultVariables,
    checkSMSLength,
    splitFullName,
    debounce,
    showAlert,
    isLocalStorageAvailable
} from './template-utils.js';

// Global variables
let currentStep = 1;
const totalSteps = 4;
let tinyMCEInitialized = false;
let lastFocusedElement = null;
let variableDetectionInitialized = false;
let htmlToTextSync = true; // Flag สำหรับ sync HTML -> Text

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
        language: 'th_TH',
        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'help', 'wordcount'
        ],
        toolbar: 'undo redo | formatselect | ' +
            'bold italic backcolor | alignleft aligncenter ' +
            'alignright alignjustify | bullist numlist outdent indent | ' +
            'removeformat | code | insertvariable | help',
        content_style: `
            body { 
                font-family: 'Sarabun', Arial, sans-serif; 
                font-size: 14px; 
                line-height: 1.6;
            }
        `,
        setup: function(editor) {
            // เพิ่มปุ่มแทรกตัวแปร
            editor.ui.registry.addMenuButton('insertvariable', {
                text: 'ตัวแปร',
                icon: 'template',
                fetch: function(callback) {
                    const userVars = getVariablesByCategory('user');
                    const systemVars = getVariablesByCategory('system');
                    const customVars = getVariablesByCategory('custom');
                    
                    const items = [
                        {
                            type: 'menuitem',
                            text: '--- ตัวแปรผู้ใช้ ---',
                            enabled: false
                        }
                    ];
                    
                    // เพิ่มตัวแปรผู้ใช้
                    Object.keys(userVars).slice(0, 6).forEach(key => {
                        items.push({
                            type: 'menuitem',
                            text: userVars[key].label,
                            onAction: function() {
                                editor.insertContent(`{{${key}}}`);
                            }
                        });
                    });
                    
                    // เพิ่มตัวแปรระบบ
                    items.push({
                        type: 'menuitem',
                        text: '--- ตัวแปรระบบ ---',
                        enabled: false
                    });
                    
                    Object.keys(systemVars).slice(0, 5).forEach(key => {
                        items.push({
                            type: 'menuitem',
                            text: systemVars[key].label,
                            onAction: function() {
                                editor.insertContent(`{{${key}}}`);
                            }
                        });
                    });
                    
                    callback(items);
                }
            });
            
            // Track focus สำหรับการแทรกตัวแปร
            editor.on('focus', function() {
                lastFocusedElement = {
                    isTinyMCE: true,
                    editorId: editor.id,
                    editor: editor
                };
            });
            
            // Auto-sync HTML to Text เมื่อมีการเปลี่ยนแปลง
            editor.on('input change keyup', debounce(function() {
                if (htmlToTextSync) {
                    syncHtmlToText();
                }
            }, 1000));
        },
        init_instance_callback: function(editor) {
            console.log('TinyMCE เริ่มต้นเรียบร้อยสำหรับ:', editor.id);
            tinyMCEInitialized = true;
            
            // Setup variable detection หลังจาก TinyMCE พร้อม
            setTimeout(function() {
                setupVariableDetection();
            }, 500);
        }
    });
}

/**
 * Sync HTML content to Text content
 */
function syncHtmlToText() {
    if (!htmlToTextSync) return;
    
    const htmlEditor = tinymce.get('body_html_template');
    const textField = document.getElementById('body_text_template');
    
    if (htmlEditor && textField) {
        const htmlContent = htmlEditor.getContent();
        if (htmlContent && htmlContent.trim()) {
            const textContent = convertHtmlToText(htmlContent);
            textField.value = textContent;
            
            // อัปเดต character count
            updateCharacterCount();
            
            // ตรวจจับตัวแปรใหม่
            setTimeout(updateDetectedVariables, 100);
        }
    }
}

/**
 * Sync Text content to HTML content (เมื่อผู้ใช้แก้ไข text โดยตรง)
 */
function syncTextToHtml() {
    const textField = document.getElementById('body_text_template');
    const htmlEditor = tinymce.get('body_html_template');
    
    if (textField && htmlEditor && textField.value.trim()) {
        // หยุด auto-sync ชั่วคราวเพื่อป้องกัน loop
        htmlToTextSync = false;
        
        const htmlContent = convertTextToHtml(textField.value);
        htmlEditor.setContent(htmlContent);
        
        // เปิด auto-sync อีกครั้งหลังจาก 2 วินาที
        setTimeout(() => {
            htmlToTextSync = true;
        }, 2000);
    }
}

/**
 * Quick Start Functions
 */
window.startFromScratch = function() {
    console.log('เริ่มสร้างเทมเพลตใหม่...');
    
    hideAllSections();
    showMainForm();
    showStep(1);
};

window.showTemplateGallery = function() {
    console.log('แสดงแกลลอรี่เทมเพลต...');
    
    hideAllSections();
    document.getElementById('templateGallery').style.display = 'block';
};

window.hideTemplateGallery = function() {
    console.log('ซ่อนแกลลอรี่เทมเพลต...');
    
    document.getElementById('templateGallery').style.display = 'none';
    document.getElementById('quickStart').style.display = 'block';
};

window.importTemplate = function() {
    console.log('นำเข้าเทมเพลต...');
    
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
                    hideTemplateGallery();
                    startFromScratch();
                    showAlert('success', 'นำเข้าเทมเพลตเรียบร้อย');
                } catch (error) {
                    showAlert('error', 'ไฟล์ไม่ถูกต้อง กรุณาเลือกไฟล์ JSON ที่ถูกต้อง');
                }
            };
            reader.readAsText(file);
        }
    };

    input.click();
};

window.useTemplate = function(templateId) {
    console.log('ใช้เทมเพลต:', templateId);
    
    const template = templateGallery[templateId];
    if (template) {
        fillTemplateForm(template);
        showAlert('success', `โหลดเทมเพลต "${template.name}" เรียบร้อย`);
    }
    
    hideTemplateGallery();
    startFromScratch();
};

function hideAllSections() {
    const sections = ['quickStart', 'templateGallery', 'mainForm', 'stepIndicator'];
    sections.forEach(id => {
        const element = document.getElementById(id);
        if (element) element.style.display = 'none';
    });
}

function showMainForm() {
    document.getElementById('mainForm').style.display = 'block';
    document.getElementById('stepIndicator').style.display = 'flex';
}

function fillTemplateForm(template) {
    // เติมข้อมูลพื้นฐาน
    setFieldValue('name', template.name);
    setFieldValue('description', template.description);
    setFieldValue('category', template.category);
    setFieldValue('priority', template.priority);
    
    // เติมข้อมูลเนื้อหา
    setFieldValue('subject_template', template.subject_template);
    setFieldValue('body_html_template', template.body_html_template);
    setFieldValue('body_text_template', template.body_text_template);
    setFieldValue('default_variables_json', template.default_variables_json);
    
    // เลือก channels
    if (template.supported_channels) {
        template.supported_channels.forEach(channel => {
            const checkbox = document.getElementById(`channel_${channel}`);
            if (checkbox) checkbox.checked = true;
        });
    }
    
    // สร้าง slug อัตโนมัติ
    generateSlugFromName();
}

function setFieldValue(fieldId, value) {
    const field = document.getElementById(fieldId);
    if (field && value) {
        field.value = value;
    }
}

/**
 * Step Management Functions
 */
window.showStep = function(step) {
    console.log('แสดงขั้นตอนที่:', step);
    
    // บันทึกเนื้อหา TinyMCE ก่อนเปลี่ยน step
    if (currentStep === 2 && step !== 2) {
        const htmlEditor = tinymce.get('body_html_template');
        if (htmlEditor) {
            const content = htmlEditor.getContent();
            setFieldValue('body_html_template', content);
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
    
    // โหลดตัวอย่างสำหรับ step 4
    if (step === 4) {
        setTimeout(() => {
            loadSampleDataForPreview();
        }, 100);
    }
    
    // อัพเดท UI elements
    updateStepIndicator(step);
    updateButtons(step);
    updateProgress(step);
    
    currentStep = step;
};

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
    
    if (prevBtn) {
        prevBtn.style.display = step === 1 ? 'none' : 'inline-block';
    }
    
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

window.nextStep = function() {
    if (validateCurrentStep()) {
        if (currentStep < totalSteps) {
            showStep(currentStep + 1);
        }
    }
};

window.previousStep = function() {
    if (currentStep > 1) {
        showStep(currentStep - 1);
    }
};

/**
 * Validation Functions
 */
function validateCurrentStep() {
    let isValid = true;
    
    // ลบข้อผิดพลาดเก่า
    document.querySelectorAll('.is-invalid').forEach(el => {
        el.classList.remove('is-invalid');
    });
    document.querySelectorAll('.invalid-feedback').forEach(el => {
        el.remove();
    });
    
    switch(currentStep) {
        case 1: // ข้อมูลพื้นฐาน
            const name = document.getElementById('name')?.value?.trim();
            const category = document.getElementById('category')?.value;
            const priority = document.getElementById('priority')?.value;
            const channels = document.querySelectorAll('input[name="supported_channels[]"]:checked');
            
            if (!name) {
                showFieldError('name', 'กรุณาระบุชื่อเทมเพลต');
                isValid = false;
            }
            if (!category) {
                showFieldError('category', 'กรุณาเลือกหมวดหมู่');
                isValid = false;
            }
            if (!priority) {
                showFieldError('priority', 'กรุณาเลือกระดับความสำคัญ');
                isValid = false;
            }
            if (channels.length === 0) {
                showAlert('error', 'กรุณาเลือกช่องทางการแจ้งเตือนอย่างน้อย 1 ช่องทาง');
                isValid = false;
            }
            break;
            
        case 2: // เนื้อหาเทมเพลต
            const subject = document.getElementById('subject_template')?.value?.trim();
            
            let htmlContent = '';
            const htmlEditor = tinymce.get('body_html_template');
            if (htmlEditor) {
                htmlContent = htmlEditor.getContent().trim();
            } else {
                htmlContent = document.getElementById('body_html_template')?.value?.trim() || '';
            }
            
            const textContent = document.getElementById('body_text_template')?.value?.trim() || '';
            
            if (!subject) {
                showFieldError('subject_template', 'กรุณาระบุหัวข้อเทมเพลต');
                isValid = false;
            }
            
            // ตรวจสอบว่ามีเนื้อหาอย่างน้อย 1 แบบ
            if (!htmlContent && !textContent) {
                showAlert('error', 'กรุณาระบุเนื้อหาเทมเพลตอย่างน้อย 1 รูปแบบ (HTML สำหรับอีเมล หรือ ข้อความธรรมดา)');
                isValid = false;
            }
            
            // ตรวจสอบ syntax ของเทมเพลต
            if (subject) {
                const subjectValidation = validateTemplateSyntax(subject);
                if (!subjectValidation.valid) {
                    showFieldError('subject_template', `รูปแบบหัวข้อไม่ถูกต้อง: ${subjectValidation.errors.join(', ')}`);
                    isValid = false;
                }
            }
            
            break;
            
        case 3: // ตัวแปร
            const defaultVars = document.getElementById('default_variables_json')?.value?.trim();
            if (defaultVars) {
                const jsonResult = parseJSON(defaultVars);
                if (!jsonResult.valid) {
                    showFieldError('default_variables_json', jsonResult.error);
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
function detectVariablesFromAllContent() {
    console.log('ตรวจจับตัวแปรจากเนื้อหาทั้งหมด...');
    
    const subjectTemplate = document.getElementById('subject_template')?.value || '';
    const bodyTextTemplate = document.getElementById('body_text_template')?.value || '';
    let bodyHtmlTemplate = '';
    
    // ดึงเนื้อหาจาก TinyMCE
    const htmlEditor = tinymce.get('body_html_template');
    if (htmlEditor) {
        bodyHtmlTemplate = htmlEditor.getContent();
    } else {
        bodyHtmlTemplate = document.getElementById('body_html_template')?.value || '';
    }
    
    const allContent = subjectTemplate + ' ' + bodyHtmlTemplate + ' ' + bodyTextTemplate;
    
    return detectVariablesFromContent(allContent);
}

function updateDetectedVariables() {
    console.log('อัพเดทตัวแปรที่ตรวจพบ...');
    const detectedVars = detectVariablesFromAllContent();
    
    let detectedSection = document.getElementById('detected-variables-section');
    
    // สร้าง section ถ้ายังไม่มี
    if (!detectedSection) {
        const variablesCard = document.querySelector('.card:has(#variables-container)');
        if (variablesCard) {
            const cardBody = variablesCard.querySelector('.card-body');
            if (cardBody) {
                detectedSection = document.createElement('div');
                detectedSection.id = 'detected-variables-section';
                detectedSection.className = 'mb-4';
                detectedSection.innerHTML = `
                    <h6 class="text-info">
                        <i class="fas fa-search me-1"></i>ตัวแปรที่ตรวจพบ
                    </h6>
                    <div id="detected-variables-list" class="d-flex flex-wrap gap-1 mb-2"></div>
                    <small class="text-muted">ตัวแปรที่พบในเนื้อหาเทมเพลต คลิกเพื่อเพิ่มไปยังตัวแปรที่จำเป็น</small>
                `;
                cardBody.insertBefore(detectedSection, cardBody.firstChild);
            }
        }
    }
    
    if (detectedSection) {
        const detectedList = document.getElementById('detected-variables-list');
        if (detectedList) {
            if (detectedVars.length > 0) {
                detectedList.innerHTML = '';
                detectedVars.forEach(function(varName) {
                    const badge = document.createElement('span');
                    badge.className = 'badge bg-info variable-badge me-1 mb-1';
                    badge.textContent = '{{' + varName + '}}';
                    badge.title = 'คลิกเพื่อเพิ่มไปยังตัวแปรที่จำเป็น';
                    badge.style.cursor = 'pointer';
                    badge.addEventListener('click', function() {
                        addDetectedVariableToRequired(varName);
                        this.classList.remove('bg-info');
                        this.classList.add('bg-success');
                        this.title = 'เพิ่มแล้วในตัวแปรที่จำเป็น';
                    });
                    detectedList.appendChild(badge);
                });
                detectedSection.style.display = 'block';
            } else {
                detectedSection.style.display = 'none';
            }
        }
    }
    
    // อัพเดท default variables
    updateDefaultVariablesJSON(detectedVars);
}

function updateDefaultVariablesFromDetected() {
    const detectedVars = detectVariablesFromAllContent();
    const defaultVarsTextarea = document.getElementById('default_variables_json');
    
    if (defaultVarsTextarea && detectedVars.length > 0) {
        let currentVars = {};
        
        const currentJson = defaultVarsTextarea.value.trim();
        if (currentJson) {
            const parseResult = parseJSON(currentJson);
            if (parseResult.valid) {
                currentVars = parseResult.data;
            }
        }
        
        // เพิ่มตัวแปรที่ตรวจพบ
        const defaultVars = generateDefaultVariables(detectedVars);
        Object.keys(defaultVars).forEach(key => {
            if (!currentVars[key]) {
                currentVars[key] = defaultVars[key];
            }
        });
        
        defaultVarsTextarea.value = JSON.stringify(currentVars, null, 2);
    }
}

function updateDefaultVariablesJSON(detectedVars) {
    if (!detectedVars) {
        detectedVars = detectVariablesFromAllContent();
    }
    
    const defaultVarsField = document.getElementById('default_variables_json');
    if (!defaultVarsField) return;
    
    let currentJson = {};
    
    const currentValue = defaultVarsField.value.trim();
    if (currentValue) {
        const parseResult = parseJSON(currentValue);
        if (parseResult.valid) {
            currentJson = parseResult.data;
        }
    }
    
    let hasNewVars = false;
    const sampleData = generateSampleData();
    
    detectedVars.forEach(function(varName) {
        if (!(varName in currentJson)) {
            currentJson[varName] = sampleData[varName] || `ตัวอย่าง ${varName}`;
            hasNewVars = true;
        }
    });
    
    if (hasNewVars || defaultVarsField.value.trim() === '') {
        defaultVarsField.value = JSON.stringify(currentJson, null, 2);
    }
}

function addDetectedVariableToRequired(variableName) {
    const existingRows = document.querySelectorAll('.variable-row');
    let exists = false;
    
    existingRows.forEach(row => {
        const nameInput = row.querySelector('input[placeholder*="ชื่อตัวแปร"]');
        if (nameInput && nameInput.value.trim() === variableName) {
            exists = true;
        }
    });
    
    if (!exists) {
        addVariable(variableName);
    }
}

/**
 * Variable Management Functions
 */
window.addVariable = function(variableName = '') {
    const container = document.getElementById('variables-container');
    if (!container) return;

    const noVarMessage = document.getElementById('no-variables-message');
    if (noVarMessage) {
        noVarMessage.style.display = 'none';
    }
    
    const variableCount = container.children.length;
    
    const row = document.createElement('div');
    row.className = 'row mb-3 variable-row';
    row.innerHTML = `
        <div class="col-md-4">
            <input type="text" class="form-control" placeholder="ชื่อตัวแปร" 
                   name="variables[${variableCount}][name]" value="${variableName}">
        </div>
        <div class="col-md-4">
            <input type="text" class="form-control" placeholder="ค่าเริ่มต้น" 
                   name="variables[${variableCount}][default]">
        </div>
        <div class="col-md-3">
            <select class="form-select" name="variables[${variableCount}][type]">
                <option value="text">ข้อความ</option>
                <option value="number">ตัวเลข</option>
                <option value="date">วันที่</option>
                <option value="url">URL</option>
                <option value="email">อีเมล</option>
            </select>
        </div>
        <div class="col-md-1">
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeVariableRow(this)">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    container.appendChild(row);
};

window.removeVariableRow = function(button) {
    const row = button.closest('.variable-row');
    if (row) {
        row.remove();
        
        // ตรวจสอบว่าเหลือตัวแปรหรือไม่
        const container = document.getElementById('variables-container');
        const remainingRows = container.querySelectorAll('.variable-row');
        
        if (remainingRows.length === 0) {
            // แสดงข้อความ "ยังไม่มีตัวแปร" อีกครั้ง
            const noVarMessage = document.getElementById('no-variables-message');
            if (noVarMessage) {
                noVarMessage.style.display = 'block';
            } else {
                // สร้างข้อความใหม่ถ้าไม่มี
                const messageDiv = document.createElement('div');
                messageDiv.className = 'text-muted text-center py-3';
                messageDiv.id = 'no-variables-message';
                messageDiv.innerHTML = '<i class="fas fa-info-circle me-2"></i>ยังไม่มีตัวแปรที่จำเป็น คลิก "เพิ่มตัวแปร" เพื่อเริ่มต้น';
                container.appendChild(messageDiv);
            }
        }
    }
};

window.debugTemplateVariables = function() {
    const variables = [];
    document.querySelectorAll('.variable-row').forEach((row, index) => {
        const nameInput = row.querySelector('input[placeholder*="ชื่อตัวแปร"]');
        const defaultInput = row.querySelector('input[placeholder*="ค่าเริ่มต้น"]');
        const typeSelect = row.querySelector('select');
        
        variables.push({
            index: index,
            name: nameInput ? nameInput.value : '',
            default: defaultInput ? defaultInput.value : '',
            type: typeSelect ? typeSelect.value : 'text'
        });
    });
    
    console.log('Current variables:', variables);
    
    const defaultVarsJson = document.getElementById('default_variables_json')?.value;
    console.log('Default variables JSON:', defaultVarsJson);
    
    try {
        const parsed = JSON.parse(defaultVarsJson || '{}');
        console.log('Parsed default variables:', parsed);
    } catch (e) {
        console.error('JSON parse error:', e);
    }
    
    return variables;
};

window.loadTemplateVariablesFromJSON = function() {
    const defaultVarsJson = document.getElementById('default_variables_json')?.value;
    if (!defaultVarsJson || defaultVarsJson.trim() === '{}') {
        console.log('No default variables found');
        return;
    }
    
    try {
        const defaultVars = JSON.parse(defaultVarsJson);
        
        // ล้างตัวแปรเดิม
        const container = document.getElementById('variables-container');
        const existingRows = container.querySelectorAll('.variable-row');
        existingRows.forEach(row => row.remove());
        
        // เพิ่มตัวแปรจาก JSON
        Object.entries(defaultVars).forEach(([varName, varValue]) => {
            addVariable(varName);
            
            // หาแถวที่เพิ่งเพิ่มและใส่ค่า default
            const lastRow = container.querySelector('.variable-row:last-child');
            if (lastRow) {
                const defaultInput = lastRow.querySelector('input[placeholder*="ค่าเริ่มต้น"]');
                if (defaultInput) {
                    defaultInput.value = varValue;
                }
            }
        });
        
        console.log('Loaded variables from JSON:', Object.keys(defaultVars));
        
    } catch (e) {
        console.error('Failed to load variables from JSON:', e);
    }
};

/**
 * Variable Insertion Functions
 */
function insertVariable(varName) {
    const varText = '{{' + varName + '}}';
    
    if (lastFocusedElement) {
        // TinyMCE editor
        if (lastFocusedElement.isTinyMCE && lastFocusedElement.editor) {
            lastFocusedElement.editor.insertContent(varText);
            lastFocusedElement.editor.focus();
            return;
        }
        
        // Regular input/textarea
        if (lastFocusedElement.value !== undefined) {
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
        showAlert('warning', 'กรุณาคลิกในช่องข้อความก่อน');
    }
}

window.addCustomVariableFunction = function() {
    const customVar = document.getElementById('customVariable')?.value?.trim();
    if (customVar) {
        insertVariable(customVar);
        document.getElementById('customVariable').value = '';
        showAlert('success', `เพิ่มตัวแปร {{${customVar}}} แล้ว`, 1500);
    } else {
        showAlert('warning', 'กรุณาระบุชื่อตัวแปร');
        const input = document.getElementById('customVariable');
        if (input) input.focus();
    }
};

/**
 * Content Generation Functions
 */
window.generateSampleContent = function() {
    const category = document.getElementById('category')?.value;
    
    let sampleSubject = '';
    let sampleHtml = '';
    let sampleText = '';
    
    switch(category) {
        case 'system':
            sampleSubject = '[{{priority}}] การแจ้งเตือนระบบ: {{subject}}';
            sampleHtml = `<h2 style="color: #dc3545;">การแจ้งเตือนระบบ</h2>
<p>เรียน {{user_name}}</p>
<p>{{message}}</p>
<p><strong>ระดับความสำคัญ:</strong> {{priority}}<br>
<strong>เวลา:</strong> {{current_datetime}}</p>`;
            sampleText = 'การแจ้งเตือนระบบ: {{subject}}\n\n{{message}}\n\nระดับความสำคัญ: {{priority}}\nเวลา: {{current_datetime}}';
            break;
            
        case 'marketing':
            sampleSubject = '{{subject}} - {{company}}';
            sampleHtml = `<h2>สวัสดีครับ/ค่ะ {{user_name}}!</h2>
<p>{{message}}</p>
<p style="text-align: center;">
<a href="{{action_url}}" style="background-color: #0d6efd; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">{{action_text}}</a>
</p>
<p>ขอบคุณครับ/ค่ะ<br>ทีมงาน {{company}}</p>`;
            sampleText = 'สวัสดีครับ/ค่ะ {{user_name}}!\n\n{{message}}\n\n{{action_text}}: {{action_url}}\n\nขอบคุณครับ/ค่ะ\nทีมงาน {{company}}';
            break;
            
        case 'operational':
            sampleSubject = 'แจ้งเตือน: {{subject}}';
            sampleHtml = `<p>เรียน {{user_name}}</p>
<p>{{message}}</p>
<p>ขอบคุณครับ/ค่ะ<br>{{system_name}}</p>`;
            sampleText = 'เรียน {{user_name}}\n\n{{message}}\n\nขอบคุณครับ/ค่ะ\n{{system_name}}';
            break;
            
        default:
            sampleSubject = '{{subject}}';
            sampleHtml = `<p>เรียน {{user_name}}</p>
<p>{{message}}</p>
<p>ขอบคุณครับ/ค่ะ</p>`;
            sampleText = 'เรียน {{user_name}}\n\n{{message}}\n\nขอบคุณครับ/ค่ะ';
    }
    
    // ใส่เนื้อหาตัวอย่าง
    setFieldValue('subject_template', sampleSubject);
    
    if (tinymce.get('body_html_template')) {
        tinymce.get('body_html_template').setContent(sampleHtml);
    } else {
        setFieldValue('body_html_template', sampleHtml);
    }
    
    setFieldValue('body_text_template', sampleText);
    
    showAlert('success', 'สร้างเนื้อหาตัวอย่างเรียบร้อย');
};

window.generateDefaultVariablesJSON = function() {
    const detectedVars = detectVariablesFromAllContent();
    const defaultVarsField = document.getElementById('default_variables_json');
    
    if (!defaultVarsField) return;
    
    if (detectedVars.length === 0) {
        showAlert('warning', 'ไม่พบตัวแปรในเนื้อหาเทมเพลต กรุณาเพิ่มตัวแปรก่อน');
        return;
    }
    
    const currentValue = defaultVarsField.value.trim();
    if (currentValue) {
        if (!confirm('การดำเนินการนี้จะเขียนทับตัวแปรเริ่มต้นที่มีอยู่ ต้องการดำเนินการต่อหรือไม่?')) {
            return;
        }
    }
    
    const defaultVars = generateDefaultVariables(detectedVars);
    defaultVarsField.value = JSON.stringify(defaultVars, null, 2);
    
    showAlert('success', `สร้างค่าเริ่มต้นสำหรับ ${detectedVars.length} ตัวแปรแล้ว`);
};

window.formatJSON = function() {
    const textarea = document.getElementById('default_variables_json');
    if (!textarea) return;
    
    const formatted = formatJSON(textarea.value);
    if (formatted !== textarea.value) {
        textarea.value = formatted;
        showAlert('success', 'จัดรูปแบบ JSON เรียบร้อย');
    } else {
        showAlert('error', 'รูปแบบ JSON ไม่ถูกต้อง กรุณาตรวจสอบ syntax');
    }
};

/**
 * Preview Functions
 */
window.loadSampleDataForPreview = function() {
    const sampleData = generateSampleData();
    generateClientSidePreview(sampleData);
};

window.refreshPreview = function() {
    loadSampleDataForPreview();
};

function generateClientSidePreview(sampleData) {
    const subjectTemplate = document.getElementById('subject_template')?.value || '';
    const bodyTextTemplate = document.getElementById('body_text_template')?.value || '';
    let bodyHtmlTemplate = '';
    
    const htmlEditor = tinymce.get('body_html_template');
    if (htmlEditor) {
        bodyHtmlTemplate = htmlEditor.getContent();
    } else {
        bodyHtmlTemplate = document.getElementById('body_html_template')?.value || '';
    }
    
    // แทนที่ตัวแปร
    const previewSubject = replaceVariables(subjectTemplate, sampleData);
    const previewHtml = replaceVariables(bodyHtmlTemplate, sampleData);
    const previewText = replaceVariables(bodyTextTemplate, sampleData);
    
    displayPreview({
        subject: previewSubject,
        body_html: previewHtml,
        body_text: previewText
    }, sampleData);
}

function displayPreview(preview, sampleData) {
    let html = '<div class="row">';
    
    if (preview.subject) {
        html += `<div class="col-12 mb-3">
            <h6><i class="fas fa-heading me-1"></i>หัวข้อ:</h6>
            <div class="alert alert-info">${preview.subject}</div>
        </div>`;
    }
    
    if (preview.body_html) {
        html += `<div class="col-md-12">
            <h6><i class="fas fa-code me-1"></i>ตัวอย่าง HTML (อีเมล):</h6>
            <div class="border rounded p-3">${preview.body_html}</div>
        </div>`;
    }
    
    if (preview.body_text) {
        html += `<div class="col-md-12">
            <h6><i class="fas fa-file-text me-1"></i>ตัวอย่างข้อความธรรมดา:</h6>
            <div class="bg-light border rounded p-3"><pre>${preview.body_text}</pre></div>
        </div>`;
    }
    
    html += '</div>';
    
    // แสดงข้อมูลตัวอย่างที่ใช้
    html += '<hr><div class="mt-3">';
    html += '<h6><i class="fas fa-database me-1"></i>ข้อมูลตัวอย่างที่ใช้:</h6>';
    html += '<div class="table-responsive">';
    html += '<table class="table table-sm table-bordered">';
    html += '<thead><tr><th>ตัวแปร</th><th>ค่า</th></tr></thead>';
    html += '<tbody>';

    Object.entries(sampleData).forEach(([key, value]) => {
        html += `<tr>
            <td><code>{{${key}}}</code></td>
            <td>${value}</td>
        </tr>`;
    });

    html += '</tbody></table></div></div>';
    
    const previewContainer = document.getElementById('final-preview');
    if (previewContainer) {
        previewContainer.innerHTML = html;
    }
}

/**
 * Content Tools Functions
 */
window.validateTemplate = function() {
    const resultDiv = document.getElementById('validation-result');
    if (!resultDiv) return;
    
    resultDiv.innerHTML = '<div class="text-info"><i class="fas fa-spinner fa-spin"></i> กำลังตรวจสอบ...</div>';
    
    setTimeout(() => {
        const subject = document.getElementById('subject_template')?.value || '';
        let htmlContent = '';
        
        const htmlEditor = tinymce.get('body_html_template');
        if (htmlEditor) {
            htmlContent = htmlEditor.getContent();
        } else {
            htmlContent = document.getElementById('body_html_template')?.value || '';
        }
        
        const textContent = document.getElementById('body_text_template')?.value || '';
        const errors = [];
        const warnings = [];
        
        // ตรวจสอบ syntax
        if (subject) {
            const subjectValidation = validateTemplateSyntax(subject);
            if (!subjectValidation.valid) {
                errors.push(...subjectValidation.errors.map(err => `หัวข้อ: ${err}`));
            }
        }
        
        if (htmlContent) {
            const htmlValidation = validateTemplateSyntax(htmlContent);
            if (!htmlValidation.valid) {
                errors.push(...htmlValidation.errors.map(err => `HTML: ${err}`));
            }
        }
        
        if (textContent) {
            const textValidation = validateTemplateSyntax(textContent);
            if (!textValidation.valid) {
                errors.push(...textValidation.errors.map(err => `ข้อความ: ${err}`));
            }
        }
        
        // ตรวจสอบ SMS length
        if (textContent) {
            const smsCheck = checkSMSLength(textContent);
            if (smsCheck.warning) {
                warnings.push(smsCheck.message);
            }
        }
        
        // ตรวจสอบตัวแปร
        const detectedVars = detectVariablesFromAllContent();
        if (detectedVars.length === 0 && (subject || htmlContent || textContent)) {
            warnings.push('ไม่พบตัวแปรในเทมเพลต อาจไม่สามารถปรับแต่งเนื้อหาได้');
        }
        
        // แสดงผล
        if (errors.length > 0) {
            resultDiv.innerHTML = `
                <div class="alert alert-danger">
                    <strong><i class="fas fa-exclamation-triangle"></i> พบข้อผิดพลาด:</strong>
                    <ul class="mb-0 mt-2">
                        ${errors.map(err => `<li>${err}</li>`).join('')}
                    </ul>
                </div>`;
        } else if (warnings.length > 0) {
            resultDiv.innerHTML = `
                <div class="alert alert-warning">
                    <strong><i class="fas fa-exclamation-circle"></i> ข้อแนะนำ:</strong>
                    <ul class="mb-0 mt-2">
                        ${warnings.map(warn => `<li>${warn}</li>`).join('')}
                    </ul>
                </div>`;
        } else {
            resultDiv.innerHTML = `
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> เทมเพลตถูกต้อง!
                    ${detectedVars.length > 0 ? `<br><small>พบตัวแปร: ${detectedVars.join(', ')}</small>` : ''}
                </div>`;
        }
    }, 1000);
};

window.previewContent = function() {
    const previewDiv = document.getElementById('content-preview');
    if (!previewDiv) return;
    
    previewDiv.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> กำลังสร้างตัวอย่าง...</div>';
    
    setTimeout(() => {
        const sampleData = generateSampleData();
        
        const subject = document.getElementById('subject_template')?.value || '';
        let htmlContent = '';
        
        if (tinymce.get('body_html_template')) {
            htmlContent = tinymce.get('body_html_template').getContent();
        } else {
            htmlContent = document.getElementById('body_html_template')?.value || '';
        }
        
        const textContent = document.getElementById('body_text_template')?.value || '';
        
        const previewSubject = replaceVariables(subject, sampleData);
        const previewHtml = replaceVariables(htmlContent, sampleData);
        const previewText = replaceVariables(textContent, sampleData);
        
        previewDiv.innerHTML = `
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-eye"></i> ตัวอย่างเทมเพลต</h6>
                </div>
                <div class="card-body">
                    ${subject ? `
                    <div class="mb-3">
                        <strong>หัวข้อ:</strong>
                        <div class="alert alert-info small mb-0">${previewSubject}</div>
                    </div>` : ''}
                    
                    ${htmlContent ? `
                    <div class="mb-3">
                        <strong>เนื้อหา HTML (อีเมล):</strong>
                        <div class="border rounded p-3 small" style="background-color: #f8f9fa;">${previewHtml}</div>
                    </div>` : ''}
                    
                    ${textContent ? `
                    <div class="mb-3">
                        <strong>เนื้อหาข้อความธรรมดา:</strong>
                        <div class="bg-light border rounded p-3"><pre class="small mb-0">${previewText}</pre></div>
                    </div>` : ''}
                </div>
            </div>
        `;
    }, 500);
};

window.clearContent = function() {
    if (confirm('ต้องการล้างเนื้อหาทั้งหมดหรือไม่?')) {
        setFieldValue('subject_template', '');
        
        if (tinymce.get('body_html_template')) {
            tinymce.get('body_html_template').setContent('');
        } else {
            setFieldValue('body_html_template', '');
        }
        
        setFieldValue('body_text_template', '');
        
        const previewDiv = document.getElementById('content-preview');
        if (previewDiv) previewDiv.innerHTML = '';
        
        const resultDiv = document.getElementById('validation-result');
        if (resultDiv) resultDiv.innerHTML = '';
        
        showAlert('success', 'ล้างเนื้อหาเรียบร้อย');
    }
};

window.validateVariables = function() {
    const resultDiv = document.getElementById('variable-validation-result');
    if (!resultDiv) return;
    
    const detectedVars = detectVariablesFromAllContent();
    const defaultVarsText = document.getElementById('default_variables_json')?.value?.trim();
    
    let defaultVars = {};
    let jsonValid = true;
    
    if (defaultVarsText) {
        const parseResult = parseJSON(defaultVarsText);
        if (parseResult.valid) {
            defaultVars = parseResult.data;
        } else {
            jsonValid = false;
            resultDiv.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> ${parseResult.error}
                </div>`;
            return;
        }
    }
    
    const missingVars = detectedVars.filter(varName => !defaultVars[varName] && !allSystemVariables[varName]);
    const unusedVars = Object.keys(defaultVars).filter(varName => !detectedVars.includes(varName));
    
    if (missingVars.length === 0 && unusedVars.length === 0) {
        resultDiv.innerHTML = `
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> ตัวแปรถูกต้องทั้งหมด
                <br><small>พบตัวแปร ${detectedVars.length} ตัว</small>
            </div>`;
    } else {
        let html = '<div class="alert alert-warning">';
        
        if (missingVars.length > 0) {
            html += `<div class="mb-2"><strong>ตัวแปรที่ไม่มีค่าเริ่มต้น:</strong> ${missingVars.join(', ')}</div>`;
        }
        
        if (unusedVars.length > 0) {
            html += `<div><strong>ตัวแปรที่ไม่ได้ใช้:</strong> ${unusedVars.join(', ')}</div>`;
        }
        
        html += '</div>';
        resultDiv.innerHTML = html;
    }
};

/**
 * Character Count Function
 */
function updateCharacterCount() {
    const textField = document.getElementById('body_text_template');
    const charCount = document.getElementById('text-char-count');
    
    if (textField && charCount) {
        const count = textField.value.length;
        charCount.textContent = `${count} ตัวอักษร`;
        
        if (count > 160) {
            charCount.className = 'badge bg-warning ms-2';
        } else {
            charCount.className = 'badge bg-secondary ms-2';
        }
    }
}

/**
 * Slug Generation
 */
function generateSlugFromName() {
    const nameField = document.getElementById('name');
    const slugField = document.getElementById('slug');
    
    if (nameField && nameField.value && slugField && !slugField.value) {
        const slug = generateSlug(nameField.value);
        slugField.value = slug;
    }
}

/**
 * Variable Badge Handlers
 */
function setupVariableBadgeHandlers() {
    document.querySelectorAll('.variable-badge[data-variable]').forEach(function(badge) {
        badge.addEventListener('click', function() {
            const variable = this.getAttribute('data-variable');
            if (variable) {
                insertVariable(variable);
                
                // Visual feedback
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 150);
            }
        });
    });
}

/**
 * Variable Detection Setup
 */
function setupVariableDetection() {
    if (variableDetectionInitialized) return;
    
    const subjectField = document.getElementById('subject_template');
    const textField = document.getElementById('body_text_template');
    
    // Setup for regular input fields
    [subjectField, textField].forEach(function(field) {
        if (field) {
            field.addEventListener('input', debounce(updateDetectedVariables, 500));
            field.addEventListener('blur', updateDetectedVariables);
            
            // Setup text field specific handlers
            if (field.id === 'body_text_template') {
                field.addEventListener('input', debounce(() => {
                    updateCharacterCount();
                    // Sync text to HTML if user is editing text directly
                    if (!htmlToTextSync) {
                        syncTextToHtml();
                    }
                }, 500));
            }
        }
    });
    
    // Setup for TinyMCE
    function setupTinyMCEListeners() {
        const htmlEditor = tinymce.get('body_html_template');
        if (htmlEditor) {
            htmlEditor.on('input', debounce(updateDetectedVariables, 500));
            htmlEditor.on('change', debounce(updateDetectedVariables, 500));
            htmlEditor.on('keyup', debounce(updateDetectedVariables, 1000));
            htmlEditor.on('paste', debounce(updateDetectedVariables, 1000));
            
            variableDetectionInitialized = true;
            setTimeout(updateDetectedVariables, 1000);
        } else {
            setTimeout(setupTinyMCEListeners, 1000);
        }
    }
    
    setTimeout(setupTinyMCEListeners, 1500);
}

/**
 * Draft Management Functions
 */
window.saveDraft = function() {
    if (!isLocalStorageAvailable()) {
        showAlert('warning', 'เบราว์เซอร์ไม่รองรับการบันทึกแบบร่าง');
        return;
    }
    
    const htmlEditor = tinymce.get('body_html_template');
    let htmlContent = '';
    if (htmlEditor) {
        htmlContent = htmlEditor.getContent();
    } else {
        htmlContent = document.getElementById('body_html_template')?.value || '';
    }
    
    const draftData = {
        name: document.getElementById('name')?.value || '',
        slug: document.getElementById('slug')?.value || '',
        category: document.getElementById('category')?.value || '',
        priority: document.getElementById('priority')?.value || '',
        description: document.getElementById('description')?.value || '',
        subject_template: document.getElementById('subject_template')?.value || '',
        body_html_template: htmlContent,
        body_text_template: document.getElementById('body_text_template')?.value || '',
        default_variables_json: document.getElementById('default_variables_json')?.value || '',
        timestamp: new Date().toISOString()
    };
    
    // บันทึก supported channels
    const channels = [];
    document.querySelectorAll('input[name="supported_channels[]"]:checked').forEach(cb => {
        channels.push(cb.value);
    });
    draftData.supported_channels = channels;
    
    localStorage.setItem('template_draft_thai', JSON.stringify(draftData));
    showAlert('success', 'บันทึกแบบร่างเรียบร้อย!', 2000);
};

function loadDraft() {
    if (!isLocalStorageAvailable()) return;
    
    const draft = localStorage.getItem('template_draft_thai');
    if (draft) {
        try {
            const draftData = JSON.parse(draft);
            const draftAge = Date.now() - new Date(draftData.timestamp).getTime();
            
            // โหลด draft เฉพาะถ้าอายุไม่เกิน 24 ชั่วโมง
            if (draftAge < 24 * 60 * 60 * 1000) {
                const shouldLoad = confirm(
                    `พบแบบร่างที่บันทึกไว้เมื่อ ${new Date(draftData.timestamp).toLocaleString('th-TH')} ` +
                    'ต้องการโหลดข้อมูลเพื่อทำงานต่อหรือไม่?'
                );
                
                if (shouldLoad) {
                    // โหลดข้อมูลพื้นฐาน
                    Object.keys(draftData).forEach(key => {
                        if (key !== 'timestamp' && key !== 'supported_channels') {
                            setFieldValue(key, draftData[key]);
                        }
                    });
                    
                    // โหลด supported channels
                    if (draftData.supported_channels) {
                        draftData.supported_channels.forEach(channel => {
                            const checkbox = document.getElementById(`channel_${channel}`);
                            if (checkbox) checkbox.checked = true;
                        });
                    }
                    
                    showAlert('success', 'โหลดแบบร่างเรียบร้อย');
                    
                    // โหลดเนื้อหาใน TinyMCE หลังจากเริ่มต้นแล้ว
                    setTimeout(() => {
                        const htmlEditor = tinymce.get('body_html_template');
                        if (htmlEditor && draftData.body_html_template) {
                            htmlEditor.setContent(draftData.body_html_template);
                        }
                    }, 2000);
                }
            }
        } catch (e) {
            console.error('ข้อผิดพลาดในการโหลดแบบร่าง:', e);
            localStorage.removeItem('template_draft_thai');
        }
    }
}

/**
 * Form Submission Functions
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
    
    // HTML content - เฉพาะอีเมลเท่านั้น
    let htmlContent = '';
    const htmlEditor = tinymce.get('body_html_template');
    if (htmlEditor) {
        htmlContent = htmlEditor.getContent();
    } else {
        htmlContent = document.getElementById('body_html_template')?.value || '';
    }
    
    // เช็คว่าเลือก email channel หรือไม่
    if (channels.includes('email')) {
        formData.append('body_html_template', htmlContent);
    } else {
        formData.append('body_html_template', ''); // ไม่ส่ง HTML ถ้าไม่ใช้อีเมล
    }
    
    // Text content
    formData.append('body_text_template', document.getElementById('body_text_template')?.value || '');
    
    // Variables
    document.querySelectorAll('.variable-row').forEach((row, index) => {
        const nameInput = row.querySelector('input[placeholder*="ชื่อตัวแปร"]');
        const defaultInput = row.querySelector('input[placeholder*="ค่าเริ่มต้น"]');
        const typeSelect = row.querySelector('select');
        
        if (nameInput && nameInput.value.trim()) {
            formData.append(`variables[${index}][name]`, nameInput.value.trim());
            formData.append(`variables[${index}][default]`, defaultInput ? defaultInput.value.trim() : '');
            formData.append(`variables[${index}][type]`, typeSelect ? typeSelect.value : 'text');
        }
    });
    
    // Default variables JSON
    const defaultVarsJson = document.getElementById('default_variables_json')?.value?.trim();
    if (defaultVarsJson) {
        const parseResult = parseJSON(defaultVarsJson);
        if (parseResult.valid) {
            Object.keys(parseResult.data).forEach(key => {
                formData.append(`default_variables[${key}]`, parseResult.data[key]);
            });
        }
    }
    
    // CSRF Token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                      document.querySelector('input[name="_token"]')?.value;
    if (csrfToken) {
        formData.append('_token', csrfToken);
    }
    
    return formData;
}

function submitToLaravel(formData, saveBtn, originalText) {
    // Debug information
    console.log('ข้อมูลที่จะส่ง:');
    for (let [key, value] of formData.entries()) {
        console.log(key + ':', value);
    }
    
    let submitUrl;
    const form = document.getElementById('templateForm');
    if (form) {
        submitUrl = form.action;
    } else {
        submitUrl = window.location.origin + '/templates';
    }
    
    console.log('ส่งข้อมูลไปยัง:', submitUrl);
    
    fetch(submitUrl, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        }
    })
    .then(response => {
        console.log('สถานะการตอบกลับ:', response.status);
        
        if (!response.ok) {
            return response.text().then(text => {
                console.error('ข้อผิดพลาด:', text);
                throw new Error(`HTTP ${response.status}: ${text}`);
            });
        }
        
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        } else {
            return { success: true, message: 'สร้างเทมเพลตเรียบร้อย' };
        }
    })
    .then(data => {
        console.log('ตอบกลับสำเร็จ:', data);
        
        if (data.success !== false) {
            showAlert('success', data.message || 'สร้างเทมเพลตเรียบร้อย!');
            
            // ลบ draft
            if (isLocalStorageAvailable()) {
                localStorage.removeItem('template_draft_thai');
            }
            
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
            throw new Error(data.message || 'ไม่สามารถบันทึกเทมเพลตได้');
        }
    })
    .catch(error => {
        console.error('ข้อผิดพลาด:', error);
        
        let errorMessage = 'เกิดข้อผิดพลาดในการบันทึกเทมเพลต';
        
        if (error.message.includes('405')) {
            errorMessage = 'ข้อผิดพลาดการกำหนดค่า Route กรุณาตรวจสอบ Laravel routes';
        } else if (error.message.includes('419')) {
            errorMessage = 'CSRF token ไม่ถูกต้อง กรุณารีเฟรชหน้าและลองใหม่';
        } else if (error.message.includes('422')) {
            errorMessage = 'ข้อมูลไม่ถูกต้อง กรุณาตรวจสอบข้อมูลที่กรอก';
        } else if (error.message) {
            errorMessage = error.message;
        }
        
        showAlert('error', errorMessage);
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
    });
}

/**
 * Auto-save functionality
 */
setInterval(function() {
    const activeElement = document.activeElement;
    if (activeElement && (activeElement.tagName === 'INPUT' || activeElement.tagName === 'TEXTAREA')) {
        return; // ไม่ save ถ้ากำลังพิมพ์
    }
    
    const nameField = document.getElementById('name');
    if (nameField && nameField.value.trim() && isLocalStorageAvailable()) {
        saveDraft();
    }
}, 5 * 60 * 1000); // 5 นาที

/**
 * Document Ready Event Handlers
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('เริ่มต้นระบบสร้างเทมเพลต...');
    
    // เริ่มต้นส่วนประกอบต่างๆ
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

    // Auto-generate slug when name changes
    const nameField = document.getElementById('name');
    if (nameField) {
        nameField.addEventListener('input', debounce(generateSlugFromName, 300));
        nameField.addEventListener('blur', generateSlugFromName);
    }
    
    // Character count for text field
    const textField = document.getElementById('body_text_template');
    if (textField) {
        textField.addEventListener('input', updateCharacterCount);
        updateCharacterCount(); // Initial count
    }
    
    // Form submission handler
    const templateForm = document.getElementById('templateForm');
    if (templateForm) {
        templateForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            console.log('เริ่มส่งข้อมูลฟอร์ม...');
            
            // Sync TinyMCE content
            if (tinymce.get('body_html_template')) {
                tinymce.get('body_html_template').save();
            }
            
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
            
            if (!allValid) return;
            
            // แสดงสถานะการโหลด
            const saveBtn = document.getElementById('saveBtn');
            if (saveBtn) {
                const originalText = saveBtn.innerHTML;
                saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังบันทึก...';
                saveBtn.disabled = true;
                
                const formData = collectFormData();
                submitToLaravel(formData, saveBtn, originalText);
            }
        });
    }
    
    console.log('เริ่มต้นระบบสร้างเทมเพลตเรียบร้อย');
});

// Export สำหรับการใช้งานภายนอก
window.TemplateCreator = {
    showStep,
    startFromScratch,
    showTemplateGallery,
    hideTemplateGallery,
    useTemplate,
    importTemplate,
    generateSampleContent,
    validateTemplate,
    previewContent,
    clearContent,
    saveDraft,
    addVariable,
    removeVariableRow,
    addCustomVariableFunction,
    generateDefaultVariablesJSON,
    formatJSON,
    validateVariables,
    nextStep,
    previousStep,
    loadSampleDataForPreview,
    refreshPreview
};

console.log('Template Creator (Thai) โหลดเรียบร้อย');