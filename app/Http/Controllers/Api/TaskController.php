<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Task::with(['category', 'attachments']);

        // Apply filters
        if ($request->has('filter')) {
            switch ($request->filter) {
                case 'active':
                    $query->where('is_completed', false);
                    break;
                case 'completed':
                    $query->where('is_completed', true);
                    break;
            }
        }

        if ($request->has('category') && $request->category !== 'all') {
            $query->where('category_id', $request->category);
        }

        if ($request->has('due_date')) {
            switch ($request->due_date) {
                case 'overdue':
                    $query->where('due_date', '<', now())->where('is_completed', false);
                    break;
                case 'due_soon':
                    $query->where('due_date', '>', now())
                          ->where('due_date', '<=', now()->addHours(24))
                          ->where('is_completed', false);
                    break;
                case 'has_due_date':
                    $query->whereNotNull('due_date');
                    break;
            }
        }

        // Apply search
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $tasks = $query->orderBy('order')->orderBy('created_at')->get();

        return response()->json([
            'success' => true,
            'data' => $tasks,
            'meta' => [
                'total' => $tasks->count(),
                'completed' => $tasks->where('is_completed', true)->count(),
                'active' => $tasks->where('is_completed', false)->count()
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'due_date' => 'nullable|date',
        ]);

        $task = Task::create([
            'title' => $request->title,
            'description' => $request->description,
            'category_id' => $request->category_id,
            'due_date' => $request->due_date,
            'is_completed' => false,
            'order' => Task::max('order') + 1
        ]);

        $task->load(['category', 'attachments']);

        return response()->json([
            'success' => true,
            'message' => 'Task created successfully',
            'data' => $task
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Task $task): JsonResponse
    {
        $task->load(['category', 'attachments']);

        return response()->json([
            'success' => true,
            'data' => $task
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Task $task): JsonResponse
    {
        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'due_date' => 'nullable|date',
            'is_completed' => 'sometimes|boolean',
        ]);

        $task->update($request->only([
            'title', 'description', 'category_id', 'due_date', 'is_completed'
        ]));

        $task->load(['category', 'attachments']);

        return response()->json([
            'success' => true,
            'message' => 'Task updated successfully',
            'data' => $task
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Task $task): JsonResponse
    {
        // Delete associated attachments
        foreach ($task->attachments as $attachment) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($attachment->path);
        }

        $task->delete();

        return response()->json([
            'success' => true,
            'message' => 'Task deleted successfully'
        ]);
    }
}
