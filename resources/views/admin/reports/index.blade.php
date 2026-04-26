<x-app-layout>
    <x-admin.page-shell :title="__('Report Center')" :description="__('Export operational HR data without loading thousands of rows in the browser.')">
        <div class="grid gap-5 xl:grid-cols-2">
            <x-admin.panel class="overflow-hidden">
                <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">{{ __('HR Operations') }}</p>
                            <h2 class="mt-1 text-base font-semibold text-slate-950 dark:text-white">{{ __('Leave Request Report') }}</h2>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                                {{ __('Export leave, permission, sick, and excused requests by period and approval status.') }}
                            </p>
                        </div>
                        <x-admin.status-badge tone="info" pill>{{ __('Excel') }}</x-admin.status-badge>
                    </div>
                </div>

                <form method="GET" action="{{ route('admin.reports.leaves.export') }}" class="grid gap-4 p-5 sm:grid-cols-2">
                    <div>
                        <x-forms.label for="leave_start_date" value="{{ __('Start Date') }}" class="mb-1.5 block" />
                        <x-forms.input id="leave_start_date" name="start_date" type="date" class="w-full" />
                    </div>

                    <div>
                        <x-forms.label for="leave_end_date" value="{{ __('End Date') }}" class="mb-1.5 block" />
                        <x-forms.input id="leave_end_date" name="end_date" type="date" class="w-full" />
                    </div>

                    <div>
                        <x-forms.label for="leave_approval_status" value="{{ __('Approval Status') }}" class="mb-1.5 block" />
                        <x-forms.select id="leave_approval_status" name="approval_status" class="w-full">
                            <option value="all">{{ __('All statuses') }}</option>
                            <option value="pending">{{ __('Pending') }}</option>
                            <option value="approved">{{ __('Approved') }}</option>
                            <option value="rejected">{{ __('Rejected') }}</option>
                        </x-forms.select>
                    </div>

                    <div>
                        <x-forms.label for="leave_request_type" value="{{ __('Request Type') }}" class="mb-1.5 block" />
                        <x-forms.select id="leave_request_type" name="request_type" class="w-full">
                            <option value="all">{{ __('All request types') }}</option>
                            <option value="leave">{{ __('Leave') }}</option>
                            <option value="permission">{{ __('Permission') }}</option>
                            <option value="sick">{{ __('Sick') }}</option>
                            <option value="excused">{{ __('Excused') }}</option>
                        </x-forms.select>
                    </div>

                    <div>
                        <x-forms.label for="leave_division" value="{{ __('Division') }}" class="mb-1.5 block" />
                        <x-forms.select id="leave_division" name="division" class="w-full">
                            <option value="">{{ __('All Divisions') }}</option>
                            @foreach ($divisions as $division)
                                <option value="{{ $division->id }}">{{ $division->name }}</option>
                            @endforeach
                        </x-forms.select>
                    </div>

                    <div>
                        <x-forms.label for="leave_job_title" value="{{ __('Job Title') }}" class="mb-1.5 block" />
                        <x-forms.select id="leave_job_title" name="job_title" class="w-full">
                            <option value="">{{ __('All Job Titles') }}</option>
                            @foreach ($jobTitles as $jobTitle)
                                <option value="{{ $jobTitle->id }}">{{ $jobTitle->name }}</option>
                            @endforeach
                        </x-forms.select>
                    </div>

                    <div class="sm:col-span-2">
                        <x-forms.label for="leave_search" value="{{ __('Search') }}" class="mb-1.5 block" />
                        <x-forms.input id="leave_search" name="search" type="search" placeholder="{{ __('Employee, NIP, note, or rejection reason') }}" class="w-full" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-actions.button type="submit" variant="primary" class="w-full justify-center sm:w-auto">
                            <x-heroicon-m-arrow-down-tray class="h-5 w-5" />
                            <span>{{ __('Export Leave Report') }}</span>
                        </x-actions.button>
                    </div>
                </form>
            </x-admin.panel>

            <x-admin.panel class="overflow-hidden">
                <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">{{ __('Finance Operations') }}</p>
                            <h2 class="mt-1 text-base font-semibold text-slate-950 dark:text-white">{{ __('Payroll Summary Report') }}</h2>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                                {{ __('Export payroll summaries by period, status, division, and job title for payroll reconciliation.') }}
                            </p>
                        </div>
                        <x-admin.status-badge tone="info" pill>{{ __('Excel') }}</x-admin.status-badge>
                    </div>
                </div>

                <form method="GET" action="{{ route('admin.reports.payrolls.export') }}" class="grid gap-4 p-5 sm:grid-cols-2">
                    <div>
                        <x-forms.label for="payroll_month" value="{{ __('Month') }}" class="mb-1.5 block" />
                        <x-forms.select id="payroll_month" name="month" class="w-full">
                            <option value="">{{ __('All Months') }}</option>
                            @foreach (range(1, 12) as $month)
                                <option value="{{ $month }}">{{ \Carbon\Carbon::createFromFormat('!m', $month)->translatedFormat('F') }}</option>
                            @endforeach
                        </x-forms.select>
                    </div>

                    <div>
                        <x-forms.label for="payroll_year" value="{{ __('Year') }}" class="mb-1.5 block" />
                        <x-forms.select id="payroll_year" name="year" class="w-full">
                            <option value="">{{ __('All Years') }}</option>
                            @foreach (range(date('Y') - 1, date('Y') + 1) as $year)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endforeach
                        </x-forms.select>
                    </div>

                    <div>
                        <x-forms.label for="payroll_status" value="{{ __('Status') }}" class="mb-1.5 block" />
                        <x-forms.select id="payroll_status" name="status" class="w-full">
                            <option value="all">{{ __('All statuses') }}</option>
                            <option value="draft">{{ __('Draft') }}</option>
                            <option value="published">{{ __('Published') }}</option>
                            <option value="paid">{{ __('Paid') }}</option>
                        </x-forms.select>
                    </div>

                    <div>
                        <x-forms.label for="payroll_division" value="{{ __('Division') }}" class="mb-1.5 block" />
                        <x-forms.select id="payroll_division" name="division" class="w-full">
                            <option value="">{{ __('All Divisions') }}</option>
                            @foreach ($divisions as $division)
                                <option value="{{ $division->id }}">{{ $division->name }}</option>
                            @endforeach
                        </x-forms.select>
                    </div>

                    <div>
                        <x-forms.label for="payroll_job_title" value="{{ __('Job Title') }}" class="mb-1.5 block" />
                        <x-forms.select id="payroll_job_title" name="job_title" class="w-full">
                            <option value="">{{ __('All Job Titles') }}</option>
                            @foreach ($jobTitles as $jobTitle)
                                <option value="{{ $jobTitle->id }}">{{ $jobTitle->name }}</option>
                            @endforeach
                        </x-forms.select>
                    </div>

                    <div>
                        <x-forms.label for="payroll_search" value="{{ __('Search') }}" class="mb-1.5 block" />
                        <x-forms.input id="payroll_search" name="search" type="search" placeholder="{{ __('Employee or NIP') }}" class="w-full" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-actions.button type="submit" variant="primary" class="w-full justify-center sm:w-auto">
                            <x-heroicon-m-arrow-down-tray class="h-5 w-5" />
                            <span>{{ __('Export Payroll Report') }}</span>
                        </x-actions.button>
                    </div>
                </form>
            </x-admin.panel>

            <x-admin.panel class="overflow-hidden">
                <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">{{ __('HR Operations') }}</p>
                            <h2 class="mt-1 text-base font-semibold text-slate-950 dark:text-white">{{ __('Schedule Roster Report') }}</h2>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                                {{ __('Export employee schedules by period, division, job title, shift, and off-day status.') }}
                            </p>
                        </div>
                        <x-admin.status-badge tone="info" pill>{{ __('Excel') }}</x-admin.status-badge>
                    </div>
                </div>

                <form method="GET" action="{{ route('admin.reports.schedules.export') }}" class="grid gap-4 p-5 sm:grid-cols-2">
                    <div>
                        <x-forms.label for="schedule_start_date" value="{{ __('Start Date') }}" class="mb-1.5 block" />
                        <x-forms.input id="schedule_start_date" name="start_date" type="date" class="w-full" />
                    </div>

                    <div>
                        <x-forms.label for="schedule_end_date" value="{{ __('End Date') }}" class="mb-1.5 block" />
                        <x-forms.input id="schedule_end_date" name="end_date" type="date" class="w-full" />
                    </div>

                    <div>
                        <x-forms.label for="schedule_division" value="{{ __('Division') }}" class="mb-1.5 block" />
                        <x-forms.select id="schedule_division" name="division" class="w-full">
                            <option value="">{{ __('All Divisions') }}</option>
                            @foreach ($divisions as $division)
                                <option value="{{ $division->id }}">{{ $division->name }}</option>
                            @endforeach
                        </x-forms.select>
                    </div>

                    <div>
                        <x-forms.label for="schedule_job_title" value="{{ __('Job Title') }}" class="mb-1.5 block" />
                        <x-forms.select id="schedule_job_title" name="job_title" class="w-full">
                            <option value="">{{ __('All Job Titles') }}</option>
                            @foreach ($jobTitles as $jobTitle)
                                <option value="{{ $jobTitle->id }}">{{ $jobTitle->name }}</option>
                            @endforeach
                        </x-forms.select>
                    </div>

                    <div>
                        <x-forms.label for="schedule_shift" value="{{ __('Shift') }}" class="mb-1.5 block" />
                        <x-forms.select id="schedule_shift" name="shift_id" class="w-full">
                            <option value="">{{ __('All Shifts') }}</option>
                            @foreach ($shifts as $shift)
                                <option value="{{ $shift->id }}">{{ $shift->name }}</option>
                            @endforeach
                        </x-forms.select>
                    </div>

                    <div>
                        <x-forms.label for="schedule_off_status" value="{{ __('Work Status') }}" class="mb-1.5 block" />
                        <x-forms.select id="schedule_off_status" name="off_status" class="w-full">
                            <option value="all">{{ __('All statuses') }}</option>
                            <option value="working">{{ __('Working') }}</option>
                            <option value="off">{{ __('Off Day') }}</option>
                        </x-forms.select>
                    </div>

                    <div class="sm:col-span-2">
                        <x-forms.label for="schedule_search" value="{{ __('Search') }}" class="mb-1.5 block" />
                        <x-forms.input id="schedule_search" name="search" type="search" placeholder="{{ __('Employee, NIP, or shift name') }}" class="w-full" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-actions.button type="submit" variant="primary" class="w-full justify-center sm:w-auto">
                            <x-heroicon-m-arrow-down-tray class="h-5 w-5" />
                            <span>{{ __('Export Schedule Report') }}</span>
                        </x-actions.button>
                    </div>
                </form>
            </x-admin.panel>

            <x-admin.panel class="overflow-hidden">
                <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">{{ __('HR Operations') }}</p>
                            <h2 class="mt-1 text-base font-semibold text-slate-950 dark:text-white">{{ __('Overtime Report') }}</h2>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                                {{ __('Export overtime requests with duration, approval status, reviewer, and estimated cost.') }}
                            </p>
                        </div>
                        <x-admin.status-badge tone="info" pill>{{ __('Excel') }}</x-admin.status-badge>
                    </div>
                </div>

                <form method="GET" action="{{ route('admin.reports.overtime.export') }}" class="grid gap-4 p-5 sm:grid-cols-2">
                    <div>
                        <x-forms.label for="overtime_start_date" value="{{ __('Start Date') }}" class="mb-1.5 block" />
                        <x-forms.input id="overtime_start_date" name="start_date" type="date" class="w-full" />
                    </div>

                    <div>
                        <x-forms.label for="overtime_end_date" value="{{ __('End Date') }}" class="mb-1.5 block" />
                        <x-forms.input id="overtime_end_date" name="end_date" type="date" class="w-full" />
                    </div>

                    <div>
                        <x-forms.label for="overtime_status" value="{{ __('Approval Status') }}" class="mb-1.5 block" />
                        <x-forms.select id="overtime_status" name="status" class="w-full">
                            <option value="all">{{ __('All statuses') }}</option>
                            <option value="pending">{{ __('Pending') }}</option>
                            <option value="approved">{{ __('Approved') }}</option>
                            <option value="rejected">{{ __('Rejected') }}</option>
                        </x-forms.select>
                    </div>

                    <div>
                        <x-forms.label for="overtime_division" value="{{ __('Division') }}" class="mb-1.5 block" />
                        <x-forms.select id="overtime_division" name="division" class="w-full">
                            <option value="">{{ __('All Divisions') }}</option>
                            @foreach ($divisions as $division)
                                <option value="{{ $division->id }}">{{ $division->name }}</option>
                            @endforeach
                        </x-forms.select>
                    </div>

                    <div>
                        <x-forms.label for="overtime_job_title" value="{{ __('Job Title') }}" class="mb-1.5 block" />
                        <x-forms.select id="overtime_job_title" name="job_title" class="w-full">
                            <option value="">{{ __('All Job Titles') }}</option>
                            @foreach ($jobTitles as $jobTitle)
                                <option value="{{ $jobTitle->id }}">{{ $jobTitle->name }}</option>
                            @endforeach
                        </x-forms.select>
                    </div>

                    <div>
                        <x-forms.label for="overtime_search" value="{{ __('Search') }}" class="mb-1.5 block" />
                        <x-forms.input id="overtime_search" name="search" type="search" placeholder="{{ __('Employee, NIP, reason, or rejection reason') }}" class="w-full" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-actions.button type="submit" variant="primary" class="w-full justify-center sm:w-auto">
                            <x-heroicon-m-arrow-down-tray class="h-5 w-5" />
                            <span>{{ __('Export Overtime Report') }}</span>
                        </x-actions.button>
                    </div>
                </form>
            </x-admin.panel>
        </div>
    </x-admin.page-shell>
</x-app-layout>
