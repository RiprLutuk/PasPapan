<?php

use App\Livewire\Admin\BarcodeComponent;
use App\Models\Barcode;
use App\Models\User;
use Livewire\Livewire;

test('admin barcode component can open delete confirmation and delete barcode', function () {
    $superadmin = User::factory()->admin(true)->create();
    $barcode = Barcode::factory()->create();

    $this->actingAs($superadmin);

    Livewire::test(BarcodeComponent::class)
        ->call('confirmDeletion', $barcode->id)
        ->assertSet('confirmingDeletion', true)
        ->assertSet('deleteName', $barcode->name)
        ->call('delete')
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('barcodes', ['id' => $barcode->id]);
});

