<?php

namespace Tests\Feature;

use App\Events\MessageRead;
use App\Events\MessageSent;
use App\Models\AdminRole;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Driver;
use App\Models\Journey;
use App\Models\JourneyPassenger;
use App\Models\Media;
use App\Models\Message;
use App\Models\Notification;
use App\Models\QrCode;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\Vehicle;
use App\Models\VehicleDriverAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MessagingSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        AdminRole::firstOrCreate(['name' => 'super_admin'], ['description' => 'Super Administrator']);
        AdminRole::firstOrCreate(['name' => 'admin'], ['description' => 'Platform Administrator']);
        AdminRole::firstOrCreate(['name' => 'support'], ['description' => 'Support Staff']);
    }

    protected function createActiveConversation(): array
    {
        $driverUser = User::create([
            'phone' => '+919876543210',
            'name' => 'Vikram Driver',
            'role' => 'driver',
            'status' => 'active',
        ]);

        $driver = Driver::create([
            'user_id' => $driverUser->id,
            'driver_code' => 'DRV-MSG001',
            'verification_status' => 'verified',
            'verified_at' => now(),
            'rating_avg' => 4.95,
        ]);

        $vehicle = Vehicle::create([
            'vehicle_code' => 'VEH-MSG001',
            'registration_number' => 'KA01MJ3001',
            'vehicle_type' => 'cab',
            'make' => 'Toyota',
            'model' => 'Etios',
            'color' => 'White',
            'verification_status' => 'verified',
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
            'token' => 'msgtesttoken12345678901234567890',
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
            'phone' => '+919811199999',
            'name' => 'Neha Gupta',
            'role' => 'tourist',
            'status' => 'active',
        ]);

        JourneyPassenger::create([
            'journey_id' => $journey->id,
            'passenger_id' => $passengerUser->id,
            'connected_at' => now()->subHour(),
            'status' => 'active',
        ]);

        $conversation = Conversation::create([
            'type' => 'journey',
            'journey_id' => $journey->id,
            'status' => 'active',
        ]);

        ConversationParticipant::create([
            'conversation_id' => $conversation->id,
            'user_id' => $passengerUser->id,
            'joined_at' => now(),
        ]);

        ConversationParticipant::create([
            'conversation_id' => $conversation->id,
            'user_id' => $driverUser->id,
            'joined_at' => now(),
        ]);

        return [$driverUser, $passengerUser, $conversation, $journey];
    }

    public function test_participants_can_view_conversation_and_messages(): void
    {
        [$driverUser, $passengerUser, $conversation] = $this->createActiveJourneyConversation();

        // Add a message
        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $driverUser->id,
            'message_type' => 'text',
            'body' => 'Hello, did you leave something in the cab?',
            'sent_at' => now(),
        ]);

        // Passenger views conversation details
        Sanctum::actingAs($passengerUser);

        $showRes = $this->getJson("/api/v1/conversations/{$conversation->uuid}");
        $showRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'uuid' => $conversation->uuid,
                    'status' => 'active',
                ],
            ]);

        // Passenger fetches messages
        $messagesRes = $this->getJson("/api/v1/conversations/{$conversation->uuid}/messages");
        $messagesRes->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
        $this->assertCount(1, $messagesRes->json('data.messages'));
        $this->assertEquals('Hello, did you leave something in the cab?', $messagesRes->json('data.messages.0.body'));

        // Driver views conversation
        Sanctum::actingAs($driverUser);
        $driverShow = $this->getJson("/api/v1/conversations/{$conversation->uuid}");
        $driverShow->assertStatus(200);
    }

    protected function createActiveJourneyConversation(): array
    {
        return $this->createActiveConversation();
    }

    public function test_non_participant_forbidden_from_viewing_conversation(): void
    {
        [$driverUser, $passengerUser, $conversation] = $this->createActiveConversation();

        $stranger = User::create([
            'phone' => '+919666677777',
            'name' => 'Stranger User',
            'role' => 'tourist',
            'status' => 'active',
        ]);

        Sanctum::actingAs($stranger);

        $showRes = $this->getJson("/api/v1/conversations/{$conversation->uuid}");
        $showRes->assertStatus(403);

        $messagesRes = $this->getJson("/api/v1/conversations/{$conversation->uuid}/messages");
        $messagesRes->assertStatus(403);
    }

    public function test_participant_can_send_message_and_broadcasts_event(): void
    {
        Event::fake([MessageSent::class]);

        [$driverUser, $passengerUser, $conversation] = $this->createActiveConversation();

        Sanctum::actingAs($passengerUser);

        $response = $this->postJson("/api/v1/conversations/{$conversation->uuid}/messages", [
            'message_type' => 'text',
            'body' => 'I left my laptop bag on the back seat. Is it there?',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Message sent successfully.',
                'data' => [
                    'conversation_uuid' => $conversation->uuid,
                    'message_type' => 'text',
                    'body' => 'I left my laptop bag on the back seat. Is it there?',
                ],
            ]);

        // Database persistence check
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $passengerUser->id,
            'body' => 'I left my laptop bag on the back seat. Is it there?',
        ]);

        // Broadcast event check
        Event::assertDispatched(MessageSent::class, function ($event) use ($conversation) {
            return $event->message->conversation->uuid === $conversation->uuid;
        });

        // In-app notification for recipient check
        $this->assertDatabaseHas('notifications', [
            'user_id' => $driverUser->id,
            'type' => 'new_message',
        ]);
    }

    public function test_participant_can_send_image_message_with_attachment(): void
    {
        Event::fake([MessageSent::class]);

        [$driverUser, $passengerUser, $conversation] = $this->createActiveConversation();

        $media = Media::create([
            'disk' => 'private',
            'path' => 'chat_media/test_found_bag.jpg',
            'original_name' => 'test_found_bag.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 204800,
            'visibility' => 'private',
            'uploaded_by' => $driverUser->id,
        ]);

        Sanctum::actingAs($driverUser);

        $response = $this->postJson("/api/v1/conversations/{$conversation->uuid}/messages", [
            'message_type' => 'image',
            'body' => 'Here is a photo of the bag I found.',
            'media_id' => $media->id,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('message_attachments', [
            'media_id' => $media->id,
        ]);
    }

    public function test_cannot_send_message_to_closed_conversation(): void
    {
        [$driverUser, $passengerUser, $conversation] = $this->createActiveConversation();

        $conversation->update(['status' => 'closed']);

        Sanctum::actingAs($passengerUser);

        $response = $this->postJson("/api/v1/conversations/{$conversation->uuid}/messages", [
            'body' => 'Can you hear me?',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
        $this->assertStringContainsString('closed', $response->json('message'));
    }

    public function test_mark_messages_as_read_updates_database_and_broadcasts(): void
    {
        Event::fake([MessageRead::class]);

        [$driverUser, $passengerUser, $conversation] = $this->createActiveConversation();

        // Driver sends a message
        $msg = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $driverUser->id,
            'body' => 'Waiting at the pickup point.',
            'sent_at' => now(),
        ]);

        $this->assertNull($msg->read_at);

        // Passenger reads message
        Sanctum::actingAs($passengerUser);

        $readRes = $this->postJson("/api/v1/conversations/{$conversation->uuid}/read");
        $readRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'marked_count' => 1,
                ],
            ]);

        $this->assertNotNull($msg->fresh()->read_at);

        Event::assertDispatched(MessageRead::class);
    }

    public function test_notification_center_listing_and_read_management(): void
    {
        [$driverUser, $passengerUser] = $this->createActiveConversation();

        Notification::create([
            'user_id' => $passengerUser->id,
            'type' => 'ticket_update',
            'title' => 'Item Found',
            'body' => 'Driver has marked your item found.',
            'created_at' => now()->subMinutes(10),
        ]);

        $notif2 = Notification::create([
            'user_id' => $passengerUser->id,
            'type' => 'new_message',
            'title' => 'New Message',
            'body' => 'Driver sent a message.',
            'created_at' => now()->subMinutes(5),
        ]);

        Sanctum::actingAs($passengerUser);

        // 1. List notifications
        $listRes = $this->getJson('/api/v1/notifications');
        $listRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'unread_count' => 2,
                ],
            ]);
        $this->assertCount(2, $listRes->json('data.notifications'));

        // 2. Mark single notification as read
        $markSingleRes = $this->postJson("/api/v1/notifications/{$notif2->id}/read");
        $markSingleRes->assertStatus(200);

        $listRes2 = $this->getJson('/api/v1/notifications');
        $this->assertEquals(1, $listRes2->json('data.unread_count'));

        // 3. Mark all as read
        $markAllRes = $this->postJson('/api/v1/notifications/read-all');
        $markAllRes->assertStatus(200);

        $listRes3 = $this->getJson('/api/v1/notifications');
        $this->assertEquals(0, $listRes3->json('data.unread_count'));
    }

    public function test_fcm_push_notification_dispatched_to_user_devices(): void
    {
        [$driverUser, $passengerUser, $conversation] = $this->createActiveConversation();

        // Register push token for passenger
        UserDevice::create([
            'user_id' => $passengerUser->id,
            'device_id' => 'device_abc_123',
            'platform' => 'android',
            'push_token' => 'fcm_sample_token_test_12345',
            'app_version' => '1.0.0',
        ]);

        Sanctum::actingAs($driverUser);

        $response = $this->postJson("/api/v1/conversations/{$conversation->uuid}/messages", [
            'body' => 'I am on my way to deliver the item.',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $passengerUser->id,
            'type' => 'new_message',
        ]);
    }

    public function test_user_can_list_their_conversations(): void
    {
        [$driverUser, $passengerUser, $conversation] = $this->createActiveConversation();

        Sanctum::actingAs($passengerUser);

        $response = $this->getJson('/api/v1/conversations');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
        $this->assertCount(1, $response->json('data.conversations'));
        $this->assertEquals($conversation->uuid, $response->json('data.conversations.0.uuid'));
    }

    public function test_cannot_attach_unowned_media_to_message(): void
    {
        [$driverUser, $passengerUser, $conversation] = $this->createActiveConversation();

        $stranger = User::create([
            'phone' => '+919999988888',
            'name' => 'Stranger KYC',
            'role' => 'driver',
            'status' => 'active',
        ]);

        // Stranger uploads private media
        $strangerMedia = Media::create([
            'disk' => 'private',
            'path' => 'kyc/secret_passport.jpg',
            'original_name' => 'secret_passport.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 102400,
            'visibility' => 'private',
            'uploaded_by' => $stranger->id,
        ]);

        Sanctum::actingAs($passengerUser);

        $response = $this->postJson("/api/v1/conversations/{$conversation->uuid}/messages", [
            'message_type' => 'image',
            'media_id' => $strangerMedia->id,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized to attach this media item.',
            ]);
    }

    public function test_driver_sees_masked_passenger_name_if_details_not_shared(): void
    {
        [$driverUser, $passengerUser, $conversation] = $this->createActiveConversation();

        Sanctum::actingAs($driverUser);

        $response = $this->getJson("/api/v1/conversations/{$conversation->uuid}");
        $response->assertStatus(200);

        $passengerParticipant = collect($response->json('data.participants'))
            ->firstWhere('id', $passengerUser->id);

        $this->assertNotNull($passengerParticipant);
        // Passenger name 'Neha Gupta' must be masked to 'Passenger (N.)'
        $this->assertEquals('Passenger (N.)', $passengerParticipant['name']);
    }
}
