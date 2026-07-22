<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceCalculationsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Sarah Staff',
            'email' => 'sarah@aiwa.com',
            'password' => bcrypt('password'),
            'role' => 'staff',
        ]);

        $this->client = Client::create([
            'name' => 'Demo Client',
            'company_name' => 'Demo Comp',
            'contact_person' => 'Jane Doe',
            'mobile' => '+971500000000',
            'email' => 'jane@demo.com',
            'assigned_manager_id' => $this->user->id,
            'status' => 'active',
        ]);
    }

    /**
     * Test basic VAT and Total calculations on Service save.
     */
    public function test_service_auto_calculates_vat_and_totals(): void
    {
        $service = Service::create([
            'client_id' => $this->client->id,
            'type' => 'SEO',
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'renewal_date' => now()->addYear(),
            'service_value' => 1000.00,
            'vat_percent' => 5.00,
            'vat_paid_status' => 'pending',
            'service_status' => 'active',
        ]);

        // Assert calculation accuracy
        $this->assertEquals(50.00, $service->vat_amount);
        $this->assertEquals(1050.00, $service->total_amount);
        $this->assertEquals(0.00, $service->paid_amount);
        $this->assertEquals(1050.00, $service->balance_amount);
        $this->assertEquals('unpaid', $service->payment_status);
    }

    /**
     * Test parent Service financials update when logging payments.
     */
    public function test_payments_auto_update_service_totals_and_status(): void
    {
        $service = Service::create([
            'client_id' => $this->client->id,
            'type' => 'Google Ads',
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'renewal_date' => now()->addYear(),
            'service_value' => 2000.00,
            'vat_percent' => 5.00,
            'vat_paid_status' => 'pending',
            'service_status' => 'active',
        ]);

        // Total = 2100.00. Log a partial payment of 500.00
        $payment1 = Payment::create([
            'service_id' => $service->id,
            'amount' => 500.00,
            'payment_date' => now(),
            'payment_method' => 'Bank Transfer',
        ]);

        // Refresh service to read fresh calculations from DB
        $service->refresh();

        $this->assertEquals(500.00, $service->paid_amount);
        $this->assertEquals(1600.00, $service->balance_amount);
        $this->assertEquals('partially_paid', $service->payment_status);

        // Log final payment of 1600.00
        $payment2 = Payment::create([
            'service_id' => $service->id,
            'amount' => 1600.00,
            'payment_date' => now(),
            'payment_method' => 'Online',
        ]);

        $service->refresh();

        $this->assertEquals(2100.00, $service->paid_amount);
        $this->assertEquals(0.00, $service->balance_amount);
        $this->assertEquals('paid', $service->payment_status);

        // Test deletion of payment
        $payment2->delete();

        $service->refresh();

        $this->assertEquals(500.00, $service->paid_amount);
        $this->assertEquals(1600.00, $service->balance_amount);
        $this->assertEquals('partially_paid', $service->payment_status);
    }

    /**
     * Test basic VAT and Total calculations on Invoice save.
     */
    public function test_invoice_auto_calculates_vat_and_totals(): void
    {
        $invoice = \App\Models\Invoice::create([
            'client_id' => $this->client->id,
            'invoice_date' => now(),
            'due_date' => now()->addDays(14),
            'amount' => 5000.00,
            'vat_percent' => 5.00,
            'payment_terms' => 'One-Time',
            'status' => 'Draft',
        ]);

        $this->assertEquals(250.00, $invoice->vat_amount);
        $this->assertEquals(5250.00, $invoice->total_amount);
        $this->assertNotEmpty($invoice->invoice_number);
    }

    /**
     * Test parent Invoice status updates when logging payments.
     */
    public function test_payments_auto_update_invoice_status(): void
    {
        $invoice = \App\Models\Invoice::create([
            'client_id' => $this->client->id,
            'invoice_date' => now(),
            'due_date' => now()->addDays(14),
            'amount' => 1000.00,
            'vat_percent' => 5.00,
            'payment_terms' => 'One-Time',
            'status' => 'Sent',
        ]);

        // Total = 1050.00. Log a partial payment of 400.00
        $payment1 = Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => 400.00,
            'payment_date' => now(),
            'payment_method' => 'Bank Transfer',
        ]);

        $invoice->refresh();
        $this->assertEquals('Partially Paid', $invoice->status);

        // Log final payment of 650.00
        $payment2 = Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => 650.00,
            'payment_date' => now(),
            'payment_method' => 'Online',
        ]);

        $invoice->refresh();
        $this->assertEquals('Paid', $invoice->status);

        // Delete payment
        $payment2->delete();

        $invoice->refresh();
        $this->assertEquals('Partially Paid', $invoice->status);
    }
}
