<x-app-layout>
       <x-slot name="header">
        <div class="overflow-hidden rounded-[1.8rem] border border-blue-100 bg-gradient-to-br from-white via-blue-50 to-sky-100 shadow-md">
            <div class="relative px-8 py-6">
                <div class="absolute -right-20 -top-20 h-60 w-60 rounded-full bg-blue-300/20 blur-3xl"></div>

                <div class="relative flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <div class="flex items-center gap-3">
                            <span class="h-3 w-3 rounded-full bg-blue-500"></span>

                            <p class="text-xs font-bold uppercase tracking-[0.28em] text-blue-600">
                                Admin Workspace
                            </p>
                        </div>

                        <h2 class="mt-3 text-4xl font-extrabold tracking-tight text-slate-950">
                            Ticket Categories
                        </h2>

                        <p class="mt-2 max-w-3xl text-base text-slate-600">
                            Create, organize, and manage the categories used across the help desk workflow.
                        </p>
                    </div>

                    <div class="inline-flex w-fit items-center gap-2 rounded-2xl bg-emerald-50 px-5 py-3 text-sm font-bold text-emerald-700 shadow-sm">
                        <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                        {{ $activeCount }} Active
                    </div>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">

        @if (session('status'))
            <x-auto-dismiss-alert :message="session('status')" />
        @endif

        @if ($errors->any())
            <x-auto-dismiss-alert type="error" :message="$errors->first()" />
        @endif

        {{-- Create Category --}}
        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <form method="POST" action="{{ route('admin.categories.store') }}">
                @csrf

                <div class="flex flex-col gap-4 border-b border-slate-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-50 text-lg">
                            🏷️
                        </div>

                        <div>
                            <h3 class="text-lg font-extrabold text-slate-950">New Category</h3>
                            <p class="text-sm text-slate-500">Add a routing label for ticket intake.</p>
                        </div>
                    </div>

                    <button type="submit"
                        class="inline-flex w-fit items-center justify-center rounded-2xl bg-blue-900 px-5 py-3 text-xs font-bold uppercase tracking-[0.18em] text-white transition hover:bg-blue-800">
                        Create Category
                    </button>
                </div>

                <div class="grid gap-4 px-6 py-5 lg:grid-cols-12">
                    <div class="lg:col-span-4">
                        <label for="name" class="block text-xs font-bold text-slate-700">Category Name</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}"
                            placeholder="Example: Network" required
                            class="mt-2 block h-12 w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                    </div>

                    <div class="lg:col-span-2">
                        <label for="sort_order" class="block text-xs font-bold text-slate-700">Sort Order</label>
                        <input id="sort_order" name="sort_order" type="number" min="0"
                            value="{{ old('sort_order', 0) }}" required
                            class="mt-2 block h-12 w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                    </div>

                    <div class="lg:col-span-2">
                        <label for="is_active" class="block text-xs font-bold text-slate-700">Status</label>
                        <select id="is_active" name="is_active"
                            class="mt-2 block h-12 w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="1" @selected(old('is_active', '1') === '1')>Active</option>
                            <option value="0" @selected(old('is_active') === '0')>Inactive</option>
                        </select>
                    </div>

                    <div class="lg:col-span-4">
                        <label for="description" class="block text-xs font-bold text-slate-700">Description</label>
                        <input id="description" name="description" type="text" value="{{ old('description') }}"
                            placeholder="Short description..."
                            class="mt-2 block h-12 w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                    </div>
                </div>
            </form>
        </section>

        {{-- All Categories --}}
        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-slate-200 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-2xl font-extrabold text-slate-950">All Categories</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Manage category names, descriptions, order, status, and usage.
                    </p>
                </div>

                <span class="w-fit rounded-full bg-blue-50 px-4 py-2 text-xs font-bold uppercase tracking-[0.18em] text-blue-700">
                    {{ $categories->count() }} Total
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.22em] text-slate-500">Category</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.22em] text-slate-500">Order</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.22em] text-slate-500">Status</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-[0.22em] text-slate-500">Usage</th>
                            <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-[0.22em] text-slate-500">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($categories as $category)
                            <tr class="transition hover:bg-blue-50/40">
                                <td class="px-6 py-5">
                                    <div class="flex items-center gap-4">
                                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-900 text-lg text-white">
                                            🏷️
                                        </div>

                                        <div>
                                            <h4 class="font-extrabold text-slate-950">
                                                {{ $category->name }}
                                            </h4>

                                            <p class="mt-1 max-w-md text-sm text-slate-500">
                                                {{ $category->description ?: 'No description available.' }}
                                            </p>

                                            <p class="mt-1 text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">
                                                ID #{{ $category->id }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-5">
                                    <span class="rounded-full bg-slate-100 px-3 py-1.5 text-sm font-bold text-slate-700">
                                        {{ $category->sort_order }}
                                    </span>
                                </td>

                                <td class="px-6 py-5">
                                    @if ($category->is_active)
                                        <span class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1.5 text-sm font-bold text-emerald-700">
                                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1.5 text-sm font-bold text-slate-600">
                                            <span class="h-2 w-2 rounded-full bg-slate-400"></span>
                                            Inactive
                                        </span>
                                    @endif
                                </td>

                                <td class="px-6 py-5">
                                    <div class="flex items-end gap-2">
                                        <span class="text-2xl font-extrabold text-slate-950">
                                            {{ $category->tickets_count }}
                                        </span>
                                        <span class="pb-1 text-sm text-slate-500">tickets</span>
                                    </div>
                                </td>

                                <td class="px-6 py-5">
                                    <div class="flex justify-end gap-2">
                                        <button type="button"
                                            onclick="document.getElementById('edit-category-{{ $category->id }}').classList.toggle('hidden')"
                                            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 transition hover:bg-slate-100">
                                            Edit
                                        </button>

                                        <button type="button"
                                            onclick="document.getElementById('delete-category-modal-{{ $category->id }}').classList.remove('hidden'); document.getElementById('delete-category-modal-{{ $category->id }}').classList.add('flex')"
                                            class="rounded-xl border border-rose-200 bg-white px-4 py-2 text-sm font-bold text-rose-600 transition hover:bg-rose-50">
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            {{-- Edit Row --}}
                            <tr id="edit-category-{{ $category->id }}" class="hidden bg-slate-50">
                                <td colspan="5" class="px-6 py-6">
                                    <form method="POST" action="{{ route('admin.categories.update', $category) }}">
                                        @csrf
                                        @method('PATCH')

                                        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                                            <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

    <div>
        <h4 class="text-lg font-extrabold text-slate-950">
            Edit {{ $category->name }}
        </h4>

        <p class="text-sm text-slate-500">
            Update category details and visibility.
        </p>
    </div>

    <div class="flex items-center gap-3">

        <button
            type="button"
            onclick="document.getElementById('edit-category-{{ $category->id }}').classList.add('hidden')"
            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-600 transition hover:bg-slate-50">
            Close
        </button>

        <button
            type="submit"
            class="rounded-xl bg-blue-900 px-5 py-2 text-sm font-bold text-white transition hover:bg-blue-800">
            Save Changes
        </button>

    </div>

</div>

                                            <div class="grid gap-4 lg:grid-cols-12">
                                                <div class="lg:col-span-3">
                                                    <label for="name_{{ $category->id }}" class="block text-xs font-bold text-slate-700">
                                                        Category Name
                                                    </label>

                                                    <input id="name_{{ $category->id }}" name="name" type="text"
                                                        value="{{ $category->name }}" required
                                                        class="mt-2 block h-12 w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                                                </div>

                                                <div class="lg:col-span-4">
                                                    <label for="description_{{ $category->id }}" class="block text-xs font-bold text-slate-700">
                                                        Description
                                                    </label>

                                                    <input id="description_{{ $category->id }}" name="description" type="text"
                                                        value="{{ $category->description }}" placeholder="No description"
                                                        class="mt-2 block h-12 w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                                                </div>

                                                <div class="lg:col-span-2">
                                                    <label for="sort_order_{{ $category->id }}" class="block text-xs font-bold text-slate-700">
                                                        Order
                                                    </label>

                                                    <input id="sort_order_{{ $category->id }}" name="sort_order" type="number"
                                                        min="0" value="{{ $category->sort_order }}" required
                                                        class="mt-2 block h-12 w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                                                </div>

                                                <div class="lg:col-span-3">
                                                    <label for="is_active_{{ $category->id }}" class="block text-xs font-bold text-slate-700">
                                                        Status
                                                    </label>

                                                    <select id="is_active_{{ $category->id }}" name="is_active"
                                                        class="mt-2 block h-12 w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                        <option value="1" @selected($category->is_active)>Active</option>
                                                        <option value="0" @selected(! $category->is_active)>Inactive</option>
                                                    </select>
                                                </div>
                                            </div>

                                            
                                        </div>
                                    </form>
                                </td>
                            </tr>

{{-- Delete Modal --}}
<div id="delete-category-modal-{{ $category->id }}"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/50 px-4 backdrop-blur-sm">
    <div class="w-full max-w-sm rounded-3xl bg-white p-7 shadow-2xl ring-1 ring-slate-900/5">

        {{-- Icon --}}
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-50 ring-1 ring-rose-100">
            <i class="ti ti-trash text-xl text-rose-500"></i>
        </div>

        {{-- Content --}}
        <div class="mt-4 text-center">
            <h3 class="text-base font-semibold text-slate-900">Delete "{{ $category->name }}"?</h3>
            <p class="mt-2 text-sm leading-relaxed text-slate-500">
                This action cannot be undone. The category and its configuration will be permanently removed.
            </p>
        </div>

        {{-- Buttons — single form, two buttons side by side --}}
        <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" class="mt-5">
            @csrf
            @method('DELETE')

            <div class="flex gap-3">
                <button
                    type="button"
                    onclick="document.getElementById('delete-category-modal-{{ $category->id }}').classList.add('hidden'); document.getElementById('delete-category-modal-{{ $category->id }}').classList.remove('flex')"
                    class="flex-1 rounded-2xl border border-slate-200 bg-white py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                    Cancel
                </button>

                <button
                    type="submit"
                    class="flex-1 rounded-2xl bg-rose-600 py-2.5 text-sm font-medium text-white transition hover:bg-rose-700">
                    Delete
                </button>
            </div>
        </form>

    </div>
</div>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-16 text-center">
                                    <p class="text-lg font-bold text-slate-900">No categories yet</p>
                                    <p class="mt-1 text-sm text-slate-500">Create your first category above.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-layout>
