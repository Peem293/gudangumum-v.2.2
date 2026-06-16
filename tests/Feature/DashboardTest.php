<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Unit;
use App\Models\User;
use App\Models\Request;
use App\Models\PurchaseOrder;
use App\Models\Item;
use App\Models\Pajak;
use App\Models\Supplier;
use App\Filament\Pages\Dashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles
        Role::firstOrCreate(['name' => 'administrator', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin_gudang', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'staf_unit', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'manager_keuangan', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'direktur', 'guard_name' => 'web']);
    }

    public function test_guest_cannot_access_dashboard()
    {
        $response = $this->get('/admin/dashboard');
        $response->assertRedirect('/admin/login');
    }

    public function test_authenticated_user_can_access_dashboard()
    {
        $user = User::factory()->create();
        $user->assignRole('administrator');

        $response = $this->actingAs($user)->get('/admin/dashboard');
        $response->assertStatus(200);
    }

    public function test_dashboard_scoped_summary_data()
    {
        // Create departments & units
        $dept1 = Department::create(['code' => 'HRD', 'name' => 'Human Resources']);
        $unit1 = Unit::create(['department_id' => $dept1->id, 'name' => 'Recruitment']);

        $dept2 = Department::create(['code' => 'ITD', 'name' => 'Information Technology']);
        $unit2 = Unit::create(['department_id' => $dept2->id, 'name' => 'Infrastructure']);

        // Create Users
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
        ]);
        $admin->assignRole('administrator');

        $staff = User::create([
            'name' => 'Staff User',
            'email' => 'staff@test.com',
            'password' => bcrypt('password'),
            'department_id' => $dept1->id,
            'unit_id' => $unit1->id,
        ]);
        $staff->assignRole('staf_unit');

        $manager = User::create([
            'name' => 'Manager User',
            'email' => 'manager@test.com',
            'password' => bcrypt('password'),
            'department_id' => $dept1->id,
        ]);
        $manager->assignRole('manager');

        // Create Items, Pajak, Supplier for PO
        $item = Item::create([
            'code' => 'ITM-001',
            'name' => 'Test Item',
            'price' => 5000,
            'stock' => 15,
            'unit' => 'Pcs',
        ]);
        $pajak = Pajak::create(['name' => 'PPN 11%', 'ppn' => 11]);
        $supplier = Supplier::create(['name' => 'Test Vendor', 'phone' => '12345', 'address' => 'Test Rd']);

        // Create Requests
        // Request in unit 1 (HRD) - completed
        Request::create([
            'request_number' => 'REQ-001',
            'user_id' => $staff->id,
            'department_id' => $dept1->id,
            'unit_id' => $unit1->id,
            'status' => 'completed',
        ]);

        // Request in unit 1 (HRD) - pending
        Request::create([
            'request_number' => 'REQ-002',
            'user_id' => $staff->id,
            'department_id' => $dept1->id,
            'unit_id' => $unit1->id,
            'status' => 'pending',
        ]);

        // Request in unit 2 (ITD) - completed
        $staff2 = User::create([
            'name' => 'Staff IT',
            'email' => 'staffit@test.com',
            'password' => bcrypt('password'),
            'department_id' => $dept2->id,
            'unit_id' => $unit2->id,
        ]);
        $staff2->assignRole('staf_unit');

        Request::create([
            'request_number' => 'REQ-003',
            'user_id' => $staff2->id,
            'department_id' => $dept2->id,
            'unit_id' => $unit2->id,
            'status' => 'completed',
        ]);

        // Create POs
        PurchaseOrder::create([
            'po_number' => 'PO-001',
            'supplier_id' => $supplier->id,
            'user_id' => $admin->id,
            'pajak_id' => $pajak->id,
            'po_date' => now(),
            'status' => 'received',
        ]);

        // 1. Assert counts for Administrator (Global)
        $this->actingAs($admin);
        $dashboardPage = new Dashboard();
        $adminSummary = $dashboardPage->getSummaryData();

        $this->assertEquals(2, $adminSummary['requestCompleted']); // REQ-001 & REQ-003
        $this->assertEquals(1, $adminSummary['requestOnProcess']); // REQ-002
        $this->assertTrue($adminSummary['hasPurchasing']);
        $this->assertEquals(1, $adminSummary['purchaseCompleted']); // PO-001

        // 2. Assert counts for Staf Unit (HRD - Recruitment unit)
        $this->actingAs($staff);
        $staffSummary = $dashboardPage->getSummaryData();

        $this->assertEquals(1, $staffSummary['requestCompleted']); // Only REQ-001
        $this->assertEquals(1, $staffSummary['requestOnProcess']); // Only REQ-002
        $this->assertFalse($staffSummary['hasPurchasing']);
        $this->assertEquals(0, $staffSummary['purchaseCompleted']);

        // 3. Assert counts for Manager (HRD department)
        $this->actingAs($manager);
        $managerSummary = $dashboardPage->getSummaryData();

        $this->assertEquals(1, $managerSummary['requestCompleted']); // Only REQ-001 (from HRD)
        $this->assertEquals(1, $managerSummary['requestOnProcess']); // Only REQ-002 (from HRD)
        $this->assertFalse($managerSummary['hasPurchasing']);
    }
}
