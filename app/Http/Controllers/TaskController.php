<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Task;
use App\Models\Category;
use App\Models\Attachment;
use Illuminate\Support\Facades\Storage;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filter = $request->get('filter', 'all');
        $categoryFilter = $request->get('category', 'all');
        $dueDateFilter = $request->get('due_date', 'all');

        $query = Task::with('category');

        // Apply status filter
        if ($filter === 'active') {
            $query->where('is_completed', false);
        } elseif ($filter === 'completed') {
            $query->where('is_completed', true);
        }

        // Apply category filter
        if ($categoryFilter !== 'all' && is_numeric($categoryFilter)) {
            $query->where('category_id', $categoryFilter);
        }

        // Apply due date filter
        if ($dueDateFilter === 'overdue') {
            $query->where('due_date', '<', now())->where('is_completed', false);
        } elseif ($dueDateFilter === 'due_soon') {
            $query->where('due_date', '>', now())
                  ->where('due_date', '<=', now()->addHours(24))
                  ->where('is_completed', false);
        } elseif ($dueDateFilter === 'has_due_date') {
            $query->whereNotNull('due_date');
        }

        $tasks = $query->with(['category', 'attachments'])->orderBy('order')->orderBy('created_at')->get();
        $categories = Category::all();

        return view('tasks.index', compact('tasks', 'filter', 'categories', 'categoryFilter', 'dueDateFilter'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240|mimes:pdf,doc,docx,txt,jpg,jpeg,png,gif'
        ]);

        $task = Task::create([
            'title' => $request->title,
            'description' => $request->description,
            'category_id' => $request->category_id,
            'due_date' => $request->due_date,
            'is_completed' => false,
            'order' => Task::max('order') + 1
        ]);

        // Handle file uploads
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('attachments', $filename, 'public');

                Attachment::create([
                    'task_id' => $task->id,
                    'filename' => $filename,
                    'original_filename' => $file->getClientOriginalName(),
                    'path' => $path,
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType()
                ]);
            }
        }

        return redirect('/tasks');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Task $task)
    {
        $task->is_completed = !$task->is_completed;
        $task->save();

        return redirect('/tasks');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Task $task)
    {
        $task->delete();

        return redirect('/tasks');
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'task_ids' => 'required|array',
            'task_ids.*' => 'required|integer|exists:tasks,id'
        ]);

        Task::whereIn('id', $request->task_ids)->delete();

        return response()->json(['success' => true, 'deleted_count' => count($request->task_ids)]);
    }

    public function updateOrder(Request $request)
    {
        $request->validate([
            'task_ids' => 'required|array',
            'task_ids.*' => 'required|integer|exists:tasks,id'
        ]);

        foreach ($request->task_ids as $index => $taskId) {
            Task::where('id', $taskId)->update(['order' => $index]);
        }

        return response()->json(['success' => true]);
    }

    public function deleteAttachment(Attachment $attachment)
    {
        // Delete the file from storage
        Storage::disk('public')->delete($attachment->path);

        // Delete the attachment record
        $attachment->delete();

        return redirect()->back()->with('success', 'Attachment deleted successfully');
    }
}
