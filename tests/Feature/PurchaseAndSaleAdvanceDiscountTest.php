<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SupplierAdvance;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PurchaseAndSaleAdvanceDiscountTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_purchase_item_percentage_discount_calculation_and_supplier_advance_deduction(): void
    {
        // 1. Setup Supplier with Advance
        $supplierName = 'Test Pharma Ltd ' . uniqid();
        SupplierAdvance::create([
            'supplier_name' => $supplierName,
            'advance_balance' => 5000.00,
        ]);

        // 2. Setup Product
        $product = Product::create([
            'name' => 'Cotton 200MG ' . uniqid(),
            'code' => 'COT-' . uniqid(),
            'purchase_price' => 250.00,
            'sale_price' => 300.00,
            'stock' => 10,
        ]);

        // 3. Post Add Purchase
        // Qty: 50, Unit Price: 272, Discount: 24%
        // Expected subtotal: 206.72 * 50 = 10,336.00
        // Available advance: 5,000.00
        // Net payable: 10,336 - 5,000 = 5,336.00
        $response = $this->actingAs($this->user)->post(route('purchases.store'), [
            'supplier_name' => $supplierName,
            'purchase_date' => now()->toDateString(),
            'paid_amount' => 5336.00,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 50,
                    'price' => 272.00,
                    'discount_percent' => 24.0,
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('purchases.index'));

        // 4. Assert Purchase Record
        $purchase = Purchase::where('supplier_name', $supplierName)->latest('id')->first();
        $this->assertNotNull($purchase);
        $this->assertEquals(13600.00, (float) $purchase->sub_total);
        $this->assertEquals(3264.00, (float) $purchase->discount);
        $this->assertEquals(10336.00, (float) $purchase->total);
        $this->assertEquals(5000.00, (float) $purchase->advance_adjusted);
        $this->assertEquals(10336.00, (float) $purchase->paid_amount); // 5000 advance + 5336 paid
        $this->assertEquals('paid', $purchase->payment_status);

        // 5. Assert Purchase Item Record
        $item = $purchase->items->first();
        $this->assertNotNull($item);
        $this->assertEquals(24.00, (float) $item->discount_percent);
        $this->assertEquals(3264.00, (float) $item->discount_amount);
        $this->assertEquals(10336.00, (float) $item->total);
        $this->assertEquals(65.28, (float) $item->discount_per_unit);
        $this->assertEquals(206.72, (float) $item->discounted_price);

        // 6. Assert Supplier Advance Balance is now 0
        $advance = SupplierAdvance::where('supplier_name', $supplierName)->first();
        $this->assertEquals(0.00, (float) $advance->advance_balance);

        // 7. Assert Stock Updated
        $product->refresh();
        $this->assertEquals(60, $product->stock); // 10 + 50
    }

    public function test_purchase_decimal_percentage_discounts(): void
    {
        $supplierName = 'Decimal Test Supplier ' . uniqid();
        $productA = Product::create([
            'name' => 'Prod A ' . uniqid(),
            'code' => 'PA-' . uniqid(),
            'purchase_price' => 100.00,
            'sale_price' => 150.00,
            'stock' => 5,
        ]);
        $productB = Product::create([
            'name' => 'Prod B ' . uniqid(),
            'code' => 'PB-' . uniqid(),
            'purchase_price' => 200.00,
            'sale_price' => 250.00,
            'stock' => 5,
        ]);

        // Product A: 100 units @ 100, 21.5% discount -> disc/unit: 21.5, discounted price: 78.50, total: 7850.00
        // Product B: 40 units @ 200, 10.25% discount -> disc/unit: 20.5, discounted price: 179.50, total: 7180.00
        $response = $this->actingAs($this->user)->post(route('purchases.store'), [
            'supplier_name' => $supplierName,
            'purchase_date' => now()->toDateString(),
            'paid_amount' => 15030.00,
            'items' => [
                [
                    'product_id' => $productA->id,
                    'quantity' => 100,
                    'price' => 100.00,
                    'discount_percent' => 21.5,
                ],
                [
                    'product_id' => $productB->id,
                    'quantity' => 40,
                    'price' => 200.00,
                    'discount_percent' => 10.25,
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('purchases.index'));

        $purchase = Purchase::where('supplier_name', $supplierName)->latest('id')->first();
        $this->assertNotNull($purchase);
        $this->assertEquals(18000.00, (float) $purchase->sub_total); // 10000 + 8000
        $this->assertEquals(2970.00, (float) $purchase->discount); // 2150 + 820
        $this->assertEquals(15030.00, (float) $purchase->total); // 7850 + 7180
    }

    public function test_customer_advance_search_and_deduction_on_pos_sale(): void
    {
        // 1. Create customer with advance balance
        $customer = Customer::create([
            'name' => 'Advance Test Customer ' . uniqid(),
            'phone' => '0300' . rand(1000000, 9999999),
            'advance_balance' => 2000.00,
        ]);

        // 2. Verify search customer returns advance_balance
        $searchRes = $this->actingAs($this->user)->getJson(route('pos.customers.search', ['query' => $customer->phone]));
        $searchRes->assertStatus(200);
        $searchRes->assertJsonFragment([
            'id' => $customer->id,
            'advance_balance' => 2000,
        ]);

        // 3. Create product
        $product = Product::create([
            'name' => 'POS Item ' . uniqid(),
            'code' => 'PI-' . uniqid(),
            'purchase_price' => 300.00,
            'sale_price' => 500.00,
            'stock' => 50,
        ]);

        // 4. POS Sale where Total is 1,500 (3 units @ 500) and customer has 2,000 advance
        $saleRes = $this->actingAs($this->user)->postJson(route('pos.store'), [
            'customer_id' => $customer->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 3,
                    'price' => 500.00,
                ],
            ],
            'paid_amount' => 0.00, // No cash needed because advance covers it
        ]);

        $saleRes->assertStatus(200);
        $saleRes->assertJson([
            'success' => true,
            'is_credit' => false,
            'due_amount' => 0.0,
            'customer_advance_balance' => 500.0,
        ]);

        // 5. Assert Sale in DB
        $saleId = $saleRes->json('sale_id');
        $sale = Sale::findOrFail($saleId);
        $this->assertEquals(1500.00, (float) $sale->total);
        $this->assertEquals(1500.00, (float) $sale->advance_adjusted);
        $this->assertEquals(1500.00, (float) $sale->paid_amount);
        $this->assertEquals('paid', $sale->payment_status);

        // 6. Assert customer remaining advance balance
        $customer->refresh();
        $this->assertEquals(500.00, (float) $customer->advance_balance);

        // 7. Assert inventory decremented
        $product->refresh();
        $this->assertEquals(47, $product->stock);
    }

    public function test_purchase_and_sale_receipt_views_render_advance_and_discount(): void
    {
        // 1. Create Purchase with discount & advance
        $supplierName = 'Receipt Supplier ' . uniqid();
        SupplierAdvance::create([
            'supplier_name' => $supplierName,
            'advance_balance' => 1000.00,
        ]);

        $product = Product::create([
            'name' => 'Receipt Product ' . uniqid(),
            'code' => 'RP-' . uniqid(),
            'purchase_price' => 150.00,
            'sale_price' => 250.00,
            'stock' => 10,
        ]);

        $postRes = $this->actingAs($this->user)->post(route('purchases.store'), [
            'supplier_name' => $supplierName,
            'purchase_date' => now()->toDateString(),
            'paid_amount' => 1000.00,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 10,
                    'price' => 200.00,
                    'discount_percent' => 15.0, // 2000 - 300 = 1700
                ],
            ],
        ]);
        $postRes->assertSessionHasNoErrors();
        $postRes->assertRedirect(route('purchases.index'));

        $purchase = Purchase::where('supplier_name', $supplierName)->latest('id')->first();
        $this->assertNotNull($purchase);

        // Check Purchase Receipt
        $purchaseReceipt = $this->actingAs($this->user)->get(route('credit-purchases.receipt', $purchase->id));
        $purchaseReceipt->assertStatus(200);
        $purchaseReceipt->assertHeader('content-type', 'application/pdf');

        // 2. Create Sale with advance
        $customer = Customer::create([
            'name' => 'Sale Receipt Customer ' . uniqid(),
            'phone' => '0311' . rand(1000000, 9999999),
            'advance_balance' => 500.00,
        ]);

        $saleRes = $this->actingAs($this->user)->postJson(route('pos.store'), [
            'customer_id' => $customer->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'price' => 300.00, // Total 600
                ],
            ],
            'paid_amount' => 100.00, // 500 advance + 100 cash = 600
        ]);

        $saleId = $saleRes->json('sale_id');
        $saleReceipt = $this->actingAs($this->user)->get(route('pos.invoice', $saleId));
        $saleReceipt->assertStatus(200);
        $saleReceipt->assertHeader('content-type', 'application/pdf');
    }

    public function test_pos_store_with_item_percentage_discount_and_receipt(): void
    {
        $product = Product::create([
            'name' => 'Surgical Blade ' . uniqid(),
            'code' => 'SB-' . uniqid(),
            'purchase_price' => 150.00,
            'sale_price' => 272.00,
            'stock' => 100,
        ]);

        // Scenario: Quantity 50, Unit Price 272, Discount 24%
        // Expected:
        // Discount per unit: 272 * 0.24 = 65.28
        // Discounted price: 272 - 65.28 = 206.72
        // Subtotal: 206.72 * 50 = 10,336.00
        // Line discount: (272 * 50) - 10,336 = 13,600 - 10,336 = 3,264.00
        $saleRes = $this->actingAs($this->user)->postJson(route('pos.store'), [
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 50,
                    'price' => 272.00,
                    'discount_percent' => 24.0,
                ],
            ],
            'paid_amount' => 10336.00,
        ]);

        $saleRes->assertStatus(200);
        $saleRes->assertJson(['success' => true]);

        $saleId = $saleRes->json('sale_id');
        $sale = Sale::with('items')->findOrFail($saleId);

        $this->assertEquals(13600.00, (float) $sale->sub_total);
        $this->assertEquals(3264.00, (float) $sale->discount);
        $this->assertEquals(10336.00, (float) $sale->total);
        $this->assertEquals(10336.00, (float) $sale->paid_amount);

        $item = $sale->items->first();
        $this->assertNotNull($item);
        $this->assertEquals(24.00, (float) $item->discount_percent);
        $this->assertEquals(3264.00, (float) $item->discount_amount);
        $this->assertEquals(10336.00, (float) $item->total);
        $this->assertEquals(65.28, (float) $item->discount_per_unit);
        $this->assertEquals(206.72, (float) $item->discounted_price);

        // Verify receipt generates PDF stream successfully
        $invoiceRes = $this->actingAs($this->user)->get(route('pos.invoice', $saleId));
        $invoiceRes->assertStatus(200);
        $invoiceRes->assertHeader('content-type', 'application/pdf');
    }

    public function test_pos_decimal_percentage_discount_and_update(): void
    {
        $productA = Product::create([
            'name' => 'Syringe ' . uniqid(),
            'code' => 'SYR-' . uniqid(),
            'purchase_price' => 50.00,
            'sale_price' => 100.00,
            'stock' => 200,
        ]);

        $productB = Product::create([
            'name' => 'Needle ' . uniqid(),
            'code' => 'NDL-' . uniqid(),
            'purchase_price' => 20.00,
            'sale_price' => 50.00,
            'stock' => 200,
        ]);

        // Product A: 10 units @ 100 with 21.5% discount -> 100 - 21.5 = 78.50 * 10 = 785.00 (discount 215.00)
        // Product B: 4 units @ 50 with 10.25% discount -> 50 - 5.125 = 44.875 * 4 = 179.50 (discount 20.50)
        $saleRes = $this->actingAs($this->user)->postJson(route('pos.store'), [
            'items' => [
                [
                    'product_id' => $productA->id,
                    'quantity' => 10,
                    'price' => 100.00,
                    'discount_percent' => 21.5,
                ],
                [
                    'product_id' => $productB->id,
                    'quantity' => 4,
                    'price' => 50.00,
                    'discount_percent' => 10.25,
                ],
            ],
            'paid_amount' => 964.50,
        ]);

        $saleRes->assertStatus(200);
        $saleId = $saleRes->json('sale_id');
        $sale = Sale::with('items')->findOrFail($saleId);

        $this->assertEquals(1200.00, (float) $sale->sub_total); // (10*100) + (4*50)
        $this->assertEquals(235.50, (float) $sale->discount);  // 215.00 + 20.50
        $this->assertEquals(964.50, (float) $sale->total);     // 785.00 + 179.50

        // Test updating the sale with revised discounts and quantities
        // Change Product A to 10% discount, quantity 5
        // Change Product B to 0% discount, quantity 2
        $updateRes = $this->actingAs($this->user)->put(route('sales.update', $saleId), [
            'order_date' => now()->toDateString(),
            'paid_amount' => 550.00,
            'items' => [
                [
                    'product_id' => $productA->id,
                    'quantity' => 5,
                    'price' => 100.00,
                    'discount_percent' => 10.0, // 5 * 90 = 450
                ],
                [
                    'product_id' => $productB->id,
                    'quantity' => 2,
                    'price' => 50.00,
                    'discount_percent' => 0.0, // 2 * 50 = 100
                ],
            ],
        ]);

        $updateRes->assertSessionHasNoErrors();
        $updateRes->assertRedirect(route('reports.sales'));
        $sale->refresh();
        $this->assertEquals(600.00, (float) $sale->sub_total);
        $this->assertEquals(50.00, (float) $sale->discount);
        $this->assertEquals(550.00, (float) $sale->total);
    }
}
