<section class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    <x-ui.page-header
        title="Leave"
        description="Submit leave requests and track recent request status."
    />

    @if (session('leave_status'))
        <div class="mb-4">
            <x-ui.alert variant="success">{{ session('leave_status') }}</x-ui.alert>
        </div>
    @endif

    <div class="grid gap-4 md:grid-cols-3">
        <x-ui.stat-card
            label="Current Month Days"
            :value="number_format($leaveStats['month_days'])"
            hint="{{ now()->format('F Y') }}"
        />
        <x-ui.stat-card
            label="Approved Leave"
            :value="number_format($leaveStats['approved_days'])"
            hint="Approved days in current month"
            variant="warning"
        />
        <x-ui.stat-card
            label="Remaining Month Days"
            :value="number_format($leaveStats['remaining_month_days'])"
            hint="Requests are still allowed after this reaches zero"
            variant="success"
        />
    </div>

    <div class="mt-5 grid gap-5 xl:grid-cols-[minmax(0,1fr)_24rem]">
        <x-ui.card title="Request Leave">
            <form wire:submit="submitLeaveRequest" class="space-y-4">
                <x-ui.select
                    wire:model.live="leaveForm.leave_type_id"
                    label="Leave Type"
                    :options="$leaveTypeOptions"
                    :error="$errors->first('leaveForm.leave_type_id')"
                    required
                />

                @if ($selectedLeaveBalance)
                    <div class="border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                        Balance: <span class="font-medium text-slate-900">{{ $selectedLeaveBalance }}</span>
                    </div>
                @endif

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-ui.input
                        wire:model.live="leaveForm.start_date"
                        type="date"
                        label="From"
                        :error="$errors->first('leaveForm.start_date')"
                        required
                    />

                    <x-ui.input
                        wire:model.live="leaveForm.end_date"
                        type="date"
                        label="To"
                        :error="$errors->first('leaveForm.end_date')"
                        required
                    />
                </div>

                <div class="space-y-2">
                    <div class="flex flex-wrap gap-2">
                        <x-ui.button wire:click="appendMessageToken('**Important:** ')" variant="outline" size="sm">B</x-ui.button>
                        <x-ui.button wire:click="appendMessageToken('_Note:_ ')" variant="outline" size="sm">I</x-ui.button>
                        <x-ui.button wire:click="appendMessageToken('- ')" variant="outline" size="sm">List</x-ui.button>
                    </div>

                    <x-ui.textarea
                        wire:model="leaveForm.reason"
                        label="Message"
                        rows="8"
                        placeholder="Write your leave request message"
                        :error="$errors->first('leaveForm.reason')"
                        required
                    />
                </div>

                <x-ui.input
                    wire:model="leaveForm.contact_during_leave"
                    label="Contact During Leave"
                    :error="$errors->first('leaveForm.contact_during_leave')"
                />

                <div class="space-y-1.5">
                    <label class="text-sm font-medium text-slate-800" for="leave-attachments">Attachments</label>
                    <input
                        id="leave-attachments"
                        wire:model="attachments"
                        type="file"
                        multiple
                        accept="image/png,image/jpeg,application/pdf"
                        class="block w-full border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 shadow-sm outline-none transition file:mr-3 file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-slate-700 focus:border-blue-600 focus:ring-2 focus:ring-blue-100"
                    >
                    <p class="text-xs text-slate-500">Images or PDF, 10 MB per file.</p>
                    @error('attachments.*')
                        <p class="text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                @if ($attachments)
                    <div class="space-y-1 border border-slate-200 bg-slate-50 p-3">
                        @foreach ($attachments as $attachment)
                            <p class="truncate text-xs text-slate-600">{{ $attachment->getClientOriginalName() }}</p>
                        @endforeach
                    </div>
                @endif

                <div class="flex justify-end">
                    <x-ui.button type="submit" variant="primary">Submit Request</x-ui.button>
                </div>
            </form>
        </x-ui.card>

        <x-ui.card title="Recent Requests">
            <div class="space-y-3">
                @forelse ($leaveHistory as $leave)
                    <div class="border border-slate-200 bg-white p-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-medium text-slate-900">{{ $leave->leaveType?->name ?? 'Leave' }}</p>
                                <p class="text-xs text-slate-400">
                                    {{ $leave->start_date?->format('d M Y') }} to {{ $leave->end_date?->format('d M Y') }}
                                </p>
                            </div>
                            <x-ui.badge :variant="$leave->status === 'approved' ? 'success' : ($leave->status === 'rejected' ? 'danger' : 'warning')">
                                {{ str_replace('_', ' ', ucfirst($leave->status)) }}
                            </x-ui.badge>
                        </div>
                        <p class="mt-2 text-xs text-slate-500">{{ number_format((float) $leave->total_days, 2) }} days</p>
                        @if ($leave->documents->isNotEmpty())
                            <p class="mt-1 text-xs text-slate-400">{{ $leave->documents->count() }} attachment(s)</p>
                        @endif
                    </div>
                @empty
                    <x-ui.empty-state
                        title="No leave requests"
                        description="Submitted leave requests will appear here."
                    />
                @endforelse
            </div>
        </x-ui.card>
    </div>
</section>
