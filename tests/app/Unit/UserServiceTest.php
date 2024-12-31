<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\UserMeta;
use App\Models\UsersBlacklist;
use App\Models\Company;
use App\Models\Department;
use App\Models\Town;
use App\Models\UserLanguages;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    public function testCreateUserCustomerRole()
    {
        $requestData = [
            'role' => env('CUSTOMER_ROLE_ID'),
            'name' => 'User name',
            'company_id' => '',
            'department_id' => '',
            'email' => 'user@example.com',
            'dob_or_orgid' => '1999-01-01',
            'phone' => '123456789',
            'mobile' => '0987654321',
            'consumer_type' => 'paid',
            'customer_type' => 'new',
            'username' => 'user',
            'post_code' => '12345',
            'address' => '123 Main St',
            'city' => 'Lahore',
            'town' => 'Punjab',
            'country' => 'PK',
            'reference' => 'yes',
            'additional_info' => 'Additional info here',
        ];

        $mockCompany = Mockery::mock(Company::class);
        $mockDepartment = Mockery::mock(Department::class);
        $mockUserMeta = Mockery::mock(UserMeta::class);

        $mockCompany->shouldReceive('create')->andReturn(new Company());
        $mockDepartment->shouldReceive('create')->andReturn(new Department());
        $mockUserMeta->shouldReceive('firstOrCreate')->andReturn(new UserMeta());

        $userService = new UserService();

        $user = $userService->createOrUpdate(null, $requestData);

        $this->assertNotNull($user);
        $this->assertEquals('user', $user->name);
        $this->assertEquals('user@example.com', $user->email);
        $this->assertTrue($user->company_id > 0);
        $this->assertTrue($user->department_id > 0);
    }

    public function testUpdateUserTranslatorRole()
    {
        $user = User::factory()->create();
        $requestData = [
            'role' => env('TRANSLATOR_ROLE_ID'),
            'name' => 'User name',
            'email' => 'user@example.com',
            'dob_or_orgid' => '1999-01-01',
            'phone' => '1234567890',
            'mobile' => '0987654321',
            'translator_type' => 'freelance',
            'worked_for' => 'yes',
            'organization_number' => '123456',
            'gender' => 'female',
            'translator_level' => 'senior',
            'post_code' => '12345',
            'address' => '456 Another St',
            'town' => 'Brooklyn',
            'address_2' => 'Apt 1B',
        ];

        $mockUserMeta = Mockery::mock(UserMeta::class);
        $mockUserMeta->shouldReceive('firstOrCreate')->andReturn(new UserMeta());
        $mockUserMeta->shouldReceive('save')->once();

        $userService = new UserService();

        $updatedUser = $userService->createOrUpdate($user->id, $requestData);

        $this->assertNotNull($updatedUser);
        $this->assertEquals('User name', $updatedUser->name);
        $this->assertEquals('user@example.com', $updatedUser->email);
    }

    public function testEnableUser()
    {
        $requestData = ['status' => '1'];

        $user = User::factory()->create(['status' => '0']);

        $userService = Mockery::mock(UserService::class);
        $userService->shouldReceive('enable')->once()->with($user->id);

        $userService->createOrUpdate($user->id, $requestData);

        $this->assertEquals('1', $user->status);
    }

    public function testDisableUser()
    {
        $requestData = ['status' => '0'];

        $user = User::factory()->create(['status' => '1']);

        $userService = Mockery::mock(UserService::class);
        $userService->shouldReceive('disable')->once()->with($user->id);

        $userService->createOrUpdate($user->id, $requestData);

        $this->assertEquals('0', $user->status);
    }


    public function testAssignNewTownToUser()
    {
        $requestData = [
            'new_towns' => 'NewTown',
            'user_towns_projects' => [1, 2]
        ];

        $mockTown = Mockery::mock(Town::class);
        $mockTown->shouldReceive('save')->once();
        $mockTown->id = 999;

        $mockUserTown = Mockery::mock(UserTowns::class);
        $mockUserTown->shouldReceive('save')->once();

        $userService = new UserService();
        $user = $userService->createOrUpdate(null, $requestData);
        $this->assertNotNull($user);
    }
}
