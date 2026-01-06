<?php

namespace App\Services\Firebase;

use App\Models\Ticket;
use App\Models\TicketComment;
use Google\Cloud\Core\Timestamp;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Carbon\Carbon;

class TicketService
{
    private $firestore;
    private $storage;
    private $bucket;

    public function __construct()
    {
        $factory = FirebaseFactory::make();
        $this->firestore = $factory->createFirestore()->database();

        $this->storage = null;
        $this->bucket = null;
        try {
            $this->storage = $factory->createStorage();
            if (config('firebase.storage_bucket')) {
                $this->bucket = $this->storage->getBucket(config('firebase.storage_bucket'));
            }
        } catch (\Throwable $e) {
            // ignore - bucket may not be configured (we support local storage)
            $this->storage = null;
            $this->bucket = null;
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getAllTickets(): array
    {
        // Use Laravel DB for faster queries
        $tickets = Ticket::with(['customer', 'agent', 'category'])
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(fn($ticket) => $this->ticketToArray($ticket))
            ->toArray();

        return $tickets;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTicketsByCustomer(string $customerId): array
    {
        // Use Laravel DB for faster queries
        $tickets = Ticket::with(['customer', 'agent', 'category'])
            ->where('customer_id', $customerId)
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(fn($ticket) => $this->ticketToArray($ticket))
            ->toArray();

        return $tickets;
    }

    /**
     * Get tickets by category (untuk admin per jobdesk/kategori)
     * @return array<int, array<string, mixed>>
     */
    public function getTicketsByCategory(int $categoryId): array
    {
        $tickets = Ticket::with(['customer', 'agent', 'category'])
            ->where('category_id', $categoryId)
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(fn($ticket) => $this->ticketToArray($ticket))
            ->toArray();

        return $tickets;
    }

    /**
     * Get tickets by agent (yang DI-ASSIGN ke agent tertentu)
     * @return array<int, array<string, mixed>>
     */
    public function getTicketsByAgent(int $agentId): array
    {
        // Agent HANYA lihat tickets yang di-assign ke dia
        // DAN exclude tickets yang sudah resolved atau closed (sudah selesai dikerjakan)
        $tickets = Ticket::with(['customer', 'agent', 'category'])
            ->where('agent_id', $agentId)
            ->whereNotIn('status', ['resolved', 'closed'])
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(fn($ticket) => $this->ticketToArray($ticket))
            ->toArray();

        return $tickets;
    }

    /**
     * Get tickets assigned to an agent (including resolved/closed)
     * @return array<int, array<string, mixed>>
     */
    public function getTicketsByAgentAllStatuses(int $agentId): array
    {
        $tickets = Ticket::with(['customer', 'agent', 'category'])
            ->where('agent_id', $agentId)
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(fn($ticket) => $this->ticketToArray($ticket))
            ->toArray();

        return $tickets;
    }

    public function createTicket(array $data): string
    {
        $now = new Timestamp(Carbon::now('Asia/Jakarta'));
        $firebaseData = $data;
        $firebaseData['created_at'] = $now;
        $firebaseData['updated_at'] = $now;
        $firebaseData['status'] = $data['status'] ?? 'open';

        // ensure customer_id is stored as string to avoid Firestore type mismatch
        if (isset($firebaseData['customer_id'])) {
            $firebaseData['customer_id'] = (string) $firebaseData['customer_id'];
        }

        // Save to Firestore
        $ticketRef = $this->firestore->collection('tickets')->add($firebaseData);
        $firebaseId = $ticketRef->id();

        // Sync to Laravel DB
        $data['firebase_id'] = $firebaseId;
        $ticket = Ticket::create($data);

        return $firebaseId;
    }

    public function findTicket(string $id)
    {
        // Try Laravel DB first (faster)
        $ticket = Ticket::where('firebase_id', $id)->first();
        if ($ticket) {
            return $ticket;
        }

        // Fallback to Firestore
        return $this->firestore->collection('tickets')->document($id)->snapshot();
    }

    public function getTicket(string $id): ?array
    {
        // Try Laravel DB first
        $ticket = Ticket::with(['customer', 'agent', 'category', 'comments.user'])
            ->where('firebase_id', $id)
            ->first();

        if ($ticket) {
            return $this->ticketToArray($ticket);
        }

        // Fallback to Firestore
        $snapshot = $this->firestore->collection('tickets')->document($id)->snapshot();
        if (!$snapshot->exists()) {
            return null;
        }

        $ticketData = $snapshot->data();
        $ticketData['id'] = $snapshot->id();

        // Load status history from subcollection tickets/{id}/status_history (if any)
        try {
            $historyDocs = $this->firestore->collection('tickets')->document($id)->collection('status_history')->documents();
            $history = [];
            foreach ($historyDocs as $h) {
                if (!$h->exists()) continue;
                $row = $h->data();
                if (isset($row['changed_at']) && $row['changed_at'] instanceof Timestamp) {
                    $row['changed_at_iso'] = Carbon::instance($row['changed_at']->get())->setTimezone('Asia/Jakarta')->toDateTimeString();
                }
                $row['id'] = $h->id();
                $history[] = $row;
            }

            // sort ascending by changed_at_iso if available
            usort($history, function ($a, $b) {
                return strcmp((string)($a['changed_at_iso'] ?? ''), (string)($b['changed_at_iso'] ?? ''));
            });

            $ticketData['status_history'] = $history;
        } catch (\Throwable $e) {
            // ignore if unable to load history
            $ticketData['status_history'] = [];
        }

        return $this->normalizeTicket($ticketData);
    }

    public function updateTicket(string $id, array $data): void
    {
        $now = new Timestamp(Carbon::now('Asia/Jakarta'));
        $firebaseData = $data;
        $firebaseData['updated_at'] = $now;

        // If status changed, record status change into subcollection 'status_history'
        $previousStatus = null;
        if (isset($data['status'])) {
            try {
                $snapshotPrev = $this->firestore->collection('tickets')->document($id)->snapshot();
                if ($snapshotPrev && $snapshotPrev->exists()) {
                    $dprev = $snapshotPrev->data();
                    if (isset($dprev['status'])) {
                        $previousStatus = $dprev['status'];
                    }
                }
            } catch (\Throwable $e) {
                logger()->warning('Could not read previous ticket status: ' . $e->getMessage());
            }
        }

        // Update Firestore
        $this->firestore->collection('tickets')->document($id)->set($firebaseData, ['merge' => true]);

        if (isset($data['status']) && $previousStatus !== $data['status']) {
            try {
                $this->firestore
                    ->collection('tickets')
                    ->document($id)
                    ->collection('status_history')
                    ->add(['status' => $data['status'], 'changed_at' => $now]);
            } catch (\Throwable $e) {
                logger()->error('Failed to save status history: ' . $e->getMessage());
            }
        }

        // Sync to Laravel DB
        $ticket = Ticket::where('firebase_id', $id)->first();
        if ($ticket) {
            // Ensure any Firestore Timestamp objects are converted to Carbon for DB compatibility
            $dbData = $data;
            if (isset($dbData['updated_at']) && $dbData['updated_at'] instanceof Timestamp) {
                try {
                    $dbData['updated_at'] = Carbon::instance($dbData['updated_at']->get())->setTimezone('Asia/Jakarta');
                } catch (\Throwable $e) {
                    // Fallback to current time on conversion failure
                    $dbData['updated_at'] = Carbon::now('Asia/Jakarta');
                }
            }
            if (isset($dbData['created_at']) && $dbData['created_at'] instanceof Timestamp) {
                try {
                    $dbData['created_at'] = Carbon::instance($dbData['created_at']->get())->setTimezone('Asia/Jakarta');
                } catch (\Throwable $e) {
                    $dbData['created_at'] = Carbon::now('Asia/Jakarta');
                }
            }

            $ticket->update($dbData);
        }
    }

    public function deleteTicket(string $id): void
    {
        // Delete from Firestore
        $this->firestore->collection('tickets')->document($id)->delete();

        // Delete from Laravel DB (soft delete)
        $ticket = Ticket::where('firebase_id', $id)->first();
        if ($ticket) {
            $ticket->delete();
        }
    }

    /**
     * @param array<int, UploadedFile> $files
     * @return array<int, array<string, mixed>>
     */
    public function uploadAttachments(array $files, string $ticketId): array
    {
        $attachments = [];

        $driver = config('attachments.driver', 'local');
        $localDisk = config('attachments.local_disk', 'local');
        $maxSize = (int) config('attachments.max_size_bytes', 5242880);

        foreach ($files as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            // validate size
            if ($file->getSize() > $maxSize) {
                // skip files larger than allowed
                continue;
            }

            $filename = trim($file->getClientOriginalName()) ?: uniqid('file_', true);

            if ($driver === 'firebase' && $this->bucket) {
                // upload to Firebase Storage
                $extension = $file->getClientOriginalExtension();
                $objectName = 'tickets/' . $ticketId . '/attachments/' . uniqid('', true) . ($extension ? ('.' . $extension) : '');

                $object = $this->bucket->upload(
                    fopen($file->getRealPath(), 'r'),
                    [
                        'name' => $objectName,
                        'metadata' => [
                            'contentType' => $file->getClientMimeType() ?: 'application/octet-stream',
                        ],
                    ]
                );

                $attachments[] = [
                    'name' => $filename,
                    'path' => $objectName,
                    'content_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                    'storage' => 'firebase',
                ];
                // Save metadata to Firestore attachments collection
                try {
                    $meta = [
                        'ticket_id' => $ticketId,
                        'name' => $filename,
                        'path' => $objectName,
                        'content_type' => $file->getClientMimeType(),
                        'size' => $file->getSize(),
                        'storage' => 'firebase',
                    ];
                    $now = new Timestamp(Carbon::now('Asia/Jakarta'));
                    $meta['created_at'] = $now;
                    $meta['updated_at'] = $now;
                    $this->firestore->collection('attachments')->add($meta);
                } catch (\Throwable $e) {
                    logger()->error('Failed to save firebase attachment metadata: ' . $e->getMessage());
                }
            } else {
                // store locally on configured disk
                try {
                    $dir = 'tickets/' . $ticketId . '/attachments';
                    $storedPath = Storage::disk($localDisk)->putFileAs($dir, $file, $filename);
                    $attachments[] = [
                        'name' => $filename,
                        'path' => $storedPath,
                        'content_type' => $file->getClientMimeType(),
                        'size' => $file->getSize(),
                        'storage' => 'local',
                    ];
                    // Save metadata to Firestore attachments collection
                    try {
                        $meta = [
                            'ticket_id' => $ticketId,
                            'name' => $filename,
                            'path' => $storedPath,
                            'content_type' => $file->getClientMimeType(),
                            'size' => $file->getSize(),
                            'storage' => 'local',
                        ];
                        $now = new Timestamp(Carbon::now('Asia/Jakarta'));
                        $meta['created_at'] = $now;
                        $meta['updated_at'] = $now;
                        $this->firestore->collection('attachments')->add($meta);
                    } catch (\Throwable $e) {
                        logger()->error('Failed to save local attachment metadata: ' . $e->getMessage());
                    }
                } catch (\Throwable $e) {
                    logger()->error('Failed to store attachment locally: ' . $e->getMessage());
                    continue;
                }
            }
        }

        return $attachments;
    }

    public function getAttachmentTemporaryUrl(string $ticketId, string $path, string $expires = '+60 minutes'): ?string
    {
        $driver = config('attachments.driver', 'local');

        if ($driver === 'firebase' && $this->bucket) {
            $object = $this->bucket->object($path);
            if (!$object->exists()) {
                return null;
            }
            return $object->signedUrl(new \DateTimeImmutable($expires));
        }

        // For local storage generate a temporary signed route to download
        try {
            $expiresAt = Carbon::parse($expires);
        } catch (\Throwable $e) {
            $expiresAt = Carbon::now()->addMinutes(60);
        }

        // route will be tickets.attachments.download and expect 'ticket' and 'path' params
        // path will be base64 encoded to keep route-safe
        return URL::temporarySignedRoute(
            'tickets.attachments.download',
            $expiresAt,
            ['ticket' => $ticketId, 'path' => base64_encode($path)]
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getComments(string $ticketId): array
    {
        // Use Laravel DB for faster queries
        $ticket = Ticket::where('firebase_id', $ticketId)->first();
        if (!$ticket) {
            return [];
        }

        $comments = TicketComment::with('user')
            ->where('ticket_id', $ticket->id)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn($comment) => $this->commentToArray($comment))
            ->toArray();

        return $comments;
    }

    public function addComment(string $ticketId, array $data): string
    {
        $now = new Timestamp(Carbon::now('Asia/Jakarta'));
        $firebaseData = $data;
        $firebaseData['created_at'] = $now;
        $firebaseData['updated_at'] = $now;

        // Save to Firestore
        $ref = $this->firestore
            ->collection('tickets')
            ->document($ticketId)
            ->collection('comments')
            ->add($firebaseData);

        $firebaseCommentId = $ref->id();

        // Sync to Laravel DB
        $ticket = Ticket::where('firebase_id', $ticketId)->first();
        if ($ticket) {
            $data['firebase_id'] = $firebaseCommentId;
            $data['ticket_id'] = $ticket->id;
            TicketComment::create($data);

            // Update ticket timestamp
            $ticket->touch();
        }

        // update ticket timestamp in Firestore for list sorting/visibility
        $this->updateTicket($ticketId, ['updated_at' => $now]);

        return $firebaseCommentId;
    }

    private function documentsToArray($documents): array
    {
        $result = [];
        foreach ($documents as $doc) {
            if (!$doc->exists()) {
                continue;
            }
            $row = $doc->data();
            $row['id'] = $doc->id();
            $result[] = $this->normalizeTicket($row);
        }

        // newest first by updated_at (if exists)
        usort($result, function ($a, $b) {
            return strcmp((string)($b['updated_at_iso'] ?? ''), (string)($a['updated_at_iso'] ?? ''));
        });

        return $result;
    }

    private function normalizeTicket(array $ticket): array
    {
        if (isset($ticket['created_at']) && $ticket['created_at'] instanceof Timestamp) {
            $ticket['created_at_iso'] = Carbon::instance($ticket['created_at']->get())->setTimezone('Asia/Jakarta')->toDateTimeString();
        }
        if (isset($ticket['updated_at']) && $ticket['updated_at'] instanceof Timestamp) {
            $ticket['updated_at_iso'] = Carbon::instance($ticket['updated_at']->get())->setTimezone('Asia/Jakarta')->toDateTimeString();
        }

        return $ticket;
    }

    private function normalizeComment(array $comment): array
    {
        if (isset($comment['created_at']) && $comment['created_at'] instanceof Timestamp) {
            $comment['created_at_iso'] = Carbon::instance($comment['created_at']->get())->setTimezone('Asia/Jakarta')->toDateTimeString();
        }
        if (isset($comment['updated_at']) && $comment['updated_at'] instanceof Timestamp) {
            $comment['updated_at_iso'] = Carbon::instance($comment['updated_at']->get())->setTimezone('Asia/Jakarta')->toDateTimeString();
        }
        return $comment;
    }

    /**
     * Convert Laravel Ticket model to array
     */
    private function ticketToArray(Ticket $ticket): array
    {
        $result = [
            'id' => $ticket->firebase_id ?? $ticket->id,
            'title' => $ticket->title,
            'description' => $ticket->description,
            'location' => $ticket->location,
            'customer_id' => (string)$ticket->customer_id,
            'customer_name' => $ticket->customer->name ?? 'Unknown',
            'agent_id' => $ticket->agent_id ? (string)$ticket->agent_id : null,
            'agent_name' => $ticket->agent->name ?? null,
            'category_id' => (string)$ticket->category_id,
            'category' => $ticket->category->name ?? 'Unknown',
            'status' => $ticket->status,
            'priority' => $ticket->priority,
            'attachments' => $ticket->attachments ?? [],
            'created_at_iso' => $ticket->created_at->setTimezone('Asia/Jakarta')->toDateTimeString(),
            'updated_at_iso' => $ticket->updated_at->setTimezone('Asia/Jakarta')->toDateTimeString(),
        ];

        // Try to load status history from Firestore if available
        try {
            $historyDocs = $this->firestore->collection('tickets')->document($result['id'])->collection('status_history')->documents();
            $history = [];
            foreach ($historyDocs as $h) {
                if (!$h->exists()) continue;
                $row = $h->data();
                if (isset($row['changed_at']) && $row['changed_at'] instanceof Timestamp) {
                    $row['changed_at_iso'] = Carbon::instance($row['changed_at']->get())->setTimezone('Asia/Jakarta')->toDateTimeString();
                }
                $row['id'] = $h->id();
                $history[] = $row;
            }
            usort($history, function ($a, $b) {
                return strcmp((string)($a['changed_at_iso'] ?? ''), (string)($b['changed_at_iso'] ?? ''));
            });
            $result['status_history'] = $history;
        } catch (\Throwable $e) {
            $result['status_history'] = [];
        }

        return $result;
    }

    /**
     * Convert Laravel TicketComment model to array
     */
    private function commentToArray(TicketComment $comment): array
    {
        return [
            'id' => $comment->firebase_id ?? $comment->id,
            'ticket_id' => $comment->ticket->firebase_id ?? $comment->ticket_id,
            'user_id' => (string)$comment->user_id,
            'user_name' => $comment->user->name ?? 'Unknown',
            'user_role' => $comment->user->role ?? 'customer',
            'comment' => $comment->comment,
            'attachments' => $comment->attachments ?? [],
            'is_internal' => $comment->is_internal,
            'created_at_iso' => $comment->created_at->setTimezone('Asia/Jakarta')->toDateTimeString(),
            'updated_at_iso' => $comment->updated_at->setTimezone('Asia/Jakarta')->toDateTimeString(),
        ];
    }
}
