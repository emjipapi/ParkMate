<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;
use App\Models\Admin;
use App\Models\User;
use Carbon\Carbon;

class CreateUserTest extends DuskTestCase
{
    use DatabaseMigrations;


    public function testAdminCanCreateNewUser()
    {
        $admin = Admin::factory()->create();


        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin, 'admin')
                    ->visit('/users/create-user')
                    
                    ->pause(5000) // Wait for Livewire to initialize
                    ->screenshot('01-page-loaded')
                    
                    // Wait for the form
                    ->waitFor('form', 20)
                    ->pause(2000)
                    
                    // Check the Student ID checkbox first (required)
                    ->check('input[id="chkStudentId"]')
                    ->pause(500)
                    
                    // Fill in the form fields (using wire:model inputs)
                    ->type('input[wire\\:model="student_id"]', '221009999')
                    ->pause(300)
                    ->type('input[wire\\:model="email"]', 'testuser@example.com')
                    ->pause(300)
                    ->type('input[wire\\:model="password"]', 'password123')
                    ->pause(300)
                    ->type('input[wire\\:model="firstname"]', 'Test')
                    ->pause(300)
                    ->type('input[wire\\:model="middlename"]', 'Middle')
                    ->pause(300)
                    ->type('input[wire\\:model="lastname"]', 'User')
                    ->pause(300)
                    
                    // Fill department, program, year & section (student-only fields)
                    ->select('select[wire\\:model="department"]', 'CCS')
                    ->pause(500)
                    ->select('select[wire\\:model="program"]', 'BSIT')
                    ->pause(500)
                    ->type('input[wire\\:model="year_section"]', '4A')
                    ->pause(300)
                    
                    // Fill vehicle info (first vehicle row)
                    ->type('input[wire\\:model="vehicles.0.serial_number"]', '12345')
                    ->pause(300)
                    ->select('select[wire\\:model="vehicles.0.type"]', 'motorcycle')
                    ->pause(300)
                    ->type('input[wire\\:model="vehicles.0.rfid_tags.0"]', '1234567890')
                    ->pause(300)
                    ->type('input[wire\\:model="vehicles.0.license_plate"]', 'ABC 1234')
                    ->pause(300)
                    ->type('input[wire\\:model="vehicles.0.body_type_model"]', 'Honda CB150')
                    ->pause(300)
                    ->type('input[wire\\:model="vehicles.0.or_number"]', 'OR123456')
                    ->pause(300)
                    ->type('input[wire\\:model="vehicles.0.cr_number"]', 'CR123456')
                    ->pause(300)
                    
                    // Scroll to see remaining fields
                    ->pause(1000)
                    
                    // Fill remaining fields
                    ->type('input[wire\\:model="address"]', '123 Main St')
                    ->pause(300)
                    ->type('input[wire\\:model="contact_number"]', '09123456789')
                    ->pause(300)
                    ->type('input[wire\\:model="license_number"]', 'LIC123456')
                    ->pause(300)
                    
                    // TODO: Fix expiration date field - date picker not registering value
                    // ->date('input[wire\\:model="expiration_date"]', Carbon::now()->addYear()->format('Y-m-d'))
                    // ->pause(500)
                    
                    ->screenshot('02-form-filled')
                    
                    // Submit the form
                    ->press('Add User')
                    
                    ->pause(5000) // Wait for form submission to process
                    ->screenshot('03-after-submit')

                    // The form shows a success flash and stays on the same page (no redirect)
                    ->waitForText('User and vehicles created successfully!', 30)
                    ->assertSee('User and vehicles created successfully!')
                    ->screenshot('04-success-flash');
        });
    }
}