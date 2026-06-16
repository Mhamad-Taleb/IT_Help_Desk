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
                                Ticket Workspace
                            </p>
                        </div>

                        <h2 class="mt-3 text-4xl font-extrabold tracking-tight text-slate-950">
                            Tickets Management
                        </h2>

                        <p class="mt-2 max-w-3xl text-base text-slate-600">
                            Track requests, manage priorities, monitor status,
                            and follow every support case from creation to resolution.
                        </p>
                    </div>

                    @if (auth()->user()->hasRole(\App\Enums\UserRole::Employee))
                        <a href="{{ route('tickets.create') }}"
                            class="inline-flex items-center justify-center rounded-2xl bg-blue-900 px-6 py-3 text-sm font-bold text-white shadow-md transition hover:bg-blue-800">
                            + Create Ticket
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </x-slot>

    <div class="space-y-8">

        @if (session('status'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-800 shadow-sm">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-semibold text-rose-800 shadow-sm">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- Stats --}}
        <section class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-md">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold uppercase tracking-[0.25em] text-slate-500">All Visible</p>
                    <span class="rounded-2xl bg-blue-50 px-3 py-2 text-sm">📋</span>
                </div>
                <p class="mt-5 text-4xl font-bold text-slate-950">{{ $totalTickets }}</p>
                <p class="mt-1 text-sm text-slate-500">Total tickets available</p>
            </div>

            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-md">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold uppercase tracking-[0.25em] text-slate-500">Open</p>
                    <span class="rounded-2xl bg-blue-50 px-3 py-2 text-sm">🔵</span>
                </div>
                <p class="mt-5 text-4xl font-bold text-slate-950">{{ $openTickets }}</p>
                <p class="mt-1 text-sm text-slate-500">Waiting for action</p>
            </div>

            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-md">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold uppercase tracking-[0.25em] text-slate-500">Resolved</p>
                    <span class="rounded-2xl bg-emerald-50 px-3 py-2 text-sm">✅</span>
                </div>
                <p class="mt-5 text-4xl font-bold text-slate-950">{{ $resolvedTickets }}</p>
                <p class="mt-1 text-sm text-slate-500">Completed cases</p>
            </div>

            <div class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-md">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold uppercase tracking-[0.25em] text-slate-500">Assigned</p>
                    <span class="rounded-2xl bg-violet-50 px-3 py-2 text-sm">👤</span>
                </div>
                <p class="mt-5 text-4xl font-bold text-slate-950">{{ $assignedTickets }}</p>
                <p class="mt-1 text-sm text-slate-500">Handled by employees</p>
            </div>
        </section>

        {{-- Filters --}}
        <section class="rounded-[2rem] border border-slate-200 bg-white p-8 shadow-lg">
            <form method="GET" action="{{ route('tickets.index') }}">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50">
                            🔍
                        </div>

                        <div>
                            <h3 class="text-2xl font-bold text-slate-950">
                                Find Tickets
                            </h3>

                            <p class="mt-1 text-sm text-slate-500">
                                Search and filter tickets by number, title, category, priority, or status.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('tickets.index') }}"
                            class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                            Clear Filters
                        </a>

                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-2xl bg-blue-900 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-800">
                            Apply Filters
                        </button>
                    </div>
                </div>

                <div class="mt-8 grid gap-5 lg:grid-cols-4 xl:grid-cols-5">
                    <div class="xl:col-span-2">
                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Search
                        </label>

                        <div class="relative">
                            <svg
                                class="absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M21 21l-4.35-4.35m1.85-5.65a7.5 7.5 0 11-15 0a7.5 7.5 0 0115 0z"/>
                            </svg>

                            <input
                                id="search"
                                type="text"
                                name="search"
                                value="{{ request('search') }}"
                                placeholder="Search ticket number, title..."
                                class="h-[3.1rem] w-full rounded-2xl border border-slate-300 bg-slate-50 pl-12 pr-4 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>

                    <div>
                        <x-input-label for="category" value="Category" />
                        <select id="category" name="category"
                            class="mt-2 block h-[3.1rem] w-full rounded-2xl border-slate-300 bg-slate-50 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">All categories</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) request('category') === (string) $category->id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="priority" value="Priority" />
                        <select id="priority" name="priority"
                            class="mt-2 block h-[3.1rem] w-full rounded-2xl border-slate-300 bg-slate-50 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">All priorities</option>
                            @foreach ($priorities as $priority)
                                <option value="{{ $priority->value }}" @selected(request('priority') === $priority->value)>
                                    {{ $priority->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="status" value="Status" />
                        <select id="status" name="status"
                            class="mt-2 block h-[3.1rem] w-full rounded-2xl border-slate-300 bg-slate-50 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">All statuses</option>
                            @foreach ($statuses as $status)
                                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>
                                    {{ $status->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </form>
        </section>

        {{-- Ticket Queue --}}
        <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-slate-200 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-xl font-bold text-slate-950">Ticket Queue</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        @if (auth()->user()->hasRole(\App\Enums\UserRole::Employee))
                            Your submitted tickets are listed here.
                        @else
                            All visible tickets are listed here for support follow-up.
                        @endif
                    </p>
                </div>

                <span class="inline-flex w-fit items-center gap-2 rounded-full bg-emerald-50 px-4 py-2 text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    Live Queue
                </span>
            </div>

            <div class="space-y-4 bg-slate-50/60 p-6">
                @forelse ($tickets as $ticket)
                    <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition duration-200 hover:border-blue-200 hover:shadow-md">
                        <div class="flex flex-col gap-6 xl:flex-row xl:items-center xl:justify-between">

                            {{-- Main Ticket Info --}}
                            <div class="flex gap-5">
                                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-blue-900 text-lg font-bold text-white shadow-sm">
                                    #
                                </div>

                                <div>
                                    <div class="flex flex-wrap items-center gap-3">
                                        <span class="rounded-full bg-blue-50 px-4 py-2 text-xs font-bold uppercase tracking-[0.18em] text-blue-700">
                                            {{ $ticket->ticket_number }}
                                        </span>

                                        <span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700">
                                            {{ $ticket->category->name }}
                                        </span>
                                    </div>

                                    <h4 class="mt-4 text-xl font-bold text-slate-950">
                                        {{ $ticket->title }}
                                    </h4>

                                    
                                </div>
                            </div>

{{-- Meta Info --}}
<div class="flex flex-wrap items-center gap-3">

    {{-- Priority --}}
    <div class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
        <span class="h-2 w-2 rounded-full bg-amber-400"></span>

        <div>
            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">
                Priority
            </p>

            <p class="text-sm font-bold text-slate-900">
                {{ $ticket->priority->label() }}
            </p>
        </div>
    </div>

    {{-- Status --}}
    <div class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
        <span class="h-2 w-2 rounded-full bg-emerald-400"></span>

        <div>
            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">
                Status
            </p>

            <p class="text-sm font-bold text-slate-900">
                {{ $ticket->status->label() }}
            </p>
        </div>
    </div>

    {{-- Owner --}}
    <div class="inline-flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-100 text-xs font-bold text-blue-700">
            {{ strtoupper(substr($ticket->creator->name, 0, 1)) }}
        </div>

        <div>
            <p class="text-sm font-bold text-slate-900">
                {{ $ticket->creator->name }}
            </p>

            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">
                {{ $ticket->creator->username }}
            </p>
        </div>
    </div>

    {{-- Button --}}
    <a href="{{ route('tickets.show', $ticket) }}"
        class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-blue-700">
        View Details
    </a>

</div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-blue-50 text-2xl">
                            📭
                        </div>
                        <p class="mt-4 text-lg font-bold text-slate-900">No tickets found</p>
                        <p class="mt-2 text-sm text-slate-500">
                            @if (auth()->user()->hasRole(\App\Enums\UserRole::Employee))
                                Create your first ticket to start the support workflow.
                            @else
                                No tickets are available in the current support queue yet.
                            @endif
                        </p>
                    </div>
                @endforelse
            </div>

            @if ($tickets->hasPages())
                <div class="border-t border-slate-200 bg-white px-6 py-4">
                    {{ $tickets->links() }}
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
