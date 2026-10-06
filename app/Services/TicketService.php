<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\ItemRecovery;
use App\Models\Journey;
use App\Models\JourneyPassenger;
use App\Models\LostItem;
use App\Models\LostItemStatusHistory;
use App\Models\LostItemTicket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TicketService
{
    /**
     * Allowed lost item categories matching migration enum.
     */
    public const ALLOWED_CATEGORIES = [
        'mobile',
        'wallet',
        'bag',
        'documents',
        'jewellery',
        'laptop',
        'camera',
        'clothing',
        'keys',
        'passport',
        'electronics',
        'other',
    ];

    /**
     * Generate a globally unique, human-readable ticket number.
     * Format: LF-YYYY-XXXXXX
     */
    public function generateTicketNumber(): string
    {
        $year = date('Y');
        do {
            $number = sprintf('LF-%s-%06d', $year, random_int(1, 999999));
        } while (LostItemTicket::where('ticket_number', $number)->exists());

        return $number;
    }

    /**
     * File a new lost item ticket for a journey.
     */
    public function createTicket(User $passenger, array $data): LostItemTicket
    {
        // 1. Resolve journey
        $journey = null;
        if (!empty($data['journey_uuid'])) {
            $journey = Journey::where('uuid', $data['journey_uuid'])->first();
        } elseif (!empty($data['journey_id'])) {
            $journey = Journey::find($data['journey_id']);
        }

        if (!$journey) {
            throw new \DomainException('Journey not found.');
        }

        // 2. Validate passenger journey association
        $passengerRecord = JourneyPassenger::where('journey_id', $journey->id)
            ->where('passenger_id', $passenger->id)
            ->first();

        if (!$passengerRecord) {
            throw new \DomainException('You were not registered as a passenger on this journey.');
        }

        // 3. Category validation
        $category = strtolower($data['category'] ?? '');
        if (!in_array($category, self::ALLOWED_CATEGORIES, true)) {
            throw new \DomainException("Invalid item category '{$category}'. Allowed categories: " . implode(', ', self::ALLOWED_CATEGORIES));
        }

        // 4. Duplicate prevention: open ticket for same passenger, journey, and category
        $duplicate = LostItemTicket::where('journey_id', $journey->id)
            ->where('passenger_id', $passenger->id)
            ->whereNotIn('status', ['closed', 'cancelled'])
            ->whereHas('item', function ($query) use ($category) {
                $query->where('category', $category);
            })
            ->first();

        if ($duplicate) {
            throw new \DomainException("An active ticket ({$duplicate->ticket_number}) already exists for this item category on this journey.");
        }

        // 5. Transactional execution
        return DB::transaction(function () use ($passenger, $journey, $data, $category) {
            // Create physical lost item
            $lostItem = LostItem::create([
                'category' => $category,
                'name' => $data['name'],
                'description' => $data['description'],
                'brand' => $data['brand'] ?? null,
                'model' => $data['model'] ?? null,
                'color' => $data['color'] ?? null,
                'estimated_value' => $data['estimated_value'] ?? null,
                'lost_at' => $data['lost_at'] ?? now(),
            ]);

            // Create ticket
            $ticketNumber = $this->generateTicketNumber();
            $ticket = LostItemTicket::create([
                'ticket_number' => $ticketNumber,
                'journey_id' => $journey->id,
                'passenger_id' => $passenger->id,
                'driver_id' => $journey->driver_id,
                'vehicle_id' => $journey->vehicle_id,
                'lost_item_id' => $lostItem->id,
                'status' => 'driver_notified', // Immediately notifies driver on creation
                'reported_at' => now(),
            ]);

            // Initial status history: created
            LostItemStatusHistory::create([
                'ticket_id' => $ticket->id,
                'old_status' => null,
                'new_status' => 'created',
                'changed_by' => $passenger->id,
                'reason' => 'Lost item ticket created by passenger.',
                'created_at' => now(),
            ]);

            // Second status history: driver_notified
            LostItemStatusHistory::create([
                'ticket_id' => $ticket->id,
                'old_status' => 'created',
                'new_status' => 'driver_notified',
                'changed_by' => $passenger->id,
                'reason' => 'Driver notified of lost item report.',
                'created_at' => now(),
            ]);

            // Initialize scoped conversation for this ticket
            $conversation = Conversation::create([
                'type' => 'lost_item',
                'journey_id' => $journey->id,
                'ticket_id' => $ticket->id,
                'status' => 'active',
            ]);

            // Attach passenger as participant
            ConversationParticipant::create([
                'conversation_id' => $conversation->id,
                'user_id' => $passenger->id,
                'joined_at' => now(),
            ]);

            // Attach driver as participant
            if ($journey->driver?->user_id) {
                ConversationParticipant::create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $journey->driver->user_id,
                    'joined_at' => now(),
                ]);
            }

            // Audit log
            AuditLog::create([
                'user_id' => $passenger->id,
                'action' => 'ticket.created',
                'auditable_type' => LostItemTicket::class,
                'auditable_id' => $ticket->id,
                'new_values' => [
                    'ticket_number' => $ticket->ticket_number,
                    'journey_id' => $journey->id,
                    'status' => 'driver_notified',
                    'category' => $category,
                ],
                'created_at' => now(),
            ]);

            return $ticket->load(['item', 'journey', 'vehicle', 'statusHistory']);
        });
    }

    /**
     * Driver begins searching the vehicle.
     */
    public function markSearching(LostItemTicket $ticket, User $user): LostItemTicket
    {
        $this->validateDriverOrAdmin($ticket, $user);

        if ($ticket->status !== 'driver_notified') {
            throw new \DomainException("Cannot begin searching from '{$ticket->status}' status. Ticket must be in 'driver_notified' status.");
        }

        return DB::transaction(function () use ($ticket, $user) {
            $oldStatus = $ticket->status;
            $ticket->update(['status' => 'searching']);

            LostItemStatusHistory::create([
                'ticket_id' => $ticket->id,
                'old_status' => $oldStatus,
                'new_status' => 'searching',
                'changed_by' => $user->id,
                'reason' => 'Driver began searching the vehicle.',
                'created_at' => now(),
            ]);

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'ticket.searching',
                'auditable_type' => LostItemTicket::class,
                'auditable_id' => $ticket->id,
                'old_values' => ['status' => $oldStatus],
                'new_values' => ['status' => 'searching'],
                'created_at' => now(),
            ]);

            return $ticket;
        });
    }

    /**
     * Driver marks item as found.
     * Transitions ticket to 'recovery_pending' and establishes ItemRecovery.
     */
    public function markFound(LostItemTicket $ticket, User $user, array $details = []): LostItemTicket
    {
        $this->validateDriverOrAdmin($ticket, $user);

        if (!in_array($ticket->status, ['driver_notified', 'searching'], true)) {
            throw new \DomainException("Cannot mark item found from current status '{$ticket->status}'.");
        }

        return DB::transaction(function () use ($ticket, $user, $details) {
            $oldStatus = $ticket->status;

            // 1. Establish ItemRecovery
            $recovery = ItemRecovery::updateOrCreate(
                ['ticket_id' => $ticket->id],
                [
                    'found_by' => $user->id,
                    'found_at' => now(),
                    'handover_method' => $details['handover_method'] ?? 'in_person',
                    'notes' => $details['notes'] ?? null,
                    'passenger_confirmed' => false,
                    'driver_confirmed' => false,
                ]
            );

            // 2. Record status history: item_found
            LostItemStatusHistory::create([
                'ticket_id' => $ticket->id,
                'old_status' => $oldStatus,
                'new_status' => 'item_found',
                'changed_by' => $user->id,
                'reason' => $details['notes'] ?? 'Driver reported item found.',
                'created_at' => now(),
            ]);

            // 3. Transition ticket to recovery_pending
            $ticket->update(['status' => 'recovery_pending']);

            // 4. Record status history: recovery_pending
            LostItemStatusHistory::create([
                'ticket_id' => $ticket->id,
                'old_status' => 'item_found',
                'new_status' => 'recovery_pending',
                'changed_by' => $user->id,
                'reason' => 'Recovery coordination initiated.',
                'created_at' => now(),
            ]);

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'ticket.found',
                'auditable_type' => LostItemTicket::class,
                'auditable_id' => $ticket->id,
                'old_values' => ['status' => $oldStatus],
                'new_values' => ['status' => 'recovery_pending', 'recovery_id' => $recovery->id],
                'created_at' => now(),
            ]);

            return $ticket->load('recovery');
        });
    }

    /**
     * Driver reports item not found.
     */
    public function markNotFound(LostItemTicket $ticket, User $user, string $reason): LostItemTicket
    {
        $this->validateDriverOrAdmin($ticket, $user);

        if (!in_array($ticket->status, ['driver_notified', 'searching'], true)) {
            throw new \DomainException("Cannot mark item not found from current status '{$ticket->status}'.");
        }

        if (trim($reason) === '') {
            throw new \DomainException('A detailed reason is required when marking an item as not found.');
        }

        return DB::transaction(function () use ($ticket, $user, $reason) {
            $oldStatus = $ticket->status;
            $ticket->update(['status' => 'not_found']);

            LostItemStatusHistory::create([
                'ticket_id' => $ticket->id,
                'old_status' => $oldStatus,
                'new_status' => 'not_found',
                'changed_by' => $user->id,
                'reason' => $reason,
                'created_at' => now(),
            ]);

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'ticket.not_found',
                'auditable_type' => LostItemTicket::class,
                'auditable_id' => $ticket->id,
                'old_values' => ['status' => $oldStatus],
                'new_values' => ['status' => 'not_found', 'reason' => $reason],
                'created_at' => now(),
            ]);

            return $ticket;
        });
    }

    /**
     * Driver confirms handover of item.
     * Dual confirmation rule: if passenger has already confirmed, auto-closes ticket.
     */
    public function confirmDriverHandover(LostItemTicket $ticket, User $user, array $data = []): ItemRecovery
    {
        $this->validateDriverOrAdmin($ticket, $user);

        if (!in_array($ticket->status, ['recovery_pending', 'item_found', 'handed_over'], true)) {
            throw new \DomainException("Cannot confirm handover from status '{$ticket->status}'.");
        }

        return DB::transaction(function () use ($ticket, $user, $data) {
            $recovery = ItemRecovery::firstOrCreate(
                ['ticket_id' => $ticket->id],
                [
                    'found_by' => $user->id,
                    'found_at' => now(),
                ]
            );

            $recovery->update([
                'driver_confirmed' => true,
                'handover_at' => now(),
                'handover_method' => $data['handover_method'] ?? $recovery->handover_method ?? 'in_person',
                'notes' => $data['notes'] ?? $recovery->notes,
            ]);

            $oldStatus = $ticket->status;

            if ($recovery->passenger_confirmed) {
                // Dual confirmation achieved -> CLOSE TICKET
                $ticket->update([
                    'status' => 'closed',
                    'closed_at' => now(),
                ]);

                LostItemStatusHistory::create([
                    'ticket_id' => $ticket->id,
                    'old_status' => $oldStatus,
                    'new_status' => 'closed',
                    'changed_by' => $user->id,
                    'reason' => 'Dual confirmation complete: driver confirmed handover.',
                    'created_at' => now(),
                ]);

                $ticket->conversation?->update(['status' => 'closed']);
            } else {
                $ticket->update(['status' => 'handed_over']);

                if ($oldStatus !== 'handed_over') {
                    LostItemStatusHistory::create([
                        'ticket_id' => $ticket->id,
                        'old_status' => $oldStatus,
                        'new_status' => 'handed_over',
                        'changed_by' => $user->id,
                        'reason' => 'Driver confirmed handover to passenger.',
                        'created_at' => now(),
                    ]);
                }
            }

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'handover.driver_confirmed',
                'auditable_type' => LostItemTicket::class,
                'auditable_id' => $ticket->id,
                'new_values' => [
                    'driver_confirmed' => true,
                    'ticket_status' => $ticket->status,
                ],
                'created_at' => now(),
            ]);

            return $recovery->fresh();
        });
    }

    /**
     * Passenger confirms receipt of item.
     * Dual confirmation rule: if driver has already confirmed, auto-closes ticket.
     */
    public function confirmPassengerReceipt(LostItemTicket $ticket, User $user): ItemRecovery
    {
        if ($ticket->passenger_id !== $user->id && !$user->hasPermission('lost_items.manage')) {
            throw new \DomainException('Only the reporting passenger can confirm receipt of this item.');
        }

        if (!in_array($ticket->status, ['recovery_pending', 'item_found', 'handed_over'], true)) {
            throw new \DomainException("Cannot confirm receipt from status '{$ticket->status}'.");
        }

        $recovery = $ticket->recovery;
        if (!$recovery) {
            throw new \DomainException('No recovery process has been established for this ticket.');
        }

        return DB::transaction(function () use ($ticket, $user, $recovery) {
            $recovery->update([
                'passenger_confirmed' => true,
            ]);

            $oldStatus = $ticket->status;

            if ($recovery->driver_confirmed) {
                // Dual confirmation achieved -> CLOSE TICKET
                $ticket->update([
                    'status' => 'closed',
                    'closed_at' => now(),
                ]);

                LostItemStatusHistory::create([
                    'ticket_id' => $ticket->id,
                    'old_status' => $oldStatus,
                    'new_status' => 'closed',
                    'changed_by' => $user->id,
                    'reason' => 'Dual confirmation complete: passenger confirmed receipt.',
                    'created_at' => now(),
                ]);

                $ticket->conversation?->update(['status' => 'closed']);
            } else {
                if ($ticket->status !== 'handed_over') {
                    $ticket->update(['status' => 'handed_over']);

                    LostItemStatusHistory::create([
                        'ticket_id' => $ticket->id,
                        'old_status' => $oldStatus,
                        'new_status' => 'handed_over',
                        'changed_by' => $user->id,
                        'reason' => 'Passenger confirmed receipt of item, awaiting driver confirmation.',
                        'created_at' => now(),
                    ]);
                }
            }

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'handover.passenger_confirmed',
                'auditable_type' => LostItemTicket::class,
                'auditable_id' => $ticket->id,
                'new_values' => [
                    'passenger_confirmed' => true,
                    'ticket_status' => $ticket->status,
                ],
                'created_at' => now(),
            ]);

            return $recovery->fresh();
        });
    }

    /**
     * Passenger cancels the ticket.
     * Allowed only in pre-recovery states ('created', 'driver_notified', 'searching').
     */
    public function cancelTicket(LostItemTicket $ticket, User $user, ?string $reason = null): LostItemTicket
    {
        if ($ticket->passenger_id !== $user->id && !$user->hasPermission('lost_items.manage')) {
            throw new \DomainException('Only the reporting passenger can cancel this ticket.');
        }

        if (!in_array($ticket->status, ['created', 'driver_notified', 'searching'], true)) {
            throw new \DomainException("Ticket cannot be cancelled from current status '{$ticket->status}'.");
        }

        return DB::transaction(function () use ($ticket, $user, $reason) {
            $oldStatus = $ticket->status;
            $ticket->update(['status' => 'cancelled']);

            LostItemStatusHistory::create([
                'ticket_id' => $ticket->id,
                'old_status' => $oldStatus,
                'new_status' => 'cancelled',
                'changed_by' => $user->id,
                'reason' => $reason ?? 'Cancelled by passenger.',
                'created_at' => now(),
            ]);

            $ticket->conversation?->update(['status' => 'closed']);

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'ticket.cancelled',
                'auditable_type' => LostItemTicket::class,
                'auditable_id' => $ticket->id,
                'old_values' => ['status' => $oldStatus],
                'new_values' => ['status' => 'cancelled', 'reason' => $reason],
                'created_at' => now(),
            ]);

            return $ticket;
        });
    }

    /**
     * Either passenger or driver disputes a ticket.
     */
    public function disputeTicket(LostItemTicket $ticket, User $user, string $reason): LostItemTicket
    {
        $this->validateParticipantOrAdmin($ticket, $user);

        if (in_array($ticket->status, ['cancelled'], true)) {
            throw new \DomainException("Cannot dispute a '{$ticket->status}' ticket.");
        }

        if (trim($reason) === '') {
            throw new \DomainException('A reason is required to dispute a ticket.');
        }

        return DB::transaction(function () use ($ticket, $user, $reason) {
            $oldStatus = $ticket->status;
            $ticket->update(['status' => 'disputed']);

            LostItemStatusHistory::create([
                'ticket_id' => $ticket->id,
                'old_status' => $oldStatus,
                'new_status' => 'disputed',
                'changed_by' => $user->id,
                'reason' => $reason,
                'created_at' => now(),
            ]);

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'ticket.disputed',
                'auditable_type' => LostItemTicket::class,
                'auditable_id' => $ticket->id,
                'old_values' => ['status' => $oldStatus],
                'new_values' => ['status' => 'disputed', 'reason' => $reason],
                'created_at' => now(),
            ]);

            return $ticket;
        });
    }

    /**
     * Escalate a disputed or unresolved ticket to administrative review.
     */
    public function escalateTicket(LostItemTicket $ticket, User $user, string $reason): LostItemTicket
    {
        $this->validateParticipantOrAdmin($ticket, $user);

        if (!in_array($ticket->status, ['disputed', 'not_found', 'handed_over', 'recovery_pending'], true)) {
            throw new \DomainException("Cannot escalate ticket from status '{$ticket->status}'.");
        }

        if (trim($reason) === '') {
            throw new \DomainException('A reason is required to escalate a ticket.');
        }

        return DB::transaction(function () use ($ticket, $user, $reason) {
            $oldStatus = $ticket->status;
            $ticket->update(['status' => 'escalated']);

            LostItemStatusHistory::create([
                'ticket_id' => $ticket->id,
                'old_status' => $oldStatus,
                'new_status' => 'escalated',
                'changed_by' => $user->id,
                'reason' => $reason,
                'created_at' => now(),
            ]);

            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'ticket.escalated',
                'auditable_type' => LostItemTicket::class,
                'auditable_id' => $ticket->id,
                'old_values' => ['status' => $oldStatus],
                'new_values' => ['status' => 'escalated', 'reason' => $reason],
                'created_at' => now(),
            ]);

            return $ticket;
        });
    }

    /**
     * Administrative resolution override for an escalated or disputed ticket.
     */
    public function resolveTicket(LostItemTicket $ticket, User $admin, string $targetStatus, string $reason): LostItemTicket
    {
        if (!$admin->hasPermission('lost_items.resolve') && !$admin->isAdmin()) {
            throw new \DomainException('Only authorized staff can administratively resolve tickets.');
        }

        $allowedTargetStatuses = ['closed', 'not_found', 'recovery_pending', 'cancelled'];
        if (!in_array($targetStatus, $allowedTargetStatuses, true)) {
            throw new \DomainException("Invalid target status '{$targetStatus}'. Allowed: " . implode(', ', $allowedTargetStatuses));
        }

        if (trim($reason) === '') {
            throw new \DomainException('An administrative reason is required to resolve a ticket.');
        }

        return DB::transaction(function () use ($ticket, $admin, $targetStatus, $reason) {
            $oldStatus = $ticket->status;
            $updates = ['status' => $targetStatus];
            if ($targetStatus === 'closed') {
                $updates['closed_at'] = now();
                $ticket->conversation?->update(['status' => 'closed']);
            }

            $ticket->update($updates);

            LostItemStatusHistory::create([
                'ticket_id' => $ticket->id,
                'old_status' => $oldStatus,
                'new_status' => $targetStatus,
                'changed_by' => $admin->id,
                'reason' => 'Administrative resolution: ' . $reason,
                'created_at' => now(),
            ]);

            AuditLog::create([
                'user_id' => $admin->id,
                'action' => 'ticket.admin_resolved',
                'auditable_type' => LostItemTicket::class,
                'auditable_id' => $ticket->id,
                'old_values' => ['status' => $oldStatus],
                'new_values' => ['status' => $targetStatus, 'reason' => $reason],
                'created_at' => now(),
            ]);

            return $ticket;
        });
    }

    /**
     * Format a ticket payload with strict zero-PII masking according to viewer role.
     */
    public function formatTicket(LostItemTicket $ticket, User $viewer): array
    {
        $ticket->loadMissing(['item', 'journey', 'vehicle', 'driver.user', 'passenger', 'recovery', 'conversation', 'statusHistory']);

        $isPassenger = ($ticket->passenger_id === $viewer->id);
        $isDriver = ($viewer->driver && $ticket->driver_id === $viewer->driver->id);
        $isAdmin = ($viewer->isAdmin() || $viewer->hasPermission('lost_items.view'));

        // Timeline formatting
        $timeline = $ticket->statusHistory->map(function ($h) {
            return [
                'old_status' => $h->old_status,
                'new_status' => $h->new_status,
                'reason' => $h->reason,
                'created_at' => $h->created_at?->toIso8601String(),
            ];
        });

        // Safe Driver Representation
        $driverData = null;
        if ($ticket->driver) {
            $driverData = [
                'driver_code' => $ticket->driver->driver_code,
                'name' => $ticket->driver->user?->name ?? 'Verified Driver',
                'is_verified' => $ticket->driver->isVerified(),
                'rating' => $ticket->driver->rating ?? 5.0,
            ];
        }

        // Safe Vehicle Representation
        $vehicleData = null;
        if ($ticket->vehicle) {
            $vehicleData = [
                'make' => $ticket->vehicle->make,
                'model' => $ticket->vehicle->model,
                'year' => $ticket->vehicle->year,
                'color' => $ticket->vehicle->color,
                'license_plate' => $ticket->vehicle->license_plate,
            ];
        }

        // Passenger Representation with Privacy Opt-In Check
        $passengerData = null;
        if ($isPassenger || $isAdmin) {
            $passengerData = [
                'id' => $ticket->passenger->id,
                'name' => $ticket->passenger->name,
                'phone' => $ticket->passenger->phone,
            ];
        } else {
            // Check if passenger opted to share contact on this journey
            $journeyPassenger = JourneyPassenger::where('journey_id', $ticket->journey_id)
                ->where('passenger_id', $ticket->passenger_id)
                ->first();

            if ($journeyPassenger && $journeyPassenger->share_details) {
                $passengerData = [
                    'name' => $ticket->passenger->name,
                    'phone' => $ticket->passenger->phone,
                    'details_shared' => true,
                ];
            } else {
                $passengerData = [
                    'name' => 'Passenger (' . substr($ticket->passenger->name ?? 'User', 0, 1) . '.)',
                    'details_shared' => false,
                ];
            }
        }

        // Recovery Representation
        $recoveryData = null;
        if ($ticket->recovery) {
            $recoveryData = [
                'found_at' => $ticket->recovery->found_at?->toIso8601String(),
                'handover_method' => $ticket->recovery->handover_method,
                'handover_at' => $ticket->recovery->handover_at?->toIso8601String(),
                'driver_confirmed' => (bool) $ticket->recovery->driver_confirmed,
                'passenger_confirmed' => (bool) $ticket->recovery->passenger_confirmed,
                'notes' => $ticket->recovery->notes,
            ];
        }

        return [
            'ticket_number' => $ticket->ticket_number,
            'status' => $ticket->status,
            'reported_at' => $ticket->reported_at?->toIso8601String(),
            'closed_at' => $ticket->closed_at?->toIso8601String(),
            'item' => [
                'uuid' => $ticket->item?->uuid,
                'category' => $ticket->item?->category,
                'name' => $ticket->item?->name,
                'description' => $ticket->item?->description,
                'brand' => $ticket->item?->brand,
                'model' => $ticket->item?->model,
                'color' => $ticket->item?->color,
                'estimated_value' => $ticket->item?->estimated_value,
                'lost_at' => $ticket->item?->lost_at?->toIso8601String(),
            ],
            'journey' => [
                'uuid' => $ticket->journey?->uuid,
                'started_at' => $ticket->journey?->started_at?->toIso8601String(),
            ],
            'vehicle' => $vehicleData,
            'driver' => $driverData,
            'passenger' => $passengerData,
            'recovery' => $recoveryData,
            'conversation_uuid' => $ticket->conversation?->uuid,
            'timeline' => $timeline,
        ];
    }

    /**
     * Validate that user is either the assigned driver or staff.
     */
    protected function validateDriverOrAdmin(LostItemTicket $ticket, User $user): void
    {
        $isAssignedDriver = ($user->driver && $ticket->driver_id === $user->driver->id);
        $isAdmin = ($user->isAdmin() || $user->hasPermission('lost_items.manage'));

        if (!$isAssignedDriver && !$isAdmin) {
            throw new \DomainException('Only the driver assigned to this journey or authorized staff can perform this action.');
        }
    }

    /**
     * Validate that user is a participant (driver or passenger) or staff.
     */
    protected function validateParticipantOrAdmin(LostItemTicket $ticket, User $user): void
    {
        $isPassenger = ($ticket->passenger_id === $user->id);
        $isDriver = ($user->driver && $ticket->driver_id === $user->driver->id);
        $isAdmin = ($user->isAdmin() || $user->hasPermission('lost_items.manage'));

        if (!$isPassenger && !$isDriver && !$isAdmin) {
            throw new \DomainException('You are not authorized to interact with this ticket.');
        }
    }
}
