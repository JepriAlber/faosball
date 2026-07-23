<?php

namespace Tests\Feature;

use App\Models\Academy;
use App\Models\Document;
use App\Models\EmploymentType;
use App\Models\Player;
use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffPosition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    use RefreshDatabase;

    protected function actingAsOwner(Academy $academy): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['staff.view', 'staff.update', 'player.view', 'player.update'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $role = Role::create(['id_academy' => $academy->id_academy, 'name' => 'Owner', 'guard_name' => 'web']);
        $role->syncPermissions(Permission::whereIn('name', ['staff.view', 'staff.update', 'player.view', 'player.update'])->get());

        $owner = User::factory()->create(['id_academy' => $academy->id_academy, 'status' => true]);
        $owner->assignRole($role);

        $this->actingAs($owner);

        return $owner;
    }

    /**
     * Player TIDAK punya factory di codebase ini -- dibuat langsung lewat
     * Player::create() dengan field minimal yang NOT NULL, pola sama
     * yang sudah dipakai PlayerTypeTest/PlayerCategoryTest.
     */
    protected function makePlayer(Academy $academy): Player
    {
        return Player::create([
            'id_academy' => $academy->id_academy,
            'player_code' => 'TEST' . random_int(10000, 99999),
            'name' => 'Test Player',
            'birth_date' => '2010-01-01',
            'gender' => 'male',
        ]);
    }

    public function test_upload_dokumen_staff_tersimpan_di_disk_privat(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $academy = Academy::factory()->create();
        $employmentType = EmploymentType::factory()->create(['id_academy' => $academy->id_academy]);
        $staffPosition = StaffPosition::factory()->create(['id_academy' => $academy->id_academy]);
        $staff = Staff::factory()->create(['id_academy' => $academy->id_academy]);

        $this->actingAsOwner($academy);

        $file = UploadedFile::fake()->create('ijazah.pdf', 100, 'application/pdf');

        $this->post(route('staff.documents.store', $staff), [
            'type' => 'ijazah',
            'file' => $file,
        ])->assertRedirect();

        $document = Document::first();

        $this->assertNotNull($document);
        $this->assertSame(Staff::class, $document->documentable_type);
        $this->assertSame($staff->id_staff, $document->documentable_id);

        Storage::disk('local')->assertExists($document->path);
        Storage::disk('public')->assertMissing($document->path);
    }

    public function test_player_bisa_upload_dokumen_akte(): void
    {
        Storage::fake('local');

        $academy = Academy::factory()->create();
        $player = $this->makePlayer($academy);

        $this->actingAsOwner($academy);

        $file = UploadedFile::fake()->create('akte.pdf', 100, 'application/pdf');

        $this->post(route('players.documents.store', $player), [
            'type' => 'akte',
            'file' => $file,
        ])->assertRedirect();

        $document = Document::first();

        $this->assertNotNull($document);
        $this->assertSame(Player::class, $document->documentable_type);
    }

    public function test_lihat_dokumen_ditolak_untuk_user_tanpa_permission_view(): void
    {
        Storage::fake('local');

        $academy = Academy::factory()->create();
        $employmentType = EmploymentType::factory()->create(['id_academy' => $academy->id_academy]);
        $staffPosition = StaffPosition::factory()->create(['id_academy' => $academy->id_academy]);
        $staff = Staff::factory()->create(['id_academy' => $academy->id_academy]);

        $document = Document::factory()->create([
            'id_academy' => $academy->id_academy,
            'documentable_type' => Staff::class,
            'documentable_id' => $staff->id_staff,
        ]);

        $user = User::factory()->create(['id_academy' => $academy->id_academy, 'status' => true]);

        $this->actingAs($user)->get(route('documents.show', $document))->assertForbidden();
    }

    public function test_dokumen_academy_lain_tidak_ditemukan_404(): void
    {
        Storage::fake('local');

        $academyA = Academy::factory()->create();
        $academyB = Academy::factory()->create();

        $staffB = Staff::factory()->create(['id_academy' => $academyB->id_academy]);

        $documentB = Document::factory()->create([
            'id_academy' => $academyB->id_academy,
            'documentable_type' => Staff::class,
            'documentable_id' => $staffB->id_staff,
        ]);

        $this->actingAsOwner($academyA);

        $this->get(route('documents.show', $documentB))->assertNotFound();
    }

    public function test_hapus_dokumen_menghapus_file_fisik_dan_row_database(): void
    {
        Storage::fake('local');

        $academy = Academy::factory()->create();
        $employmentType = EmploymentType::factory()->create(['id_academy' => $academy->id_academy]);
        $staffPosition = StaffPosition::factory()->create(['id_academy' => $academy->id_academy]);
        $staff = Staff::factory()->create(['id_academy' => $academy->id_academy]);

        $this->actingAsOwner($academy);

        $file = UploadedFile::fake()->create('ijazah.pdf', 100, 'application/pdf');

        $this->post(route('staff.documents.store', $staff), ['type' => 'ijazah', 'file' => $file]);

        $document = Document::first();
        $path = $document->path;

        $this->delete(route('documents.destroy', $document))->assertRedirect();

        $this->assertNull(Document::find($document->id_document));
        Storage::disk('local')->assertMissing($path);
    }
}
