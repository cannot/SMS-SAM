const teamsTemplates = {
    basic: {
        "type": "AdaptiveCard",
        "version": "1.4",
        "body": [
            {
                "type": "TextBlock",
                "text": "{{title}}",
                "size": "Medium",
                "weight": "Bolder"
            },
            {
                "type": "TextBlock",
                "text": "{{message}}",
                "wrap": true
            }
        ]
    },
    hero: {
        "type": "AdaptiveCard",
        "version": "1.4",
        "body": [
            {
                "type": "Container",
                "style": "emphasis",
                "items": [
                    {
                        "type": "TextBlock",
                        "text": "{{title}}",
                        "size": "Large",
                        "weight": "Bolder",
                        "color": "Accent"
                    },
                    {
                        "type": "TextBlock",
                        "text": "{{message}}",
                        "wrap": true
                    }
                ]
            }
        ],
        "actions": [
            {
                "type": "Action.OpenUrl",
                "title": "View Details",
                "url": "{{url}}"
            }
        ]
    },
    factset: {
        "type": "AdaptiveCard",
        "version": "1.4",
        "body": [
            {
                "type": "TextBlock",
                "text": "{{title}}",
                "size": "Medium",
                "weight": "Bolder"
            },
            {
                "type": "FactSet",
                "facts": [
                    {
                        "title": "User:",
                        "value": "{{user.name}}"
                    },
                    {
                        "title": "Department:",
                        "value": "{{user.department}}"
                    },
                    {
                        "title": "Date:",
                        "value": "{{date}}"
                    }
                ]
            },
            {
                "type": "TextBlock",
                "text": "{{message}}",
                "wrap": true
            }
        ]
    }
};

// Initialize TinyMCE
function initTinyMCE() {
    tinymce.init({
        selector: '#body_html_template',
        height: 400,
        menubar: false,
        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'help', 'wordcount'
        ],
        toolbar: 'undo redo | blocks | bold italic forecolor | alignleft aligncenter ' +
                'alignright alignjustify | bullist numlist outdent indent | ' +
                'removeformat | help | code',
        content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
        setup: function(editor) {
            editor.on('change', function() {
                editor.save();
            });
        }
    });
}

// Auto-generate slug from name (only if slug is empty)
document.getElementById('name').addEventListener('input', function() {
    const slugField = document.getElementById('slug');
    if (!slugField.value.trim() || !slugField.dataset.userModified) {
        slugField.value = this.value
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }
});

document.getElementById('slug').addEventListener('input', function() {
    this.dataset.userModified = true;
});

// Show/hide content sections based on type
document.getElementById('type').addEventListener('change', function() {
    const emailContent = document.getElementById('email-content');
    const teamsContent = document.getElementById('teams-content');
    
    emailContent.style.display = 'none';
    teamsContent.style.display = 'none';
    
    if (this.value === 'email' || this.value === 'both') {
        emailContent.style.display = 'block';
        if (!tinymce.get('body_html')) {
            initTinyMCE();
        }
    }
    
    if (this.value === 'teams' || this.value === 'both') {
        teamsContent.style.display = 'block';
    }
});

// Variable insertion
let activeField = null;

document.addEventListener('focusin', function(e) {
    if (e.target.matches('#subject, #body_text, #teams_card_template')) {
        activeField = e.target;
    }
});

if (typeof tinymce !== 'undefined') {
    tinymce.on('AddEditor', function(e) {
        e.editor.on('focus', function() {
            activeField = 'tinymce';
        });
    });
}

// document.querySelectorAll('.variable-badge').forEach(badge => {
//     badge.addEventListener('click', function() {
//         const variable = this.dataset.variable;
//         insertVariable(`@{{${variable}}}`);
//     });
// });

document.querySelectorAll('.variable-badge').forEach(badge => {
    badge.addEventListener('click', function() {
        const variable = this.dataset.variable;
        insertVariable('{{' + variable + '}}');
    });
});

document.getElementById('customVariable').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        document.getElementById('addCustomVariable').click();
    }
});

function insertVariable(variable) {
    if (!activeField) {
        alert('Please click on a text field first');
        return;
    }
    
    if (activeField === 'tinymce' && tinymce.get('body_html_template')) {
        // ใส่ครั้งเดียว
        tinymce.get('body_html_template').insertContent(variable);
        return; // หยุดทันที
    } 
    
    if (activeField && typeof activeField === 'object') {
        const start = activeField.selectionStart;
        const end = activeField.selectionEnd;
        const value = activeField.value;
        
        activeField.value = value.substring(0, start) + variable + value.substring(end);
        activeField.selectionStart = activeField.selectionEnd = start + variable.length;
        activeField.focus();
    }
}

function loadTeamsTemplate(template) {
    if (teamsTemplates[template]) {
        const confirmReplace = confirm('This will replace the current Teams card template. Continue?');
        if (confirmReplace) {
            document.getElementById('teams_card_template').value = 
                JSON.stringify(teamsTemplates[template], null, 2);
        }
    }
}

// Preview functionality
document.getElementById('previewBtn').addEventListener('click', function() {
    const formData = new FormData(document.getElementById('templateForm'));
    const previewData = {};
    
    for (let [key, value] of formData.entries()) {
        previewData[key] = value;
    }
    
    if (tinymce.get('body_html_template')) {
        previewData.body_html_template = tinymce.get('body_html_template').getContent();
    }
    
    showPreview(previewData);
});

function showPreview(data) {
    const modal = new bootstrap.Modal(document.getElementById('previewModal'));
    const previewContent = document.getElementById('previewContent');
    
    const sampleData = {
        'user.name': 'John Doe',
        'user.email': 'john.doe@company.com',
        'user.department': 'IT Department',
        'user.title': 'Software Developer',
        'date': new Date().toLocaleDateString(),
        'time': new Date().toLocaleTimeString(),
        'datetime': new Date().toLocaleString(),
        'title': 'Sample Notification',
        'message': 'This is a sample message for preview purposes.',
        'company': 'Your Company',
        'url': 'https://example.com'
    };
    
    function replaceVariables(content, data) {
        if (!content) return '';
        let result = content;
        for (let [key, value] of Object.entries(data)) {
            result = result.replace(new RegExp(`\\{\\{${key}\\}\\}`, 'g'), value);
        }
        return result;
    }
    
    let html = `
        <div class="row">
            <div class="col-12 mb-3">
                <h6>Subject/Title:</h6>
                <div class="bg-light p-2 rounded">${replaceVariables(data.subject || '', sampleData)}</div>
            </div>
    `;
    
    if (data.type === 'email' || data.type === 'both') {
        if (data.body_html) {
            html += `
                <div class="col-12 mb-3">
                    <h6>HTML Email Content:</h6>
                    <div class="border p-3 rounded" style="max-height: 300px; overflow-y: auto;">${replaceVariables(data.body_html, sampleData)}</div>
                </div>
            `;
        }
        
        if (data.body_text) {
            html += `
                <div class="col-12 mb-3">
                    <h6>Plain Text Email Content:</h6>
                    <div class="bg-light p-2 rounded" style="white-space: pre-wrap; max-height: 300px; overflow-y: auto;">${replaceVariables(data.body_text, sampleData)}</div>
                </div>
            `;
        }
    }
    
    if (data.type === 'teams' || data.type === 'both') {
        if (data.teams_card_template) {
            try {
                const cardTemplate = JSON.parse(data.teams_card_template);
                const processedCard = JSON.parse(replaceVariables(JSON.stringify(cardTemplate), sampleData));
                
                html += `
                    <div class="col-12 mb-3">
                        <h6>Teams Adaptive Card:</h6>
                        <div class="bg-light p-2 rounded" style="max-height: 300px; overflow-y: auto;">
                            <pre><code>${JSON.stringify(processedCard, null, 2)}</code></pre>
                        </div>
                    </div>
                `;
            } catch (e) {
                html += `
                    <div class="col-12 mb-3">
                        <h6>Teams Adaptive Card:</h6>
                        <div class="alert alert-warning">Invalid JSON format</div>
                    </div>
                `;
            }
        }
    }
    
    html += `
            <div class="col-12">
                <h6>Sample Data Used:</h6>
                <div class="small text-muted">
                    ${Object.entries(sampleData).map(([key, value]) => 
                        '<span class="badge bg-light text-dark me-1">{{' + key + '}} = "' + value + '"</span>'
                    ).join('')}
                </div>
            </div>
        </div>
    `;
    
    previewContent.innerHTML = html;
    modal.show();
}

// Form validation
document.getElementById('templateForm').addEventListener('submit', function(e) {
    if (tinymce.get('body_html_template')) {
        tinymce.get('body_html_template').save();
    }
    
    const teamsCardField = document.getElementById('teams_card_template');
    if (teamsCardField.value.trim()) {
        try {
            JSON.parse(teamsCardField.value);
        } catch (error) {
            e.preventDefault();
            alert('Invalid JSON format in Teams Card Template');
            teamsCardField.focus();
            return false;
        }
    }
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const typeField = document.getElementById('type');
    if (typeField.value) {
        typeField.dispatchEvent(new Event('change'));
    }
});