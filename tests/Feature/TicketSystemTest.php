<?php

namespace Tests\Feature;

use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\Conversation;
use App\Models\Driver;
use App\Models\ItemRecovery;
use App\Models\Journey;
use App\Models\JourneyPassenger;
use App\Models\LostItem;
use App\Models\LostItemStatusHistory;
use App\Models\LostItemTicket;
use App\Models\QrCode;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleDriverAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TicketSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        AdminRole::firstOrCreate(['name' => 'super_admin'], ['description' => 'Super Administrator']);
        AdminRole::firstOrCreate(['name' => 'admin'], ['description' => 'Platform Administrator']);
        AdminRole::firstOrCreate(['name' => 'support'], ['description' => 'Support Staff']);
    }

    protected function createActiveJourneyWithPassenger(): array
    {
        $driverUser = User::create([
            'phone' => '+919876543210',
            'name' => 'Ramesh Driver',
            'role' => 'driver',
            'status' => 'active',
        ]);

        $driver = Driver::create([
            'user_id' => $driverUser->id,
            'driver_code' => 'DRV-TCK001',
            'verification_status' => 'verified',
            'verified_at' => now(),
            'rating_avg' => 4.9,
            'rating_count' => 20,
        ]);

        $vehicle = Vehicle::create([
            'vehicle_code' => 'VEH-TCK001',
            'registration_number' => 'KA01MJ2001',
            'vehicle_type' => 'cab',
            'make' => 'Hyundai',
            'model' => 'Aura',
            'color' => 'Silver',
            'verification_status' => 'verified',
            'verified_at' => now(),
            'status' => 'active',
        ]);

        VehicleDriverAssignment::create([
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'started_at' => now(),
            'status' => 'active',
        ]);

        QrCode::create([
            'vehicle_id' => $vehicle->id,
            'token' => 'ticketingtesttoken12345678901234',
            'version' => 1,
            'status' => 'active',
            'activated_at' => now(),
        ]);

        $journey = Journey::create([
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'started_at' => now()->subHour(),
            'expires_at' => now()->addHours(71),
            'status' => 'active',
        ]);

        $passengerUser = User::create([
            'phone' => '+919811122233',
            'name' => 'Ananya Sharma',
            'role' => 'tourist',
            'status' => 'active',
        ]);

        $passengerRecord = JourneyPassenger::create([
            'journey_id' => $journey->id,
            'passenger_id' => $passengerUser->id,
            'connected_at' => now()->subHour(),
            'status' => 'active',
        ]);

        return [$driverUser, $driver, $vehicle, $journey, $passengerUser, $passengerRecord];
    }

    public function test_passenger_can_create_ticket_for_their_journey(): void
    {
        [$driverUser, $driver, $vehicle, $journey, $passengerUser] = $this->createActiveJourneyWithPassenger();

        Sanctum::actingAs($passengerUser);

        $response = $this->postJson('/api/v1/tickets', [
            'journey_uuid' => $journey->uuid,
            'category' => 'mobile',
            'name' => 'iPhone 15 Pro Max',
            'description' => 'Blue Titanium iPhone with transparent MagSafe case, left on rear left seat.',
            'brand' => 'Apple',
            'model' => '15 Pro Max',
            'color' => 'Blue Titanium',
            'estimated_value' => 134999.00,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Lost item ticket created successfully.',
            ]);

        $ticketData = $response->json('data');
        $this->assertNotEmpty($ticketData['ticket_number']);
        $this->assertMatchesRegularExpression('/^LF-\d{4}-\d{6}$/', $ticketData['ticket_number']);
        $this->assertEquals('driver_notified', $ticketData['status']);
        $this->assertEquals('mobile', $ticketData['item']['category']);
        $this->assertEquals('iPhone 15 Pro Max', $ticketData['item']['name']);
        $this->assertEquals($vehicle->make, $ticketData['vehicle']['make']);
        $this->assertEquals($driver->driver_code, $ticketData['driver']['driver_code']);

        // Check database persistence
        $this->assertDatabaseHas('lost_items', [
            'category' => 'mobile',
            'name' => 'iPhone 15 Pro Max',
            'brand' => 'Apple',
        ]);

        $this->assertDatabaseHas('lost_item_tickets', [
            'ticket_number' => $ticketData['ticket_number'],
            'passenger_id' => $passengerUser->id,
            'driver_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'status' => 'driver_notified',
        ]);

        // Check immutable timeline history (created and driver_notified)
        $this->assertDatabaseHas('lost_item_status_history', [
            'old_status' => null,
            'new_status' => 'created',
            'changed_by' => $passengerUser->id,
        ]);

        $this->assertDatabaseHas('lost_item_status_history', [
            'old_status' => 'created',
            'new_status' => 'driver_notified',
            'changed_by' => $passengerUser->id,
        ]);

        // Check conversation and participants established
        $this->assertDatabaseHas('conversations', [
            'type' => 'lost_item',
            'journey_id' => $journey->id,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $passengerUser->id,
            'action' => 'ticket.created',
        ]);
    }

    public function test_unregistered_passenger_cannot_create_ticket_for_journey(): void
    {
        [$driverUser, $driver, $vehicle, $journey] = $this->createActiveJourneyWithPassenger();

        $unrelatedUser = User::create([
            'phone' => '+919777788888',
            'name' => 'Unrelated User',
            'role' => 'tourist',
            'status' => 'active',
        ]);

        Sanctum::actingAs($unrelatedUser);

        $response = $this->postJson('/api/v1/tickets', [
            'journey_uuid' => $journey->uuid,
            'category' => 'wallet',
            'name' => 'Leather Wallet',
            'description' => 'Brown leather wallet with ID cards.',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'You were not registered as a passenger on this journey.',
            ]);
    }

    public function test_duplicate_open_ticket_prevented_for_same_category_on_same_journey(): void
    {
        [$driverUser, $driver, $vehicle, $journey, $passengerUser] = $this->createActiveJourneyWithPassenger();

        Sanctum::actingAs($passengerUser);

        // First ticket succeeds
        $res1 = $this->postJson('/api/v1/tickets', [
            'journey_uuid' => $journey->uuid,
            'category' => 'mobile',
            'name' => 'Samsung S24',
            'description' => 'Black Samsung phone.',
        ]);
        $res1->assertStatus(201);

        // Second ticket for same category on same journey fails
        $res2 = $this->postJson('/api/v1/tickets', [
            'journey_uuid' => $journey->uuid,
            'category' => 'mobile',
            'name' => 'Another Samsung',
            'description' => 'Another phone.',
        ]);
        $res2->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
        $this->assertStringContainsString('An active ticket', $res2->json('message'));

        // Different category on same journey succeeds
        $res3 = $this->postJson('/api/v1/tickets', [
            'journey_uuid' => $journey->uuid,
            'category' => 'keys',
            'name' => 'House Keys',
            'description' => 'Keyring with 3 keys.',
        ]);
        $res3->assertStatus(201);
    }

    public function test_invalid_item_category_is_rejected(): void
    {
        [$driverUser, $driver, $vehicle, $journey, $passengerUser] = $this->createActiveJourneyWithPassenger();

        Sanctum::actingAs($passengerUser);

        $response = $this->postJson('/api/v1/tickets', [
            'journey_uuid' => $journey->uuid,
            'category' => 'spaceship',
            'name' => 'Apollo Rocket',
            'description' => 'Miniature rocket.',
        ]);

        $response->assertStatus(422);
    }

    public function test_assigned_driver_can_view_ticket_and_start_searching(): void
    {
        [$driverUser, $driver, $vehicle, $journey, $passengerUser] = $this->createActiveJourneyWithPassenger();

        Sanctum::actingAs($passengerUser);
        $res = $this->postJson('/api/v1/tickets', [
            'journey_uuid' => $journey->uuid,
            'category' => 'bag',
            'name' => 'Backpack',
            'description' => 'Black Swissgear backpack with laptop inside.',
        ]);
        $ticketNumber = $res->json('data.ticket_number');

        // Driver inspects ticket
        Sanctum::actingAs($driverUser);

        $viewRes = $this->getJson("/api/v1/tickets/{$ticketNumber}");
        $viewRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'ticket_number' => $ticketNumber,
                    'status' => 'driver_notified',
                ],
            ]);

        // Driver begins searching
        $searchRes = $this->postJson("/api/v1/tickets/{$ticketNumber}/searching");
        $searchRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'searching',
                ],
            ]);

        $this->assertDatabaseHas('lost_item_tickets', [
            'ticket_number' => $ticketNumber,
            'status' => 'searching',
        ]);

        $this->assertDatabaseHas('lost_item_status_history', [
            'old_status' => 'driver_notified',
            'new_status' => 'searching',
            'changed_by' => $driverUser->id,
        ]);
    }

    public function test_unrelated_driver_cannot_view_or_search_ticket(): void
    {
        [$driverUser, $driver, $vehicle, $journey, $passengerUser] = $this->createActiveJourneyWithPassenger();

        Sanctum::actingAs($passengerUser);
        $res = $this->postJson('/api/v1/tickets', [
            'journey_uuid' => $journey->uuid,
            'category' => 'wallet',
            'name' => 'Leather Wallet',
            'description' => 'Black wallet.',
        ]);
        $ticketNumber = $res->json('data.ticket_number');

        // Create unrelated driver
        $otherDriverUser = User::create([
            'phone' => '+919555544444',
            'name' => 'Suresh Other',
            'role' => 'driver',
            'status' => 'active',
        ]);
        Driver::create([
            'user_id' => $otherDriverUser->id,
            'driver_code' => 'DRV-OTH999',
            'verification_status' => 'verified',
        ]);

        Sanctum::actingAs($otherDriverUser);

        $viewRes = $this->getJson("/api/v1/tickets/{$ticketNumber}");
        $viewRes->assertStatus(403);

        $searchRes = $this->postJson("/api/v1/tickets/{$ticketNumber}/searching");
        $searchRes->assertStatus(403);
    }

    public function test_driver_can_mark_item_found_and_initiates_recovery(): void
    {
        [$driverUser, $driver, $vehicle, $journey, $passengerUser] = $this->createActiveJourneyWithPassenger();

        Sanctum::actingAs($passengerUser);
        $res = $this->postJson('/api/v1/tickets', [
            'journey_uuid' => $journey->uuid,
            'category' => 'keys',
            'name' => 'Car Keys',
            'description' => 'Silver Honda key.',
        ]);
        $ticketNumber = $res->json('data.ticket_number');

        Sanctum::actingAs($driverUser);

        // Mark searching first
        $this->postJson("/api/v1/tickets/{$ticketNumber}/searching")->assertStatus(200);

        // Mark found
        $foundRes = $this->postJson("/api/v1/tickets/{$ticketNumber}/found", [
            'handover_method' => 'in_person',
            'notes' => 'Found under driver seat mat.',
        ]);

        $foundRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'recovery_pending',
                    'recovery' => [
                        'handover_method' => 'in_person',
                        'notes' => 'Found under driver seat mat.',
                        'driver_confirmed' => false,
                        'passenger_confirmed' => false,
                    ],
                ],
            ]);

        $this->assertDatabaseHas('item_recoveries', [
            'found_by' => $driverUser->id,
            'handover_method' => 'in_person',
            'driver_confirmed' => false,
            'passenger_confirmed' => false,
        ]);

        $this->assertDatabaseHas('lost_item_status_history', [
            'new_status' => 'item_found',
            'changed_by' => $driverUser->id,
        ]);

        $this->assertDatabaseHas('lost_item_status_history', [
            'new_status' => 'recovery_pending',
            'changed_by' => $driverUser->id,
        ]);
    }

    public function test_driver_can_mark_item_not_found_with_reason(): void
    {
        [$driverUser, $driver, $vehicle, $journey, $passengerUser] = $this->createActiveJourneyWithPassenger();

        Sanctum::actingAs($passengerUser);
        $res = $this->postJson('/api/v1/tickets', [
            'journey_uuid' => $journey->uuid,
            'category' => 'jewellery',
            'name' => 'Gold Ring',
            'description' => 'Diamond studded gold ring.',
        ]);
        $ticketNumber = $res->json('data.ticket_number');

        Sanctum::actingAs($driverUser);
        $this->postJson("/api/v1/tickets/{$ticketNumber}/searching")->assertStatus(200);

        // Not found requires reason
        $failRes = $this->postJson("/api/v1/tickets/{$ticketNumber}/not-found", []);
        $failRes->assertStatus(422);

        $notFoundRes = $this->postJson("/api/v1/tickets/{$ticketNumber}/not-found", [
            'reason' => 'Thoroughly vacuumed and inspected vehicle; ring was not found.',
        ]);

        $notFoundRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'not_found',
                ],
            ]);

        $this->assertDatabaseHas('lost_item_tickets', [
            'ticket_number' => $ticketNumber,
            'status' => 'not_found',
        ]);
    }

    public function test_two_way_handover_confirmation_auto_closes_ticket(): void
    {
        [$driverUser, $driver, $vehicle, $journey, $passengerUser] = $this->createActiveJourneyWithPassenger();

        Sanctum::actingAs($passengerUser);
        $res = $this->postJson('/api/v1/tickets', [
            'journey_uuid' => $journey->uuid,
            'category' => 'laptop',
            'name' => 'MacBook Pro 14',
            'description' => 'Space Gray MacBook in grey felt sleeve.',
        ]);
        $ticketNumber = $res->json('data.ticket_number');

        // Driver finds item
        Sanctum::actingAs($driverUser);
        $this->postJson("/api/v1/tickets/{$ticketNumber}/found", [
            'handover_method' => 'in_person',
            'notes' => 'Found in trunk.',
        ])->assertStatus(200);

        // Step 1: Driver confirms handover
        $driverHandoverRes = $this->postJson("/api/v1/tickets/{$ticketNumber}/handover/driver", [
            'handover_method' => 'in_person',
            'notes' => 'Handed over at Indiranagar Metro Station.',
        ]);

        $driverHandoverRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'handed_over',
                    'recovery' => [
                        'driver_confirmed' => true,
                        'passenger_confirmed' => false,
                    ],
                ],
            ]);

        // Step 2: Passenger confirms receipt -> AUTO CLOSES TICKET
        Sanctum::actingAs($passengerUser);

        $passengerReceiptRes = $this->postJson("/api/v1/tickets/{$ticketNumber}/handover/passenger");
        $passengerReceiptRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Receipt confirmed. Dual confirmation complete; ticket closed.',
                'data' => [
                    'status' => 'closed',
                    'recovery' => [
                        'driver_confirmed' => true,
                        'passenger_confirmed' => true,
                    ],
                ],
            ]);

        $this->assertNotNull($passengerReceiptRes->json('data.closed_at'));

        // Verify database state
        $this->assertDatabaseHas('lost_item_tickets', [
            'ticket_number' => $ticketNumber,
            'status' => 'closed',
        ]);

        $this->assertDatabaseHas('item_recoveries', [
            'driver_confirmed' => true,
            'passenger_confirmed' => true,
        ]);

        $this->assertDatabaseHas('lost_item_status_history', [
            'new_status' => 'closed',
        ]);

        $this->assertDatabaseHas('conversations', [
            'ticket_id' => LostItemTicket::where('ticket_number', $ticketNumber)->value('id'),
            'status' => 'closed',
        ]);
    }

    public function test_two_way_handover_reverse_order_auto_closes_ticket(): void
    {
        [$driverUser, $driver, $vehicle, $journey, $passengerUser] = $this->createActiveJourneyWithPassenger();

        Sanctum::actingAs($passengerUser);
        $res = $this->postJson('/api/v1/tickets', [
            'journey_uuid' => $journey->uuid,
            'category' => 'passport',
            'name' => 'Indian Passport',
            'description' => 'Blue passport booklet.',
        ]);
        $ticketNumber = $res->json('data.ticket_number');

        // Driver finds item
        Sanctum::actingAs($driverUser);
        $this->postJson("/api/v1/tickets/{$ticketNumber}/found")->assertStatus(200);

        // Step 1: Passenger confirms receipt first
        Sanctum::actingAs($passengerUser);
        $passengerFirstRes = $this->postJson("/api/v1/tickets/{$ticketNumber}/handover/passenger");
        $passengerFirstRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'handed_over',
                    'recovery' => [
                        'passenger_confirmed' => true,
                        'driver_confirmed' => false,
                    ],
                ],
            ]);

        // Step 2: Driver confirms handover second -> AUTO CLOSES
        Sanctum::actingAs($driverUser);
        $driverSecondRes = $this->postJson("/api/v1/tickets/{$ticketNumber}/handover/driver");
        $driverSecondRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Handover confirmed. Dual confirmation complete; ticket closed.',
                'data' => [
                    'status' => 'closed',
                    'recovery' => [
                        'driver_confirmed' => true,
                        'passenger_confirmed' => true,
                    ],
                ],
            ]);

        $this->assertDatabaseHas('lost_item_tickets', [
            'ticket_number' => $ticketNumber,
            'status' => 'closed',
        ]);
    }

    public function test_passenger_can_cancel_ticket_in_eligible_status(): void
    {
        [$driverUser, $driver, $vehicle, $journey, $passengerUser] = $this->createActiveJourneyWithPassenger();

        Sanctum::actingAs($passengerUser);
        $res = $this->postJson('/api/v1/tickets', [
            'journey_uuid' => $journey->uuid,
            'category' => 'clothing',
            'name' => 'Jacket',
            'description' => 'Denim jacket.',
        ]);
        $ticketNumber = $res->json('data.ticket_number');

        $cancelRes = $this->postJson("/api/v1/tickets/{$ticketNumber}/cancel", [
            'reason' => 'Found it in my hotel room closet.',
        ]);

        $cancelRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Ticket cancelled successfully.',
                'data' => [
                    'status' => 'cancelled',
                ],
            ]);

        $this->assertDatabaseHas('lost_item_tickets', [
            'ticket_number' => $ticketNumber,
            'status' => 'cancelled',
        ]);
    }

    public function test_ticket_dispute_and_escalation_flow(): void
    {
        [$driverUser, $driver, $vehicle, $journey, $passengerUser] = $this->createActiveJourneyWithPassenger();

        Sanctum::actingAs($passengerUser);
        $res = $this->postJson('/api/v1/tickets', [
            'journey_uuid' => $journey->uuid,
            'category' => 'camera',
            'name' => 'Sony A7 IV',
            'description' => 'Mirrorless camera with 24-70mm lens.',
        ]);
        $ticketNumber = $res->json('data.ticket_number');

        Sanctum::actingAs($driverUser);
        $this->postJson("/api/v1/tickets/{$ticketNumber}/found")->assertStatus(200);
        $this->postJson("/api/v1/tickets/{$ticketNumber}/handover/driver")->assertStatus(200);

        // Passenger disputes handover
        Sanctum::actingAs($passengerUser);
        $disputeRes = $this->postJson("/api/v1/tickets/{$ticketNumber}/dispute", [
            'reason' => 'Package delivered was empty; camera missing.',
        ]);

        $disputeRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'disputed',
                ],
            ]);

        // Passenger escalates to staff
        $escalateRes = $this->postJson("/api/v1/tickets/{$ticketNumber}/escalate", [
            'reason' => 'Driver not answering calls; urgent staff mediation required.',
        ]);

        $escalateRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'escalated',
                ],
            ]);

        $this->assertDatabaseHas('lost_item_tickets', [
            'ticket_number' => $ticketNumber,
            'status' => 'escalated',
        ]);
    }

    public function test_admin_can_resolve_ticket(): void
    {
        [$driverUser, $driver, $vehicle, $journey, $passengerUser] = $this->createActiveJourneyWithPassenger();

        Sanctum::actingAs($passengerUser);
        $res = $this->postJson('/api/v1/tickets', [
            'journey_uuid' => $journey->uuid,
            'category' => 'documents',
            'name' => 'Birth Certificate',
            'description' => 'Original document folder.',
        ]);
        $ticketNumber = $res->json('data.ticket_number');

        // Escalate ticket
        Sanctum::actingAs($passengerUser);
        $this->postJson("/api/v1/tickets/{$ticketNumber}/dispute", ['reason' => 'Disputed'])->assertStatus(200);
        $this->postJson("/api/v1/tickets/{$ticketNumber}/escalate", ['reason' => 'Escalated'])->assertStatus(200);

        // Admin resolves
        $adminUser = User::create([
            'phone' => '+919999900000',
            'name' => 'Super Administrator',
            'role' => 'admin',
            'status' => 'active',
        ]);
        AdminUser::create([
            'user_id' => $adminUser->id,
            'role_id' => AdminRole::where('name', 'super_admin')->first()->id,
            'assigned_by' => $adminUser->id,
        ]);

        Sanctum::actingAs($adminUser);

        $resolveRes = $this->postJson("/api/v1/tickets/{$ticketNumber}/resolve", [
            'status' => 'closed',
            'reason' => 'Staff verified document was handed over at Lost & Found headquarters.',
        ]);

        $resolveRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'closed',
                ],
            ]);

        $this->assertDatabaseHas('lost_item_tickets', [
            'ticket_number' => $ticketNumber,
            'status' => 'closed',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $adminUser->id,
            'action' => 'ticket.admin_resolved',
        ]);
    }

    public function test_index_endpoint_filters_tickets_by_role_and_status(): void
    {
        [$driverUser, $driver, $vehicle, $journey, $passengerUser] = $this->createActiveJourneyWithPassenger();

        Sanctum::actingAs($passengerUser);

        // Create 2 tickets
        $this->postJson('/api/v1/tickets', [
            'journey_uuid' => $journey->uuid,
            'category' => 'mobile',
            'name' => 'Passenger Phone',
            'description' => 'Phone',
        ])->assertStatus(201);

        $this->postJson('/api/v1/tickets', [
            'journey_uuid' => $journey->uuid,
            'category' => 'wallet',
            'name' => 'Passenger Wallet',
            'description' => 'Wallet',
        ])->assertStatus(201);

        // Passenger lists tickets
        $passengerIndex = $this->getJson('/api/v1/tickets');
        $passengerIndex->assertStatus(200);
        $this->assertCount(2, $passengerIndex->json('data.tickets'));

        // Driver lists tickets
        Sanctum::actingAs($driverUser);
        $driverIndex = $this->getJson('/api/v1/tickets');
        $driverIndex->assertStatus(200);
        $this->assertCount(2, $driverIndex->json('data.tickets'));

        // Driver filters by status
        $statusIndex = $this->getJson('/api/v1/tickets?status=closed');
        $statusIndex->assertStatus(200);
        $this->assertCount(0, $statusIndex->json('data.tickets'));
    }
}
