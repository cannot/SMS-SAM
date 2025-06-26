@extends('layouts.app')

@section('title', 'สร้างกลุ่มการแจ้งเตือนใหม่')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">
                <i class="bi bi-plus-circle text-primary me-2"></i>
                สร้างกลุ่มการแจ้งเตือนใหม่
            </h2>
            <p class="text-muted mb-0">กำหนดข้อมูลและสมาชิกของกลุ่มใหม่</p>
        </div>
        <a href="{{ route('groups.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>กลับ
        </a>
    </div>

    <form method="POST" action="{{ route('groups.store') }}" id="createGroupForm">
        @csrf
        <!-- Hidden inputs for selected users -->
        <div id="hiddenInputs"></div>
        <div class="row">
            <!-- Main Form -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">ข้อมูลพื้นฐาน</h5>
                    </div>
                    <div class="card-body">
                        <!-- Group Name -->
                        <div class="mb-3">
                            <label for="name" class="form-label">
                                ชื่อกลุ่ม <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name') }}" 
                                   required 
                                   placeholder="เช่น ทีมพัฒนา, แผนก IT, ผู้บริหาร">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Group Description -->
                        <div class="mb-3">
                            <label for="description" class="form-label">คำอธิบาย</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" 
                                      name="description" 
                                      rows="3" 
                                      placeholder="อธิบายวัตถุประสงค์หรือเงื่อนไขของกลุ่มนี้">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Group Type -->
                        <div class="mb-3">
                            <label for="type" class="form-label">
                                ประเภทกลุ่ม <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('type') is-invalid @enderror" 
                                    id="type" 
                                    name="type" 
                                    required 
                                    onchange="handleTypeChange()">
                                <option value="">-- เลือกประเภทกลุ่ม --</option>
                                <option value="manual" {{ old('type') === 'manual' ? 'selected' : '' }}>
                                    กำหนดเอง (Manual) - เลือกสมาชิกเอง
                                </option>
                                <option value="department" {{ old('type') === 'department' ? 'selected' : '' }}>
                                    แผนก (Department) - อิงจากแผนกใน LDAP
                                </option>
                                <option value="role" {{ old('type') === 'role' ? 'selected' : '' }}>
                                    ตำแหน่ง (Role) - อิงจากตำแหน่งงาน
                                </option>
                                <option value="ldap_group" {{ old('type') === 'ldap_group' ? 'selected' : '' }}>
                                    LDAP Group - อิงจากกลุ่มใน LDAP
                                </option>
                                <option value="dynamic" {{ old('type') === 'dynamic' ? 'selected' : '' }}>
                                    Dynamic - อิงจากเงื่อนไขที่กำหนด
                                </option>
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                <small class="text-muted" id="typeDescription"></small>
                            </div>
                        </div>

                        <!-- Department Selection (for department type) -->
                        <div id="departmentSection" class="mb-3" style="display: none;">
                            <label for="department" class="form-label">เลือกแผนก</label>
                            <select class="form-select" id="department" name="criteria[department]">
                                <option value="">-- เลือกแผนก --</option>
                                @foreach($departments as $dept)
                                <option value="{{ $dept }}" {{ old('criteria.department') === $dept ? 'selected' : '' }}>
                                    {{ $dept }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- LDAP Group Selection (for ldap_group type) -->
                        <div id="ldapGroupSection" class="mb-3" style="display: none;">
                            <label for="ldap_group" class="form-label">เลือก LDAP Group</label>
                            <select class="form-select" id="ldap_group" name="criteria[ldap_group]">
                                <option value="">-- เลือก LDAP Group --</option>
                                @foreach($ldapGroups as $ldapGroup)
                                <option value="{{ $ldapGroup }}" {{ old('criteria.ldap_group') === $ldapGroup ? 'selected' : '' }}>
                                    {{ $ldapGroup }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Role/Title Selection (for role type) -->
                        <div id="roleSection" class="mb-3" style="display: none;">
                            <label for="title" class="form-label">ตำแหน่งงาน</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="title" 
                                   name="criteria[title]" 
                                   value="{{ old('criteria.title') }}" 
                                   placeholder="เช่น Manager, Developer, Admin">
                            <div class="form-text">
                                <small class="text-muted">ระบบจะค้นหาผู้ใช้ที่มีตำแหน่งที่ตรงกับที่ระบุ</small>
                            </div>
                        </div>

                        <!-- Dynamic Criteria (for dynamic type) -->
                        <div id="dynamicSection" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">เงื่อนไขการกรอง</label>
                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="dynamic_department" class="form-label">แผนก</label>
                                        <select class="form-select" id="dynamic_department" name="criteria[department]">
                                            <option value="">-- ทุกแผนก --</option>
                                            @foreach($departments as $dept)
                                            <option value="{{ $dept }}" {{ old('criteria.department') === $dept ? 'selected' : '' }}>
                                                {{ $dept }}
                                            </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="dynamic_title" class="form-label">ตำแหน่ง</label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="dynamic_title" 
                                               name="criteria[title]" 
                                               value="{{ old('criteria.title') }}"
                                               placeholder="ตำแหน่งที่ต้องการ">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Manual Members Selection -->
                <div id="manualMembersCard" class="card mt-4" style="display: none;">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">เลือกสมาชิก</h5>
                        <span class="badge bg-primary" id="memberCount">0 คน</span>
                    </div>
                    <div class="card-body">
                        <!-- Search and Add Users -->
                        <div class="mb-3">
                            <label for="userSearch" class="form-label">ค้นหาและเพิ่มสมาชิก</label>
                            <div class="input-group">
                                <input type="text" 
                                       class="form-control" 
                                       id="userSearch" 
                                       placeholder="พิมพ์ชื่อหรืออีเมลเพื่อค้นหา...">
                                <button type="button" class="btn btn-outline-secondary" onclick="searchUsers()">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Search Results -->
                        <div id="searchResults" class="mb-3" style="display: none;">
                            <div class="border rounded p-2" style="max-height: 200px; overflow-y: auto;">
                                <!-- Results will be populated here -->
                            </div>
                        </div>

                        <!-- Selected Members -->
                        <div id="selectedMembers">
                            <h6>สมาชิกที่เลือก</h6>
                            <div id="selectedMembersList" class="border rounded p-3" style="min-height: 100px;">
                                <p class="text-muted mb-0 text-center">ยังไม่มีสมาชิกที่เลือก</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Preview Card -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">ตัวอย่างกลุ่ม</h5>
                    </div>
                    <div class="card-body">
                        <div id="groupPreview">
                            <div class="text-center text-muted py-4">
                                <i class="bi bi-people display-4 mb-3"></i>
                                <p>กรอกข้อมูลเพื่อดูตัวอย่าง</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-1"></i>
                                สร้างกลุ่ม
                            </button>
                            
                            <button type="button" class="btn btn-info" onclick="previewMembers()" id="previewBtn" style="display: none;">
                                <i class="bi bi-eye me-1"></i>
                                ดูตัวอย่างสมาชิก
                            </button>
                            
                            <a href="{{ route('groups.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-x-lg me-1"></i>
                                ยกเลิก
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Help -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-question-circle text-info me-1"></i>
                            ประเภทกลุ่ม
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="accordion" id="helpAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#manualHelp">
                                        <i class="bi bi-hand-index me-2"></i>กำหนดเอง (Manual)
                                    </button>
                                </h2>
                                <div id="manualHelp" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                                    <div class="accordion-body">
                                        <small>เลือกสมาชิกด้วยตนเอง สามารถเพิ่ม/ลบสมาชิกได้ตลอดเวลา</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#departmentHelp">
                                        <i class="bi bi-building me-2"></i>แผนก (Department)
                                    </button>
                                </h2>
                                <div id="departmentHelp" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                                    <div class="accordion-body">
                                        <small>ระบบจะเพิ่มสมาชิกจากแผนกที่เลือกโดยอัตโนมัติ</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#ldapHelp">
                                        <i class="bi bi-diagram-3 me-2"></i>LDAP Group
                                    </button>
                                </h2>
                                <div id="ldapHelp" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                                    <div class="accordion-body">
                                        <small>ระบบจะเพิ่มสมาชิกจากกลุ่ม LDAP ที่เลือกโดยอัตโนมัติ</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#dynamicHelp">
                                        <i class="bi bi-gear me-2"></i>Dynamic
                                    </button>
                                </h2>
                                <div id="dynamicHelp" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                                    <div class="accordion-body">
                                        <small>ระบบจะเพิ่มสมาชิกตามเงื่อนไขที่กำหนดโดยอัตโนมัติ</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Member Preview Modal -->
<div class="modal fade" id="memberPreviewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ตัวอย่างสมาชิกตามเงื่อนไข</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="memberPreviewContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let selectedUsers = [];

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners
    ['name', 'description', 'type'].forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.addEventListener('input', updatePreview);
        }
    });

    // User search
    // const userSearchInput = document.getElementById('userSearch');
    // if (userSearchInput) {
    //     let searchTimeout;
    //     userSearchInput.addEventListener('input', function() {
    //         clearTimeout(searchTimeout);
    //         searchTimeout = setTimeout(searchUsers, 300);
    //     });
    // }

    updatePreview();
});

function handleTypeChange() {
    const type = document.getElementById('type').value;
    const descriptions = {
        'manual': 'เลือกสมาชิกด้วยตนเอง สามารถเพิ่ม/ลบสมาชิกได้ตลอดเวลา',
        'department': 'ระบบจะเพิ่มสมาชิกทั้งหมดจากแผนกที่เลือกโดยอัตโนมัติ',
        'role': 'ระบบจะเพิ่มสมาชิกที่มีตำแหน่งตรงกับที่กำหนดโดยอัตโนมัติ',
        'ldap_group': 'ระบบจะเพิ่มสมาชิกจากกลุ่ม LDAP ที่เลือกโดยอัตโนมัติ',
        'dynamic': 'ระบบจะเพิ่มสมาชิกตามเงื่อนไขที่กำหนดโดยอัตโนมัติ'
    };

    // Update description
    const descElement = document.getElementById('typeDescription');
    descElement.textContent = descriptions[type] || 'เลือกประเภทกลุ่มเพื่อดูคำอธิบาย';

    // Show/hide sections
    document.getElementById('departmentSection').style.display = type === 'department' ? 'block' : 'none';
    document.getElementById('ldapGroupSection').style.display = type === 'ldap_group' ? 'block' : 'none';
    document.getElementById('roleSection').style.display = type === 'role' ? 'block' : 'none';
    document.getElementById('dynamicSection').style.display = type === 'dynamic' ? 'block' : 'none';
    document.getElementById('manualMembersCard').style.display = type === 'manual' ? 'block' : 'none';
    
    // Show/hide preview button
    const previewBtn = document.getElementById('previewBtn');
    previewBtn.style.display = (type && type !== 'manual') ? 'block' : 'none';

    updatePreview();
}

function updatePreview() {
    const name = document.getElementById('name').value;
    const type = document.getElementById('type').value;
    const description = document.getElementById('description').value;
    
    let previewHtml = '';
    
    if (name || type) {
        previewHtml = `
            <div class="text-center">
                <div class="mb-3">
                    <i class="bi bi-people-fill display-4 text-primary"></i>
                </div>
                <h5 class="mb-2">${name || 'ชื่อกลุ่ม'}</h5>
                ${type ? `<span class="badge bg-primary mb-2">${getTypeLabel(type)}</span>` : ''}
                ${description ? `<p class="text-muted small mt-2">${description}</p>` : ''}
                <hr>
                <div class="text-start">
                    <small class="text-muted">
                        <strong>สมาชิก:</strong> ${type === 'manual' ? selectedUsers.length + ' คน' : 'จะอัปเดตอัตโนมัติ'}
                    </small>
                </div>
            </div>
        `;
    } else {
        previewHtml = `
            <div class="text-center text-muted py-4">
                <i class="bi bi-people display-4 mb-3"></i>
                <p>กรอกข้อมูลเพื่อดูตัวอย่าง</p>
            </div>
        `;
    }
    
    document.getElementById('groupPreview').innerHTML = previewHtml;
}

function getTypeLabel(type) {
    const labels = {
        'manual': 'กำหนดเอง',
        'department': 'แผนก',
        'role': 'ตำแหน่ง',
        'ldap_group': 'LDAP Group',
        'dynamic': 'Dynamic'
    };
    return labels[type] || type;
}

// User search functionality
async function searchUsers() {
    const query = document.getElementById('userSearch').value.trim();
    if (query.length < 2) {
        document.getElementById('searchResults').style.display = 'none';
        return;
    }

    try {
        const response = await fetch(`/groups/users?q=${encodeURIComponent(query)}`);
        const users = await response.json();
        
        let resultsHtml = '';
        users.forEach(user => {
            const isSelected = selectedUsers.includes(user.id);
            if (!isSelected) {
                resultsHtml += `
                    <div class="d-flex justify-content-between align-items-center p-2 border-bottom user-item" 
                         style="cursor: pointer;" onclick="addUser(${user.id}, '${user.text}')">
                        <div>
                            <strong>${user.text}</strong>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-plus"></i>
                        </button>
                    </div>
                `;
            }
        });

        if (resultsHtml === '') {
            resultsHtml = '<div class="text-center text-muted p-3">ไม่พบผู้ใช้หรือเลือกหมดแล้ว</div>';
        }

        document.getElementById('searchResults').innerHTML = resultsHtml;
        document.getElementById('searchResults').style.display = 'block';
    } catch (error) {
        console.error('Search error:', error);
    }
}

function addUser(userId, userText) {
    if (selectedUsers.includes(userId)) {
        return;
    }

    selectedUsers.push(userId);
    
    // Add to display
    const membersList = document.getElementById('selectedMembersList');
    const newMemberHtml = `
        <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded mb-2 member-item" 
             data-user-id="${userId}">
            <div class="d-flex align-items-center">
                <div class="user-avatar me-2">
                    ${userText.substring(0, 2).toUpperCase()}
                </div>
                <div>
                    <strong>${userText}</strong>
                </div>
            </div>
            <button type="button" 
                    class="btn btn-sm btn-outline-danger" 
                    onclick="removeUser(${userId})">
                <i class="bi bi-x"></i>
            </button>
        </div>
    `;
    
    // Remove "no members" message if exists
    const noMembersMsg = membersList.querySelector('.text-muted');
    if (noMembersMsg && noMembersMsg.textContent.includes('ยังไม่มีสมาชิก')) {
        membersList.innerHTML = '';
    }
    
    membersList.insertAdjacentHTML('beforeend', newMemberHtml);
    
    updateMemberCount();
    updateHiddenInputs();
    updatePreview();
    
    // Clear search
    document.getElementById('userSearch').value = '';
    document.getElementById('searchResults').style.display = 'none';
}

function removeUser(userId) {
    selectedUsers = selectedUsers.filter(id => id !== userId);
    
    // Remove from display
    const memberItem = document.querySelector(`[data-user-id="${userId}"]`);
    if (memberItem) {
        memberItem.remove();
    }
    
    // Check if no members left
    const membersList = document.getElementById('selectedMembersList');
    if (membersList.children.length === 0) {
        membersList.innerHTML = '<p class="text-muted mb-0 text-center">ยังไม่มีสมาชิกที่เลือก</p>';
    }
    
    updateMemberCount();
    updateHiddenInputs();
    updatePreview();
}

function updateMemberCount() {
    const countBadge = document.getElementById('memberCount');
    if (countBadge) {
        countBadge.textContent = selectedUsers.length + ' คน';
    }
}

function updateHiddenInputs() {
    const container = document.getElementById('hiddenInputs');
    let html = '';
    
    selectedUsers.forEach(userId => {
        html += `<input type="hidden" name="users[]" value="${userId}">`;
    });
    
    container.innerHTML = html;
    
    // Debug: ตรวจสอบว่า hidden inputs ถูกสร้างแล้ว
    console.log('Updated hidden inputs:', {
        selectedUsers: selectedUsers,
        hiddenInputsHTML: html,
        containerContent: container.innerHTML
    });
}

async function previewMembers() {
    const type = document.getElementById('type').value;
    if (!type || type === 'manual') {
        return;
    }

    try {
        const formData = new FormData(document.getElementById('createGroupForm'));
        
        const response = await fetch('/groups/preview-members', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: formData
        });

        const result = await response.json();
        
        if (result.success) {
            let content = `<p class="mb-3">จำนวนสมาชิกที่พบ: <strong>${result.members.length} คน</strong></p>`;
            
            if (result.members.length > 0) {
                content += '<div class="table-responsive">';
                content += '<table class="table table-sm">';
                content += '<thead><tr><th>ชื่อ</th><th>อีเมล</th><th>แผนก</th><th>ตำแหน่ง</th></tr></thead>';
                content += '<tbody>';
                
                result.members.forEach(member => {
                    content += `
                        <tr>
                            <td>${member.display_name}</td>
                            <td>${member.email}</td>
                            <td>${member.department || '-'}</td>
                            <td>${member.title || '-'}</td>
                        </tr>
                    `;
                });
                
                content += '</tbody></table></div>';
            } else {
                content += '<div class="alert alert-info">ไม่พบสมาชิกที่ตรงกับเงื่อนไข</div>';
            }
            
            document.getElementById('memberPreviewContent').innerHTML = content;
            
            const modal = new bootstrap.Modal(document.getElementById('memberPreviewModal'));
            modal.show();
        } else {
            showNotification('ไม่สามารถดูตัวอย่างสมาชิกได้', 'error');
        }
    } catch (error) {
        console.error('Preview error:', error);
        showNotification('เกิดข้อผิดพลาดในการดูตัวอย่าง', 'error');
    }
}

// Form validation
document.getElementById('createGroupForm').addEventListener('submit', function(e) {
    const type = document.getElementById('type').value;
    const name = document.getElementById('name').value.trim();
    
    // Debug: ตรวจสอบข้อมูลก่อน submit
    console.log('Form submission data:', {
        type: type,
        name: name,
        selectedUsers: selectedUsers,
        formData: new FormData(this)
    });
    
    if (!name) {
        e.preventDefault();
        showNotification('กรุณากรอกชื่อกลุ่ม', 'warning');
        document.getElementById('name').focus();
        return;
    }
    
    if (!type) {
        e.preventDefault();
        showNotification('กรุณาเลือกประเภทกลุ่ม', 'warning');
        document.getElementById('type').focus();
        return;
    }
    
    if (type === 'manual' && selectedUsers.length === 0) {
        const proceed = confirm('กลุ่มนี้ยังไม่มีสมาชิก คุณต้องการสร้างต่อไปหรือไม่?');
        if (!proceed) {
            e.preventDefault();
            return;
        }
    }
    
    // Additional validation for specific types
    if (type === 'department') {
        const department = document.getElementById('department').value;
        if (!department) {
            e.preventDefault();
            showNotification('กรุณาเลือกแผนก', 'warning');
            document.getElementById('department').focus();
            return;
        }
    }
    
    if (type === 'ldap_group') {
        const ldapGroup = document.getElementById('ldap_group').value;
        if (!ldapGroup) {
            e.preventDefault();
            showNotification('กรุณาเลือก LDAP Group', 'warning');
            document.getElementById('ldap_group').focus();
            return;
        }
    }
    
    if (type === 'role') {
        const title = document.getElementById('title').value.trim();
        if (!title) {
            e.preventDefault();
            showNotification('กรุณาระบุตำแหน่งงาน', 'warning');
            document.getElementById('title').focus();
            return;
        }
    }
    
    // Show loading
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalContent = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="spinner-border spinner-border-sm me-1"></i>กำลังสร้าง...';
    submitBtn.disabled = true;
    
    // Re-enable on error (success will redirect)
    setTimeout(() => {
        submitBtn.innerHTML = originalContent;
        submitBtn.disabled = false;
    }, 10000);
});

// Hide search results when clicking outside
document.addEventListener('click', function(event) {
    const searchContainer = document.querySelector('#userSearch').closest('.mb-3');
    const searchResults = document.getElementById('searchResults');
    
    if (searchContainer && !searchContainer.contains(event.target)) {
        searchResults.style.display = 'none';
    }
});

// Notification function
function showNotification(message, type = 'info') {
    const alertClass = type === 'success' ? 'alert-success' : 
                      type === 'error' ? 'alert-danger' : 
                      type === 'warning' ? 'alert-warning' : 'alert-info';
    
    const notification = document.createElement('div');
    notification.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl+S = Save
    if (e.ctrlKey && e.key === 's') {
        e.preventDefault();
        document.getElementById('createGroupForm').submit();
    }
    
    // Escape = Cancel
    if (e.key === 'Escape') {
        window.location.href = '{{ route("groups.index") }}';
    }
});

// Auto-save draft (optional)
let autoSaveTimeout;
function autoSaveDraft() {
    clearTimeout(autoSaveTimeout);
    autoSaveTimeout = setTimeout(() => {
        const formData = new FormData(document.getElementById('createGroupForm'));
        localStorage.setItem('group_create_draft', JSON.stringify({
            name: formData.get('name'),
            description: formData.get('description'),
            type: formData.get('type'),
            timestamp: new Date().toISOString()
        }));
    }, 2000);
}

// Load draft on page load
function loadDraft() {
    const draft = localStorage.getItem('group_create_draft');
    if (draft) {
        try {
            const data = JSON.parse(draft);
            const loadDraftConfirm = confirm('พบร่างที่บันทึกไว้ คุณต้องการโหลดร่างนี้หรือไม่?');
            if (loadDraftConfirm) {
                document.getElementById('name').value = data.name || '';
                document.getElementById('description').value = data.description || '';
                document.getElementById('type').value = data.type || '';
                
                handleTypeChange();
                updatePreview();
            }
        } catch (e) {
            console.error('Failed to load draft:', e);
        }
    }
}

// Add auto-save listeners
['name', 'description', 'type'].forEach(id => {
    const element = document.getElementById(id);
    if (element) {
        element.addEventListener('input', autoSaveDraft);
        element.addEventListener('change', autoSaveDraft);
    }
});

// Clear draft on successful submit
window.addEventListener('beforeunload', function() {
    // Don't clear if form is being submitted
    if (!document.getElementById('createGroupForm').classList.contains('submitting')) {
        localStorage.removeItem('group_create_draft');
    }
});

// Load draft when page loads (uncomment if you want auto-save functionality)
// loadDraft();
</script>
@endpush

@push('styles')
<style>
.user-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(45deg, #007bff, #6c757d);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: bold;
}

.member-item {
    transition: all 0.3s ease;
}

.member-item:hover {
    background-color: rgba(0, 123, 255, 0.1) !important;
}

.user-item:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

#searchResults {
    max-height: 250px;
    overflow-y: auto;
}

#selectedMembersList {
    max-height: 300px;
    overflow-y: auto;
    min-height: 100px;
}

.form-control:focus,
.form-select:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.btn:focus {
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

/* Loading states */
.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Preview card styling */
#groupPreview {
    min-height: 200px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .member-item {
        flex-direction: column;
        align-items: flex-start !important;
        gap: 10px;
    }
    
    .member-item .btn {
        align-self: flex-end;
        margin-top: 5px;
    }
    
    .user-item {
        flex-direction: column;
        align-items: flex-start !important;
        text-align: left;
    }
}

/* Animation for adding/removing members */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(-10px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.member-item {
    animation: slideIn 0.3s ease;
}

/* Enhanced search results */
#searchResults .user-item {
    border-bottom: 1px solid #dee2e6;
    cursor: pointer;
    transition: background-color 0.2s ease;
}

#searchResults .user-item:last-child {
    border-bottom: none;
}

#searchResults .user-item:hover {
    background-color: #f8f9fa;
}

/* Form validation styling */
.is-invalid {
    border-color: #dc3545;
}

.invalid-feedback {
    display: block;
    width: 100%;
    margin-top: 0.25rem;
    font-size: 0.875em;
    color: #dc3545;
}

/* Alert styling */
.alert {
    border: none;
    border-radius: 0.5rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

/* Card enhancements */
.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
}

.card-header {
    background-color: rgba(0, 0, 0, 0.03);
    border-bottom: 1px solid rgba(0, 0, 0, 0.125);
}

/* Preview modal styling */
.modal-content {
    border: none;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.modal-header {
    border-bottom: 1px solid #dee2e6;
    background-color: #f8f9fa;
}

/* Badge enhancements */
.badge {
    font-size: 0.75em;
    padding: 0.35em 0.65em;
}

/* Accordion enhancements */
.accordion-button:not(.collapsed) {
    background-color: rgba(13, 110, 253, 0.1);
    border-color: rgba(13, 110, 253, 0.25);
}

.accordion-button:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

/* Scrollbar styling for webkit browsers */
#selectedMembersList::-webkit-scrollbar,
#searchResults::-webkit-scrollbar {
    width: 6px;
}

#selectedMembersList::-webkit-scrollbar-track,
#searchResults::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

#selectedMembersList::-webkit-scrollbar-thumb,
#searchResults::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

#selectedMembersList::-webkit-scrollbar-thumb:hover,
#searchResults::-webkit-scrollbar-thumb:hover {
    background: #a1a1a1;
}

/* Input group enhancements */
.input-group .form-control:focus {
    border-color: #86b7fe;
    box-shadow: none;
}

.input-group .btn-outline-secondary:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

/* Enhanced button states */
.btn-primary:hover {
    background-color: #0b5ed7;
    border-color: #0a58ca;
}

.btn-outline-secondary:hover {
    background-color: #6c757d;
    border-color: #6c757d;
    color: #fff;
}

/* Form text enhancements */
.form-text {
    margin-top: 0.25rem;
    font-size: 0.875em;
    color: #6c757d;
}

/* Type description styling */
#typeDescription {
    font-style: italic;
    font-weight: 500;
}

/* Preview enhancements */
#groupPreview .badge {
    font-size: 0.7em;
    margin: 2px;
}

/* Loading spinner in search results */
.spinner-border-sm {
    width: 1rem;
    height: 1rem;
}

/* Enhanced form sections */
.card-header h5 {
    color: #495057;
    font-weight: 600;
}

/* Member count badge */
#memberCount {
    background-color: #0d6efd !important;
    color: white !important;
}
</style>
@endpush