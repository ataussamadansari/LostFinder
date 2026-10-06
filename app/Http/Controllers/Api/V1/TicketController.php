<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LostItemTicket;
use App\Services\TicketService;
use App\Traits\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    use ApiResponse, AuthorizesRequests;

    public function __construct(
        protected TicketService $ticketService
    ) {}

    /**
     * List tickets for authenticated user based on role (Passenger, Driver, Staff).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = LostItemTicket::with(['item', 'journey', 'vehicle', 'recovery']);

        if ($user->isAdmin() || $user->hasPermission('lost_items.view')) {
            // Staff sees all tickets
        } elseif ($user->driver) {
            // Driver sees assigned tickets
            $query->where('driver_id', $user->driver->id);
        } else {
            // Tourist/Passenger sees their own tickets
            $query->where('passenger_id', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $tickets = $query->orderBy('created_at', 'desc')->paginate((int) $request->query('per_page', 15));

        $formatted = collect($tickets->items())->map(function ($ticket) use ($user) {
            return $this->ticketService->formatTicket($ticket, $user);
        });

        return $this->successResponse([
            'tickets' => $formatted,
            'pagination' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'per_page' => $tickets->perPage(),
                'total' => $tickets->total(),
            ],
        ], 'Tickets retrieved successfully.');
    }

    /**
     * File a new lost item ticket for a journey.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'journey_uuid' => 'nullable|uuid',
            'journey_id' => 'nullable|integer',
            'category' => 'required|string|in:' . implode(',', TicketService::ALLOWED_CATEGORIES),
            'name' => 'required|string|max:150',
            'description' => 'required|string|max:2000',
            'brand' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:50',
            'estimated_value' => 'nullable|numeric|min:0',
            'lost_at' => 'nullable|date',
        ]);

        if (empty($validated['journey_uuid']) && empty($validated['journey_id'])) {
            return $this->errorResponse('Either journey_uuid or journey_id is required.', 422);
        }

        try {
            $ticket = $this->ticketService->createTicket($request->user(), $validated);

            return $this->successResponse(
                $this->ticketService->formatTicket($ticket, $request->user()),
                'Lost item ticket created successfully.',
                201
            );
        } catch (\DomainException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * View detailed ticket info, timeline, and recovery details.
     */
    public function show(Request $request, string $ticketNumber): JsonResponse
    {
        $ticket = $this->findTicket($ticketNumber);
        $this->authorize('view', $ticket);

        return $this->successResponse(
            $this->ticketService->formatTicket($ticket, $request->user()),
            'Ticket details retrieved successfully.'
        );
    }

    /**
     * Driver begins searching the vehicle.
     */
    public function searching(Request $request, string $ticketNumber): JsonResponse
    {
        $ticket = $this->findTicket($ticketNumber);
        $this->authorize('respondDriver', $ticket);

        try {
            $updatedTicket = $this->ticketService->markSearching($ticket, $request->user());

            return $this->successResponse(
                $this->ticketService->formatTicket($updatedTicket, $request->user()),
                'Driver began searching for the item.'
            );
        } catch (\DomainException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Driver reports item found.
     */
    public function found(Request $request, string $ticketNumber): JsonResponse
    {
        $validated = $request->validate([
            'handover_method' => 'nullable|string|in:in_person,delivery,other',
            'notes' => 'nullable|string|max:1000',
        ]);

        $ticket = $this->findTicket($ticketNumber);
        $this->authorize('respondDriver', $ticket);

        try {
            $updatedTicket = $this->ticketService->markFound($ticket, $request->user(), $validated);

            return $this->successResponse(
                $this->ticketService->formatTicket($updatedTicket, $request->user()),
                'Item marked as found. Recovery pending.'
            );
        } catch (\DomainException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Driver reports item not found.
     */
    public function notFound(Request $request, string $ticketNumber): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $ticket = $this->findTicket($ticketNumber);
        $this->authorize('respondDriver', $ticket);

        try {
            $updatedTicket = $this->ticketService->markNotFound($ticket, $request->user(), $validated['reason']);

            return $this->successResponse(
                $this->ticketService->formatTicket($updatedTicket, $request->user()),
                'Item marked as not found.'
            );
        } catch (\DomainException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Driver confirms handover of item.
     */
    public function driverHandover(Request $request, string $ticketNumber): JsonResponse
    {
        $validated = $request->validate([
            'handover_method' => 'nullable|string|in:in_person,delivery,other',
            'notes' => 'nullable|string|max:1000',
        ]);

        $ticket = $this->findTicket($ticketNumber);
        $this->authorize('respondDriver', $ticket);

        try {
            $recovery = $this->ticketService->confirmDriverHandover($ticket, $request->user(), $validated);
            $freshTicket = $ticket->fresh();

            $message = ($freshTicket->status === 'closed')
                ? 'Handover confirmed. Dual confirmation complete; ticket closed.'
                : 'Driver handover recorded. Awaiting passenger receipt confirmation.';

            return $this->successResponse(
                $this->ticketService->formatTicket($freshTicket, $request->user()),
                $message
            );
        } catch (\DomainException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Passenger confirms receipt of item.
     */
    public function passengerReceipt(Request $request, string $ticketNumber): JsonResponse
    {
        $ticket = $this->findTicket($ticketNumber);
        $this->authorize('confirmPassenger', $ticket);

        try {
            $recovery = $this->ticketService->confirmPassengerReceipt($ticket, $request->user());
            $freshTicket = $ticket->fresh();

            $message = ($freshTicket->status === 'closed')
                ? 'Receipt confirmed. Dual confirmation complete; ticket closed.'
                : 'Passenger receipt recorded. Awaiting driver handover confirmation.';

            return $this->successResponse(
                $this->ticketService->formatTicket($freshTicket, $request->user()),
                $message
            );
        } catch (\DomainException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Passenger cancels the ticket.
     */
    public function cancel(Request $request, string $ticketNumber): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:1000',
        ]);

        $ticket = $this->findTicket($ticketNumber);
        $this->authorize('cancel', $ticket);

        try {
            $updatedTicket = $this->ticketService->cancelTicket($ticket, $request->user(), $validated['reason'] ?? null);

            return $this->successResponse(
                $this->ticketService->formatTicket($updatedTicket, $request->user()),
                'Ticket cancelled successfully.'
            );
        } catch (\DomainException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Passenger or driver disputes the ticket.
     */
    public function dispute(Request $request, string $ticketNumber): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $ticket = $this->findTicket($ticketNumber);
        $this->authorize('dispute', $ticket);

        try {
            $updatedTicket = $this->ticketService->disputeTicket($ticket, $request->user(), $validated['reason']);

            return $this->successResponse(
                $this->ticketService->formatTicket($updatedTicket, $request->user()),
                'Ticket marked as disputed.'
            );
        } catch (\DomainException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Escalate ticket to administrative review.
     */
    public function escalate(Request $request, string $ticketNumber): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $ticket = $this->findTicket($ticketNumber);
        $this->authorize('escalate', $ticket);

        try {
            $updatedTicket = $this->ticketService->escalateTicket($ticket, $request->user(), $validated['reason']);

            return $this->successResponse(
                $this->ticketService->formatTicket($updatedTicket, $request->user()),
                'Ticket escalated to support staff.'
            );
        } catch (\DomainException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Administrative override to resolve a ticket.
     */
    public function resolve(Request $request, string $ticketNumber): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:closed,not_found,recovery_pending,cancelled',
            'reason' => 'required|string|max:1000',
        ]);

        $ticket = $this->findTicket($ticketNumber);
        $this->authorize('resolve', $ticket);

        try {
            $updatedTicket = $this->ticketService->resolveTicket(
                $ticket,
                $request->user(),
                $validated['status'],
                $validated['reason']
            );

            return $this->successResponse(
                $this->ticketService->formatTicket($updatedTicket, $request->user()),
                'Ticket resolved administratively.'
            );
        } catch (\DomainException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Find ticket by ticket_number or internal ID.
     */
    protected function findTicket(string $identifier): LostItemTicket
    {
        $ticket = LostItemTicket::where('ticket_number', $identifier)->first();

        if (!$ticket && is_numeric($identifier)) {
            $ticket = LostItemTicket::find((int) $identifier);
        }

        if (!$ticket) {
            abort(404, 'Ticket not found.');
        }

        return $ticket;
    }
}
