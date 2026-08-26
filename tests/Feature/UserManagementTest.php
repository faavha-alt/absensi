<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_admin_baru_bisa_dibuat_dengan_password_ter_hash(): void
    {
        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Admin Kedua',
                'email' => 'admin2@example.com',
                'password' => 'rahasia123',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::where('email', 'admin2@example.com')->first();

        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('rahasia123', $user->password));
    }

    public function test_edit_tanpa_isi_password_tidak_mengubah_password_lama(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password-lama')]);

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm([
                'name' => 'Nama Baru',
                'email' => $user->email,
                'password' => '',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();

        $this->assertSame('Nama Baru', $user->name);
        $this->assertTrue(Hash::check('password-lama', $user->password));
    }

    public function test_tidak_bisa_menghapus_akun_sendiri(): void
    {
        $self = auth()->user();

        $this->assertFalse($self->can('delete', $self));
    }

    public function test_bisa_menghapus_akun_admin_lain(): void
    {
        $other = User::factory()->create();

        $this->assertTrue(auth()->user()->can('delete', $other));
    }

    public function test_edit_dengan_isi_password_mengganti_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password-lama')]);

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm([
                'name' => $user->name,
                'email' => $user->email,
                'password' => 'password-baru123',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();

        $this->assertTrue(Hash::check('password-baru123', $user->password));
    }
}
