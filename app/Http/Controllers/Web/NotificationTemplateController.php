<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\NotificationTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotificationTemplateController extends Controller
{
    public function index(Request $request)
    {
        $query = NotificationTemplate::with(['creator', 'updater'])
            ->withCount('notifications');

        // Search
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%')
                  ->orWhere('slug', 'like', '%' . $request->search . '%');
            });
        }

        // Filters
        if ($request->filled('category')) {
            $query->byCategory($request->category);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } else {
                $query->where('is_active', false);
            }
        }

        // Sorting
        $sortBy = $request->get('sort', 'created_at');
        $direction = $request->get('direction', 'desc');
        $query->orderBy($sortBy, $direction);

        $templates = $query->paginate(15)->withQueryString();

        // Statistics
        $stats = [
            'total' => NotificationTemplate::count(),
            'active' => NotificationTemplate::where('is_active', true)->count(),
            'categories' => NotificationTemplate::distinct('category')->count(),
            'most_used' => NotificationTemplate::withCount('notifications')
                ->orderBy('notifications_count', 'desc')
                ->first()?->name ?? 'N/A'
        ];

        return view('templates.index', compact('templates', 'stats'));
    }

    public function create()
    {
        $categories = NotificationTemplate::getCategories();
        $availableVariables = NotificationTemplate::getAvailableVariables();
        
        return view('templates.create', compact('categories', 'availableVariables'));
    }

    public function store(Request $request)
    {
        $this->processAndTransformData($request);
        
        $request->validate([
            'name' => 'required|string|max:255|unique:notification_templates,name',
            'description' => 'nullable|string',
            'category' => 'required|string|in:' . implode(',', array_keys(NotificationTemplate::getCategories())),
            'subject_template' => 'required|string',
            'body_html_template' => 'nullable|string',
            'body_text_template' => 'nullable|string',
            'variables' => 'nullable|array',
            'default_variables' => 'nullable|array',
            'supported_channels' => 'required|array',
            'supported_channels.*' => 'in:email,teams,sms',
            'priority' => 'required|in:low,medium,normal,high,urgent',
            'is_active' => 'boolean'
        ]);

        $template = new NotificationTemplate($request->all());
        $template->created_by = Auth::id();
        $template->save();

        // Validate template syntax
        $errors = $template->validateTemplate();
        if (!empty($errors)) {
            return back()->withErrors(['template' => $errors])->withInput();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Template created successfully!',
                'template_id' => $template->id,
                'redirect' => route('templates.index')
            ]);
        }

        return redirect()->route('templates.index')
            ->with('success', 'Template created successfully!');
    }

    public function show(NotificationTemplate $template)
    {
        $template->load(['creator', 'updater', 'notifications']);
        
        // สร้างข้อมูลตัวอย่างสำหรับ preview
        $sampleData = $template->generateSampleData();
        
        // สร้าง preview
        $preview = $template->preview($sampleData);
        
        // ดึงตัวแปรที่ตรวจพบ
        $extractedVariables = $template->extractVariables();
        
        // ข้อมูลเพิ่มเติมสำหรับ view
        $defaultTestData = $sampleData;
        
        return view('templates.show', compact(
            'template', 
            'preview', 
            'extractedVariables',
            'defaultTestData'
        ));
    }

    public function edit(NotificationTemplate $template)
    {
        $categories = NotificationTemplate::getCategories();
        $availableVariables = NotificationTemplate::getAvailableVariables();
        $extractedVariables = $template->extractVariables();

        $processedVariables = $this->processTemplateVariables($template);
        
        return view('templates.edit', compact('template', 'categories', 'availableVariables', 'extractedVariables', 'processedVariables'));
    }

    private function processTemplateVariables(NotificationTemplate $template)
{
    $result = [
        'variables' => [],
        'default_variables' => [],
        'variables_json' => '{}',
        'has_variables' => false
    ];
    
    // ประมวลผล variables column
    if (!empty($template->variables)) {
        if (is_string($template->variables)) {
            $decoded = json_decode($template->variables, true);
            if (is_array($decoded)) {
                $result['variables'] = $decoded;
                $result['has_variables'] = true;
            }
        } elseif (is_array($template->variables)) {
            $result['variables'] = $template->variables;
            $result['has_variables'] = true;
        }
    }
    
    // ประมวลผล default_variables column
    if (!empty($template->default_variables)) {
        if (is_string($template->default_variables)) {
            $decoded = json_decode($template->default_variables, true);
            if (is_array($decoded)) {
                $result['default_variables'] = $decoded;
                $result['variables_json'] = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            } else {
                $result['variables_json'] = $template->default_variables;
            }
        } elseif (is_array($template->default_variables)) {
            $result['default_variables'] = $template->default_variables;
            $result['variables_json'] = json_encode($template->default_variables, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
    }
    
    // ถ้าไม่มี variables แต่มี default_variables ให้สร้าง variables จาก default_variables
    if (empty($result['variables']) && !empty($result['default_variables'])) {
        $index = 0;
        foreach ($result['default_variables'] as $varName => $varValue) {
            $result['variables'][] = [
                'name' => $varName,
                'default' => $varValue,
                'type' => 'text'
            ];
            $index++;
        }
        $result['has_variables'] = true;
    }
    
    return $result;
}

    public function updatex(Request $request, NotificationTemplate $template)
    {
        $this->processAndTransformData($request);
        $processedVariables = $this->processUpdateVariables($request);
        // dd($template,$request);

        $request->validate([
            'name' => 'required|string|max:255|unique:notification_templates,name,' . $template->id,
            'slug' => 'nullable|string|max:255|unique:notification_templates,slug,' . $template->id,
            'description' => 'nullable|string',
            'category' => 'required|string|in:' . implode(',', array_keys(NotificationTemplate::getCategories())),
            'subject_template' => 'required|string',
            'body_html_template' => 'nullable|string',
            'body_text_template' => 'nullable|string',
            'variables' => 'nullable|array',
            'default_variables' => 'nullable|array',
            'supported_channels' => 'required|array',
            'supported_channels.*' => 'in:email,teams,sms',
            'priority' => 'required|in:low,medium,normal,high,urgent',
            'is_active' => 'boolean'
        ]);

        // dd($request, $template);

        // Check if template content changed to increment version
        $contentChanged = $template->isDirty(['subject_template', 'body_html_template', 'body_text_template']);
        
        $template->fill($request->all());
        $template->updated_by = Auth::id();
        
        // Generate new slug if name changed
        if (empty($validated['slug'])) {
            // Generate from name if slug is empty
            $validated['slug'] = $template->generateUniqueSlug();
        } else {
            // If slug provided, ensure it's unique (additional check)
            $existingTemplate = NotificationTemplate::where('slug', $validated['slug'])
                ->where('id', '!=', $template->id)
                ->first();
                
            if ($existingTemplate) {
                // Generate unique slug by appending number
                $baseSlug = $validated['slug'];
                $counter = 1;
                do {
                    $validated['slug'] = $baseSlug . '-' . $counter;
                    $counter++;
                    $existingTemplate = NotificationTemplate::where('slug', $validated['slug'])
                        ->where('id', '!=', $template->id)
                        ->first();
                } while ($existingTemplate);
            }
        }
        
        $template->save();

        // Validate template syntax
        $errors = $template->validateTemplate();
        if (!empty($errors)) {
            return back()->withErrors(['template' => $errors])->withInput();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Template updated successfully!',
                'template_id' => $template->id,
                'redirect' => route('templates.index')
            ]);
        }

        return redirect()->route('templates.index')
            ->with('success', 'Template updated successfully!');
    }

    public function update(Request $request, NotificationTemplate $template)
{
    // Validation rules
    $rules = [
        'name' => 'required|string|max:255|unique:notification_templates,name,' . $template->id,
        'slug' => 'nullable|string|max:255|unique:notification_templates,slug,' . $template->id,
        'category' => 'required|in:system,marketing,operational,emergency',
        'priority' => 'required|in:low,medium,normal,high,urgent',
        'description' => 'nullable|string|max:1000',
        'subject_template' => 'required|string|max:500',
        'body_html_template' => 'nullable|string',
        'body_text_template' => 'nullable|string',
        'supported_channels' => 'required|array|min:1',
        'supported_channels.*' => 'in:email,teams,sms',
        'is_active' => 'boolean',
        'variables' => 'nullable|array',
        'variables.*.name' => 'required_with:variables|string|max:100',
        'variables.*.default' => 'nullable|string|max:500',
        'variables.*.type' => 'nullable|in:text,number,date,url,email',
        'default_variables_json' => 'nullable|string',
    ];

    $validated = $request->validate($rules);

    try {
        // ประมวลผลตัวแปร
        $processedVariables = $this->processUpdateVariables($request);
        
        // Debug log
        \Log::info('Template Update Debug', [
            'template_id' => $template->id,
            'request_variables' => $request->get('variables'),
            'request_default_variables_json' => $request->get('default_variables_json'),
            'processed_variables' => $processedVariables,
        ]);

        // เตรียมข้อมูลสำหรับอัปเดต
        $updateData = [
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?: Str::slug($validated['name']),
            'category' => $validated['category'],
            'priority' => $validated['priority'],
            'description' => $validated['description'],
            'subject_template' => $validated['subject_template'],
            'body_html_template' => $validated['body_html_template'],
            'body_text_template' => $validated['body_text_template'],
            'supported_channels' => json_encode($validated['supported_channels']),
            'is_active' => $request->has('is_active'),
            'variables' => $processedVariables['variables_json'],
            'default_variables' => $processedVariables['default_variables_json'],
        ];

        // อัปเดต template
        $template->update($updateData);

        // Log successful update
        \Log::info('Template updated successfully', [
            'template_id' => $template->id,
            'updated_by' => auth()->id(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'อัปเดตเทมเพลตเรียบร้อย',
                'template_id' => $template->id,
                'redirect' => route('templates.show', $template)
            ]);
        }

        return redirect()->route('templates.show', $template)
            ->with('success', 'Template updated successfully!');


    } catch (\Exception $e) {
        \Log::error('Template update failed', [
            'template_id' => $template->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการอัปเดตเทมเพลต: ' . $e->getMessage()
            ], 500);
        }

        return redirect()
            ->back()
            ->withInput()
            ->withErrors(['error' => 'เกิดข้อผิดพลาดในการอัปเดตเทมเพลต: ' . $e->getMessage()]);
    }
}

    private function processUpdateVariables(Request $request) 
{
    $result = [
        'variables_json' => null,
        'default_variables_json' => null
    ];

    // ประมวลผล required variables
    $variables = $request->get('variables', []);
    $processedVariables = [];
    
    if (is_array($variables)) {
        foreach ($variables as $variable) {
            if (isset($variable['name']) && !empty($variable['name'])) {
                $processedVariables[] = [
                    'name' => $variable['name'],
                    'default' => $variable['default'] ?? '',
                    'type' => $variable['type'] ?? 'text'
                ];
            }
        }
    }
    
    // บันทึก variables เป็น JSON ถ้ามีข้อมูล
    if (!empty($processedVariables)) {
        $result['variables_json'] = json_encode($processedVariables, JSON_UNESCAPED_UNICODE);
    }

    // ประมวลผล default variables JSON
    $defaultVarsJson = $request->get('default_variables_json');
    if (!empty($defaultVarsJson)) {
        try {
            $decoded = json_decode($defaultVarsJson, true);
            if (is_array($decoded) && !empty($decoded)) {
                $result['default_variables_json'] = json_encode($decoded, JSON_UNESCAPED_UNICODE);
            }
        } catch (\Exception $e) {
            \Log::warning('Invalid default variables JSON', [
                'json' => $defaultVarsJson,
                'error' => $e->getMessage()
            ]);
        }
    }

    // ถ้าไม่มี default_variables แต่มี variables ให้สร้าง default_variables จาก variables
    if (empty($result['default_variables_json']) && !empty($processedVariables)) {
        $defaultVars = [];
        foreach ($processedVariables as $variable) {
            $defaultVars[$variable['name']] = $variable['default'] ?: 'ตัวอย่าง ' . $variable['name'];
        }
        if (!empty($defaultVars)) {
            $result['default_variables_json'] = json_encode($defaultVars, JSON_UNESCAPED_UNICODE);
        }
    }

    // ถ้าไม่มีข้อมูลเลยให้สร้าง empty structures
    if (empty($result['variables_json'])) {
        $result['variables_json'] = json_encode([], JSON_UNESCAPED_UNICODE);
    }
    
    if (empty($result['default_variables_json'])) {
        $result['default_variables_json'] = json_encode(new \stdClass(), JSON_UNESCAPED_UNICODE);
    }

    return $result;
}

    /**
     * Process and transform request data to proper format for Laravel
     */
    private function processAndTransformData(Request $request)
    {
        // Transform variables from array format to associative array
        if ($request->has('variables') && is_array($request->variables)) {
            $variablesAssoc = [];
            
            foreach ($request->variables as $variable) {
                if (isset($variable['name']) && !empty($variable['name'])) {
                    // $variablesAssoc[$variable['name']] = [
                    //     'type' => $variable['type'] ?? 'text',
                    //     'default' => $variable['default'] ?? null,
                    //     'required' => true
                    // ];

                    $variablesAssoc[$variable['name']] = $variable['default'] ?? null;

                }
            }
            
            $request->merge(['variables' => $variablesAssoc]);
        } else {
            $request->merge(['variables' => []]);
        }

        // Ensure default_variables is array
        if ($request->has('default_variables')) {
            if (is_string($request->default_variables)) {
                try {
                    $defaultVars = json_decode($request->default_variables, true);
                    $request->merge(['default_variables' => $defaultVars ?? []]);
                } catch (\Exception $e) {
                    $request->merge(['default_variables' => []]);
                }
            } elseif (!is_array($request->default_variables)) {
                $request->merge(['default_variables' => []]);
            }
        } else {
            $request->merge(['default_variables' => []]);
        }

        // Ensure supported_channels is array
        if ($request->has('supported_channels')) {
            if (is_string($request->supported_channels)) {
                try {
                    $channels = json_decode($request->supported_channels, true);
                    $request->merge(['supported_channels' => $channels ?? []]);
                } catch (\Exception $e) {
                    $request->merge(['supported_channels' => []]);
                }
            } elseif (!is_array($request->supported_channels)) {
                $request->merge(['supported_channels' => []]);
            }
        } else {
            $request->merge(['supported_channels' => []]);
        }

        // Process is_active
        if (!$request->has('is_active')) {
            $request->merge(['is_active' => false]);
        } else {
            $request->merge(['is_active' => (bool) $request->is_active]);
        }
    }

    /**
     * Process JSON fields from request and convert them to arrays
     */
    private function processJsonFields(Request $request)
    {
        // Process variables field
        if ($request->has('variables')) {
            if (is_string($request->variables)) {
                $request->merge([
                    'variables' => json_decode($request->variables, true) ?? []
                ]);
            }
        } else {
            $request->merge(['variables' => []]);
        }

        // Process default_variables field
        if ($request->has('default_variables')) {
            if (is_string($request->default_variables)) {
                $request->merge([
                    'default_variables' => json_decode($request->default_variables, true) ?? []
                ]);
            }
        } else {
            $request->merge(['default_variables' => []]);
        }

        // Process supported_channels field
        if ($request->has('supported_channels')) {
            if (is_string($request->supported_channels)) {
                $request->merge([
                    'supported_channels' => json_decode($request->supported_channels, true) ?? []
                ]);
            }
        } else {
            $request->merge(['supported_channels' => []]);
        }

        // Process is_active field
        if (!$request->has('is_active')) {
            $request->merge(['is_active' => false]);
        }
    }


    public function destroy(NotificationTemplate $template)
    {
        // Check if template is being used
        if ($template->notifications()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete template that has been used in notifications'
            ]);
        }

        $template->delete();

        return response()->json([
            'success' => true,
            'message' => 'Template deleted successfully'
        ]);
    }

    public function preview(NotificationTemplate $template)
    {
        $template->load(['creator', 'updater', 'notifications']);
        
        // สร้างข้อมูลตัวอย่างสำหรับ preview
        $sampleData = $template->generateSampleData();
        
        // ดึงตัวแปรที่ตรวจพบ
        $detectedVariables = $template->extractVariables();
        
        // ข้อมูลเพิ่มเติมสำหรับ view
        $defaultTestData = $sampleData;
        
        return view('templates.preview', compact(
            'template',
            'detectedVariables', 
            'defaultTestData'
        ));
    }

    public function duplicate(NotificationTemplate $template)
    {
        $newTemplate = $template->replicate();
        $newTemplate->name = $template->name . ' (Copy)';
        $newTemplate->slug = null; // Will be generated in boot method
        $newTemplate->is_active = false;
        $newTemplate->created_by = Auth::id();
        $newTemplate->updated_by = null;
        $newTemplate->created_at = now();
        $newTemplate->updated_at = now();
        $newTemplate->save();

        return redirect()->route('templates.edit', $newTemplate)
            ->with('success', 'Template duplicated successfully!');
    }

    public function toggleStatus(NotificationTemplate $template)
    {
        $template->update([
            'is_active' => !$template->is_active,
            'updated_by' => Auth::id()
        ]);
        
        return response()->json([
            'success' => true,
            'status' => $template->is_active ? 'activated' : 'deactivated',
            'message' => 'Template ' . ($template->is_active ? 'activated' : 'deactivated') . ' successfully'
        ]);
    }

    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:activate,deactivate,delete,export',
            'template_ids' => 'required|array',
            'template_ids.*' => 'exists:notification_templates,id'
        ]);

        $templates = NotificationTemplate::whereIn('id', $request->template_ids);
        $affectedCount = 0;

        try {
            DB::beginTransaction();

            switch ($request->action) {
                case 'activate':
                    $affectedCount = $templates->update([
                        'is_active' => true,
                        'updated_by' => Auth::id()
                    ]);
                    break;
                    
                case 'deactivate':
                    $affectedCount = $templates->update([
                        'is_active' => false,
                        'updated_by' => Auth::id()
                    ]);
                    break;
                    
                case 'delete':
                    // Check if any template has notifications
                    $templatesWithNotifications = $templates->withCount('notifications')
                        ->get()
                        ->where('notifications_count', '>', 0);
                    
                    if ($templatesWithNotifications->count() > 0) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Cannot delete templates that have been used in notifications. ' . 
                                    $templatesWithNotifications->count() . ' template(s) are in use.'
                        ]);
                    }
                    
                    $affectedCount = $templates->count();
                    $templates->delete();
                    break;
                    
                case 'export':
                    return $this->exportTemplates($request->template_ids);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully {$request->action}d {$affectedCount} template(s)"
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Error executing bulk action: ' . $e->getMessage()
            ]);
        }
    }

    public function export(Request $request)
    {
        $format = $request->get('format', 'excel');
        $templateIds = $request->get('template_ids');
        
        return $this->exportTemplates($templateIds, $format);
    }

    private function exportTemplates($templateIds = null, $format = 'excel')
    {
        $query = NotificationTemplate::with(['creator', 'updater'])
            ->withCount('notifications');
            
        if ($templateIds) {
            $query->whereIn('id', $templateIds);
        }
        
        $templates = $query->get();

        switch ($format) {
            case 'pdf':
                // Implement PDF export using DOMPDF or similar
                $pdf = app('dompdf.wrapper');
                $pdf->loadView('templates.export.pdf', compact('templates'));
                return $pdf->download('notification-templates.pdf');
                
            case 'excel':
            default:
                // Implement Excel export using Laravel Excel
                return Excel::download(new TemplatesExport($templates), 'notification-templates.xlsx');
        }
    }

    public function validateSyntax(Request $request)
    {
        $request->validate([
            'subject_template' => 'required|string',
            'body_html_template' => 'nullable|string',
            'body_text_template' => 'nullable|string'
        ]);

        // Create temporary template for validation
        $tempTemplate = new NotificationTemplate([
            'subject_template' => $request->subject_template,
            'body_html_template' => $request->body_html_template,
            'body_text_template' => $request->body_text_template
        ]);

        $errors = $tempTemplate->validateTemplate();
        $extractedVars = $tempTemplate->extractVariables();

        return response()->json([
            'success' => empty($errors),
            'errors' => $errors,
            'extracted_variables' => $extractedVars,
            'message' => empty($errors) ? 'Template syntax is valid' : 'Template has validation errors'
        ]);
    }

    public function testRender(Request $request)
    {
        $request->validate([
            'template_id' => 'required|exists:notification_templates,id',
            'test_data' => 'nullable|array'
        ]);

        $template = NotificationTemplate::findOrFail($request->template_id);
        $testData = $request->get('test_data', []);
        
        try {
            $rendered = $template->render($testData);
            
            return response()->json([
                'success' => true,
                'rendered' => $rendered
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error rendering template: ' . $e->getMessage()
            ]);
        }
    }
}