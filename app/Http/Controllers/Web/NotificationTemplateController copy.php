<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\NotificationTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule; 

class NotificationTemplateControllerx extends Controller
{
    public function index(Request $request)
    {
        $query = NotificationTemplate::with('creator');
        
        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->byType($request->type);
        }

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $templates = $query->latest()->paginate(10);

        return view('templates.index', compact('templates'));
    }

    public function create()
    {
        return view('templates.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:notification_templates,slug',
            'description' => 'nullable|string',
            'type' => 'required|in:email,teams,both',
            'subject' => 'required|string|max:255',
            'body_html' => 'nullable|string',
            'body_text' => 'nullable|string',
            'variables' => 'nullable|array',
            'teams_card_template' => 'nullable|json',
            'is_active' => 'boolean'
        ]);

        // Auto-generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        // Ensure slug is unique
        $originalSlug = $validated['slug'];
        $counter = 1;
        while (NotificationTemplate::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = $originalSlug . '-' . $counter;
            $counter++;
        }

        // Auto-extract variables from content
        $allContent = ($validated['subject'] ?? '') . ' ' . 
                     ($validated['body_html'] ?? '') . ' ' . 
                     ($validated['body_text'] ?? '');
        
        if ($request->filled('teams_card_template')) {
            $allContent .= ' ' . $validated['teams_card_template'];
        }

        preg_match_all('/\{\{([^}]+)\}\}/', $allContent, $matches);
        $extractedVars = array_unique($matches[1] ?? []);
        
        if (!empty($extractedVars)) {
            $validated['variables'] = array_merge($validated['variables'] ?? [], $extractedVars);
            $validated['variables'] = array_unique($validated['variables']);
        }

        // Decode Teams card template if provided
        if ($request->filled('teams_card_template')) {
            $validated['teams_card_template'] = json_decode($validated['teams_card_template'], true);
        }

        $validated['created_by'] = Auth::id();
        $validated['is_active'] = $request->boolean('is_active', true);

        $template = NotificationTemplate::create($validated);

        return redirect()->route('templates.show', $template)
                         ->with('success', 'Template created successfully!');
    }

    public function show(NotificationTemplate $template)
    {
        $template->load('creator');
        return view('templates.show', compact('template'));
    }

    public function edit(NotificationTemplate $template)
    {
        return view('templates.edit', compact('template'));
    }

    public function update(Request $request, NotificationTemplate $template)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('notification_templates', 'slug')->ignore($template->id)],
            'description' => 'nullable|string',
            'type' => 'required|in:email,teams,both',
            'subject' => 'required|string|max:255',
            'body_html' => 'nullable|string',
            'body_text' => 'nullable|string',
            'variables' => 'nullable|array',
            'teams_card_template' => 'nullable|json',
            'is_active' => 'boolean'
        ]);

        // Auto-generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        // Auto-extract variables from content
        $allContent = ($validated['subject'] ?? '') . ' ' . 
                     ($validated['body_html'] ?? '') . ' ' . 
                     ($validated['body_text'] ?? '');
        
        if ($request->filled('teams_card_template')) {
            $allContent .= ' ' . $validated['teams_card_template'];
        }

        preg_match_all('/\{\{([^}]+)\}\}/', $allContent, $matches);
        $extractedVars = array_unique($matches[1] ?? []);
        
        if (!empty($extractedVars)) {
            $validated['variables'] = array_merge($validated['variables'] ?? [], $extractedVars);
            $validated['variables'] = array_unique($validated['variables']);
        }

        // Decode Teams card template if provided
        if ($request->filled('teams_card_template')) {
            $validated['teams_card_template'] = json_decode($validated['teams_card_template'], true);
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        $template->update($validated);

        return redirect()->route('templates.show', $template)
                         ->with('success', 'Template updated successfully!');
    }

    public function destroy(NotificationTemplate $template)
    {
        // Check if template is being used
        if ($template->notifications()->exists()) {
            return back()->with('error', 'Cannot delete template that is being used by notifications.');
        }

        $template->delete();

        return redirect()->route('templates.index')
                         ->with('success', 'Template deleted successfully!');
    }

    public function toggle(NotificationTemplate $template)
    {
        $template->update(['is_active' => !$template->is_active]);
        
        $status = $template->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Template {$status} successfully!");
    }

    public function preview(Request $request, NotificationTemplate $template)
    {
        $sampleData = $request->input('data', []);
        
        // Provide sample data if none provided
        if (empty($sampleData)) {
            $sampleData = [
                'user.name' => 'John Doe',
                'user.email' => 'john.doe@company.com',
                'user.department' => 'IT Department',
                'date' => now()->format('Y-m-d'),
                'time' => now()->format('H:i'),
                'datetime' => now()->format('Y-m-d H:i:s'),
                'company' => 'Your Company',
                'title' => 'Sample Notification',
                'message' => 'This is a sample message for preview.',
            ];
        }

        $preview = [
            'subject' => $template->getProcessedSubject($sampleData),
            'body_html' => $template->getProcessedBodyHtml($sampleData),
            'body_text' => $template->getProcessedBodyText($sampleData),
            'teams_card' => $template->getProcessedTeamsCard($sampleData),
        ];

        return response()->json([
            'success' => true,
            'preview' => $preview,
            'sample_data' => $sampleData
        ]);
    }

    public function duplicate(NotificationTemplate $template)
    {
        $newTemplate = $template->replicate();
        $newTemplate->name = $template->name . ' (Copy)';
        $newTemplate->slug = Str::slug($newTemplate->name);
        $newTemplate->created_by = Auth::id();
        
        // Ensure unique slug
        $originalSlug = $newTemplate->slug;
        $counter = 1;
        while (NotificationTemplate::where('slug', $newTemplate->slug)->exists()) {
            $newTemplate->slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        $newTemplate->save();

        return redirect()->route('templates.edit', $newTemplate)
                         ->with('success', 'Template duplicated successfully!');
    }
}