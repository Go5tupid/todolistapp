<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>To-Do List</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class'
        }
    </script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
        function themeManager() {
            return {
                isDark: false,
                searchQuery: '',
                tasks: @json($tasks),
                categories: @json($categories),
                selectedTasks: [],
                getWeeklyStats() {
                    const now = new Date();
                    const startOfWeek = new Date(now);
                    startOfWeek.setDate(now.getDate() - now.getDay());

                    const days = [];
                    let totalCompleted = 0;

                    for (let i = 0; i < 7; i++) {
                        const date = new Date(startOfWeek);
                        date.setDate(startOfWeek.getDate() + i);

                        const dayTasks = this.tasks.filter(task => {
                            if (!task.updated_at) return false;
                            const taskDate = new Date(task.updated_at);
                            return taskDate.toDateString() === date.toDateString() && task.is_completed;
                        });

                        const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                        days.push({
                            date: date.toLocaleDateString(),
                            label: dayNames[date.getDay()],
                            count: dayTasks.length
                        });

                        totalCompleted += dayTasks.length;
                    }

                    return { days, completed: totalCompleted };
                },
                initTheme() {
                    this.isDark = localStorage.getItem('theme') === 'dark' ||
                        (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches);
                    this.applyTheme();
                },
                toggleTheme() {
                    this.isDark = !this.isDark;
                    localStorage.setItem('theme', this.isDark ? 'dark' : 'light');
                    this.applyTheme();
                },
                applyTheme() {
                    if (this.isDark) {
                        document.documentElement.classList.add('dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                    }
                },
                get filteredTasks() {
                    if (!this.searchQuery) {
                        return this.tasks;
                    }
                    const query = this.searchQuery.toLowerCase();
                    return this.tasks.filter(task =>
                        task.title.toLowerCase().includes(query) ||
                        (task.description && task.description.toLowerCase().includes(query))
                    );
                },
                toggleTaskSelection(taskId) {
                    if (this.selectedTasks.includes(taskId)) {
                        this.selectedTasks = this.selectedTasks.filter(id => id !== taskId);
                    } else {
                        this.selectedTasks.push(taskId);
                    }
                },
                selectAllTasks() {
                    this.selectedTasks = this.filteredTasks.map(task => task.id);
                },
                deselectAllTasks() {
                    this.selectedTasks = [];
                },
                deleteSelectedTasks() {
                    if (this.selectedTasks.length === 0) return;

                    if (confirm(`Are you sure you want to delete ${this.selectedTasks.length} selected task(s)?`)) {
                        // Create form data for bulk delete
                        const formData = new FormData();
                        formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
                        formData.append('_method', 'DELETE');
                        this.selectedTasks.forEach(id => formData.append('task_ids[]', id));

                        fetch('/tasks/bulk-delete', {
                            method: 'POST',
                            body: formData
                        }).then(response => {
                            if (response.ok) {
                                // Remove deleted tasks from local array
                                this.tasks = this.tasks.filter(task => !this.selectedTasks.includes(task.id));
                                this.selectedTasks = [];
                                // Optional: Show success message
                            }
                        });
                    }
                }
            }
        }
    </script>
    <style>
        @keyframes fadeIn { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }
        .animate-fadeIn { animation:fadeIn 0.3s ease-out forwards; }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 min-h-screen font-sans transition-colors" x-data="themeManager()" x-init="initTheme()">
    <div class="max-w-2xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="flex justify-between items-start mb-8">
            <div class="text-center flex-1">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">My Tasks</h1>
                <p class="text-gray-600 dark:text-gray-400">Stay organized and get things done</p>
            </div>
            <!-- Dark Mode Toggle -->
            <button
                @click="toggleTheme()"
                class="p-2 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors"
                :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
            >
                <svg x-show="!isDark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                </svg>
                <svg x-show="isDark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
            </button>
        </div>

        <!-- Search Bar -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-6">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input
                    x-model="searchQuery"
                    type="text"
                    placeholder="Search tasks..."
                    class="w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                >
            </div>
        </div>

        <!-- Productivity Stats -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6 animate-fadeIn">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                Productivity Stats
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Total Tasks -->
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Tasks</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="tasks.length"></p>
                        </div>
                        <div class="p-2 bg-indigo-100 dark:bg-indigo-900 rounded-lg">
                            <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Completed Tasks -->
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Completed</p>
                            <p class="text-2xl font-bold text-green-600 dark:text-green-400" x-text="tasks.filter(t => t.is_completed).length"></p>
                        </div>
                        <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-2">
                        <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
                            <span>Progress</span>
                            <span x-text="tasks.length > 0 ? Math.round((tasks.filter(t => t.is_completed).length / tasks.length) * 100) : 0" x-text="tasks.length > 0 ? Math.round((tasks.filter(t => t.is_completed).length / tasks.length) * 100) : 0">%</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                            <div class="bg-green-500 h-2 rounded-full transition-all duration-300" :style="`width: ${tasks.length > 0 ? (tasks.filter(t => t.is_completed).length / tasks.length) * 100 : 0}%`"></div>
                        </div>
                    </div>
                </div>

                <!-- Completion Rate -->
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Completion Rate</p>
                            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400" x-text="tasks.length > 0 ? Math.round((tasks.filter(t => t.is_completed).length / tasks.length) * 100) : 0">%</p>
                        </div>
                        <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-2">
                        <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400">
                            <span>This Week</span>
                            <span x-text="getWeeklyStats().completed + ' completed'"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Weekly Activity -->
            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-600">
                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">This Week's Activity</h4>
                <div class="flex gap-1">
                    <template x-for="day in getWeeklyStats().days" :key="day.date">
                        <div class="flex-1">
                            <div class="text-center">
                                <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-sm h-8 flex items-end justify-center relative group" :class="day.count > 0 ? 'bg-green-200 dark:bg-green-800' : ''">
                                    <div class="absolute -top-8 left-1/2 transform -translate-x-1/2 bg-gray-800 dark:bg-gray-200 text-white dark:text-gray-800 text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                                        <span x-text="day.date"></span>: <span x-text="day.count"></span> tasks
                                    </div>
                                    <div class="w-full bg-green-500 rounded-sm transition-all duration-300" :style="`height: ${Math.min(day.count * 8, 32)}px`"></div>
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1" x-text="day.label"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-6 animate-fadeIn">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <input
                    type="text"
                    x-model="searchQuery"
                    placeholder="Search tasks..."
                    class="w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                >
                <button
                    x-show="searchQuery"
                    @click="searchQuery = ''"
                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Add Task Form -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6 animate-fadeIn">
            <form method="POST" action="{{ route('tasks.store') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div class="flex-1 space-y-3">
                    <div>
                        <input
                            type="text"
                            name="title"
                            placeholder="Add a new task..."
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors @error('title') border-red-300 dark:border-red-500 @enderror"
                            required
                        >
                        @error('title')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <select
                            name="category_id"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                        >
                            <option value="">Select a category (optional)</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" style="color: {{ $category->color }};">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <textarea
                            name="description"
                            placeholder="Add a description (optional)..."
                            rows="2"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors resize-none"
                        ></textarea>
                    </div>
                    <div>
                        <input
                            type="datetime-local"
                            name="due_date"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                        >
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Set a due date (optional)</p>
                    </div>
                    <div>
                        <input
                            type="file"
                            name="attachments[]"
                            multiple
                            accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png,.gif"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 dark:file:bg-indigo-900 dark:file:text-indigo-300 hover:file:bg-indigo-100 dark:hover:file:bg-indigo-800 transition-colors"
                        >
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Attach files (PDF, DOC, TXT, images - max 10MB each)</p>
                    </div>
                </div>
                <button
                    type="submit"
                    class="px-6 py-3 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-colors flex items-center gap-2"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Add Task
                </button>
            </form>
        </div>

        <!-- Filter Navigation -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-6">
            <div class="flex justify-center gap-1">
                <a
                    href="{{ route('tasks.index', ['filter' => 'all']) }}"
                    class="px-4 py-2 rounded-lg font-medium transition-colors {{ $filter === 'all' ? 'bg-indigo-600 text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                >
                    All
                </a>
                <a
                    href="{{ route('tasks.index', ['filter' => 'active']) }}"
                    class="px-4 py-2 rounded-lg font-medium transition-colors {{ $filter === 'active' ? 'bg-indigo-600 text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                >
                    Active
                </a>
                <a
                    href="{{ route('tasks.index', ['filter' => 'completed']) }}"
                    class="px-4 py-2 rounded-lg font-medium transition-colors {{ $filter === 'completed' ? 'bg-indigo-600 text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                >
                    Completed
                </a>
            </div>
        </div>

        <!-- Category Filter -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-6">
            <div class="flex flex-wrap gap-2">
                <a
                    href="{{ route('tasks.index', ['filter' => $filter, 'category' => 'all']) }}"
                    class="px-3 py-1 rounded-full text-sm font-medium transition-colors {{ $categoryFilter === 'all' ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}"
                >
                    All Categories
                </a>
                @foreach($categories as $category)
                    <a
                        href="{{ route('tasks.index', ['filter' => $filter, 'category' => $category->id]) }}"
                        class="px-3 py-1 rounded-full text-sm font-medium transition-colors {{ $categoryFilter == $category->id ? 'text-white' : 'text-gray-700 dark:text-gray-300 hover:bg-opacity-80' }}"
                        style="background-color: {{ $categoryFilter == $category->id ? $category->color : 'transparent' }}; border: 1px solid {{ $category->color }};"
                    >
                        {{ $category->name }}
                    </a>
                @endforeach
            </div>
        </div>

        <!-- Due Date Filter -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-6">
            <div class="flex flex-wrap gap-2">
                <a
                    href="{{ route('tasks.index', ['filter' => $filter, 'category' => $categoryFilter, 'due_date' => 'all']) }}"
                    class="px-3 py-1 rounded-full text-sm font-medium transition-colors {{ $dueDateFilter === 'all' ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}"
                >
                    All Dates
                </a>
                <a
                    href="{{ route('tasks.index', ['filter' => $filter, 'category' => $categoryFilter, 'due_date' => 'overdue']) }}"
                    class="px-3 py-1 rounded-full text-sm font-medium transition-colors {{ $dueDateFilter === 'overdue' ? 'bg-red-600 text-white' : 'bg-red-100 dark:bg-red-900/20 text-red-700 dark:text-red-300 hover:bg-red-200 dark:hover:bg-red-900/40' }}"
                >
                    Overdue
                </a>
                <a
                    href="{{ route('tasks.index', ['filter' => $filter, 'category' => $categoryFilter, 'due_date' => 'due_soon']) }}"
                    class="px-3 py-1 rounded-full text-sm font-medium transition-colors {{ $dueDateFilter === 'due_soon' ? 'bg-yellow-600 text-white' : 'bg-yellow-100 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-300 hover:bg-yellow-200 dark:hover:bg-yellow-900/40' }}"
                >
                    Due Soon
                </a>
                <a
                    href="{{ route('tasks.index', ['filter' => $filter, 'category' => $categoryFilter, 'due_date' => 'has_due_date']) }}"
                    class="px-3 py-1 rounded-full text-sm font-medium transition-colors {{ $dueDateFilter === 'has_due_date' ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}"
                >
                    Has Due Date
                </a>
            </div>
        </div>

        <!-- Bulk Actions Bar -->
        <div x-show="selectedTasks.length > 0" class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-xl p-4 mb-6 animate-fadeIn">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="text-sm font-medium text-indigo-800 dark:text-indigo-200">
                        <span x-text="selectedTasks.length"></span> task(s) selected
                    </span>
                    <button
                        @click="deselectAllTasks()"
                        class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-200 underline"
                    >
                        Deselect all
                    </button>
                </div>
                <div class="flex gap-2">
                    <button
                        @click="deleteSelectedTasks()"
                        class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-colors flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Delete Selected
                    </button>
                </div>
            </div>
        </div>

        <!-- Task List -->
        <div class="space-y-3">
            <template x-for="task in filteredTasks" :key="task.id">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 animate-fadeIn cursor-move hover:shadow-md transition-shadow" x-data="{ completed: task.is_completed }" :data-task-id="task.id">
                    <div class="flex items-center gap-4">
                        <!-- Drag Handle -->
                        <div class="cursor-grab active:cursor-grabbing text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                            </svg>
                        </div>

                        <!-- Selection Checkbox -->
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input
                                type="checkbox"
                                :checked="selectedTasks.includes(task.id)"
                                @change="toggleTaskSelection(task.id)"
                                class="w-4 h-4 text-indigo-600 bg-gray-100 border-gray-300 rounded focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                            >
                        </label>

                        <!-- Toggle Checkbox -->
                        <form :id="'toggle-form-' + task.id" method="POST" :action="'/tasks/' + task.id" class="hidden">
                            @csrf
                            @method('PATCH')
                        </form>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input
                                type="checkbox"
                                x-model="completed"
                                @change="document.getElementById('toggle-form-' + task.id).submit()"
                                class="sr-only peer"
                            >
                            <div class="w-5 h-5 bg-gray-200 dark:bg-gray-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded border-2 border-gray-300 dark:border-gray-500 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-indigo-600"></div>
                        </label>

                        <!-- Task Title -->
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <div
                                    class="text-gray-900 dark:text-white transition-all duration-300"
                                    x-bind:class="{ 'line-through text-gray-500 dark:text-gray-400': completed }"
                                    x-text="task.title"
                                ></div>
                                <div x-show="task.category" class="px-2 py-1 rounded-full text-xs font-medium text-white" x-bind:style="'background-color: ' + task.category.color" x-text="task.category.name"></div>
                            </div>
                            <div x-show="task.due_date" class="flex items-center gap-1 text-xs" x-bind:class="{
                                'text-red-600 dark:text-red-400': !task.is_completed && new Date(task.due_date) < new Date(),
                                'text-yellow-600 dark:text-yellow-400': !task.is_completed && new Date(task.due_date) > new Date() && (new Date(task.due_date) - new Date()) / (1000 * 60 * 60) <= 24,
                                'text-gray-500 dark:text-gray-400': task.is_completed || (new Date(task.due_date) > new Date() && (new Date(task.due_date) - new Date()) / (1000 * 60 * 60) > 24)
                            }">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span x-text="new Date(task.due_date).toLocaleDateString() + ' ' + new Date(task.due_date).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})"></span>
                                <span x-show="!task.is_completed && new Date(task.due_date) < new Date()" class="font-medium">(Overdue)</span>
                                <span x-show="!task.is_completed && new Date(task.due_date) > new Date() && (new Date(task.due_date) - new Date()) / (1000 * 60 * 60) <= 24" class="font-medium">(Due Soon)</span>
                            </div>
                            <div x-show="task.description" class="text-sm text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 rounded-md p-2 mt-2" x-text="task.description"></div>

                            <!-- Attachments -->
                            <div x-show="task.attachments && task.attachments.length > 0" class="mt-3 space-y-2">
                                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Attachments</div>
                                <div class="space-y-1">
                                    <template x-for="attachment in task.attachments" :key="attachment.id">
                                        <div class="flex items-center gap-2 p-2 bg-gray-50 dark:bg-gray-800 rounded-md">
                                            <div class="flex-shrink-0">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                                                </svg>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white truncate" x-text="attachment.original_filename"></div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400" x-text="attachment.size_for_humans"></div>
                                            </div>
                                            <div class="flex gap-1">
                                                <a
                                                    :href="'/storage/' + attachment.path"
                                                    target="_blank"
                                                    class="p-1 text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                                                    title="Download"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                    </svg>
                                                </a>
                                                <form method="POST" :action="'/attachments/' + attachment.id" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button
                                                        type="submit"
                                                        class="p-1 text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition-colors"
                                                        title="Delete attachment"
                                                        onclick="return confirm('Are you sure you want to delete this attachment?')"
                                                    >
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                        </svg>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Delete Button -->
                        <form method="POST" :action="'/tasks/' + task.id" class="inline">
                            @csrf
                            @method('DELETE')
                            <button
                                type="submit"
                                class="p-2 text-gray-400 dark:text-gray-500 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors group"
                                title="Delete task"
                                onclick="return confirm('Are you sure you want to delete this task?')"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </template>

            <!-- Empty State -->
            <div x-show="filteredTasks.length === 0" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
                <svg class="mx-auto h-16 w-16 text-gray-400 dark:text-gray-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">No tasks found</h3>
                <p class="text-gray-500 dark:text-gray-400" x-text="searchQuery ? 'No tasks match your search.' : 'Get started by adding your first task above.'"></p>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const taskList = document.querySelector('.space-y-3');

            if (taskList) {
                Sortable.create(taskList, {
                    handle: '.cursor-move',
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    dragClass: 'sortable-drag',
                    onEnd: function(evt) {
                        const taskElements = taskList.querySelectorAll('[data-task-id]');
                        const taskIds = Array.from(taskElements).map(el =>
                            parseInt(el.getAttribute('data-task-id'))
                        );

                        // Send AJAX request to update order
                        fetch('/tasks/update-order', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                task_ids: taskIds
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                console.log('Order updated successfully');
                            }
                        })
                        .catch(error => {
                            console.error('Error updating order:', error);
                        });
                    }
                });
            }
        });
    </script>

    <style>
        .sortable-ghost {
            opacity: 0.4;
            background: #f3f4f6;
        }
        .sortable-chosen {
            opacity: 1;
        }
        .sortable-drag {
            transform: rotate(5deg);
        }
    </style>
</body>
</html>