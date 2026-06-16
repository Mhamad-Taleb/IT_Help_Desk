<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.32em] text-[#0f7b92]">Ticket Intake</p>
                <h2 class="mt-2 text-2xl font-semibold leading-tight text-slate-950">Create Ticket</h2>
                <p class="text-sm text-slate-500">
                    Capture the issue clearly so the support workflow can track and resolve it quickly.
                </p>
            </div>

            <a href="{{ route('tickets.index') }}" class="inline-flex w-fit items-center rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                Back to Tickets
            </a>
        </div>
    </x-slot>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.2fr)_22rem]">
        <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
            @if ($errors->any())
                <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-medium text-rose-800">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('tickets.store') }}" class="space-y-6">
                @csrf

                <div>
                    <x-input-label for="title" value="Issue Title" />
                    <x-text-input id="title" name="title" type="text" class="mt-2 block w-full rounded-2xl" :value="old('title')" required placeholder="Example: Laptop cannot connect to office Wi-Fi" />
                </div>

                <div>
                    <x-input-label for="description" value="Description" />
                    <textarea id="description" name="description" rows="7" class="mt-2 block w-full rounded-2xl border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500" required placeholder="Describe the problem, error messages, and steps already tried.">{{ old('description') }}</textarea>
                </div>

                <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                    <div>
                        <x-input-label for="category_id" value="Category" />
                        <select id="category_id" name="category_id" class="mt-2 block h-[3.1rem] w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500" required>
                            <option value="">Select category</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="priority" value="Priority" />
                        <select id="priority" name="priority" class="mt-2 block h-[3.1rem] w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500" required>
                            @foreach ($priorities as $priority)
                                <option value="{{ $priority->value }}" @selected(old('priority', \App\Enums\TicketPriority::Medium->value) === $priority->value)>
                                    {{ $priority->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    @if (! $user->hasRole(\App\Enums\UserRole::Employee))
                        <div>
                            <x-input-label for="assigned_to" value="Assign To" />
                            <select id="assigned_to" name="assigned_to" class="mt-2 block h-[3.1rem] w-full rounded-2xl border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
                                <option value="">Unassigned</option>
                                @foreach ($assignees as $assignee)
                                    <option value="{{ $assignee->id }}" @selected((string) old('assigned_to') === (string) $assignee->id)>
                                        {{ $assignee->name }} - {{ $assignee->role->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>

                <div class="flex justify-end">
                    <x-primary-button class="rounded-2xl bg-slate-900 px-6 py-3 text-xs tracking-[0.24em]">
                        Create Ticket
                    </x-primary-button>
                </div>
            </form>
        </section>

        <aside class="rounded-[2rem] bg-gradient-to-br from-[#0b5060] to-[#11829a] p-6 text-white shadow-[0_22px_60px_rgba(6,36,46,0.18)]">
            <p class="text-xs font-semibold uppercase tracking-[0.32em] text-cyan-100/80">Submission Guidance</p>
            <h3 class="mt-3 text-2xl font-semibold">Write tickets that move faster</h3>

            <div class="mt-6 space-y-4 text-sm leading-7 text-cyan-50/90">
                <p>Use a short, concrete title that clearly describes the problem.</p>
                <p>Include screenshots, system behavior, or steps already attempted when you add attachments in the next sprint.</p>
                <p>Choose the right priority so the support team can triage workload correctly.</p>
            </div>
        </aside>
    </div>
</x-app-layout>
