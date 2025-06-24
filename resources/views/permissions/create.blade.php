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
                                <small class="text-muted" id="typeDescription">เลือกประเภทกลุ่มเพื่อดูคำอธิบาย</small>
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
                                @foreach($ldapGroups as $group)
                                <option value="{{ $group }}" {{ old('criteria.ldap_group') === $group ? 'selected' : '' }}>
                                    {{ $group }}
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
                                            <option value="{{ $dept }}">{{ $dept }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="dynamic_title" class="form-label">ตำแหน่ง</label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="dynamic_title" 
                                               name="criteria[title]" 
                                               placeholder="ตำแหน่งที่ต้องการ">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Manual Members Selection -->
                <div id="manualMembersCard" class="card mt-4" style="display: none;">
                    <div class="card-header">
                        <h5 class="card-title mb-0">เลือกสมาชิก</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="userSearch" class="form-label">ค้นหาและเลือกผู้ใช้</label>
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

                        <!-- Selected Users -->
                        <div id="selectedUsers">
                            <h6>สมาชิกที่เลือก</h6>
                            <div id="selectedUsersList" class="border rounded p-3 min-height-100">
                                <p class="text-muted mb-0 text-center">ยังไม่มีสมาชิก</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Preview Card -->
                <div class="card">
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

                <!-- Help Card -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-info-circle text-info me-1"></i>
                            คำแนะนำ
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="accordion" id="helpAccordion">
                            <div class="accordion-item">
                                <h6 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" 
                                            data-bs-toggle="collapse" data-bs-target="#groupTypes">
                                        ประเภทกลุ่ม
                                    </button>
                                </h6>
                                <div id="groupTypes" class="accordion-collapse collapse" 
                                     data-bs-parent="#helpAccordion">
                                    <div class="accordion-body">
                                        <ul class="list-unstyled small">
                                            <li><strong>กำหนดเอง:</strong> เลือกสมาชิกเอง</li>
                                            <li><strong>แผนก:</strong> สมาชิกจากแผนกใน LDAP</li>
                                            <li><strong>ตำแหน่ง:</strong> สมาชิกตามตำแหน่งงาน</li>
                                            <li><strong>LDAP Group:</strong> สมาชิกจากกลุ่ม LDAP</li>
                                            <li><strong>Dynamic:</strong> สมาชิกตามเงื่อนไขที่กำหนด</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h6 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" 
                                            data-bs-toggle="collapse" data-bs-target="#syncInfo">
                                        การซิงค์ข้อมูล
                                    </button>
                                </h6>
                                <div id="syncInfo" class="accordion-collapse collapse" 
                                     data-bs-parent="#helpAccordion">
                                    <div class="accordion-body">
                                        <p class="small">กลุ่มที่ไม่ใช่แบบ "กำหนดเอง" จะซิงค์สมาชิกอัตโนมัติจาก LDAP ทุก 6 ชั่วโมง</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="card mt-4">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-1"></i>
                                สร้างกลุ่ม
                            </button>
                            <a href="{{ route('groups.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-x-lg me-1"></i>
                                ยกเลิก
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Hidden input for selected users -->
<div id="hiddenInputs"></div>
@endsection

@push('scripts')
<script>
let selectedUsers = [];

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
                ${description ? `<p class="text-muted small">${description}</p>` : ''}
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
let searchTimeout;

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
            const isSelected = selectedUsers.find(u => u.id === user.id);
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
    if (selectedUsers.find(u => u.id === userId)) {
        return;
    }

    selectedUsers.push({ id: userId, text: userText });
    updateSelectedUsersList();
    updateHiddenInputs();
    updatePreview();
    
    // Clear search
    document.getElementById('userSearch').value = '';
    document.getElementById('searchResults').style.display = 'none';
}

function removeUser(userId) {
    selectedUsers = selectedUsers.filter(u => u.id !== userId);
    updateSelectedUsersList();
    updateHiddenInputs();
    updatePreview();
}

function updateSelectedUsersList() {
    const container = document.getElementById('selectedUsersList');
    
    if (selectedUsers.length === 0) {
        container.innerHTML = '<p class="text-muted mb-0 text-center">ยังไม่มีสมาชิก</p>';
        return;
    }

    let html = '';
    selectedUsers.forEach(user => {
        html += `
            <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded mb-2">
                <span>${user.text}</span>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeUser(${user.id})">
                    <i class="bi bi-x"></i>
                </button>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

function updateHiddenInputs() {
    const container = document.getElementById('hiddenInputs');
    let html = '';
    
    selectedUsers.forEach(user => {
        html += `<input type="hidden" name="users[]" value="${user.id}">`;
    });
    
    container.innerHTML = html;
}

// Auto-search on input
document.addEventListener('DOMContentLoaded', function() {
    const userSearchInput = document.getElementById('userSearch');
    if (userSearchInput) {
        userSearchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(searchUsers, 300);
        });
    }

    // Add event listeners for preview updates
    ['name', 'description', 'type'].forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.addEventListener('input', updatePreview);
        }
    });

    // Initialize preview
    updatePreview();
});

// Hide search results when clicking outside
document.addEventListener('click', function(event) {
    const searchContainer = document.querySelector('#userSearch').closest('.mb-3');
    const searchResults = document.getElementById('searchResults');
    
    if (!searchContainer.contains(event.target)) {
        searchResults.style.display = 'none';
    }
});
</script>
@endpush