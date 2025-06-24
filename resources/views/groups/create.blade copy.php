@extends('layouts.app')

@section('title', 'Create New Permission')

@push('styles')
<style>
.create-form-container {
    max-width: 800px;
    margin: 0 auto;
}

.form-section {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    margin-bottom: 2rem;
    overflow: hidden;
}

.form-section-header {
    background: linear-gradient(135deg, var(--primary-green) 0%, var(--light-green) 100%);
    color: white;
    padding: 1.5rem;
    margin: 0;
}

.form-section-body {
    padding: 2rem;
}

.category-selector {
    background: #f8f9fa;
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    padding: 1.5rem;
    text-align: center;
    transition: all 0.3s ease;
    cursor: pointer;
}

.category-selector:hover {
    border-color: var(--primary-green);
    background: rgba(101, 209, 181, 0.05);
}

.category-selector.selected {
    border-color: var(--primary-green);
    background: rgba(101, 209, 181, 0.1);
    border-style: solid;
}

.category-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-green), var(--light-green));
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    color: white;
    font-size: 1.5rem;
}

.permission-preview {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 1rem;
    margin-top: 1rem;
}

.role-assignment-card {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    transition: all 0.3s ease;
}

.role-assignment-card:hover {
    border-color: var(--primary-green);
    box-shadow: 0 4px 12px rgba(101, 209, 181, 0.15);
}

.role-assignment-card.selected {
    border-color: var(--primary-green);
    background: rgba(101, 209, 181, 0.05);
}

.role-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.role-admin { background: #dc3545; color: white; }
.role-manager { background: #28a745; color: white; }
.role-user { background: #6c757d; color: white; }
.role-api { background: #ffc107; color: #212529; }
.role-support { background: #17a2b8; color: white; }

.template-card {
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.template-card:hover {
    border-color: var(--primary-green);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(101, 209, 181, 0.15);
}

.template-card.selected {
    border-color: var(--primary-green);
    background: rgba(101, 209, 181, 0.05);
}

.step-indicator {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 2rem;
}

.step {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #e9ecef;
    color: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin: 0 1rem;
    position: relative;
}

.step.active {
    background: var(--primary-green);
    color: white;
}

.step.completed {
    background: var(--dark-green);
    color: white;
}

.step:not(:last-child)::after {
    content: '';
    position: absolute;
    left: 100%;
    top: 50%;
    width: 2rem;
    height: 2px;
    background: #e9ecef;
    transform: translateY(-50%);
}

.step.completed:not(:last-child)::after {
    background: var(--primary-green);
}

.form-help {
    background: #e3f2fd;
    border-left: 4px solid #2196f3;
    padding: 1rem;
    border-radius: 0 4px 4px 0;
    margin-bottom: 1rem;
}

.breadcrumb-item a {
    color: var(--primary-green);
    text-decoration: none;
}

.breadcrumb-item a:hover {
    color: var(--dark-green);
}
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('permissions.index') }}">Permissions</a></li>
            <li class="breadcrumb-item active">Create Permission</li>
        </ol>
    </nav>

    <div class="create-form-container">
        <!-- Header -->
        <div class="text-center mb-4">
            <h2><i class="fas fa-plus-circle text-success me-2"></i>Create New Permission</h2>
            <p class="text-muted">Define a new permission for your Smart Notification System</p>
        </div>

        <!-- Step Indicator -->
        <div class="step-indicator">
            <div class="step active" id="step1">1</div>
            <div class="step" id="step2">2</div>
            <div class="step" id="step3">3</div>
        </div>

        <form method="POST" action="{{ route('permissions.store') }}" id="permissionForm">
            @csrf

            <!-- Step 1: Choose Category -->
            <div class="form-section" id="section1">
                <div class="form-section-header">
                    <h4 class="mb-0"><i class="fas fa-folder-open me-2"></i>Step 1: Choose Category</h4>
                </div>
                <div class="form-section-body">
                    <div class="form-help">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Categories help organize permissions by functionality.</strong>
                        Choose an existing category or create a new one for your permission.
                    </div>

                    <div class="row">
                        <!-- Existing Categories -->
                        @if($categories->count() > 0)
                            @foreach($categories as $category)
                                @php
                                    $iconMap = [
                                        'user' => 'fas fa-users',
                                        'notification' => 'fas fa-bell',
                                        'group' => 'fas fa-layer-group',
                                        'api' => 'fas fa-key',
                                        'system' => 'fas fa-cogs',
                                        'report' => 'fas fa-chart-bar',
                                        'admin' => 'fas fa-shield-alt'
                                    ];
                                    $icon = $iconMap[$category] ?? 'fas fa-folder';
                                @endphp
                                <div class="col-md-3 col-sm-6 mb-3">
                                    <div class="category-selector" onclick="selectCategory('{{ $category }}')">
                                        <div class="category-icon">
                                            <i class="{{ $icon }}"></i>
                                        </div>
                                        <h6 class="mb-2">{{ ucfirst($category) }}</h6>
                                        <small class="text-muted">
                                            {{ \Spatie\Permission\Models\Permission::where('name', 'LIKE', $category . '-%')->count() }} permissions
                                        </small>
                                    </div>
                                </div>
                            @endforeach
                        @endif

                        <!-- Custom Category -->
                        <div class="col-md-3 col-sm-6 mb-3">
                            <div class="category-selector" onclick="selectCustomCategory()">
                                <div class="category-icon">
                                    <i class="fas fa-plus"></i>
                                </div>
                                <h6 class="mb-2">Custom</h6>
                                <small class="text-muted">Create new category</small>
                            </div>
                        </div>
                    </div>

                    <!-- Hidden Inputs -->
                    <input type="hidden" name="category" id="selectedCategory">
                    
                    <!-- Custom Category Input -->
                    <div id="customCategoryInput" style="display: none;" class="mt-3">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="customCategory" class="form-label">New Category Name</label>
                                <input type="text" class="form-control" id="customCategory" 
                                       placeholder="e.g., content, workflow, integration">
                                <small class="form-text text-muted">Use lowercase, single word preferred</small>
                            </div>
                        </div>
                    </div>

                    <div class="text-end mt-3">
                        <button type="button" class="btn btn-primary" onclick="nextStep(1)" disabled id="step1Next">
                            Next <i class="fas fa-arrow-right ms-1"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Step 2: Permission Details -->
            <div class="form-section" id="section2" style="display: none;">
                <div class="form-section-header">
                    <h4 class="mb-0"><i class="fas fa-edit me-2"></i>Step 2: Permission Details</h4>
                </div>
                <div class="form-section-body">
                    <div class="form-help">
                        <i class="fas fa-lightbulb me-2"></i>
                        <strong>Define the permission name and description.</strong>
                        The system will automatically combine your category with the permission name.
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="name" class="form-label">Permission Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                       id="name" name="name" value="{{ old('name') }}" required
                                       placeholder="e.g., view, create, edit, delete">
                                <small class="form-text text-muted">Use lowercase, hyphens for spaces</small>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="display_name" class="form-label">Display Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('display_name') is-invalid @enderror" 
                                       id="display_name" name="display_name" value="{{ old('display_name') }}" required
                                       placeholder="e.g., View Users, Create Notifications">
                                <small class="form-text text-muted">Human-readable name</small>
                                @error('display_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control @error('description') is-invalid @enderror" 
                                  id="description" name="description" rows="3"
                                  placeholder="Describe what this permission allows users to do...">{{ old('description') }}</textarea>
                        <small class="form-text text-muted">Optional but recommended for clarity</small>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Permission Preview -->
                    <div class="permission-preview">
                        <h6 class="mb-2"><i class="fas fa-eye me-2"></i>Permission Preview</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Final Permission Name:</strong>
                                <code id="finalPermissionName" class="text-primary">-</code>
                            </div>
                            <div class="col-md-6">
                                <strong>Display Name:</strong>
                                <span id="finalDisplayName" class="text-success">-</span>
                            </div>
                        </div>
                        <div class="mt-2">
                            <strong>Description:</strong>
                            <span id="finalDescription" class="text-muted">-</span>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-3">
                        <button type="button" class="btn btn-outline-secondary" onclick="prevStep(2)">
                            <i class="fas fa-arrow-left me-1"></i> Previous
                        </button>
                        <button type="button" class="btn btn-primary" onclick="nextStep(2)" id="step2Next">
                            Next <i class="fas fa-arrow-right ms-1"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Step 3: Role Assignment -->
            <div class="form-section" id="section3" style="display: none;">
                <div class="form-section-header">
                    <h4 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Step 3: Assign to Roles (Optional)</h4>
                </div>
                <div class="form-section-body">
                    <div class="form-help">
                        <i class="fas fa-users-cog me-2"></i>
                        <strong>Choose which roles should have this permission.</strong>
                        You can skip this step and assign roles later, or select multiple roles now.
                    </div>

                    @php
                        $allRoles = \Spatie\Permission\Models\Role::all()->groupBy(function($role) {
                            if (str_contains(strtolower($role->name), 'admin')) return 'admin';
                            if (str_contains(strtolower($role->name), 'manager')) return 'manager';
                            if (str_contains(strtolower($role->name), 'support')) return 'support';
                            if (str_contains(strtolower($role->name), 'api')) return 'api';
                            return 'user';
                        });
                    @endphp

                    @if($allRoles->count() > 0)
                        @foreach(['admin', 'manager', 'support', 'api', 'user'] as $roleType)
                            @if($allRoles->has($roleType))
                                <h6 class="mt-3 mb-3">
                                    <span class="role-badge role-{{ $roleType }} me-2">{{ $roleType }}</span>
                                    {{ ucfirst($roleType) }} Roles ({{ $allRoles[$roleType]->count() }})
                                </h6>
                                
                                <div class="row">
                                    @foreach($allRoles[$roleType] as $role)
                                        <div class="col-md-6 mb-3">
                                            <div class="role-assignment-card" onclick="toggleRole({{ $role->id }})">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="roles[]" 
                                                           value="{{ $role->name }}" id="role_{{ $role->id }}">
                                                    <label class="form-check-label w-100" for="role_{{ $role->id }}">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <div>
                                                                <h6 class="mb-1">{{ $role->display_name ?? ucfirst(str_replace('_', ' ', $role->name)) }}</h6>
                                                                <small class="text-muted">
                                                                    <code>{{ $role->name }}</code> • {{ $role->users->count() }} users
                                                                </small>
                                                                <div class="mt-1">
                                                                    <small class="text-info">
                                                                        {{ $role->permissions->count() }} permissions assigned
                                                                    </small>
                                                                </div>
                                                            </div>
                                                            <span class="role-badge role-{{ $roleType }}">{{ $roleType }}</span>
                                                        </div>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        @endforeach
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-shield-alt fa-3x text-muted mb-3"></i>
                            <h6 class="text-muted">No roles found</h6>
                            <p class="text-muted">Create roles first to assign permissions.</p>
                            <a href="{{ route('roles.create') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus me-1"></i> Create Role
                            </a>
                        </div>
                    @endif

                    <div class="d-flex justify-content-between mt-4">
                        <button type="button" class="btn btn-outline-secondary" onclick="prevStep(3)">
                            <i class="fas fa-arrow-left me-1"></i> Previous
                        </button>
                        <div>
                            <button type="button" class="btn btn-outline-primary me-2" onclick="skipRoleAssignment()">
                                Skip & Create Permission
                            </button>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-check me-1"></i> Create Permission
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <!-- Quick Templates -->
        <div class="form-section">
            <div class="form-section-header">
                <h4 class="mb-0"><i class="fas fa-magic me-2"></i>Quick Templates</h4>
            </div>
            <div class="form-section-body">
                <p class="text-muted mb-3">Use these common permission templates to speed up creation:</p>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="template-card" onclick="useTemplate('crud')">
                            <div class="text-center">
                                <i class="fas fa-database fa-2x text-primary mb-2"></i>
                                <h6>CRUD Operations</h6>
                                <small class="text-muted">View, Create, Edit, Delete</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <div class="template-card" onclick="useTemplate('admin')">
                            <div class="text-center">
                                <i class="fas fa-shield-alt fa-2x text-danger mb-2"></i>
                                <h6>Admin Functions</h6>
                                <small class="text-muted">Settings, Logs, Maintenance</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <div class="template-card" onclick="useTemplate('api')">
                            <div class="text-center">
                                <i class="fas fa-code fa-2x text-warning mb-2"></i>
                                <h6>API Access</h6>
                                <small class="text-muted">Read, Write, Delete API</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let currentStep = 1;
let selectedCategory = '';

// Step navigation
function nextStep(step) {
    if (validateStep(step)) {
        document.getElementById(`section${step}`).style.display = 'none';
        document.getElementById(`step${step}`).classList.remove('active');
        document.getElementById(`step${step}`).classList.add('completed');
        
        currentStep = step + 1;
        document.getElementById(`section${currentStep}`).style.display = 'block';
        document.getElementById(`step${currentStep}`).classList.add('active');
        
        if (step === 2) {
            updatePermissionPreview();
        }
    }
}

function prevStep(step) {
    document.getElementById(`section${step}`).style.display = 'none';
    document.getElementById(`step${step}`).classList.remove('active');
    
    currentStep = step - 1;
    document.getElementById(`section${currentStep}`).style.display = 'block';
    document.getElementById(`step${currentStep}`).classList.remove('completed');
    document.getElementById(`step${currentStep}`).classList.add('active');
}

function validateStep(step) {
    switch(step) {
        case 1:
            return selectedCategory !== '';
        case 2:
            const name = document.getElementById('name').value.trim();
            const displayName = document.getElementById('display_name').value.trim();
            return name !== '' && displayName !== '';
        default:
            return true;
    }
}

// Category selection
function selectCategory(category) {
    selectedCategory = category;
    document.getElementById('selectedCategory').value = category;
    document.getElementById('customCategoryInput').style.display = 'none';
    
    // Update UI
    document.querySelectorAll('.category-selector').forEach(el => {
        el.classList.remove('selected');
    });
    event.target.closest('.category-selector').classList.add('selected');
    
    // Enable next button
    document.getElementById('step1Next').disabled = false;
}

function selectCustomCategory() {
    selectedCategory = 'custom';
    document.getElementById('customCategoryInput').style.display = 'block';
    
    // Update UI
    document.querySelectorAll('.category-selector').forEach(el => {
        el.classList.remove('selected');
    });
    event.target.closest('.category-selector').classList.add('selected');
    
    // Enable next button
    document.getElementById('step1Next').disabled = false;
    
    // Listen for custom category input
    document.getElementById('customCategory').addEventListener('input', function() {
        selectedCategory = this.value.toLowerCase();
        document.getElementById('selectedCategory').value = selectedCategory;
    });
}

// Permission preview update
function updatePermissionPreview() {
    const category = selectedCategory === 'custom' ? 
        document.getElementById('customCategory').value.toLowerCase() : selectedCategory;
    const name = document.getElementById('name').value.toLowerCase();
    const displayName = document.getElementById('display_name').value;
    const description = document.getElementById('description').value;
    
    const finalName = category && name ? `${category}-${name}` : name;
    
    document.getElementById('finalPermissionName').textContent = finalName || '-';
    document.getElementById('finalDisplayName').textContent = displayName || '-';
    document.getElementById('finalDescription').textContent = description || '-';
}

// Auto-update preview on input
document.addEventListener('DOMContentLoaded', function() {
    ['name', 'display_name', 'description'].forEach(id => {
        document.getElementById(id).addEventListener('input', updatePermissionPreview);
    });
    
    // Auto-generate display name from name
    document.getElementById('name').addEventListener('input', function() {
        const displayNameField = document.getElementById('display_name');
        if (!displayNameField.value) {
            const words = this.value.split(/[-_\s]+/).map(word => 
                word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()
            );
            displayNameField.value = words.join(' ');
        }
        updatePermissionPreview();
    });
});

// Role selection
function toggleRole(roleId) {
    const checkbox = document.getElementById(`role_${roleId}`);
    const card = checkbox.closest('.role-assignment-card');
    
    checkbox.checked = !checkbox.checked;
    
    if (checkbox.checked) {
        card.classList.add('selected');
    } else {
        card.classList.remove('selected');
    }
}

// Skip role assignment
function skipRoleAssignment() {
    // Uncheck all roles
    document.querySelectorAll('input[name="roles[]"]').forEach(checkbox => {
        checkbox.checked = false;
    });
    
    // Submit form
    document.getElementById('permissionForm').submit();
}

// Template functions
function useTemplate(type) {
    const templates = {
        'crud': {
            category: 'content',
            permissions: [
                { name: 'view', display: 'View Content', desc: 'View and browse content items' },
                { name: 'create', display: 'Create Content', desc: 'Create new content items' },
                { name: 'edit', display: 'Edit Content', desc: 'Modify existing content items' },
                { name: 'delete', display: 'Delete Content', desc: 'Remove content items' }
            ]
        },
        'admin': {
            category: 'admin',
            permissions: [
                { name: 'settings', display: 'Manage Settings', desc: 'Access and modify system settings' },
                { name: 'logs', display: 'View Logs', desc: 'Access system and activity logs' },
                { name: 'maintenance', display: 'System Maintenance', desc: 'Perform system maintenance tasks' }
            ]
        },
        'api': {
            category: 'api',
            permissions: [
                { name: 'read', display: 'API Read Access', desc: 'Read data through API endpoints' },
                { name: 'write', display: 'API Write Access', desc: 'Create and modify data through API' },
                { name: 'delete', display: 'API Delete Access', desc: 'Delete data through API endpoints' }
            ]
        }
    };
    
    if (templates[type]) {
        const template = templates[type];
        
        // Set category
        selectedCategory = template.category;
        document.getElementById('selectedCategory').value = template.category;
        
        // Fill first permission
        const firstPerm = template.permissions[0];
        document.getElementById('name').value = firstPerm.name;
        document.getElementById('display_name').value = firstPerm.display;
        document.getElementById('description').value = firstPerm.desc;
        
        // Show alert about multiple permissions
        if (template.permissions.length > 1) {
            alert(`This template includes ${template.permissions.length} permissions. After creating this first one, you can use the bulk create feature to add the others.`);
        }
        
        // Go to step 2
        selectCategory(template.category);
        nextStep(1);
        updatePermissionPreview();
        
        // Highlight the template
        document.querySelectorAll('.template-card').forEach(card => card.classList.remove('selected'));
        event.target.closest('.template-card').classList.add('selected');
    }
}

// Form validation
document.getElementById('permissionForm').addEventListener('submit', function(e) {
    if (!validateStep(currentStep)) {
        e.preventDefault();
        alert('Please fill in all required fields before submitting.');
    }
});
</script>
@endpush