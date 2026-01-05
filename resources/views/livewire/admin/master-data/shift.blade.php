<div>
  <div class="mb-4 flex-col items-center gap-5 sm:flex-row md:flex md:justify-between lg:mr-4">
    <h3 class="mb-4 text-lg font-semibold leading-tight text-gray-800 dark:text-gray-200 md:mb-0">
      Data Shift
    </h3>
    <x-button wire:click="showCreating" class="w-full sm:w-auto justify-center">
      <x-heroicon-o-plus class="mr-2 h-4 w-4" /> Tambah Shift
    </x-button>
  </div>

  <!-- Mobile Card View -->
  <div class="grid grid-cols-1 gap-4 sm:hidden mb-4">
    @foreach ($shifts as $shift)
      <div class="card p-4">
          <div class="flex justify-between items-start mb-3">
              <h4 class="text-base font-bold text-gray-900 dark:text-white">{{ $shift->name }}</h4>
          </div>
          <div class="grid grid-cols-2 gap-4 text-sm mb-3">
             <div>
                 <span class="text-gray-500 block text-xs">Mulai</span>
                 <span class="font-medium dark:text-gray-200">{{ $shift->start_time }}</span>
             </div>
             <div>
                 <span class="text-gray-500 block text-xs">Selesai</span>
                 <span class="font-medium dark:text-gray-200">{{ $shift->end_time ?? '-' }}</span>
             </div>
          </div>
          <div class="grid grid-cols-2 gap-3 pt-3 border-t border-gray-100 dark:border-gray-700">
              <x-secondary-button wire:click="edit({{ $shift->id }})" class="justify-center px-2 py-1">
                  <x-heroicon-o-pencil class="w-4 h-4" />
              </x-secondary-button>
              <x-danger-button wire:click="confirmDeletion({{ $shift->id }}, '{{ $shift->name }}')" class="justify-center px-2 py-1">
                  <x-heroicon-o-trash class="w-4 h-4" />
              </x-danger-button>
          </div>
      </div>
    @endforeach
  </div>

  <div class="hidden sm:block overflow-x-scroll">
    <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
      <thead class="bg-gray-50 dark:bg-gray-900">
        <tr>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
            Shift
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
            {{ __('Time Start') }}
          </th>
          <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300">
            {{ __('Time End') }}
          </th>
          <th scope="col" class="relative px-6 py-3">
            <span class="sr-only">Actions</span>
          </th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
        @foreach ($shifts as $shift)
          <tr>
            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
              {{ $shift->name }}
            </td>
            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
              {{ $shift->start_time }}
            </td>
            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
              {{ $shift->end_time ?? '-' }}
            </td>
            <td class="relative flex justify-end gap-2 px-6 py-4">
              <x-button wire:click="edit({{ $shift->id }})" class="px-2 py-1">
                <x-heroicon-o-pencil class="w-4 h-4" />
              </x-button>
              <x-danger-button wire:click="confirmDeletion({{ $shift->id }}, '{{ $shift->name }}')" class="px-2 py-1">
                <x-heroicon-o-trash class="w-4 h-4" />
              </x-danger-button>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <x-confirmation-modal wire:model="confirmingDeletion">
    <x-slot name="title">
      Hapus Shift
    </x-slot>

    <x-slot name="content">
      Apakah Anda yakin ingin menghapus <b>{{ $deleteName }}</b>?
    </x-slot>

    <x-slot name="footer">
      <x-secondary-button wire:click="$toggle('confirmingDeletion')" wire:loading.attr="disabled">
        {{ __('Cancel') }}
      </x-secondary-button>

      <x-danger-button class="ml-2" wire:click="delete" wire:loading.attr="disabled">
        {{ __('Confirm') }}
      </x-danger-button>
    </x-slot>
  </x-confirmation-modal>

  <x-dialog-modal wire:model="creating">
    <x-slot name="title">
      Shift Baru
    </x-slot>

    <x-slot name="content">
      <form wire:submit="create">
        <div>
          <x-label for="create_name">Nama Shift</x-label>
          <x-input id="create_name" class="mt-1 block w-full" type="text" wire:model="form.name" autocomplete="off" />
          @error('form.name')
            <x-input-error for="form.name" class="mt-2" message="{{ $message }}" />
          @enderror
        </div>
        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:gap-3">
          <div class="w-full">
            <x-label for="create_start_time">{{ __('Time Start') }}</x-label>
            <x-input id="create_start_time" class="mt-1 block w-full" type="time" wire:model="form.start_time" required autocomplete="off" />
            @error('form.start_time')
              <x-input-error for="form.start_time" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
          <div class="w-full">
            <x-label for="create_end_time">{{ __('Time End') }}</x-label>
            <x-input id="create_end_time" class="mt-1 block w-full" type="time" wire:model="form.end_time" autocomplete="off" />
            @error('form.end_time')
              <x-input-error for="form.end_time" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
        </div>
      </form>
    </x-slot>

    <x-slot name="footer">
      <x-secondary-button wire:click="$toggle('creating')" wire:loading.attr="disabled">
        {{ __('Cancel') }}
      </x-secondary-button>

      <x-button class="ml-2" wire:click="create" wire:loading.attr="disabled">
        {{ __('Confirm') }}
      </x-button>
    </x-slot>
  </x-dialog-modal>

  <x-dialog-modal wire:model="editing">
    <x-slot name="title">
      Edit Shift
    </x-slot>

    <x-slot name="content">
      <form wire:submit.prevent="update" id="shift-edit">
        <div>
          <x-label for="edit_name">Nama Shift</x-label>
          <x-input id="edit_name" class="mt-1 block w-full" type="text" wire:model="form.name" autocomplete="off" />
          @error('form.name')
            <x-input-error for="form.name" class="mt-2" message="{{ $message }}" />
          @enderror
        </div>
        <div class="mt-4 flex flex-col gap-4 sm:flex-row sm:gap-3">
          <div class="w-full">
            <x-label for="edit_start_time">{{ __('Time Start') }}</x-label>
            <x-input id="edit_start_time" class="mt-1 block w-full" type="time" wire:model="form.start_time" required autocomplete="off" />
            @error('form.start_time')
              <x-input-error for="form.start_time" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
          <div class="w-full">
            <x-label for="edit_end_time">{{ __('Time End') }}</x-label>
            <x-input id="edit_end_time" class="mt-1 block w-full" type="time" wire:model="form.end_time" autocomplete="off" />
            @error('form.end_time')
              <x-input-error for="form.end_time" class="mt-2" message="{{ $message }}" />
            @enderror
          </div>
        </div>
      </form>
    </x-slot>

    <x-slot name="footer">
      <x-secondary-button wire:click="$toggle('editing')" wire:loading.attr="disabled">
        {{ __('Cancel') }}
      </x-secondary-button>

      <x-button class="ml-2" wire:click="update" wire:loading.attr="disabled">
        {{ __('Confirm') }}
      </x-button>
    </x-slot>
  </x-dialog-modal>
</div>
