<?php

namespace Tests\Unit\Traits;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HasRolesTraitTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_user_can_be_assigned_a_role()
    {
        $user = factory(User::class)->create();
        $role = factory(Role::class)->create();

        $user->assignRole($role);

        $this->assertTrue($user->hasRole($role));
    }

    /** @test */
    public function a_user_with_a_role_has_a_given_permission()
    {
        $user = factory(User::class)->create();
        $role = factory(Role::class)->create();
        $permission = factory(Permission::class)->create();

        $user->assignRole($role);
        $role->givePermissionTo($permission);

        $this->assertTrue($user->hasPermission($permission->slug));
    }
}