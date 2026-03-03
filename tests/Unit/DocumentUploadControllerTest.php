<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;

use App\Models\User;
use App\Models\Teacher;
use App\Models\Category;
use App\Models\Document;
use App\Notifications\DocumentUploaded;

class DocumentUploadControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_authenticated_user_can_upload_document_for_teacher()
    {
        Storage::fake('public');
        Notification::fake();

        // 1️⃣ Create user (uploader)
        $user = User::factory()->create();

        // 2️⃣ Create teacher linked to a user
        $teacherUser = User::factory()->create();
        $teacher = Teacher::factory()->create([
            'user_id' => $teacherUser->id,
        ]);

        // 3️⃣ Create category
        $category = Category::factory()->create([
            'name' => 'Personal Data Sheet',
        ]);

        // 4️⃣ Fake uploaded file
        $file = UploadedFile::fake()->create(
            'pds.pdf',
            200,
            'application/pdf'
        );

        // 5️⃣ Act as authenticated user
        $response = $this->actingAs($user)->postJson(
            route('documents.upload'), // ⚠️ make sure this route exists
            [
                'files' => [$file],
                'teacher_id' => $teacher->id,
                'category_id' => $category->id,
            ]
        );

        // 6️⃣ Assert response
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'success' => true,
            'duplicate' => false,
        ]);

        // 7️⃣ Assert database record
        $this->assertDatabaseHas('documents', [
            'teacher_id' => $teacher->id,
            'category_id' => $category->id,
            'name' => 'pds.pdf',
        ]);

        // 8️⃣ Assert file stored (encrypted blob exists)
        Storage::disk('public')->assertExists(
            Document::first()->path
        );

        // 9️⃣ Assert notification sent to teacher
        Notification::assertSentTo(
            $teacherUser,
            DocumentUploaded::class
        );
    }
}
