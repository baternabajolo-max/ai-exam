<?php

namespace Tests\Feature;

use App\Models\Achievement;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AccountManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_student_profile_route_renders_the_student_profile_view(): void
    {
        $student = User::factory()->create([
            'name' => 'Taylor',
            'role' => 'student',
        ]);

        $this->actingAs($student)
            ->get(route('student.profile'))
            ->assertViewIs('student.profile.index')
            ->assertSee('id="student-profile-edit-toggle"', false)
            ->assertSee('aria-expanded="false"', false)
            ->assertSee('data-file-input="student-profile-picture"', false)
            ->assertSee('data-file-input="student-background-picture"', false)
            ->assertSee('id="student-profile-picture-form"', false)
            ->assertSee('id="student-background-picture-form"', false)
            ->assertSee('Surname (optional)')
            ->assertSee('id="student-last-name" name="last_name" value="" autocomplete="family-name"', false)
            ->assertDontSee('Edit Profile')
            ->assertDontSee('editProfileModal');
    }

    public function test_student_can_update_their_profile_and_featured_badge(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $achievement = Achievement::create([
            'title' => 'Award',
            'description' => 'A claimed award',
            'icon' => 'bi-award',
        ]);
        $student->achievements()->attach($achievement->id, ['claimed_at' => now()]);

        $this->actingAs($student)
            ->put(route('student.profile.update'), [
                'first_name' => 'Updated',
                'email' => 'updated@example.test',
                'badge_icon' => 'bi-award',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'name' => 'Updated',
            'email' => $student->email,
            'badge_icon' => 'bi-award',
        ]);
    }

    public function test_super_admin_profile_has_its_own_role_specific_settings(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super-admin']);

        $this->actingAs($superAdmin)
            ->get(route('super-admin.profile'))
            ->assertViewIs('admin.profile.index')
            ->assertSee('id="profileMainContainer"', false)
            ->assertSee('Admin Overview')
            ->assertSee('Institution-wide admin access')
            ->assertSee('All colleges and platform settings')
            ->assertSee(route('super-admin.profile.update'), false)
            ->assertSee(route('super-admin.profile.password.update'), false)
            ->assertSee(route('super-admin.users.index'))
            ->assertSee(route('super-admin.departments.index'))
            ->assertSee('>Admin</span>', false)
            ->assertDontSee('Super Admin')
            ->assertSee('Settings')
            ->assertSee('Immutable')
            ->assertDontSee('Assigned Department Scope');
        $this->assertDatabaseHas('users', [
            'id' => $superAdmin->id,
            'role' => 'super-admin',
        ]);
    }

    public function test_super_admin_can_change_their_name_but_not_account_email(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super-admin']);

        $this->actingAs($superAdmin)
            ->put(route('super-admin.profile.update'), [
                'first_name' => 'System',
                'email' => 'changed@example.test',
                'current_password' => 'password',
            ])->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $superAdmin->id,
            'name' => 'System',
            'email' => $superAdmin->email,
        ]);
    }

    public function test_department_admin_profile_keeps_email_read_only_and_shows_its_scope(): void
    {
        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $admin = User::factory()->create([
            'role' => 'admin',
            'department_id' => $department->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.profile'))
            ->assertViewIs('admin.profile.index')
            ->assertSee('>Faculty</span>', false)
            ->assertSee('College-scoped faculty access')
            ->assertSee('placeholder="Last name (optional)"', false)
            ->assertSee('Immutable')
            ->assertDontSee('Edit Email');
        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'role' => 'admin',
        ]);
    }

    public function test_admin_profile_cannot_change_its_email(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->put(route('admin.profile.update'), [
                'first_name' => 'Department',
                'email' => 'changed@example.test',
                'current_password' => 'password',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'name' => 'Department',
            'email' => $admin->email,
        ]);
    }

    public function test_admin_can_change_password_without_numbers(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->put(route('admin.profile.password.update'), [
                'current_password' => 'password',
                'password' => 'NewPassword!',
                'password_confirmation' => 'NewPassword!',
            ])
            ->assertSessionHas('success', 'Password changed successfully.');

        $this->assertTrue(Hash::check('NewPassword!', $admin->fresh()->password));
    }

    public function test_student_can_upload_profile_and_background_pictures(): void
    {
        Storage::fake('public');
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)
            ->put(route('student.profile.update'), [
                'name' => $student->name,
                'email' => $student->email,
                'profile_picture' => UploadedFile::fake()->image('student-profile.png'),
                'background_picture' => UploadedFile::fake()->image('student-background.jpg'),
            ])
            ->assertSessionHas('success');

        $student->refresh();

        Storage::disk('public')->assertExists($student->profile_picture_path);
        Storage::disk('public')->assertExists($student->background_picture_path);
    }

    public function test_student_profile_picture_is_shown_in_the_navigation_and_leaderboard(): void
    {
        Storage::fake('public');
        $department = Department::create(['name' => 'Science', 'code' => 'SCI']);
        $student = User::factory()->create([
            'role' => 'student',
            'department_id' => $department->id,
            'total_points' => 30,
            'profile_picture_path' => 'profile-images/student-avatar.png',
        ]);
        Storage::disk('public')->put($student->profile_picture_path, 'image contents');
        $profilePictureUrl = asset('storage/'.$student->profile_picture_path);

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertSee($profilePictureUrl, false);

        $this->actingAs($student)
            ->get(route('student.leaderboard'))
            ->assertSee($profilePictureUrl, false);
    }

    public function test_student_cannot_feature_an_unclaimed_badge(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        Achievement::create([
            'title' => 'Award',
            'description' => 'An unclaimed award',
            'icon' => 'bi-award',
        ]);

        $this->actingAs($student)
            ->put(route('student.profile.update'), [
                'name' => $student->name,
                'email' => $student->email,
                'badge_icon' => 'bi-award',
            ])
            ->assertSessionHasErrors('badge_icon');

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'badge_icon' => null,
        ]);
    }

    public function test_admin_can_upload_profile_and_background_pictures(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->put(route('admin.profile.update'), [
                'name' => $admin->name,
                'email' => $admin->email,
                'profile_picture' => UploadedFile::fake()->image('profile.png'),
                'background_picture' => UploadedFile::fake()->image('background.jpg'),
            ])
            ->assertSessionHas('success');

        $admin->refresh();

        Storage::disk('public')->assertExists($admin->profile_picture_path);
        Storage::disk('public')->assertExists($admin->background_picture_path);
    }
}
