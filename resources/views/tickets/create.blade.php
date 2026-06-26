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

    <div class="ticket-create-page grid gap-6 xl:grid-cols-[minmax(0,1.2fr)_22rem]">
        <section class="ticket-create-panel rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
            @if ($errors->any())
                <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-medium text-rose-800">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf

                <div>
                    <x-input-label for="title" value="Issue Title" />
                    <x-text-input id="title" name="title" type="text" class="mt-2 block w-full rounded-2xl" :value="old('title')" required placeholder="Example: Laptop cannot connect to office Wi-Fi" />
                </div>

                <div>
                    <x-input-label for="description" value="Description" />
                    <textarea id="description" name="description" rows="7" class="mt-2 block w-full rounded-2xl border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500" required placeholder="Describe the problem, error messages, and steps already tried.">{{ old('description') }}</textarea>
                </div>

                <div class="overflow-hidden rounded-[1.6rem] border border-cyan-100 bg-gradient-to-r from-cyan-50 via-sky-50 to-white p-5 shadow-[0_18px_40px_rgba(14,116,144,0.08)]">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-start gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white text-cyan-700 shadow-sm ring-1 ring-cyan-100">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M12 3l1.9 4.6L18.5 9 15 12.1l.9 4.9L12 14.8 8.1 17l.9-4.9L5.5 9l4.6-1.4L12 3z" />
                                </svg>
                            </div>

                            <div>
                                <p class="text-[0.68rem] font-semibold uppercase tracking-[0.28em] text-cyan-700">AI Assistance</p>
                                <h3 class="mt-2 text-lg font-semibold tracking-tight text-slate-950">Smart Ticket Classification</h3>
                                <p class="mt-1.5 max-w-2xl text-sm leading-6 text-slate-600">
                                    Enter the issue title and description only. The system will automatically detect the best category and priority before the ticket is created.
                                </p>
                            </div>
                        </div>

                        <span class="inline-flex w-fit shrink-0 items-center rounded-full border border-cyan-100 bg-white/90 px-4 py-2 text-[0.68rem] font-semibold uppercase tracking-[0.24em] text-cyan-700 shadow-sm whitespace-nowrap">
                            Smart Intake
                        </span>
                    </div>
                </div>

                <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50/80 p-5">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-slate-950">Upload Supporting Files</h3>
                            <p class="mt-1 text-sm text-slate-500">{{ $allowedAttachmentText }}</p>
                        </div>

                        <span class="inline-flex w-fit rounded-full bg-cyan-50 px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.22em] text-cyan-700">
                            Max 5 files
                        </span>
                    </div>

                    <div class="mt-4">
                        <x-input-label for="attachments" value="Attachments" />
                        <input
                            id="attachments"
                            name="attachments[]"
                            type="file"
                            multiple
                            accept=".pdf,.txt,.docx,.jpg,.jpeg,.png"
                            class="mt-2 block w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm file:mr-4 file:rounded-xl file:border-0 file:bg-slate-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-slate-800 focus:border-slate-500 focus:outline-none focus:ring-slate-500"
                        />
                    </div>
                </div>

                <div class="flex justify-end">
                    <x-primary-button class="rounded-2xl bg-slate-900 px-6 py-3 text-xs tracking-[0.24em]">
                        Create Ticket
                    </x-primary-button>
                </div>
            </form>
        </section>

        <aside class="ticket-create-aside rounded-[2rem] bg-gradient-to-br from-[#0b5060] to-[#11829a] p-6 text-white shadow-[0_22px_60px_rgba(6,36,46,0.18)]">
            <p class="text-xs font-semibold uppercase tracking-[0.32em] text-cyan-100/80">Submission Guidance</p>
            <h3 class="mt-3 text-2xl font-semibold">Write tickets that move faster</h3>

            <div class="mt-6 space-y-4 text-sm leading-7 text-cyan-50/90">
                <p>Use a short, concrete title that clearly describes the problem.</p>
                <p>Include screenshots, system behavior, or steps already attempted when you add attachments.</p>
                <p>The system will auto-detect the category and priority, so clear descriptions lead to better routing.</p>
            </div>
        </aside>
    </div>
</x-app-layout>
