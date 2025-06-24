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
        $preview = $template->preview();
        $extractedVariables = $template->extractVariables();
        
        return view('templates.show', compact('template', 'preview', 'extractedVariables'));
    }

    public function edit(NotificationTemplate $template)
    {
        $categories = NotificationTemplate::getCategories();
        $availableVariables = NotificationTemplate::getAvailableVariables();
        $extractedVariables = $template->extractVariables();
        
        return view('templates.edit', compact('template', 'categories', 'availableVariables', 'extractedVariables'));
    }

    public function update(Request $request, NotificationTemplate $template)
    {
        $this->processAndTransformData($request);
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

    public function preview(NotificationTemplate $template, Request $request)
    {
        $sampleData = $request->get('sample_data', []);
        $preview = $template->preview($sampleData);
        
        return response()->json([
            'success' => true,
            'preview' => $preview,
            'html' => view('templates.preview', compact('preview', 'template'))->render()
        ]);
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