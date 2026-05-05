<x-admin.page-shell :title="__('Employee Document Requests')" :description="__('Review employee requests, request employee uploads, and generate HR or finance documents from templates.')">
    <div class="space-y-4">
        <div class="rounded-lg border border-slate-200 bg-white p-3 dark:border-slate-800 dark:bg-slate-900/80">
            <div class="flex flex-col gap-3 xl:flex-row xl:items-end">
                <div class="grid flex-1 gap-3 md:grid-cols-[minmax(16rem,1.4fr)_minmax(10rem,0.8fr)_minmax(12rem,0.8fr)]">
                    <div>
                        <x-forms.label for="document-request-search" value="{{ __('Search') }}" class="mb-1.5 block" />
                        <x-forms.input id="document-request-search" type="search" wire:model.live.debounce.300ms="search"
                            placeholder="{{ __('Employee, NIP, or purpose') }}" class="w-full min-h-[42px]" />
                    </div>
                    <div>
                        <x-forms.label for="document-request-status" value="{{ __('Status') }}" class="mb-1.5 block" />
                        <x-forms.select id="document-request-status" wire:model.live="statusFilter">
                            <option value="all">{{ __('All statuses') }}</option>
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </x-forms.select>
                    </div>
                    <div>
                        <x-forms.label for="document-request-type" value="{{ __('Document Type') }}" class="mb-1.5 block" />
                        <x-forms.select id="document-request-type" wire:model.live="typeFilter">
                            <option value="all">{{ __('All types') }}</option>
                            @foreach ($documentTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </x-forms.select>
                    </div>
                </div>
                @can('createForEmployee', \App\Models\EmployeeDocumentRequest::class)
                    <x-actions.button type="button" wire:click="createRequest" class="w-full xl:w-auto">
                        <x-heroicon-o-plus class="h-4 w-4" />
                        {{ __('Request Document') }}
                    </x-actions.button>
                @endcan
            </div>
        </div>

        @if (session()->has('success'))
            <div class="rounded-xl border border-green-100 bg-green-50 p-4 text-sm font-medium text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-200">
                {{ session('success') }}
            </div>
        @endif
        @if (session()->has('warning'))
            <div class="rounded-xl border border-amber-100 bg-amber-50 p-4 text-sm font-medium text-amber-800 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-200">
                {{ session('warning') }}
            </div>
        @endif

        @if (count($selectedRequestIds) > 0)
            <x-admin.alert tone="primary" class="flex items-center gap-3">
                <span class="text-sm font-medium text-primary-700 dark:text-primary-300">
                    {{ count($selectedRequestIds) }} {{ __('selected') }}
                </span>
                <div class="ml-auto flex flex-wrap items-center gap-2">
                    <x-actions.button type="button" wire:click="bulkGenerate" wire:confirm="{{ __('Generate all selected eligible requests?') }}" size="sm">
                        <x-heroicon-m-document-text class="h-4 w-4" />
                        {{ __('Generate Selected') }}
                    </x-actions.button>
                    <x-actions.button type="button" wire:click="bulkApprove" wire:confirm="{{ __('Approve all selected eligible requests?') }}" variant="success" size="sm">
                        <x-heroicon-m-check-circle class="h-4 w-4" />
                        {{ __('Approve Selected') }}
                    </x-actions.button>
                    <x-actions.button type="button" wire:click="bulkReject" wire:confirm="{{ __('Reject all selected eligible requests?') }}" variant="danger" size="sm">
                        <x-heroicon-m-x-circle class="h-4 w-4" />
                        {{ __('Reject Selected') }}
                    </x-actions.button>
                </div>
            </x-admin.alert>
        @endif

        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
            <div class="overflow-x-scroll">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                    <thead class="bg-slate-50 dark:bg-slate-900/40">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                            <th class="w-10 px-4 py-3 text-center">
                                <x-forms.checkbox wire:model.live="selectAll" />
                            </th>
                            <th class="px-4 py-3">{{ __('Employee') }}</th>
                            <th class="px-4 py-3">{{ __('Document') }}</th>
                            <th class="px-4 py-3">{{ __('Purpose') }}</th>
                            <th class="px-4 py-3">{{ __('Status') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($requests as $request)
                            <tr class="align-top">
                                <td class="px-4 py-3 text-center">
                                    <x-forms.checkbox wire:model.live="selectedRequestIds" value="{{ $request->id }}" />
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200">
                                    <div class="font-semibold">{{ $request->user->name }}</div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400">{{ $request->user->nip }}</div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400">{{ $request->user->division->name ?? '-' }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200">
                                    <div class="font-semibold">{{ $request->documentTypeLabel() }}</div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400">{{ $request->created_at->diffForHumans() }}</div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400">
                                        {{ ucfirst($request->request_source ?: 'employee') }}
                                        @if ($request->requester)
                                            · {{ $request->requester->name }}
                                        @endif
                                    </div>
                                    @if ($request->due_date)
                                        @php
                                            $isOverdue = $request->due_date->isPast()
                                                && ! in_array($request->status, ['ready', 'rejected', 'generated'], true);
                                        @endphp
                                        <div class="text-xs {{ $isOverdue ? 'font-semibold text-rose-600 dark:text-rose-300' : 'text-slate-500 dark:text-slate-400' }}">
                                            {{ __('Due') }} {{ $request->due_date->format('d M Y') }}
                                            @if ($isOverdue)
                                                · {{ __('Overdue') }}
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="max-w-md px-4 py-3 text-sm text-slate-700 dark:text-slate-200">
                                    <div class="font-medium">{{ $request->purpose }}</div>
                                    @if ($request->details)
                                        <div class="mt-1 whitespace-pre-line text-xs text-slate-500 dark:text-slate-400">{{ $request->details }}</div>
                                    @endif
                                    @if ($request->fulfillment_note || $request->rejection_note)
                                        <div class="mt-2 rounded-lg bg-slate-50 p-2 text-xs text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                            {{ $request->fulfillment_note ?: $request->rejection_note }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold
                                        {{ $request->status === \App\Models\EmployeeDocumentRequest::STATUS_READY
                                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
                                            : ($request->status === \App\Models\EmployeeDocumentRequest::STATUS_REJECTED
                                                ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300'
                                                : ($request->status === \App\Models\EmployeeDocumentRequest::STATUS_UPLOAD_PROCESSING
                                                    ? 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300'
                                                    : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300')) }}">
                                        {{ $request->statusLabel() }}
                                    </span>
                                    @if ($request->reviewer)
                                        <div class="mt-2 text-xs text-slate-500 dark:text-slate-400">
                                            {{ __('By :name', ['name' => $request->reviewer->name]) }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right text-sm">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        @can('generate', $request)
                                            <x-actions.icon-button wire:click="generate({{ $request->id }})" variant="primary" label="{{ __('Generate document') }}: {{ $request->user->name }}">
                                                <x-heroicon-m-document-text class="h-5 w-5" />
                                            </x-actions.icon-button>
                                        @endcan
                                        @can('fulfill', $request)
                                            <x-actions.icon-button wire:click="confirmReady({{ $request->id }})" variant="success" label="{{ __('Approve document request') }}: {{ $request->user->name }}">
                                                <x-heroicon-m-check-circle class="h-5 w-5" />
                                            </x-actions.icon-button>
                                        @endcan
                                        @can('reject', $request)
                                            <x-actions.icon-button wire:click="confirmReject({{ $request->id }})" variant="danger" label="{{ __('Reject document request') }}: {{ $request->user->name }}">
                                                <x-heroicon-m-x-circle class="h-5 w-5" />
                                            </x-actions.icon-button>
                                        @endcan
                                        @if ($request->generated_path)
                                            <x-actions.icon-button href="{{ route('admin.document-requests.download', $request) }}" variant="neutral" label="{{ __('Download generated document') }}: {{ $request->user->name }}">
                                                <x-heroicon-m-arrow-down-tray class="h-5 w-5" />
                                            </x-actions.icon-button>
                                        @endif
                                        @if ($request->uploaded_path)
                                            <x-actions.icon-button href="{{ route('admin.document-requests.uploaded', $request) }}" variant="neutral" label="{{ __('Download uploaded document') }}: {{ $request->user->name }}">
                                                <x-heroicon-m-arrow-down-tray class="h-5 w-5" />
                                            </x-actions.icon-button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                                    {{ __('No document requests found.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($requests->hasPages())
            <div>{{ $requests->links() }}</div>
        @endif
    </div>

    <x-overlays.dialog-modal wire:model.live="showCreateModal" maxWidth="4xl">
        <x-slot name="title">{{ __('Create Document Request') }}</x-slot>
        <x-slot name="content">
            @php
                $hasActiveTemplate = (bool) $selectedDocumentTypeProfile?->activeTemplate();
                $selectedEmployeesCount = count($targetUserIds);
                $flowStepTwo = $selectedDocumentTypeProfile?->requires_employee_upload
                    ? __('Employee uploads file')
                    : (($selectedDocumentTypeProfile?->auto_generate_enabled && $hasActiveTemplate && $generateImmediately)
                        ? __('PDF is generated now')
                        : (($selectedDocumentTypeProfile?->auto_generate_enabled && $hasActiveTemplate)
                            ? __('Admin generates PDF')
                            : __('Admin prepares manually')));
                $flowStepThree = $selectedDocumentTypeProfile?->requires_employee_upload
                    ? __('Admin reviews upload')
                    : (($selectedDocumentTypeProfile?->auto_generate_enabled && $hasActiveTemplate)
                        ? __('Employee receives attached PDF')
                        : __('Employee receives status update'));
            @endphp

            <div class="space-y-5">
                <ol class="grid gap-2 border-b border-slate-100 pb-4 text-sm dark:border-slate-800 sm:grid-cols-3">
                    <li class="flex items-start gap-2">
                        <span class="mt-0.5 inline-flex h-5 w-5 items-center justify-center rounded-full bg-primary-600 text-[11px] font-semibold text-white">1</span>
                        <span class="font-medium text-slate-900 dark:text-white">{{ __('Create request') }}</span>
                    </li>
                    <li class="flex items-start gap-2 text-slate-700 dark:text-slate-200">
                        <span class="mt-0.5 inline-flex h-5 w-5 items-center justify-center rounded-full bg-slate-100 text-[11px] font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">2</span>
                        <span class="font-medium">{{ $flowStepTwo }}</span>
                    </li>
                    <li class="flex items-start gap-2 text-slate-700 dark:text-slate-200">
                        <span class="mt-0.5 inline-flex h-5 w-5 items-center justify-center rounded-full bg-slate-100 text-[11px] font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">3</span>
                        <span class="font-medium">{{ $flowStepThree }}</span>
                    </li>
                </ol>

                <div class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
                <div class="space-y-4">
                    <section class="space-y-3">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('Who is this for?') }}</h3>
                                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ __('One request will be created for each selected employee.') }}</p>
                            </div>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                {{ trans_choice(':count employee selected|:count employees selected', $selectedEmployeesCount, ['count' => $selectedEmployeesCount]) }}
                            </span>
                        </div>
                        <x-forms.select id="target-user-ids" wire:model.live="targetUserIds" class="block w-full" multiple>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->name }} · {{ $employee->nip ?: $employee->email }}</option>
                            @endforeach
                        </x-forms.select>
                        <x-forms.input-error for="targetUserIds" class="mt-1" />
                        <x-forms.input-error for="targetUserId" class="mt-1" />
                    </section>

                    <section class="space-y-3">
                        <div class="mb-1.5 flex items-center justify-between gap-3">
                            <div>
                                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('What document is needed?') }}</h3>
                                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ __('The document type controls whether the employee uploads a file, the system generates a PDF, or an admin handles it manually.') }}</p>
                            </div>
                            <x-actions.button type="button" size="sm" variant="ghost" wire:click="applyRequestPreset">
                                <x-heroicon-m-sparkles class="h-4 w-4" />
                                {{ __('Use Preset') }}
                            </x-actions.button>
                        </div>
                        <x-forms.select id="admin-document-type" wire:model.live="documentType" class="block w-full">
                            @foreach ($adminDocumentTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </x-forms.select>
                        <x-forms.input-error for="documentType" class="mt-1" />
                    </section>

                    <section class="space-y-3">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('What should the employee see?') }}</h3>
                        <x-forms.label for="admin-document-purpose" value="{{ __('Purpose') }}" class="mb-1.5 block" />
                        <x-forms.textarea id="admin-document-purpose" wire:model.live="purpose" rows="3" class="block w-full" placeholder="{{ __('Example: please upload NPWP for payroll tax data.') }}" />
                        <x-forms.input-error for="purpose" class="mt-1" />

                        <div>
                            <x-forms.label for="admin-document-details" value="{{ __('Details') }} ({{ __('Optional') }})" class="mb-1.5 block" />
                            <x-forms.textarea id="admin-document-details" wire:model.live="details" rows="4" class="block w-full" placeholder="{{ __('Recipient, bank/agency name, required note, or upload instruction.') }}" />
                            <x-forms.input-error for="details" class="mt-1" />
                        </div>
                    </section>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-forms.label for="admin-document-due-date" value="{{ __('Due Date') }} ({{ __('Optional') }})" class="mb-1.5 block" />
                            <x-forms.input
                                id="admin-document-due-date"
                                type="date"
                                wire:model.live="dueDate"
                                min="{{ now()->toDateString() }}"
                                class="block w-full"
                            />
                            <x-forms.input-error for="dueDate" class="mt-1" />
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                <x-actions.button type="button" size="sm" variant="ghost" wire:click="setDueDatePreset(0)">{{ __('Today') }}</x-actions.button>
                                <x-actions.button type="button" size="sm" variant="ghost" wire:click="setDueDatePreset(3)">+3 {{ __('days') }}</x-actions.button>
                                <x-actions.button type="button" size="sm" variant="ghost" wire:click="setDueDatePreset(7)">+7 {{ __('days') }}</x-actions.button>
                                <x-actions.button type="button" size="sm" variant="ghost" wire:click="setDueDatePreset(14)">+14 {{ __('days') }}</x-actions.button>
                                <x-actions.button type="button" size="sm" variant="ghost" wire:click="clearDueDate">{{ __('Clear') }}</x-actions.button>
                            </div>
                        </div>
                        @if ($selectedDocumentTypeProfile?->auto_generate_enabled && $selectedDocumentTypeProfile?->activeTemplate())
                            <label class="mt-7 flex items-start gap-2 rounded-lg border border-emerald-100 bg-emerald-50 p-3 text-sm text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-200">
                                <x-forms.checkbox wire:model.live="generateImmediately" />
                                <span>
                                    <span class="block font-semibold">{{ __('Generate PDF immediately') }}</span>
                                    <span class="mt-0.5 block text-xs">{{ __('If enabled, the PDF is generated as soon as this request is created.') }}</span>
                                </span>
                            </label>
                        @endif
                    </div>
                </div>

                <aside class="border-t border-slate-100 pt-4 dark:border-slate-800 lg:border-l lg:border-t-0 lg:pl-5 lg:pt-0">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('What happens after Create Request?') }}</h3>

                    @if ($selectedDocumentTypeProfile)
                        <div class="mt-4 divide-y divide-slate-100 text-sm dark:divide-slate-800">
                            <div class="pb-3">
                                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Selected document') }}</div>
                                <div class="mt-1 font-semibold text-gray-900 dark:text-white">{{ $selectedDocumentTypeProfile->name }}</div>
                                <div class="text-xs text-gray-500">{{ strtoupper($selectedDocumentTypeProfile->category) }} · {{ $selectedDocumentTypeProfile->code }}</div>
                            </div>
                            <div class="py-3">
                                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Initial status') }}</div>
                                <div class="mt-1 font-semibold text-gray-900 dark:text-white">
                                    {{ $selectedDocumentTypeProfile->requires_employee_upload ? __('Waiting for employee upload') : ($generateImmediately ? __('Generated PDF') : __('Pending admin action')) }}
                                </div>
                            </div>
                            <div class="py-3">
                                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Due date') }}</div>
                                <div class="mt-1 font-semibold text-gray-900 dark:text-white">
                                    {{ $dueDate ? \Carbon\Carbon::parse($dueDate)->format('d M Y') : __('No deadline') }}
                                </div>
                            </div>

                            <div class="space-y-2 py-3">
                                <div class="flex items-center justify-between gap-3">
                                    <span>{{ __('Employee upload') }}</span>
                                    <span class="text-xs font-semibold {{ $selectedDocumentTypeProfile->requires_employee_upload ? 'text-amber-600 dark:text-amber-300' : 'text-gray-500 dark:text-gray-400' }}">
                                        {{ $selectedDocumentTypeProfile->requires_employee_upload ? __('Required') : __('Not required') }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span>{{ __('PDF generation') }}</span>
                                    <span class="text-xs font-semibold {{ $selectedDocumentTypeProfile->auto_generate_enabled ? 'text-emerald-600 dark:text-emerald-300' : 'text-gray-500 dark:text-gray-400' }}">
                                        {{ $selectedDocumentTypeProfile->auto_generate_enabled ? __('Enabled') : __('Manual only') }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span>{{ __('Active template') }}</span>
                                    <span class="text-xs font-semibold {{ $selectedDocumentTypeProfile->activeTemplate() ? 'text-emerald-600 dark:text-emerald-300' : 'text-rose-600 dark:text-rose-300' }}">
                                        {{ $selectedDocumentTypeProfile->activeTemplate()?->name ?? __('Missing') }}
                                    </span>
                                </div>
                            </div>

                            @if ($selectedDocumentTypeProfile->requires_employee_upload)
                                <p class="pt-3 text-xs font-medium text-amber-700 dark:text-amber-300">
                                    {{ __('Employee receives an upload request. After the file is uploaded, admin can download it, approve it, or reject it.') }}
                                </p>
                            @elseif ($selectedDocumentTypeProfile->auto_generate_enabled && $selectedDocumentTypeProfile->activeTemplate())
                                <p class="pt-3 text-xs font-medium text-emerald-700 dark:text-emerald-300">
                                    {{ $generateImmediately
                                        ? __('The request will be created and the generated PDF will be attached to the employee notification email.')
                                        : __('The request will be pending. Admin can generate the PDF later from the table action.')
                                    }}
                                </p>
                            @else
                                <p class="pt-3 text-xs text-gray-600 dark:text-gray-300">
                                    {{ __('The request will stay pending until an admin prepares the document manually and marks it ready.') }}
                                </p>
                            @endif
                        </div>
                    @else
                        <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">{{ __('Choose a document type to see the workflow.') }}</p>
                    @endif
                </aside>
                </div>
            </div>
        </x-slot>
        <x-slot name="footer">
            <div class="flex w-full justify-end gap-2">
                <x-actions.secondary-button type="button" wire:click="closeCreateModal">{{ __('Cancel') }}</x-actions.secondary-button>
                <x-actions.button type="button" wire:click="storeRequest">{{ __('Create Request') }}</x-actions.button>
            </div>
        </x-slot>
    </x-overlays.dialog-modal>

    <x-overlays.dialog-modal wire:model.live="confirmingReady">
        <x-slot name="title">{{ __('Approve Document') }}</x-slot>
        <x-slot name="content">
            <div class="space-y-4">
                @if ($reviewRequest)
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm dark:border-slate-700 dark:bg-slate-900">
                        <div class="font-semibold text-slate-900 dark:text-white">{{ $reviewRequest->user?->name }} · {{ $reviewRequest->documentTypeLabel() }}</div>
                        <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $reviewRequest->purpose }}</div>
                    </div>
                @endif
                <p class="text-sm text-slate-600 dark:text-slate-300">
                    {{ __('Use approve when the document is ready or the uploaded file is accepted. Add delivery, pickup, or confirmation details for the employee.') }}
                </p>
                <x-forms.textarea wire:model.live="reviewNote" rows="4"
                    placeholder="{{ __('Example: document is ready for pickup at HR desk after 14:00.') }}" />
            </div>
        </x-slot>
        <x-slot name="footer">
            <div class="flex w-full justify-end gap-2">
                <x-actions.secondary-button type="button" wire:click="cancelReview">{{ __('Cancel') }}</x-actions.secondary-button>
                <x-actions.button type="button" wire:click="markReady">{{ __('Approve') }}</x-actions.button>
            </div>
        </x-slot>
    </x-overlays.dialog-modal>

    <x-overlays.dialog-modal wire:model.live="confirmingRejection">
        <x-slot name="title">{{ __('Reject Document Request') }}</x-slot>
        <x-slot name="content">
            <div class="space-y-4">
                @if ($reviewRequest)
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm dark:border-slate-700 dark:bg-slate-900">
                        <div class="font-semibold text-slate-900 dark:text-white">{{ $reviewRequest->user?->name }} · {{ $reviewRequest->documentTypeLabel() }}</div>
                        <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $reviewRequest->purpose }}</div>
                    </div>
                @endif
                <p class="text-sm text-slate-600 dark:text-slate-300">
                    {{ __('Rejecting sends the employee a status update. Add a clear reason or what they need to fix.') }}
                </p>
                <x-forms.textarea wire:model.live="reviewNote" rows="4"
                    placeholder="{{ __('Example: please resubmit with a clearer purpose and deadline.') }}" />
            </div>
        </x-slot>
        <x-slot name="footer">
            <div class="flex w-full justify-end gap-2">
                <x-actions.secondary-button type="button" wire:click="cancelReview">{{ __('Cancel') }}</x-actions.secondary-button>
                <x-actions.button type="button" wire:click="reject">{{ __('Reject Request') }}</x-actions.button>
            </div>
        </x-slot>
    </x-overlays.dialog-modal>
</x-admin.page-shell>
