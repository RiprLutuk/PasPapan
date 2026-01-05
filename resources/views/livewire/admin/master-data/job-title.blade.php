<div>
  <div class="mb-4 flex-col items-center gap-5 sm:flex-row md:flex md:justify-between lg:mr-4">
    <h3 class="mb-4 text-lg font-semibold leading-tight text-gray-800 dark:text-gray-200 md:mb-0">
      Data Jabatan
    </h3>
    <x-button wire:click="showCreating" class="w-full sm:w-auto justify-center">
      <x-heroicon-o-plus class="mr-2 h-4 w-4" /> Tambah Jabatan
    </x-button>
  </div>

  <!-- Mobile Card View -->
  <div class="grid grid-cols-1 gap-4 sm:hidden mb-4">
    @foreach ($jobTitles as $jobTitle)
      <div class="card p-4">
          <div class="flex justify-between items-start mb-3">
              <h4 class="text-base font-bold text-gray-900 dark:text-white">{{ $jobTitle->name }}</h4>
          </div>
          <div class="grid grid-cols-2 gap-3 pt-3 border-t border-gray-100 dark:border-gray-700">
              <x-secondary-button wire:click="edit({{ $jobTitle->id }})" class="justify-center px-2 py-1">
                  <x-heroicon-o-pencil class="w-4 h-4" />
              </x-secondary-button>
              <x-danger-button wire:click="confirmDeletion({{ $jobTitle->id }}, '{{ $jobTitle->name }}')" class="justify-center px-2 py-1">
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
            Jabatan
          </th>
          <th scope="col" class="relative px-6 py-3">
            <span class="sr-only">Actions</span>
          </th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
        @foreach ($jobTitles as $jobTitle)
          <tr>
            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
              {{ $jobTitle->name }}
            </td>
            <td class="relative flex justify-end gap-2 px-6 py-4">
              <x-button wire:click="edit({{ $jobTitle->id }})" class="px-2 py-1">
                <x-heroicon-o-pencil class="w-4 h-4" />
              </x-button>
              <x-danger-button wire:click="confirmDeletion({{ $jobTitle->id }}, '{{ $jobTitle->name }}')" class="px-2 py-1">
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
      Hapus Jabatan
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
      Jabatan Baru
    </x-slot>

    <x-slot name="content">
      <form wire:submit="create">
        <x-label for="create_name">Nama Jabatan</x-label>
        <x-input id="create_name" class="mt-1 block w-full" type="text" wire:model="name" autocomplete="off" />
        @error('name')
          <x-input-error for="create_name" class="mt-2" message="{{ $message }}" />
        @enderror
      </form>
    </x-slot>

    <x-slot name="footer">
      <x-secondary-button wire:click="$toggle('creating')" wire:loading.attr="disabled">
        {{ __('Cancel') }}
      </x-secondary-button>

      <x-button class="ml-2" wire:click="create" wire:loading.attr="disabled">
        {{ __('Add') }}
      </x-button>
    </x-slot>
  </x-dialog-modal>

  <x-dialog-modal wire:model="editing">
    <x-slot name="title">
      Edit Jabatan
    </x-slot>

    <x-slot name="content">
      <form wire:submit.prevent="update">
        <x-label for="edit_name">Nama Jabatan</x-label>
        <x-input id="edit_name" class="mt-1 block w-full" type="text" wire:model="name" autocomplete="off" />
        @error('name')
          <x-input-error for="edit_name" class="mt-2" message="{{ $message }}" />
        @enderror
      </form>
    </x-slot>

    <x-slot name="footer">
      <x-secondary-button wire:click="$toggle('editing')" wire:loading.attr="disabled">
        {{ __('Cancel') }}
      </x-secondary-button>

      <x-button class="ml-2" wire:click="update" wire:loading.attr="disabled">
        {{ __('Save') }}
      </x-button>
    </x-slot>
  </x-dialog-modal>
</div>
