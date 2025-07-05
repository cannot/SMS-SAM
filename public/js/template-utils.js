/**
 * Template Utilities - Thai Version
 * ฟังก์ชันช่วยเหลือสำหรับจัดการ Template
 */

import { allSystemVariables, reservedSystemVariables, generateSampleData } from './system-variables.js';

/**
 * แทนที่ตัวแปรใน template ด้วยข้อมูลจริง
 */
export function replaceVariables(template, data) {
    if (!template || typeof template !== 'string') {
        return '';
    }
    
    let result = template;
    
    // แทนที่ตัวแปรแบบง่าย {{variable}}
    Object.entries(data || {}).forEach(([key, value]) => {
        const regex = new RegExp(`\\{\\{\\s*${key}\\s*\\}\\}`, 'g');
        result = result.replace(regex, value || '');
    });
    
    // แทนที่ตัวแปรที่เหลือด้วย placeholder
    result = result.replace(/\{\{([^}]+)\}\}/g, (match, varName) => {
        const cleanVarName = varName.trim();
        return `[${cleanVarName}]`;
    });
    
    return result;
}

/**
 * ตรวจจับตัวแปรจาก template content
 */
export function detectVariablesFromContent(content) {
    if (!content || typeof content !== 'string') {
        return [];
    }
    
    const variables = new Set();
    
    // หาตัวแปรแบบง่าย {{variable}}
    const simpleMatches = content.match(/\{\{([^}#\/][^}]*?)\}\}/g);
    if (simpleMatches) {
        simpleMatches.forEach(match => {
            const varName = match.replace(/[{}]/g, '').split(':')[0].split('|')[0].trim();
            if (varName && !reservedSystemVariables.includes(varName)) {
                variables.add(varName);
            }
        });
    }
    
    // หาตัวแปรใน conditional statements {{#if variable}}
    const conditionalMatches = content.match(/\{\{#(if|each)\s+([^}]+)\}\}/g);
    if (conditionalMatches) {
        conditionalMatches.forEach(match => {
            const varName = match.replace(/\{\{#(if|each)\s+/, '').replace(/\}\}/, '').trim();
            if (varName && !reservedSystemVariables.includes(varName)) {
                variables.add(varName);
            }
        });
    }
    
    return Array.from(variables);
}

/**
 * สร้างเนื้อหา HTML จาก text content
 */
export function convertTextToHtml(textContent) {
    if (!textContent) return '';
    
    return textContent
        .replace(/\n\n/g, '</p><p>')
        .replace(/\n/g, '<br>')
        .replace(/^/, '<p>')
        .replace(/$/, '</p>')
        .replace(/<p><\/p>/g, '');
}

/**
 * แปลง HTML เป็น text โดยลบ tags
 */
export function convertHtmlToText(htmlContent) {
    if (!htmlContent) return '';
    
    // สร้าง temporary div สำหรับแปลง HTML
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = htmlContent;
    
    // แทนที่ <br> และ <p> ด้วย newlines
    tempDiv.innerHTML = tempDiv.innerHTML
        .replace(/<br\s*\/?>/gi, '\n')
        .replace(/<\/p>/gi, '\n\n')
        .replace(/<p[^>]*>/gi, '');
    
    return tempDiv.textContent || tempDiv.innerText || '';
}

/**
 * ตรวจสอบ syntax ของ template
 */
export function validateTemplateSyntax(template) {
    const errors = [];
    
    if (!template || typeof template !== 'string') {
        return { valid: false, errors: ['เทมเพลตต้องเป็น string'] };
    }
    
    // ตรวจสอบ {{ }} ที่ไม่สมดุล
    const openBraces = (template.match(/\{\{/g) || []).length;
    const closeBraces = (template.match(/\}\}/g) || []).length;
    
    if (openBraces !== closeBraces) {
        errors.push(`วงเล็บปีกกาไม่สมดุล: เปิด ${openBraces} ปิด ${closeBraces}`);
    }
    
    // ตรวจสอบ conditional statements ที่ไม่สมดุล
    const ifMatches = (template.match(/\{\{#if\s+[^}]+\}\}/g) || []).length;
    const endifMatches = (template.match(/\{\{\/if\}\}/g) || []).length;
    
    if (ifMatches !== endifMatches) {
        errors.push(`คำสั่ง if ไม่สมดุล: เปิด ${ifMatches} ปิด ${endifMatches}`);
    }
    
    // ตรวจสอบ each statements ที่ไม่สมดุล
    const eachMatches = (template.match(/\{\{#each\s+[^}]+\}\}/g) || []).length;
    const endeachMatches = (template.match(/\{\{\/each\}\}/g) || []).length;
    
    if (eachMatches !== endeachMatches) {
        errors.push(`คำสั่ง each ไม่สมดุล: เปิด ${eachMatches} ปิด ${endeachMatches}`);
    }
    
    return {
        valid: errors.length === 0,
        errors: errors
    };
}

/**
 * สร้าง slug จากชื่อ
 */
export function generateSlug(name) {
    if (!name || typeof name !== 'string') {
        return '';
    }
    
    return name
        .toLowerCase()
        .trim()
        // แทนที่อักขระพิเศษด้วย -
        .replace(/[^\u0E00-\u0E7Fa-z0-9\s-]/g, '')
        // แทนที่ช่องว่างด้วย -
        .replace(/\s+/g, '-')
        // ลบ - ที่ซ้ำกัน
        .replace(/-+/g, '-')
        // ลบ - ที่ขึ้นต้นและลงท้าย
        .replace(/^-|-$/g, '');
}

/**
 * ตรวจสอบและแปลง JSON
 */
export function parseJSON(jsonString) {
    if (!jsonString || typeof jsonString !== 'string') {
        return { valid: false, data: null, error: 'ข้อมูล JSON ไม่ถูกต้อง' };
    }
    
    try {
        const data = JSON.parse(jsonString);
        
        // ตรวจสอบว่าเป็น object ไม่ใช่ array
        if (Array.isArray(data)) {
            return { 
                valid: false, 
                data: null, 
                error: 'ข้อมูลตัวแปรต้องเป็น Object ไม่ใช่ Array' 
            };
        }
        
        if (typeof data !== 'object' || data === null) {
            return { 
                valid: false, 
                data: null, 
                error: 'ข้อมูลตัวแปรต้องเป็น Object' 
            };
        }
        
        return { valid: true, data: data, error: null };
    } catch (error) {
        return { 
            valid: false, 
            data: null, 
            error: `รูปแบบ JSON ไม่ถูกต้อง: ${error.message}` 
        };
    }
}

/**
 * จัดรูปแบบ JSON
 */
export function formatJSON(jsonString) {
    const result = parseJSON(jsonString);
    if (result.valid) {
        return JSON.stringify(result.data, null, 2);
    }
    return jsonString;
}

/**
 * สร้างตัวอย่างตัวแปรจากตัวแปรที่ตรวจพบ
 */
export function generateDefaultVariables(detectedVariables) {
    const defaultVars = {};
    const sampleData = generateSampleData();
    
    detectedVariables.forEach(varName => {
        if (sampleData[varName]) {
            defaultVars[varName] = sampleData[varName];
        } else {
            // สร้างค่าตัวอย่างสำหรับตัวแปรที่ไม่รู้จัก
            defaultVars[varName] = getSampleValueForVariable(varName);
        }
    });
    
    return defaultVars;
}

/**
 * สร้างค่าตัวอย่างสำหรับตัวแปรที่ไม่รู้จัก
 */
export function getSampleValueForVariable(variableName) {
    const commonSamples = {
        'title': 'หัวข้อตัวอย่าง',
        'description': 'คำอธิบายตัวอย่าง',
        'content': 'เนื้อหาตัวอย่าง',
        'text': 'ข้อความตัวอย่าง',
        'link': 'https://example.com',
        'button': 'คลิกที่นี่',
        'date': new Date().toISOString().split('T')[0],
        'time': new Date().toTimeString().split(' ')[0],
        'number': '100',
        'price': '1,000.00',
        'percentage': '50'
    };
    
    // ค้นหาคำที่คล้ายกัน
    for (const [key, value] of Object.entries(commonSamples)) {
        if (variableName.toLowerCase().includes(key)) {
            return value;
        }
    }
    
    // ถ้าไม่พบ ให้สร้างค่าทั่วไป
    return `ตัวอย่าง ${variableName}`;
}

/**
 * ตรวจสอบความยาวข้อความสำหรับ SMS
 */
export function checkSMSLength(text) {
    if (!text) return { length: 0, segments: 0, warning: false };
    
    const length = text.length;
    const segments = Math.ceil(length / 160);
    const warning = length > 160;
    
    return {
        length: length,
        segments: segments,
        warning: warning,
        message: warning ? 
            `ข้อความยาว ${length} ตัวอักษร จะถูกแบ่งเป็น ${segments} ข้อความ` :
            `ข้อความยาว ${length} ตัวอักษร`
    };
}

/**
 * สร้างตัวอย่าง email preview
 */
export function generateEmailPreview(template, variables) {
    const data = { ...generateSampleData(), ...variables };
    
    return {
        subject: replaceVariables(template.subject || '', data),
        html: replaceVariables(template.html || '', data),
        text: replaceVariables(template.text || '', data)
    };
}

/**
 * ตรวจสอบและแยกชื่อ-นามสกุล
 */
export function splitFullName(fullName) {
    if (!fullName || typeof fullName !== 'string') {
        return { firstName: '', lastName: '' };
    }
    
    const parts = fullName.trim().split(/\s+/);
    
    if (parts.length === 1) {
        return { firstName: parts[0], lastName: '' };
    } else if (parts.length === 2) {
        return { firstName: parts[0], lastName: parts[1] };
    } else {
        // มากกว่า 2 คำ ให้เอาคำแรกเป็นชื่อ ที่เหลือเป็นนามสกุล
        return { 
            firstName: parts[0], 
            lastName: parts.slice(1).join(' ') 
        };
    }
}

/**
 * สร้าง debounce function
 */
export function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func.apply(this, args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * แสดงการแจ้งเตือน
 */
export function showAlert(type, message, duration = 3000) {
    // ลบ alert เก่า
    const existingAlerts = document.querySelectorAll('.template-alert');
    existingAlerts.forEach(alert => alert.remove());
    
    const alertClass = {
        'success': 'alert-success',
        'error': 'alert-danger',
        'warning': 'alert-warning',
        'info': 'alert-info'
    }[type] || 'alert-info';
    
    const icon = {
        'success': 'fas fa-check-circle',
        'error': 'fas fa-exclamation-triangle',
        'warning': 'fas fa-exclamation-triangle',
        'info': 'fas fa-info-circle'
    }[type] || 'fas fa-info-circle';
    
    const alertElement = document.createElement('div');
    alertElement.className = `alert ${alertClass} alert-dismissible fade show position-fixed template-alert`;
    alertElement.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertElement.innerHTML = `
        <i class="${icon}"></i> ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    document.body.appendChild(alertElement);
    
    // Auto remove
    if (type === 'success' && duration > 0) {
        setTimeout(() => {
            if (alertElement.parentNode) {
                alertElement.remove();
            }
        }, duration);
    }
    
    return alertElement;
}

/**
 * ตรวจสอบการรองรับ localStorage
 */
export function isLocalStorageAvailable() {
    try {
        const test = 'localStorage-test';
        localStorage.setItem(test, test);
        localStorage.removeItem(test);
        return true;
    } catch (e) {
        return false;
    }
}