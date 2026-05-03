<?php

use App\Models\Role;
use App\Models\User;

test('document workflow admin pages do not require the enterprise document feature', function () {
    $requestViewer = User::factory()->admin()->create();
    $templateManager = User::factory()->admin()->create();

    $requestRole = Role::create([
        'name' => 'Document Request Viewer',
        'slug' => 'document_request_viewer',
        'description' => 'Can access document requests without an enterprise document feature flag.',
        'permissions' => [
            'admin.document_requests.view',
        ],
    ]);
    $templateRole = Role::create([
        'name' => 'Document Template Manager',
        'slug' => 'document_template_manager',
        'description' => 'Can access document templates without an enterprise document feature flag.',
        'permissions' => [
            'admin.document_requests.templates',
        ],
    ]);

    $requestViewer->roles()->sync([$requestRole->id]);
    $templateManager->roles()->sync([$templateRole->id]);

    $this->actingAs($requestViewer)
        ->get(route('admin.document-requests'))
        ->assertOk();

    $this->actingAs($templateManager)
        ->get(route('admin.document-templates'))
        ->assertOk();
});
