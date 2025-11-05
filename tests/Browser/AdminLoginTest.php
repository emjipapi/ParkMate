<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use App\Models\Admin;

class AdminLoginTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function testAdminCanLoginSuccessfully()
    {
        // Create admin with test_admin username (factory now has permissions)
        $admin = Admin::factory()->create([
            'username' => 'test_admin',
        ]);

        $this->browse(function (Browser $browser) {
            $browser->visit('/admin/login')
                    ->waitFor('input[name="username"]', 10)
                    ->pause(1000)
                    
                    ->type('username', 'test_admin')
                    ->type('password', 'password')
                    ->press('Sign In')
                    
                    ->pause(2000)
                    
                    // Should redirect to admin-dashboard since admin has permissions
                    ->waitForLocation('/admin-dashboard', 15)
                    ->assertPathIs('/admin-dashboard');
        });
    }
}